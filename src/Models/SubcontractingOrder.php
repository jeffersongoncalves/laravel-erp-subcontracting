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
use JeffersonGoncalves\Erp\Core\Contracts\SubmittableDocument;
use JeffersonGoncalves\Erp\Core\Enums\DocStatus;
use JeffersonGoncalves\Erp\Stock\Support\ModelResolver as StockModelResolver;
use JeffersonGoncalves\Erp\Subcontracting\Enums\SubcontractingOrderStatus;
use JeffersonGoncalves\Erp\Subcontracting\Support\ModelResolver;

/**
 * A subcontracting order placed with a supplier: a PO-like commitment to have a
 * subcontractor produce a finished good from raw materials the company
 * supplies. Submittable with no ledger impact of its own; inventory and
 * accounting follow from the downstream subcontracting receipt.
 *
 * @property int $id
 * @property string|null $naming_series
 * @property string $party_type
 * @property int|null $party_id
 * @property string $supplier_name
 * @property Carbon $transaction_date
 * @property int|null $company_id
 * @property SubcontractingOrderStatus $status
 * @property int|null $supplier_warehouse_id
 * @property float $net_total
 * @property float $grand_total
 * @property DocStatus $docstatus
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, SubcontractingOrderItem> $items
 * @property-read Collection<int, SubcontractingOrderSuppliedItem> $suppliedItems
 */
class SubcontractingOrder extends Model implements SubmittableDocument
{
    use HasCompany;
    use HasFactory;
    use HasNamingSeries;
    use IsSubmittable;

    protected $fillable = [
        'naming_series',
        'party_type',
        'party_id',
        'supplier_name',
        'transaction_date',
        'company_id',
        'status',
        'supplier_warehouse_id',
        'net_total',
        'grand_total',
        'docstatus',
    ];

    protected $attributes = [
        'party_type' => 'Supplier',
        'status' => SubcontractingOrderStatus::Draft->value,
        'net_total' => 0,
        'grand_total' => 0,
        'docstatus' => 0,
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'status' => SubcontractingOrderStatus::class,
        'supplier_warehouse_id' => 'integer',
        'net_total' => 'float',
        'grand_total' => 'float',
        'docstatus' => DocStatus::class,
    ];

    protected static function booted(): void
    {
        static::saving(function (SubcontractingOrder $order): void {
            if ($order->docstatus === DocStatus::Draft) {
                $order->calculateTotals();
            }

            if ($order->docstatus === DocStatus::Submitted && $order->status === SubcontractingOrderStatus::Draft) {
                $order->status = SubcontractingOrderStatus::Open;
            }

            if ($order->docstatus === DocStatus::Cancelled) {
                $order->status = SubcontractingOrderStatus::Cancelled;
            }
        });
    }

    public function getTable(): string
    {
        return (config('erp-subcontracting.table_prefix') ?? '').'subcontracting_orders';
    }

    public function supplierWarehouse(): BelongsTo
    {
        return $this->belongsTo(StockModelResolver::warehouse(), 'supplier_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ModelResolver::subcontractingOrderItem(), 'subcontracting_order_id');
    }

    public function suppliedItems(): HasMany
    {
        return $this->hasMany(ModelResolver::subcontractingOrderSuppliedItem(), 'subcontracting_order_id');
    }

    public function calculateTotals(): void
    {
        $netTotal = $this->exists ? (float) $this->items()->sum('amount') : 0.0;

        $this->net_total = $netTotal;
        $this->grand_total = $netTotal;
    }
}
