<?php

namespace Riki\Subscription\Model\Emulator\PaymentMethod;

class Paygent
    extends \Bluecom\Paygent\Model\Paygent
{

    /**
     * Init checkout
     *
     * @param string $paymentAction PaymentAction
     * @param object $stateObject   StateOrder
     *
     * @return $this
     *
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        // do not need to trigger payment transaction
        $stateObject->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $stateObject->setStatus(\Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_NOT_SHIPPED);
        $stateObject->setIsNotified(false);
        return $this;
    }
}