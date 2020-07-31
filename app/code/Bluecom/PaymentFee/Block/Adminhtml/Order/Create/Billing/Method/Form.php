<?php

namespace Bluecom\PaymentFee\Block\Adminhtml\Order\Create\Billing\Method;


class Form extends \Magento\Sales\Block\Adminhtml\Order\Create\Billing\Method\Form
{
    /**
     * Payment fee
     *
     * @var \Bluecom\PaymentFee\Model\ResourceModel\PaymentFee
     */
    protected $_paymentFee;
    /**
     * Currency
     *
     * @var \Magento\Directory\Model\Currency
     */
    protected $_currency;
    /**
     * Currency interface
     *
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    protected $_localeCurrency;
    /**
     * Customer repository interface
     *
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customerModel;
    /**
     * Rule factory
     *
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $_ruleFactory;
    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $_addressRepository;
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;
    /**
     * @var \Bluecom\PaymentFee\Logger\LoggerGiftOrder
     */
    protected $_loggerGiftOrder;

    /**
     * Form constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Payment\Model\Checks\SpecificationFactory $methodSpecificationFactory
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Bluecom\PaymentFee\Model\ResourceModel\PaymentFee $paymentFee
     * @param \Magento\Directory\Model\Currency $currency
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Magento\Customer\Model\Customer $customerModel
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Bluecom\PaymentFee\Logger\LoggerGiftOrder $loggerGiftOrder
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Payment\Model\Checks\SpecificationFactory $methodSpecificationFactory,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Bluecom\PaymentFee\Model\ResourceModel\PaymentFee $paymentFee,
        \Magento\Directory\Model\Currency $currency,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Customer\Model\Customer $customerModel,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Bluecom\PaymentFee\Logger\LoggerGiftOrder $loggerGiftOrder,
        array $data = []
    ) {
        $this->_paymentFee = $paymentFee;
        $this->_currency = $currency;
        $this->_localeCurrency = $localeCurrency;
        $this->customerRepository = $customerRepository;
        $this->_customerModel = $customerModel;
        $this->_ruleFactory = $ruleFactory;
        $this->_addressRepository = $addressRepository;
        $this->_jsonHelper = $jsonHelper;
        $this->_loggerGiftOrder = $loggerGiftOrder;
        parent::__construct($context, $paymentHelper, $methodSpecificationFactory, $sessionQuote, $data);
    }

    /**
     * Get all payment fee codes
     *
     * @return array
     */
    public function getPaymentFeeCode()
    {
        $data = $this->_paymentFee->getAllCodes();
        $ruleApplied = $this->getQuote()->getAppliedRuleIds();
        $freeSurchargeMethod = [];
        if ($ruleApplied >0) {
            $ruleModel = $this->_ruleFactory->create()->getCollection();

            $ruleModel->addFieldToFilter('rule_id', explode(',', $ruleApplied));
            foreach ($ruleModel as $rule) {
                if ($rule->getData('free_cod_charge') == 1) {
                    $conditions = $rule->getConditions()->asArray();
                    if (isset($conditions['conditions'])) {
                        foreach ($conditions['conditions'] as $condition) {
                            if (!isset($condition['attribute'])) {
                                continue;
                            }
                            if ($condition['attribute'] != 'payment_method') {
                                continue;
                            }

                            $freeSurchargeMethod[] = $condition['value'];
                        }
                    }
                }
            }
        }
        if ($this->_sessionQuote->getFreeSurcharge()) {
            foreach ($data as $code => $amount) {
                $data[$code] = 0;
            }
        } else if ($freeSurchargeMethod) {
            foreach ($data as $code => $amount) {
                if (in_array($code, $freeSurchargeMethod)) {
                    $data[$code] = 0;
                }
            }
        }

        return $data;
    }

    /**
     * Get key code
     *
     * @return array
     */
    public function getKeyCode()
    {
        return $this->_paymentFee->getKeyCodes($this->getPaymentFeeCode());
    }

    /**
     * Retrieve currency symbol
     *
     * @return string
     */
    public function getCurrencySymbol()
    {
        $currency = $this->_localeCurrency->getCurrency($this->getCurrencyCode());
        return $currency->getSymbol() ? $currency->getSymbol() : $currency->getShortName();
    }

