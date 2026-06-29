<?php

namespace JeffersonGoncalves\Erp\Subcontracting\Enums;

enum SubcontractingOrderStatus: string
{
    case Draft = 'Draft';
    case Open = 'Open';
    case PartiallyReceived = 'Partially Received';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';
    case Closed = 'Closed';

    public function label(): string
    {
        return __('erp-subcontracting::erp-subcontracting.subcontracting_order_status.'.$this->value);
    }
}
