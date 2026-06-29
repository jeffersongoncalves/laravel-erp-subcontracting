<?php

namespace JeffersonGoncalves\Erp\Subcontracting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\Erp\Core\Enums\DocStatus;
use JeffersonGoncalves\Erp\Manufacturing\Support\ModelResolver as ManufacturingModelResolver;
use JeffersonGoncalves\Erp\Stock\Support\ModelResolver as StockModelResolver;
use JeffersonGoncalves\Erp\Subcontracting\Support\ModelResolver;

/**
 * A finished good line on a subcontracting order.
 *
 * @property int $id
 * @property int $subcontracting_order_id
 * @property string $item_code
 * @property string|null $item_name
 * @property float $qty
 * @property float $rate
 * @property float $amount
 * @property int|null $bom_id
 * @property float $received_qty
 * @property int|null $fg_warehouse_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SubcontractingOrder|null $subcontractingOrder
 */
class SubcontractingOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'subcontracting_order_id',
        'item_code',
        'item_name',
        'qty',
        'rate',
        'amount',
        'bom_id',
        'received_qty',
        'fg_warehouse_id',
    ];

    protected $attributes = [
        'qty' => 1,
        'rate' => 0,
        'amount' => 0,
        'received_qty' => 0,
    ];

    protected $casts = [
        'qty' => 'float',
        'rate' => 'float',
        'amount' => 'float',
        'received_qty' => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (SubcontractingOrderItem $item): void {
            $item->amount = (float) $item->qty * (float) $item->rate;
        });

        static::saved(fn (SubcontractingOrderItem $item) => $item->syncParentTotals());
        static::deleted(fn (SubcontractingOrderItem $item) => $item->syncParentTotals());
    }

    public function getTable(): string
    {
        return (config('erp-subcontracting.table_prefix') ?? '').'subcontracting_order_items';
    }

    public function subcontractingOrder(): BelongsTo
    {
        return $this->belongsTo(ModelResolver::subcontractingOrder(), 'subcontracting_order_id');
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(ManufacturingModelResolver::bom(), 'bom_id');
    }

    public function fgWarehouse(): BelongsTo
    {
        return $this->belongsTo(StockModelResolver::warehouse(), 'fg_warehouse_id');
    }

    protected function syncParentTotals(): void
    {
        $parent = $this->subcontractingOrder;

        if ($parent === null || $parent->docstatus !== DocStatus::Draft) {
            return;
        }

        $parent->save();
    }
}
