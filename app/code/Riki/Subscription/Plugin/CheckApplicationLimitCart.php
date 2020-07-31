<?php

namespace Riki\Subscription\Plugin;

class CheckApplicationLimitCart extends CheckApplicationLimitAbstract
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $subscriptionPageHelper;

    /**
     * CheckApplicationLimitCart constructor.
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->messageManager = $messageManager;
        $this->subscriptionPageHelper = $subscriptionPageHelper;
        parent::__construct($customerSession, $checkoutSession, $subscriptionPageHelper);
    }

    /**
     * Before Execute
     * @return void
     */
    public function beforeExecute()
    {
        if ($this->customerSession->isLoggedIn()) {
            $applicationLimitValidatingResult = $this->validateApplicationLimit();
            if ($applicationLimitValidatingResult['has_error']) {
                $errorMessage = $this->subscriptionPageHelper->getApplicationLimitErrorMessage(
                    $applicationLimitValidatingResult
                );
                $this->messageManager->addError($errorMessage);
            }
        }
    }
}
