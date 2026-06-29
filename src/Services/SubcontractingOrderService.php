<?php

namespace JeffersonGoncalves\Erp\Subcontracting\Services;

use JeffersonGoncalves\Erp\Subcontracting\Models\SubcontractingOrder;
use JeffersonGoncalves\Erp\Subcontracting\Models\SubcontractingReceipt;
use JeffersonGoncalves\Erp\Subcontracting\Support\ModelResolver;

/**
 * Converts a subcontracting order into its downstream subcontracting receipt.
 */
class SubcontractingOrderService
{
    /**
     * Draft a subcontracting receipt for the finished-good quantities still to
     * be received, copying the supplied raw materials over as consumed.
     */
    public function createReceipt(SubcontractingOrder $subcontractingOrder): SubcontractingReceipt
    {
        $receiptClass = ModelResolver::subcontractingReceipt();

        /** @var SubcontractingReceipt $receipt */
        $receipt = new $receiptClass;
        $receipt->fill([
            'subcontracting_order_id' => $subcontractingOrder->getKey(),
            'party_type' => $subcontractingOrder->party_type,
            'party_id' => $subcontractingOrder->party_id,
            'supplier_name' => $subcontractingOrder->supplier_name,
            'company_id' => $subcontractingOrder->company_id,
            'supplier_warehouse_id' => $subcontractingOrder->supplier_warehouse_id,
            'posting_date' => today(),
        ]);
        $receipt->save();

        foreach ($subcontractingOrder->items as $item) {
            $remaining = (float) $item->qty - (float) $item->received_qty;

            if ($remaining <= 0.0) {
                continue;
            }

            $receipt->items()->create([
                'item_code' => $item->item_code,
                'qty' => $remaining,
                'rate' => $item->rate,
                'warehouse_id' => $item->fg_warehouse_id,
                'bom_id' => $item->bom_id,
            ]);
        }

        foreach ($subcontractingOrder->suppliedItems as $supplied) {
            $receipt->suppliedItems()->create([
                'main_item_code' => $supplied->main_item_code,
                'rm_item_code' => $supplied->rm_item_code,
                'consumed_qty' => $supplied->required_qty,
            ]);
        }

        return $receipt->refresh();
    }
}
