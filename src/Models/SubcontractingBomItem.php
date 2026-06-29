<?php

namespace JeffersonGoncalves\Erp\Subcontracting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\Erp\Subcontracting\Support\ModelResolver;

/**
 * A raw material to supply for a subcontracting BOM's finished good.
 *
 * @property int $id
 * @property int $subcontracting_bom_id
 * @property string $item_code
 * @property float $qty
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SubcontractingBom|null $subcontractingBom
 */
class SubcontractingBomItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'subcontracting_bom_id',
        'item_code',
        'qty',
    ];

    protected $attributes = [
        'qty' => 1,
    ];

    protected $casts = [
        'qty' => 'float',
    ];

    public function getTable(): string
    {
        return (config('erp-subcontracting.table_prefix') ?? '').'subcontracting_bom_items';
    }

    public function subcontractingBom(): BelongsTo
    {
        return $this->belongsTo(ModelResolver::subcontractingBom(), 'subcontracting_bom_id');
    }
}
