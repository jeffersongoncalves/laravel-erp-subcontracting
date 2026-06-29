<?php

namespace JeffersonGoncalves\Erp\Subcontracting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use JeffersonGoncalves\Erp\Subcontracting\Models\SubcontractingBom;

/** @extends Factory<SubcontractingBom> */
class SubcontractingBomFactory extends Factory
{
    protected $model = SubcontractingBom::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'finished_good' => Str::upper(Str::slug($name)).'-'.fake()->unique()->numberBetween(100, 99999),
            'finished_good_qty' => 1,
            'service_item' => null,
            'is_active' => true,
        ];
    }
}
