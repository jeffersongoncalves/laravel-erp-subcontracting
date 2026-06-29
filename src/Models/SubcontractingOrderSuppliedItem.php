<?php

namespace JeffersonGoncalves\Erp\Subcontracting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\Erp\Subcontracting\Support\ModelResolver;

/**
 * A raw material the company supplies to the subcontractor for an order.
 *
 * @property int $id
 * @property int $subcontracting_order_id
 * @property string $main_item_code
 * @property string $rm_item_code
 * @property float $required_qty
 * @property float $supplied_qty
 * @property float $consumed_qty
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SubcontractingOrder|null $subcontractingOrder
 */
class SubcontractingOrderSuppliedItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'subcontracting_order_id',
        'main_item_code',
        'rm_item_code',
        'required_qty',
        'supplied_qty',
        'consumed_qty',
    ];

    protected $attributes = [
        'required_qty' => 0,
        'supplied_qty' => 0,
        'consumed_qty' => 0,
    ];

    protected $casts = [
        'required_qty' => 'float',
        'supplied_qty' => 'float',
        'consumed_qty' => 'float',
    ];

    public function getTable(): string
    {
        return (config('erp-subcontracting.table_prefix') ?? '').'subcontracting_order_supplied_items';
    }

    public function subcontractingOrder(): BelongsTo
    {
        return $this->belongsTo(ModelResolver::subcontractingOrder(), 'subcontracting_order_id');
    }
}
