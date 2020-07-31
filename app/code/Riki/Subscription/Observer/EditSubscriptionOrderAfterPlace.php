<?php

namespace Riki\Subscription\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riki\Sales\Helper\Order;

class EditSubscriptionOrderAfterPlace implements ObserverInterface
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator
     */
    protected $monthlyFeeProfileValidator;

    /**
     * UpdateDataAfterEditOrder constructor.
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator $monthlyFeeProfileValidator
     */
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator $monthlyFeeProfileValidator
    ) {
        $this->orderFactory = $orderFactory;
        $this->monthlyFeeProfileValidator = $monthlyFeeProfileValidator;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Checkout\Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return;
        }
        $oldOrderId = $order->getRelationParentId();
        $subscriptionOrderTimes = null;
        $subscriptionCourse = null;
        $subscriptionFrequency = null;
        if ($oldOrderId) {
            $oldOrderModel = $this->orderFactory->create()->load($oldOrderId);
            if ($oldOrderModel->getId()) {
                $subscriptionOrderTimes = $oldOrderModel->getSubscriptionOrderTime();
                $subscriptionProfileId = $oldOrderModel->getSubscriptionProfileId();
                if ($subscriptionProfileId) {
                    $order->setData('subscription_order_time', $subscriptionOrderTimes);
                    $order->setData('subscription_profile_id', $subscriptionProfileId);
                    $order->setData('riki_type', Order::RIKI_TYPE_SUBSCRIPTION);
                }
            }
        } else {
            // Check subscription type of order
            // If subscription is monthly fee, riki type of order must be 'SUBSCRIPTION'
            if ($profileId = $order->getProfileId()) {
                if ($this->monthlyFeeProfileValidator->isMonthlyFeeProfile($profileId)) {
                    $order->setData('riki_type', Order::RIKI_TYPE_SUBSCRIPTION);
                }
            }
        }
    }
}
