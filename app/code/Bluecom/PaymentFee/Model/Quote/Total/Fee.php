<?php

namespace Bluecom\PaymentFee\Model\Quote\Total;

class Fee extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    /**
     * @var \Bluecom\PaymentFee\Helper\Data
     */
    protected $_helperData;

    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $_request;

    /**
     * Fee constructor.
     *
     * @param \Bluecom\PaymentFee\Helper\Data $helperData helper
     * @param \Magento\Framework\Webapi\Rest\Request $request helper
     */
    public function __construct(
        \Bluecom\PaymentFee\Helper\Data $helperData,
        \Magento\Framework\Webapi\Rest\Request $request
    )
    {
        $this->setCode('fee');
        $this->_helperData = $helperData;
        $this->_request = $request;
    }

    /**
     * Collect totals process.
     *
     * @param \Magento\Quote\Model\Quote $quote quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment shipping assignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total total
     *
     * @return $this
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        $total->setTotalAmount('fee', 0);
        $total->setBaseTotalAmount('base_fee', 0);

        if (!count($shippingAssignment->getItems())) {
            return $this;
        }

        $fee = null;
        $paymentMethod = $quote->getPayment()->getMethod();
        $address = $shippingAssignment->getShipping()->getAddress();

        // todo: decoupling
        if($this->checkRequestWebApi()){
            $fee = 0;
        }

        if (!$address->getFreeSurchargeFee() && $paymentMethod && is_null($fee)) {
            $fee = $this->_helperData->getPaymentCharge($paymentMethod);
        } else {
            $fee = 0;
        }

        $total->setFee($fee);
        $total->setBaseFee($fee);

        $quote->setFee($fee);
        $quote->setBaseFee($fee);

        $total->setTotalAmount('fee', $fee);
        $total->setBaseTotalAmount('base_fee', $fee);

        return $this;
    }

    /**
     * Assign subtotal amount and label to address object
     *
     * @param \Magento\Quote\Model\Quote               $quote quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total total
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        $quoteFee = $quote->getFee();
        $result = [];
        if (!is_null($quoteFee)) {
            $result = [
                'code' => 'fee',
                'title' => __('Payment Fee'),
                'value' => $quoteFee
            ];
        }
        return $result;
    }

    /**
     * Get Subtotal label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getLabel()
    {
        return __('Payment Fee');
    }

    /**
     * check request from web api
     *
     * @return bool
     */
    public function checkRequestWebApi()
    {
        $pathInfo = $this->_request->getPathInfo();
        $patternStep5 = '#V1/mm/carts/order/payment-information#';
        if (preg_match($patternStep5, $pathInfo, $match)) {
            return true;
        }

        $pattern = '#/V1/mm/carts/#';
        if (preg_match($pattern, $pathInfo, $match)) {
            return true;
        }
        return false;

    }
}
