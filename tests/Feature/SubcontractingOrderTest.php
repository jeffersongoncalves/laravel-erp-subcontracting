<?php

use JeffersonGoncalves\Erp\Core\Enums\DocStatus;
use JeffersonGoncalves\Erp\Subcontracting\Enums\SubcontractingOrderStatus;
use JeffersonGoncalves\Erp\Subcontracting\Models\SubcontractingOrder;

it('submits a subcontracting order, computing totals and opening its status', function () {
    $order = SubcontractingOrder::factory()->create();

    $order->items()->create([
        'item_code' => 'FG-WIDGET',
        'qty' => 10,
        'rate' => 15,
        'fg_warehouse_id' => null,
    ]);

    $order->suppliedItems()->create([
        'main_item_code' => 'FG-WIDGET',
        'rm_item_code' => 'RM-STEEL',
        'required_qty' => 20,
    ]);

    $order->refresh();

    expect($order->net_total)->toBe(150.0)
        ->and($order->grand_total)->toBe(150.0)
        ->and($order->isDraft())->toBeTrue()
        ->and($order->status)->toBe(SubcontractingOrderStatus::Draft);

    $order->submit();

    expect($order->docstatus)->toBe(DocStatus::Submitted)
        ->and($order->isSubmitted())->toBeTrue()
        ->and($order->status)->toBe(SubcontractingOrderStatus::Open)
        ->and($order->net_total)->toBe(150.0)
        ->and($order->suppliedItems()->count())->toBe(1)
        ->and($order->suppliedItems()->first()->rm_item_code)->toBe('RM-STEEL');
});
