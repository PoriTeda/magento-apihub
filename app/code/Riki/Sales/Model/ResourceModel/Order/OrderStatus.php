<?php
namespace Riki\Sales\Model\ResourceModel\Order;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class OrderStatus
 * @package Riki\Sales\Model\ResourceModel\Order
 */
class OrderStatus
{
    const STATUS_ORDER_CANCELED = 'canceled';
    const STATUS_ORDER_CAPTURE_FAILED = 'capture_failed';
    const STATUS_ORDER_COMPLETE = 'complete';
    const STATUS_ORDER_CRD_FEEDBACK = 'feedback_crd';
    const STATUS_ORDER_SUSPECTED_FRAUD = 'fraud';
    const STATUS_ORDER_PENDING_CRD_REVIEW = 'holded';
    const STATUS_ORDER_PARTIALLY_SHIPPED = 'partially_shipped';
    const STATUS_ORDER_PENDING_CVS = 'pending_cvs_payment';
    const STATUS_ORDER_PENDING_NP = 'pending_np';
    const STATUS_ORDER_PENDING_CC = 'pending_payment';
    const STATUS_ORDER_IN_PROCESSING = 'preparing_for_shipping';
    const STATUS_ORDER_SHIPPED_ALL = 'shipped_all';
    const STATUS_ORDER_SUSPICIOUS = 'suspicious';
    const STATUS_ORDER_NOT_SHIPPED = 'waiting_for_shipping';
    const STATUS_ORDER_HOLD_CVS_NOPAYMENT = 'hold_cvs_nopayment';
    const STATUS_ORDER_CVS_CANCELLATION_WITH_PAYMENT = 'cvs_cancellation_with_payment';
    const STATUS_ORDER_PENDING_FOR_MACHINE = 'pending_for_machine';
    const STATUS_ORDER_PROCESSING_CANCELED = 'processing_canceled';

}
