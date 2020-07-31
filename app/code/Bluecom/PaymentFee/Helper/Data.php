<?php

namespace Bluecom\PaymentFee\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    // tax classes
    const CONFIG_XML_PATH_PAYMENT_TAX_CLASS = 'tax/classes/payment_tax_class';

    /**
     * Payment fee
     *
     * @var \Bluecom\PaymentFee\Model\PaymentFee
     */
    protected $_paymentFee;

    /**
     * \Magento\Catalog\Helper\Data
     *
     * @var \Magento\Catalog\Helper\Data
     */
    protected $catalogHelper;

    /**
     * @var \Magento\Tax\Model\TaxCalculation
     */
    protected $taxCalculation;

    /**
     * Data constructor.
     * @param \Bluecom\PaymentFee\Model\PaymentFee $paymentFee
     * @param \Magento\Catalog\Helper\Data $catalogHelper
     * @param \Magento\Tax\Model\TaxCalculation $taxCalculation
     * @param Context $context
     */
    public function __construct(
        \Bluecom\PaymentFee\Model\PaymentFee $paymentFee,
        \Magento\Catalog\Helper\Data $catalogHelper,
        \Magento\Tax\Model\TaxCalculation $taxCalculation,
        Context $context
    ) {
        $this->_paymentFee = $paymentFee;
        $this->catalogHelper = $catalogHelper;
        $this->taxCalculation = $taxCalculation;
        parent::__construct($context);
    }

    /**
     * Get Payment charge
     *
     * @param string $code code
     *
     * @return int
     */
    public function getPaymentCharge($code)
    {
        $amount = 0;
        if ($this->isPaymentAvailable($code)) {
            //Active or not
            if ($this->_paymentFee->getData('active')) {
                $amount = $this->_paymentFee->getFixedAmount();
            }
        }
        return $amount;
    }

    /**
     * Check available payment fee
     *
     * @param string $code code
     *
     * @return bool
     */
    public function isPaymentAvailable($code)
    {
        $available = false;
        if ($this->_paymentFee->loadByCode($code)) {
            $available = true;
        };
        return $available;

    }

    /**
     * Get fixed amount
     *
     * @return mixed
     */
    public function getFixedAmount()
    {
        return $this->_paymentFee->getFixedAmount();
    }

    /**
     * Get tax class id specified for payment tax estimation
     *
     * @param   null|string|bool|int|Store $store
     * @return  int
     */
    public function getPaymentTaxClass($store = null)
    {
        return (int)$this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_PAYMENT_TAX_CLASS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     *
     */
    public function getFeeExcludeTax($price, $shippingAddress = null, $ctc = null, $store = null){
        $pseudoProduct = new \Magento\Framework\DataObject();
        $pseudoProduct->setTaxClassId($this->getPaymentTaxClass($store));

        $billingAddress = false;
        if ($shippingAddress && $shippingAddress->getQuote() && $shippingAddress->getQuote()->getBillingAddress()) {
            $billingAddress = $shippingAddress->getQuote()->getBillingAddress();
        }

        $price = $this->catalogHelper->getTaxPrice(
            $pseudoProduct,
            $price,
            false,
            $shippingAddress,
            $billingAddress,
            $ctc,
            $store,
            true
        );

        return $price;
    }

    /**
     * @return float
     */
    public function getPaymentTaxRate()
    {
        return $this->taxCalculation->getCalculatedRate(
            $this->getPaymentTaxClass()
        );
    }
}