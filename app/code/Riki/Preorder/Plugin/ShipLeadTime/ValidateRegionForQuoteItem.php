<?php

namespace Riki\Preorder\Plugin\ShipLeadTime;

class ValidateRegionForQuoteItem
{
    /**
     * @var \Riki\Preorder\Helper\Data
     */
    protected $preOrderHelper;

    /**
     * ValidateRegionForQuoteItem constructor.
     *
     * @param \Riki\Preorder\Helper\Data $preOrderHelper
     */
    public function __construct(
        \Riki\Preorder\Helper\Data $preOrderHelper
    ) {
        $this->preOrderHelper = $preOrderHelper;
    }

    /**
     * do not need validate region for quote item is pre-order
     *
     * @param \Riki\ShipLeadTime\Helper\Data $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return bool
     */
    public function aroundNeedValidateRegionForQuoteItem(
        \Riki\ShipLeadTime\Helper\Data $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item $quoteItem
    ) {
        if ($this->preOrderHelper->getQuoteItemIsPreorder($quoteItem)) {
            return false;
        }

        return $proceed($quoteItem);
    }
}