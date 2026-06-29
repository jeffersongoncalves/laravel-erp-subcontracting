<?php

use JeffersonGoncalves\Erp\Subcontracting\Models\SubcontractingBom;
use JeffersonGoncalves\Erp\Subcontracting\Models\SubcontractingBomItem;
use JeffersonGoncalves\Erp\Subcontracting\Models\SubcontractingOrder;
use JeffersonGoncalves\Erp\Subcontracting\Models\SubcontractingOrderItem;
use JeffersonGoncalves\Erp\Subcontracting\Models\SubcontractingOrderSuppliedItem;
use JeffersonGoncalves\Erp\Subcontracting\Models\SubcontractingReceipt;
use JeffersonGoncalves\Erp\Subcontracting\Models\SubcontractingReceiptItem;
use JeffersonGoncalves\Erp\Subcontracting\Models\SubcontractingReceiptSuppliedItem;

return [
    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix applied to all tables created by the package. This is shared with
    | laravel-erp-core, laravel-erp-accounting, laravel-erp-stock,
    | laravel-erp-buying and laravel-erp-manufacturing so that foreign keys
    | across the ERP ecosystem resolve against a single set of prefixed tables.
    | Set to null to disable.
    |
    */
    'table_prefix' => 'erp_',

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | Models used by the package. Can be overridden to extend the default
    | behavior via the ModelResolver pattern.
    |
    */
    'models' => [
        'subcontracting_bom' => SubcontractingBom::class,
        'subcontracting_bom_item' => SubcontractingBomItem::class,
        'subcontracting_order' => SubcontractingOrder::class,
        'subcontracting_order_item' => SubcontractingOrderItem::class,
        'subcontracting_order_supplied_item' => SubcontractingOrderSuppliedItem::class,
        'subcontracting_receipt' => SubcontractingReceipt::class,
        'subcontracting_receipt_item' => SubcontractingReceiptItem::class,
        'subcontracting_receipt_supplied_item' => SubcontractingReceiptSuppliedItem::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    |
    | The default account that absorbs the offsetting entry when a
    | subcontracting receipt posts the net subcontracting cost to the general
    | ledger ("Stock Received But Not Billed" / a subcontracting expense). When
    | null the receipt must declare its own counter account before submission.
    |
    */
    'default_subcontracting_counter_account' => null,
];
