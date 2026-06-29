<?php

namespace JeffersonGoncalves\Erp\Subcontracting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\Erp\Core\Models\Company;
use JeffersonGoncalves\Erp\Subcontracting\Models\SubcontractingReceipt;

/** @extends Factory<SubcontractingReceipt> */
class SubcontractingReceiptFactory extends Factory
{
    protected $model = SubcontractingReceipt::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'party_type' => 'Supplier',
            'supplier_name' => fake()->company(),
            'posting_date' => now(),
            'company_id' => Company::factory(),
        ];
    }
}
