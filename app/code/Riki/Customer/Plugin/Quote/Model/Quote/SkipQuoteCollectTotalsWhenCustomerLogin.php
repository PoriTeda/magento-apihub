<?php

namespace Riki\Customer\Plugin\Quote\Model\Quote;

class SkipQuoteCollectTotalsWhenCustomerLogin
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * CustomerLoginBeforeQuoteTotalsCollector constructor.
     *
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        $this->registry = $registry;
    }

    /**
     * Skip collect totals data when customer login.
     *
     * @param \Magento\Quote\Model\Quote $subject
     */
    public function beforeCollectTotals(
        \Magento\Quote\Model\Quote $subject
    ) {
        if ($this->registry->registry('is_customer_sso_login')) {
            $this->registry->unregister('is_customer_sso_login');
            // Enable flag collect totals to skip collect totals data when customer login.
            $subject->setTotalsCollectedFlag(true);
        }
    }
}
