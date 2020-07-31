<?php
namespace Riki\Rma\Helper;

use Magento\Framework\Exception\LocalizedException;

final class Constant
{
    const REGISTRY_KEY_RMA_SHIPMENT_NUMBER = 'rma_shipment_number';
    const REGISTRY_KEY_REASON_ID = 'reason_id';
    const REGISTRY_KEY_RETURNED_WAREHOUSE = 'returned_warehouse';
    const REGISTRY_KEY_FULL_PARTIAL = 'full_partial';
    const REGISTRY_KEY_WARNING = 'warning';
    const REGISTRY_KEY_REFUND_ALLOWED = 'refund_allowed';
    const REGISTRY_KEY_REFUND_METHOD = 'refund_method';
    const REGISTRY_KEY_TOTAL_CANCEL_POINT = 'total_cancel_point';
    const REGISTRY_KEY_TOTAL_RETURN_POINT = 'total_return_point';
    const REGISTRY_KEY_RETURN_SHIPPING_FEE = 'return_shipping_fee';
    const REGISTRY_KEY_RETURN_PAYMENT_FEE = 'return_payment_fee';
    const REGISTRY_KEY_TOTAL_RETURN_AMOUNT = 'total_return_amount';
    const REGISTRY_KEY_DISABLE_COLLECT_TOTAL_CREDIT_MEMO = 'disable_collect_total_credit_memo';
    const REGISTRY_KEY_RMA_CUSTOMER = 'rma_customer';
    const REGISTRY_KEY_SUBSTITUTION_ORDER = 'substitution_order';

    private function __construct()
    {
        throw new \Exception("Can't get an instance of " . __CLASS__);
    }
}
