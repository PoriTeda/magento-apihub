<?php

namespace Bluecom\Paygent\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UpdateOrderProfileAfterAuthorizeSuccess implements ObserverInterface
{
    /**
     * @var \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator
     */
    private $monthlyFeeProfileValidator;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    private $profileFactory;


    /**
     * UpdateOrderProfileAfterAuthorizeSuccess constructor.
     */
    public function __construct(
        \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator $monthlyFeeProfileValidator,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
    ) {
        $this->monthlyFeeProfileValidator = $monthlyFeeProfileValidator;
        $this->profileFactory = $profileFactory;
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        if ($this->monthlyFeeProfileValidator->isMonthlyFeeProfile($order->getSubscriptionProfileId())) {
            $profile = $this->profileFactory->create()->load($order->getSubscriptionProfileId());
            $profile->setTradingId($order->getRefTradingId());
            $profile->setPaymentMethod(\Bluecom\Paygent\Model\ConfigProvider::PAYGENT_CODE);
            $profile->save();
        }
    }
}