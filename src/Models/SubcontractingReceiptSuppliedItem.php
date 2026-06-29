<?php

namespace JeffersonGoncalves\Erp\Subcontracting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\Erp\Subcontracting\Support\ModelResolver;

/**
 * A raw material consumed at the subcontractor for a subcontracting receipt.
 *
 * @property int $id
 * @property int $subcontracting_receipt_id
 * @property string $main_item_code
 * @property string $rm_item_code
 * @property float $consumed_qty
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SubcontractingReceipt|null $subcontractingReceipt
 */
class SubcontractingReceiptSuppliedItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'subcontracting_receipt_id',
        'main_item_code',
        'rm_item_code',
        'consumed_qty',
    ];

    protected $attributes = [
        'consumed_qty' => 0,
    ];

    protected $casts = [
        'consumed_qty' => 'float',
    ];

    public function getTable(): string
    {
        return (config('erp-subcontracting.table_prefix') ?? '').'subcontracting_receipt_supplied_items';
    }

    public function subcontractingReceipt(): BelongsTo
    {
        return $this->belongsTo(ModelResolver::subcontractingReceipt(), 'subcontracting_receipt_id');
    }
}
