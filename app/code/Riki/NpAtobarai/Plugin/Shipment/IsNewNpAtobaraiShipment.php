<?php

namespace Riki\NpAtobarai\Plugin\Shipment;

use Riki\NpAtobarai\Model\Payment\NpAtobarai;

class IsNewNpAtobaraiShipment
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

        // If shipment is new created
        if (!$order instanceof \Riki\Subscription\Model\Emulator\Order && !$object->getId()
        ) {
            $paymentMethod = $order->getPayment()->getMethod();
            if ($paymentMethod == NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE) {
                // Add flag is_new_np_atobarai_shipment is true into this shipment
                $object->setData('is_new_np_atobarai_shipment', true);
            }
        }
    }
}
