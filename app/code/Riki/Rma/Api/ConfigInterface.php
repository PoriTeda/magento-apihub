<?php
namespace Riki\Rma\Api;

interface ConfigInterface
{
    const RMA = 'rma';
    const RMA_REASON_COD_NOT_ALLOWED = 'rma/reason/cod_not_allowed';
    const XML_PATH_RMA_REASON_COD_REJECTED = 'rma/reason/cod_rejected';
    const RMA_REFUND_METHOD = 'rma/refund_method';
    const RMA_REFUND_METHOD_ENABLE_PAYMENT = 'rma/refund_method/enable_payment';
    const RMA_REFUND_METHOD_ENABLE_REFUND = 'rma/refund_method/enable_refund';
    const RMA_RETURN_AMOUNT_REMAINING_AMOUNT_LIMIT = 'rma/return_amount/remaining_amount_limit';
    const RMA_RETURN_AMOUNT_SHIPMENT_FEES_WITH_REMAINING = 'rma/return_amount/shipment_fees_with_remaining';
    const RMA_CARRIER_COD = 'rma/carrier/cod';
}