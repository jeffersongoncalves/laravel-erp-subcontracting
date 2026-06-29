<?php

namespace JeffersonGoncalves\Erp\Subcontracting\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\Erp\Core\Concerns\HasCompany;
use JeffersonGoncalves\Erp\Core\Concerns\HasNamingSeries;
use JeffersonGoncalves\Erp\Core\Concerns\IsSubmittable;
use JeffersonGoncalves\Erp\Core\Contracts\PostsToLedger;
use JeffersonGoncalves\Erp\Core\Contracts\SubmittableDocument;
use JeffersonGoncalves\Erp\Core\Enums\DocStatus;
use JeffersonGoncalves\Erp\Stock\Concerns\ResolvesStockGlAccounts;
use JeffersonGoncalves\Erp\Stock\Contracts\PostsStockLedger;
use JeffersonGoncalves\Erp\Stock\Services\StockLedgerService;
use JeffersonGoncalves\Erp\Stock\Support\ModelResolver as StockModelResolver;
use JeffersonGoncalves\Erp\Subcontracting\Support\ModelResolver;

/**
 * Receives a finished good from a subcontractor: an inbound movement of the
 * finished good (valued at the service rate plus the value of the consumed raw
 * materials) and an outbound movement of those raw materials from the supplier
 * warehouse they were sent to. The net stock-value change is posted to the
 * inventory account against a subcontracting counter account.
 *
 * @property int $id
 * @property string|null $naming_series
 * @property int|null $subcontracting_order_id
 * @property string $party_type
 * @property int|null $party_id
 * @property string $supplier_name
 * @property Carbon $posting_date
 * @property int|null $company_id
 * @property int|null $supplier_warehouse_id
 * @property float $total_qty
 * @property DocStatus $docstatus
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, SubcontractingReceiptItem> $items
 * @property-read Collection<int, SubcontractingReceiptSuppliedItem> $suppliedItems
 */
class SubcontractingReceipt extends Model implements PostsStockLedger, PostsToLedger, SubmittableDocument
{
    use HasCompany;
    use HasFactory;
    use HasNamingSeries;
    use IsSubmittable;
    use ResolvesStockGlAccounts;

    /** The subcontracting counter account (SRBNB / subcontracting expense). */
    public ?int $counterAccountId = null;

    protected $fillable = [
        'naming_series',
        'subcontracting_order_id',
        'party_type',
        'party_id',
        'supplier_name',
        'posting_date',
        'company_id',
        'supplier_warehouse_id',
        'total_qty',
        'docstatus',
    ];

    protected $attributes = [
        'party_type' => 'Supplier',
        'total_qty' => 0,
        'docstatus' => 0,
    ];

    protected $casts = [
        'posting_date' => 'datetime',
        'supplier_warehouse_id' => 'integer',
        'total_qty' => 'float',
        'docstatus' => DocStatus::class,
    ];

    protected static function booted(): void
    {
        static::saving(function (SubcontractingReceipt $receipt): void {
            if ($receipt->docstatus === DocStatus::Draft) {
                $receipt->calculateTotals();
            }

            if ($receipt->counterAccountId === null) {
                $account = config('erp-subcontracting.default_subcontracting_counter_account');
                $receipt->counterAccountId = $account !== null ? (int) $account : null;
            }
        });
    }

    public function getTable(): string
    {
        return (config('erp-subcontracting.table_prefix') ?? '').'subcontracting_receipts';
    }

    public function subcontractingOrder(): BelongsTo
    {
        return $this->belongsTo(ModelResolver::subcontractingOrder(), 'subcontracting_order_id');
    }

    public function supplierWarehouse(): BelongsTo
    {
        return $this->belongsTo(StockModelResolver::warehouse(), 'supplier_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ModelResolver::subcontractingReceiptItem(), 'subcontracting_receipt_id');
    }

    public function suppliedItems(): HasMany
    {
        return $this->hasMany(ModelResolver::subcontractingReceiptSuppliedItem(), 'subcontracting_receipt_id');
    }

    public function calculateTotals(): void
    {
        $this->total_qty = $this->exists ? (float) $this->items()->sum('qty') : 0.0;
    }

    public function postLedgerEntries(): void
    {
        app(StockLedgerService::class)->post($this, $this->buildMovements());
    }

    public function reverseLedgerEntries(): void
    {
        app(StockLedgerService::class)->reverse($this);
    }

    public function stockGlAccounts(): array
    {
        return [
            'stock_account_id' => $this->warehouseAccountId($this->primaryWarehouseId()),
            'counter_account_id' => $this->counterAccountId,
        ];
    }

    /**
     * Build the stock movements posted on submission: the consumed raw
     * materials leave the supplier warehouse and the finished goods enter their
     * destination warehouse valued at the service rate plus the consumed raw
     * material value.
     *
     * @return list<array{item_id: int, warehouse_id: int, actual_qty: float, incoming_rate: float, posting_date: mixed}>
     */
    protected function buildMovements(): array
    {
        $movements = [];

        // Outbound: raw materials consumed at the subcontractor are issued from
        // the supplier warehouse they were sent to.
        $consumedValue = 0.0;
        $supplierWarehouseId = $this->supplier_warehouse_id;

        if ($supplierWarehouseId !== null) {
            foreach ($this->suppliedItems as $supplied) {
                $rmItemId = $this->resolveItemId($supplied->rm_item_code);
                $qty = (float) $supplied->consumed_qty;

                if ($rmItemId === null || $qty <= 0.0) {
                    continue;
                }

                $consumedValue += $qty * $this->currentValuationRate($rmItemId, (int) $supplierWarehouseId);

                $movements[] = [
                    'item_id' => $rmItemId,
                    'warehouse_id' => (int) $supplierWarehouseId,
                    'actual_qty' => -1 * $qty,
                    'incoming_rate' => 0.0,
                    'posting_date' => $this->posting_date,
                ];
            }
        }

        // Inbound: finished goods received, valued at their service rate plus a
        // proportional share of the consumed raw material value.
        $totalQty = 0.0;
        foreach ($this->items as $item) {
            if ($item->warehouse_id !== null) {
                $totalQty += (float) $item->qty;
            }
        }

        $rmValuePerUnit = $totalQty > 0.0 ? $consumedValue / $totalQty : 0.0;

        foreach ($this->items as $item) {
            $fgItemId = $this->resolveItemId($item->item_code);

            if ($fgItemId === null || $item->warehouse_id === null) {
                continue;
            }

            $movements[] = [
                'item_id' => $fgItemId,
                'warehouse_id' => (int) $item->warehouse_id,
                'actual_qty' => (float) $item->qty,
                'incoming_rate' => (float) $item->rate + $rmValuePerUnit,
                'posting_date' => $this->posting_date,
            ];
        }

        return $movements;
    }

    protected function primaryWarehouseId(): ?int
    {
        $first = $this->items->first();

        if ($first?->warehouse_id !== null) {
            return (int) $first->warehouse_id;
        }

        return $this->supplier_warehouse_id !== null ? (int) $this->supplier_warehouse_id : null;
    }

    protected function resolveItemId(string $itemCode): ?int
    {
        $itemClass = StockModelResolver::item();

        /** @var Model|null $item */
        $item = $itemClass::query()->where('item_code', $itemCode)->first();

        return $item === null ? null : (int) $item->getKey();
    }

    protected function currentValuationRate(int $itemId, int $warehouseId): float
    {
        $bin = StockModelResolver::bin()::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $bin !== null ? (float) $bin->getAttribute('valuation_rate') : 0.0;
    }
}
