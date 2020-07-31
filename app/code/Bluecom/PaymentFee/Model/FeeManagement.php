<?php
namespace Bluecom\PaymentFee\Model;

class FeeManagement implements \Bluecom\PaymentFee\Api\FeeManagementInterface
{
    /**
     * @var \Bluecom\PaymentFee\Helper\Data
     */
    protected $dataHelper;

    /**
     * FeeManagement constructor.
     *
     * @param \Bluecom\PaymentFee\Helper\Data $dataHelper
     */
    public function __construct(
        \Bluecom\PaymentFee\Helper\Data $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    /**
     * {@inheritdoc}
     *
     * @param $method
     *
     * @return int
     */
    public function getFeeByMethod($method)
    {
        return intval($this->dataHelper->getPaymentCharge($method));
    }

}