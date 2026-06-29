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
 * A finished good received on a subcontracting receipt.
 *
 * @property int $id
 * @property int $subcontracting_receipt_id
 * @property string $item_code
 * @property float $qty
 * @property float $rate
 * @property float $amount
 * @property int|null $warehouse_id
 * @property int|null $bom_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SubcontractingReceipt|null $subcontractingReceipt
 */
class SubcontractingReceiptItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'subcontracting_receipt_id',
        'item_code',
        'qty',
        'rate',
        'amount',
        'warehouse_id',
        'bom_id',
    ];

    protected $attributes = [
        'qty' => 1,
        'rate' => 0,
        'amount' => 0,
    ];

    protected $casts = [
        'qty' => 'float',
        'rate' => 'float',
        'amount' => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (SubcontractingReceiptItem $item): void {
            $item->amount = (float) $item->qty * (float) $item->rate;
        });

        static::saved(fn (SubcontractingReceiptItem $item) => $item->syncParentTotals());
        static::deleted(fn (SubcontractingReceiptItem $item) => $item->syncParentTotals());
    }

    public function getTable(): string
    {
        return (config('erp-subcontracting.table_prefix') ?? '').'subcontracting_receipt_items';
    }

    public function subcontractingReceipt(): BelongsTo
    {
        return $this->belongsTo(ModelResolver::subcontractingReceipt(), 'subcontracting_receipt_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(StockModelResolver::warehouse(), 'warehouse_id');
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(ManufacturingModelResolver::bom(), 'bom_id');
    }

    protected function syncParentTotals(): void
    {
        $parent = $this->subcontractingReceipt;

        if ($parent === null || $parent->docstatus !== DocStatus::Draft) {
            return;
        }

        $parent->save();
    }
}