    /**
     * Retrieve currency code
     *
     * @return mixed
     */
    public function getCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    /**
     * Get preferred payment method of customer
     *
     * @return string|false
     */
    public function getPreferredMethod()
    {
        $customerId = $this->_sessionQuote->getCustomerId();

        if (!$customerId) {
            return false;
        }

        $customer = $this->customerRepository->getById($customerId);
        $preferredMethod = $customer->getCustomAttribute('preferred_payment_method');

        if (!$preferredMethod) {
            return false;
        }

        return $preferredMethod->getValue();
    }

    /**
     * Is gift order
     *
     * @return bool
     */
    public function isGiftOrder()
    {
        $shippingAddressDetail = $this->getShippingAddressDetail();

        if($shippingAddressDetail['riki_type_address'] == \Riki\Customer\Model\Address\AddressType::HOME){
            return false;
        } elseif ($shippingAddressDetail['riki_type_address'] == \Riki\Customer\Model\Address\AddressType::OFFICE) {
            $customerIsAmbassador = $this->checkIsAmbassador($this->getQuote()->getCustomerId());
            if($customerIsAmbassador){
                return false;
            }
        }
        return true;
    }

    /**
     * Get shipping address detail
     *
     * @return null
     */
    public function getShippingAddressDetail()
    {
        $result = null;

        $shippingAddress = $this->getQuote()->getShippingAddress();

        $result['riki_type_address'] = $this->getShippingAddressType($shippingAddress);

        $result['region_id'] = $shippingAddress->getData('region_id');
        $result['city'] = $shippingAddress->getData('city');
        $result['street'] = $shippingAddress->getData('street');
        $result['first_name'] = $shippingAddress->getData('firstname');
        $result['last_name'] = $shippingAddress->getData('lastname');
        $result['first_name_kana'] = $shippingAddress->getData('firstnamekana');
        $result['last_name_kana'] = $shippingAddress->getData('lastnamekana');
        return $result;
    }

    /**
     * @param $shippingAddress
     * @return mixed|string
     */
    public function getShippingAddressType($shippingAddress)
    {
        $rs = \Riki\Customer\Model\Address\AddressType::HOME;
        if ( !empty( $shippingAddress->getCustomerAddressId() ) ) {
            $address = $this->getAddressById($shippingAddress->getCustomerAddressId());
            if ( !empty($address) && !empty($address->getCustomAttribute('riki_type_address')) ) {
                $addressType = $address->getCustomAttribute('riki_type_address')->getValue();
                if (!empty($addressType)) {
                    $rs = $addressType;
                }
            }
        }

        return $rs;
    }

    /**
     * @param $addressId
     * @return bool|\Magento\Customer\Api\Data\AddressInterface
     */
    public function getAddressById($addressId)
    {
        try {
            return $this->_addressRepository->getById($addressId);
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            return false;
        }
    }

    /**
     * Get billing address detail
     *
     * @return null
     */
    public function getBillingAddressDetail()
    {
        $result = null;
        $billingAddress = $this->getQuote()->getBillingAddress();
        $result['region_id'] = $billingAddress->getData('region_id');
        $result['city'] = $billingAddress->getData('city');
        $result['street'] = $billingAddress->getData('street');
        $result['first_name'] = $billingAddress->getData('firstname');
        $result['last_name'] = $billingAddress->getData('lastname');
        $result['first_name_kana'] = $billingAddress->getData('firstnamekana');
        $result['last_name_kana'] = $billingAddress->getData('lastnamekana');
        return $result;
    }

    /**
     * Check is ambassador
     *
     * @param string $customerId customer id
     *
     * @return bool
     */
    public function checkIsAmbassador($customerId)
    {
        $customer = $this->_customerModel->load($customerId);
        if ($customer) {
            $customerMemberShip = $customer->getMembership();

            if (strpos($customerMemberShip, '3') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Is B2b customer
     *
     * @param string $customerId customer id
     *
     * @return mixed
     */
    public function isB2b($customerId)
    {
        $customer = $this->_customerModel->load($customerId);
        return $customer->getData('b2b_flag') && $customer->getData('shosha_business_code');
    }

}
