<?php

namespace Riki\Loyalty\Model\Payment;

use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;

class ZeroTotal extends \Magento\Payment\Model\Checks\ZeroTotal
{
    protected $rewardManagement;

    public function __construct(
        \Riki\Loyalty\Model\RewardManagement $rewardManagement
    )
    {
        $this->rewardManagement = $rewardManagement;
    }

    /**
     * Check whether payment method is applicable to quote
     * Purposed to allow use in controllers some logic that was implemented in blocks only before
     *
     * @param MethodInterface $paymentMethod
     * @param \Magento\Quote\Model\Quote $quote
     * @return bool
     */
    public function isApplicable(MethodInterface $paymentMethod, Quote $quote)
    {
//        if ($this->rewardManagement->isSpecialCase($quote)) {
//            return true;
//        }
        return parent::isApplicable($paymentMethod, $quote);
    }
}