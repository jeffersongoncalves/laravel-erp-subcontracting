<?php

use JeffersonGoncalves\Erp\Stock\Models\Warehouse;
use JeffersonGoncalves\Erp\Subcontracting\Models\SubcontractingOrder;
use JeffersonGoncalves\Erp\Subcontracting\Models\SubcontractingReceipt;
use JeffersonGoncalves\Erp\Subcontracting\Services\SubcontractingOrderService;

it('creates a subcontracting receipt from an order with matching finished-good items', function () {
    $supplierWarehouse = Warehouse::factory()->create();
    $fgWarehouse = Warehouse::factory()->create();

    $order = SubcontractingOrder::factory()->create([
        'supplier_warehouse_id' => $supplierWarehouse->id,
    ]);

    $order->items()->create([
        'item_code' => 'FG-WIDGET',
        'qty' => 8,
        'rate' => 12,
        'fg_warehouse_id' => $fgWarehouse->id,
    ]);

    $order->suppliedItems()->create([
        'main_item_code' => 'FG-WIDGET',
        'rm_item_code' => 'RM-STEEL',
        'required_qty' => 16,
    ]);

    $order->submit();

    $receipt = app(SubcontractingOrderService::class)->createReceipt($order);

    expect($receipt)->toBeInstanceOf(SubcontractingReceipt::class)
        ->and($receipt->isDraft())->toBeTrue()
        ->and($receipt->subcontracting_order_id)->toBe($order->id)
        ->and($receipt->supplier_warehouse_id)->toBe($supplierWarehouse->id)
        ->and($receipt->items()->count())->toBe(1)
        ->and($receipt->suppliedItems()->count())->toBe(1);

    $item = $receipt->items()->first();

    expect($item->item_code)->toBe('FG-WIDGET')
        ->and($item->qty)->toBe(8.0)
        ->and($item->rate)->toBe(12.0)
        ->and($item->warehouse_id)->toBe($fgWarehouse->id);

    expect($receipt->suppliedItems()->first()->consumed_qty)->toBe(16.0);
});
