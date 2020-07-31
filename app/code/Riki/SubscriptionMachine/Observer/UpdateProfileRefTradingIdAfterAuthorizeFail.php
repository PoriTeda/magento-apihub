<?php

namespace Riki\SubscriptionMachine\Observer;

use Magento\Framework\Event\Observer;

class UpdateProfileRefTradingIdAfterAuthorizeFail implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator
     */
    private $monthlyFeeProfileValidator;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $datetime;

    public function __construct(
        \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator $monthlyFeeProfileValidator,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime
    ) {
        $this->monthlyFeeProfileValidator = $monthlyFeeProfileValidator;
        $this->datetime = $datetime;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $observer->getEvent()->getProfile();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        if ($this->monthlyFeeProfileValidator->isMonthlyFeeProfile($profile->getProfileId())
            && $profile->getPaymentMethod() == \Bluecom\Paygent\Model\ConfigProvider::PAYGENT_CODE
            && $order->getStatus() == \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT
        ) {
            $authorizationFailedTime = (int)$profile->getData('authorization_failed_time');
            $profile->setData('authorization_failed_time', ($authorizationFailedTime + 1));
            $profile->setData('last_authorization_failed_date', $this->datetime->gmtDate());
            $profile->setPaymentMethod(null);
            $profile->setTradingId(null);
        }
    }
}