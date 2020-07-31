<?php

namespace Riki\Promo\Model\Plugin\Quote\Discount;

class RegisterSubtotal
{
    protected $registry;

    public function __construct(
        \Magento\Framework\Registry $registry
    )
    {
        $this->registry = $registry;
    }

    public function beforeCollect(
        $subject,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    )
    {
        $this->registry->unregister('riki_promo_subtotal');
        $this->registry->register('riki_promo_subtotal', $total->getTotalAmount('subtotal'));
        return [$quote, $shippingAssignment, $total];
    }
}