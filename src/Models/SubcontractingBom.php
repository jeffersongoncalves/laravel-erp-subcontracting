<?php

namespace JeffersonGoncalves\Erp\Subcontracting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\Erp\Manufacturing\Support\ModelResolver as ManufacturingModelResolver;
use JeffersonGoncalves\Erp\Subcontracting\Support\ModelResolver;

/**
 * A subcontracting bill of materials: the finished good a subcontractor
 * produces and the raw materials that must be supplied for it.
 *
 * A master record, not a submittable document. The raw materials may be listed
 * explicitly on the child {@see SubcontractingBomItem} rows or derived from the
 * linked manufacturing BOM at order time.
 *
 * @property int $id
 * @property string $finished_good
 * @property float $finished_good_qty
 * @property int|null $bom_id
 * @property string|null $service_item
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, SubcontractingBomItem> $items
 */
class SubcontractingBom extends Model
{
    use HasFactory;

    protected $fillable = [
        'finished_good',
        'finished_good_qty',
        'bom_id',
        'service_item',
        'is_active',
    ];

    protected $attributes = [
        'finished_good_qty' => 1,
        'is_active' => true,
    ];

    protected $casts = [
        'finished_good_qty' => 'float',
        'is_active' => 'boolean',
    ];

    public function getTable(): string
    {
        return (config('erp-subcontracting.table_prefix') ?? '').'subcontracting_boms';
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(ManufacturingModelResolver::bom(), 'bom_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ModelResolver::subcontractingBomItem(), 'subcontracting_bom_id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
