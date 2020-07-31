<?php

namespace Riki\Promo\Plugin;

class ResetFlagBeforeDiscountCollect
{
    /**
     * @param \Magento\SalesRule\Model\Quote\Discount $subject
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     *
     * @return array
     */
    public function beforeCollect(
        \Magento\SalesRule\Model\Quote\Discount $subject,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    )
    {
        $quote->setIsAutoAddFirstItem(false);
        return [$quote, $shippingAssignment, $total];
    }
}
