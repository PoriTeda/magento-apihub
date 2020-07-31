<?php

namespace Riki\NpAtobarai\Plugin\Shipment;

use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Riki\NpAtobarai\Model\Payment\NpAtobarai;

class UpdatePaymentMethodBeforeSave
{
    /**
     * Before save
     *
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment $subject
     * @param \Magento\Framework\Model\AbstractModel $object
     */
    public function beforeSave(
        \Magento\Sales\Model\ResourceModel\Order\Shipment $subject,
        \Magento\Framework\Model\AbstractModel $object
    ) {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $object->getOrder();

        // If shipment is create and payment method is npatobarai, update payment status for shipment is Authorized
        if (!$order instanceof \Riki\Subscription\Model\Emulator\Order && !$object->getId()) {
            $paymentMethod = $order->getPayment()->getMethod();
            if ($paymentMethod == NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE) {
                $object->setData('payment_status', PaymentStatus::SHIPPING_PAYMENT_STATUS_AUTHORIZED);
            }
        }
    }
}
