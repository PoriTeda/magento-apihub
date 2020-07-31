<?php
namespace Riki\SubscriptionProfileDisengagement\Plugin\SubscriptionPage\Helper;

class Data
{
    /** @var \Riki\SubscriptionProfileDisengagement\Helper\Data  */
    protected $helper;

    /** @var \Magento\Framework\App\RequestInterface  */
    protected $request;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Riki\SubscriptionProfileDisengagement\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Riki\SubscriptionProfileDisengagement\Helper\Data $helper
    ){
        $this->helper = $helper;
        $this->request = $request;
    }

    /**
     * Ignore validate for pending disengaged profile
     *
     * @param \Riki\SubscriptionPage\Helper\Data $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote $quote
     * @return int|mixed
     */
    public function aroundValidateSubscriptionRule(
        \Riki\SubscriptionPage\Helper\Data $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote $quote
    ) {

        $profileId = $this->request->getParam('profile_id', 0);

        if ($profileId && $this->helper->isPendingToDisengage($profileId)) {
            return 0;
        }

        return $proceed($quote);
    }
}
