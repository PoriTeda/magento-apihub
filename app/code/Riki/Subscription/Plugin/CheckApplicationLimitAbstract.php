<?php

namespace Riki\Subscription\Plugin;

abstract class CheckApplicationLimitAbstract
{
    /**
     * @var \Magento\Customer\Model\Session\Proxy
     */
    protected $customerSession;

    /**
     * @var \Magento\Checkout\Model\Session\Proxy
     */
    protected $checkoutSession;

    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $subscriptionPageHelper;

    /**
     * CheckApplicationLimitAbstract constructor.
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper
    ) {
        $this->customerSession = $customerSession;
        $this->subscriptionPageHelper = $subscriptionPageHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @return array
     */
    protected function validateApplicationLimit()
    {
        $courseId = $this->checkoutSession->getQuote()->getData('riki_course_id');
        $customerId = $this->customerSession->getCustomerId();
        return $this->subscriptionPageHelper->checkApplicationLimit(
            $customerId,
            $courseId
        );
    }
}
