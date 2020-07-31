<?php

namespace Riki\CsvOrderMultiple\Plugin;

class SetFreeCodFlag
{
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
        if ($quote->getCsvOrderCodFree()) {
            $shippingAssignment->getShipping()
                ->getAddress()
                ->setFreeSurchargeFee(true);
        }

        return [$quote, $shippingAssignment, $total];
    }
}
