<?php
namespace Riki\Rma\Model\Config\Source\Payment;

class Method extends \Riki\Framework\Model\Source\AbstractOption
{
    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentDataHelper;

    /**
     * PaymentMethod constructor.
     * @param \Magento\Payment\Helper\Data $paymentDataHelper
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentDataHelper
    )
    {
        $this->paymentDataHelper = $paymentDataHelper;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->paymentDataHelper->getPaymentMethodList(true, true);
    }

}
