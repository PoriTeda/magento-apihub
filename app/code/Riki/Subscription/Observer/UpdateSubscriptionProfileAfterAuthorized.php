<?php

namespace Riki\Subscription\Observer;

class UpdateSubscriptionProfileAfterAuthorized implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Bluecom\Paygent\Model\ResourceModel\PaygentHistory\CollectionFactory
     */
    protected $paygentHistoryCollectionFactory;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfilePoolFactory
     */
    protected $profilePoolFactory;

    /**
     * UpdateSubscriptionProfileAfterAuthorized constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Subscription\Model\Profile\ProfilePoolFactory $profilePoolFactory
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Bluecom\Paygent\Model\ResourceModel\PaygentHistory\CollectionFactory $paygentHistoryCollectionFactory,
        \Riki\Subscription\Model\Profile\ProfilePoolFactory $profilePoolFactory
    ) {
        $this->logger = $logger;
        $this->paygentHistoryCollectionFactory = $paygentHistoryCollectionFactory;
        $this->profilePoolFactory = $profilePoolFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        /*Simulate doesn't need to update Sale Rule */
        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return;
        }

        $payment = $order->getPayment();

        if (empty($payment)) {
            return;
        }

        if (!empty($payment) && $payment->getMethod() != \Bluecom\Paygent\Model\Paygent::CODE) {
            return;
        }

        $tradingId = $this->getOrderTradingIdByOrderNumber($order->getIncrementId());

        /*order did not authorize*/
        if (empty($tradingId)) {
            return;
        }

        $subscriptionProfileId = $order->getSubscriptionProfileId();

        if (empty($subscriptionProfileId)) {
            return;
        }

        /** @var \Riki\Subscription\Model\Profile\ProfilePool $profilePool */
        $profilePool = $this->profilePoolFactory->create()->load($subscriptionProfileId);

        if ($profilePool->getId()) {

            $oldTradingId = $profilePool->getData('trading_id');

            if ($oldTradingId != $tradingId) {
                $profilePool->setData('trading_id', $tradingId);

                try {
                    $profilePool->save();
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
        }
    }
    /**
     * Get order trading id by order number
     *
     * @param $orderNumber
     * @return bool|mixed
     */
    private function getOrderTradingIdByOrderNumber($orderNumber)
    {
        /** @var \Bluecom\Paygent\Model\ResourceModel\PaygentHistory\Collection $collection */
        $collection = $this->paygentHistoryCollectionFactory->create();

        $collection->addFieldToFilter(
            'order_number', $orderNumber
        )->addFieldToFilter(
            'type', 'authorize'
        )->setOrder(
            'id', 'DESC'
        );

        if ($collection->getSize()) {
            return $collection->setPageSize(1)->getFirstItem()->getData('trading_id');
        }

        return false;
    }
}