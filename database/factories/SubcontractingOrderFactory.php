<?php

namespace JeffersonGoncalves\Erp\Subcontracting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\Erp\Core\Models\Company;
use JeffersonGoncalves\Erp\Subcontracting\Enums\SubcontractingOrderStatus;
use JeffersonGoncalves\Erp\Subcontracting\Models\SubcontractingOrder;

/** @extends Factory<SubcontractingOrder> */
class SubcontractingOrderFactory extends Factory
{
    protected $model = SubcontractingOrder::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'party_type' => 'Supplier',
            'supplier_name' => fake()->company(),
            'transaction_date' => now(),
            'company_id' => Company::factory(),
            'status' => SubcontractingOrderStatus::Draft,
        ];
    }
}
