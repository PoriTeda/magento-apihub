<?php

namespace Riki\Sales\Plugin;

class SetFlagFreePaymentFee
{
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $backendQuoteSession;

    /**
     * SetFlagFreePaymentFee constructor.
     *
     * @param \Magento\Backend\Model\Session\Quote $backendQuoteSession
     */
    public function __construct(
        \Magento\Backend\Model\Session\Quote $backendQuoteSession
    )
    {
        $this->backendQuoteSession = $backendQuoteSession;
    }

    /**
     * @param \Bluecom\PaymentFee\Model\Quote\Total\Fee $subject
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     *
     * @return array
     */
    public function beforeCollect(
        \Bluecom\PaymentFee\Model\Quote\Total\Fee $subject,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    )
    {
        if ($this->backendQuoteSession->getFreeSurcharge()) {
            $shippingAssignment->getShipping()
                ->getAddress()
                ->setFreeSurchargeFee(true);
        }

        return [$quote, $shippingAssignment, $total];
    }
}
