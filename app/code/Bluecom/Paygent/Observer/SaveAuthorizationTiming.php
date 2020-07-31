<?php

namespace Bluecom\Paygent\Observer;

use Magento\Framework\Event\ObserverInterface;

class SaveAuthorizationTiming implements ObserverInterface
{
    /**
     * @var \Bluecom\Paygent\Model\AuthorizationHistory
     */
    protected $authorizationHistory;
    /**
     * @var $logger \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        \Bluecom\Paygent\Model\AuthorizationHistory $authorizationHistory,
        \Psr\Log\LoggerInterface $loggerInterface
    )
    {
        $this->authorizationHistory = $authorizationHistory;
        $this->logger = $loggerInterface;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        /*Simulate don't need to update authorized timing */
        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return;
        }

        if (!$order->getId()
            || $order->getPayment()->getMethod() != \Bluecom\Paygent\Model\Paygent::CODE
            || $order->getStatus() == \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT
        ) {
            return false;
        }

        //save authorization timing for order used paygent method
        $this->authorizationHistory->saveAuthorizationTiming($order);

        return $this;
    }
}
