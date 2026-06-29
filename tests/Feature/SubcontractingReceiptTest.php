<?php

use JeffersonGoncalves\Erp\Accounting\Models\Account;
use JeffersonGoncalves\Erp\Accounting\Models\GlEntry;
use JeffersonGoncalves\Erp\Core\Models\Company;
use JeffersonGoncalves\Erp\Stock\Enums\StockEntryType;
use JeffersonGoncalves\Erp\Stock\Models\Item;
use JeffersonGoncalves\Erp\Stock\Models\StockEntry;
use JeffersonGoncalves\Erp\Stock\Models\StockLedgerEntry;
use JeffersonGoncalves\Erp\Stock\Models\Warehouse;
use JeffersonGoncalves\Erp\Subcontracting\Models\SubcontractingReceipt;

/**
 * Seed raw-material stock into the supplier warehouse via a Material Receipt
 * so the subcontracting receipt has valued stock to consume.
 */
function seedRawMaterial(Company $company, Warehouse $warehouse, Item $item, float $qty, float $rate): void
{
    $entry = StockEntry::factory()->create([
        'stock_entry_type' => StockEntryType::MaterialReceipt,
        'company_id' => $company->id,
        'to_warehouse_id' => $warehouse->id,
        'posting_date' => now(),
    ]);

    $entry->items()->create([
        'item_id' => $item->id,
        't_warehouse_id' => $warehouse->id,
        'qty' => $qty,
        'basic_rate' => $rate,
    ]);

    $entry->submit();
}

it('posts balanced stock and GL entries when a subcontracting receipt is submitted, and reverses on cancel', function () {
    $company = Company::factory()->create();

    $fgAccount = Account::factory()->create(['company_id' => $company->id]);
    $supplierAccount = Account::factory()->create(['company_id' => $company->id]);
    $counterAccount = Account::factory()->create(['company_id' => $company->id]);

    $fgWarehouse = Warehouse::factory()->create(['company_id' => $company->id, 'account_id' => $fgAccount->id]);
    $supplierWarehouse = Warehouse::factory()->create(['company_id' => $company->id, 'account_id' => $supplierAccount->id]);

    $rmItem = Item::factory()->create();
    $fgItem = Item::factory()->create();

    // 10 units of raw material at rate 5 are sent to the supplier warehouse.
    seedRawMaterial($company, $supplierWarehouse, $rmItem, 10, 5);

    $receipt = SubcontractingReceipt::factory()->create([
        'company_id' => $company->id,
        'supplier_warehouse_id' => $supplierWarehouse->id,
        'posting_date' => now(),
    ]);
    $receipt->counterAccountId = $counterAccount->id;

    $receipt->items()->create([
        'item_code' => $fgItem->item_code,
        'qty' => 5,
        'rate' => 20,
        'warehouse_id' => $fgWarehouse->id,
    ]);

    $receipt->suppliedItems()->create([
        'main_item_code' => $fgItem->item_code,
        'rm_item_code' => $rmItem->item_code,
        'consumed_qty' => 5,
    ]);

    $receipt->submit();

    $morph = $receipt->getMorphClass();

    $sles = StockLedgerEntry::query()
        ->where('voucherable_type', $morph)
        ->where('voucherable_id', $receipt->id)
        ->where('is_cancelled', false)
        ->get();

    // One finished-good inbound + one raw-material outbound.
    expect($sles)->toHaveCount(2);

    $fgSle = $sles->firstWhere('item_id', $fgItem->id);
    $rmSle = $sles->firstWhere('item_id', $rmItem->id);

    expect($fgSle->warehouse_id)->toBe($fgWarehouse->id)
        ->and($fgSle->actual_qty)->toBe(5.0)
        ->and($fgSle->incoming_rate)->toBe(25.0) // 20 service + 5 consumed RM per unit
        ->and($rmSle->warehouse_id)->toBe($supplierWarehouse->id)
        ->and($rmSle->actual_qty)->toBe(-5.0);

    // GL: net stock value increase (the service portion) is balanced.
    $glEntries = GlEntry::query()
        ->where('voucherable_type', $morph)
        ->where('voucherable_id', $receipt->id)
        ->where('is_cancelled', false)
        ->get();

    expect($glEntries)->toHaveCount(2)
        ->and(round((float) $glEntries->sum('debit'), 2))->toBe(100.0)
        ->and(round((float) $glEntries->sum('credit'), 2))->toBe(100.0);

    $fgGl = $glEntries->firstWhere('account_id', $fgAccount->id);
    expect((float) $fgGl->debit)->toBe(100.0);

    // Cancellation reverses both the stock and GL impact.
    $receipt->cancel();

    $activeSles = StockLedgerEntry::query()
        ->where('voucherable_type', $morph)
        ->where('voucherable_id', $receipt->id)
        ->where('is_cancelled', false)
        ->count();

    $activeGl = GlEntry::query()
        ->where('voucherable_type', $morph)
        ->where('voucherable_id', $receipt->id)
        ->where('is_cancelled', false)
        ->count();

    expect($activeSles)->toBe(0)
        ->and($activeGl)->toBe(0);
});
