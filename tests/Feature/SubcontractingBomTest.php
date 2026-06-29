<?php

use JeffersonGoncalves\Erp\Subcontracting\Models\SubcontractingBom;

it('creates a subcontracting BOM master with supplied raw materials', function () {
    $bom = SubcontractingBom::factory()->create([
        'finished_good' => 'FG-WIDGET',
        'finished_good_qty' => 1,
    ]);

    $bom->items()->create(['item_code' => 'RM-STEEL', 'qty' => 2]);
    $bom->items()->create(['item_code' => 'RM-PAINT', 'qty' => 1]);

    expect($bom->is_active)->toBeTrue()
        ->and($bom->finished_good)->toBe('FG-WIDGET')
        ->and($bom->finished_good_qty)->toBe(1.0)
        ->and($bom->items()->count())->toBe(2)
        ->and($bom->items()->where('item_code', 'RM-STEEL')->first()->qty)->toBe(2.0);
});
