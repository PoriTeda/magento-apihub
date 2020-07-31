<?php
namespace Bluecom\Customer\Model\Customer\Attribute\Source;

class Preferred extends \Magento\Eav\Model\Entity\Attribute\Source\Table
{

    /**
     * @var \Magento\Payment\Model\Config
     */
    protected $paymentConfig;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * Preferred constructor.
     * 
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory CollectionFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory            $attrOptionFactory           OptionFactory
     * @param \Magento\Payment\Model\Config                                              $paymentConfig               Config
     * @param \Magento\Framework\App\Config\ScopeConfigInterface                         $scopeConfigInterface        ScopeConfigInterface
     * @param \Magento\Backend\Model\Session                                             $session                     Session
     */
    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory $attrOptionFactory,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Backend\Model\Session $session
    ) {
        parent::__construct($attrOptionCollectionFactory, $attrOptionFactory);
        $this->paymentConfig = $paymentConfig;
        $this->scopeConfig = $scopeConfigInterface;
        $this->session = $session;
    }

    /**
     * Get all options
     * 
     * @return array
     */
    public function getAllOptions($withEmpty = true, $defaultValues = false)
    {
        $payments = $this->paymentConfig->getActiveMethods();
        $methods = [];

        $methods[] = [
            'label' => 'Select your preferred payment method.',
            'value' => ''
        ];
        $customerData = $this->session->getCustomerData();
        //get customer group
        if (isset($customerData['customer_id']) && isset($customerData['account'])) {
            if (isset($customerData['account']['group_id'])) {
                $customerGroup = $customerData['account']['group_id'];
            }
            if (isset($customerData['account']['b2b_flag'])) {
                $b2bFlag = $customerData['account']['b2b_flag'] && isset($customerData['account']['shosha_business_code']) && $customerData['account']['shosha_business_code'];
            }
        }

        foreach ($payments as $paymentCode => $paymentModel) {
            if ($paymentCode == 'free') {
                // Not Show Zero Subtotal Checkout
                continue;
            }
            if ((!isset($b2bFlag) || $b2bFlag == 0) && $paymentCode == \Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE) {
                continue;
            }

            $path = 'payment/' . $paymentCode . '/customergroup';
            //get config customer group allow with payment
            $paymentAllowGroup = $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $paymentAllowGroup = explode(',', $paymentAllowGroup);
            if (isset($customerGroup)) {
                if (in_array($customerGroup, $paymentAllowGroup)) {
                    $paymentTitle = $this->scopeConfig->getValue('payment/' . $paymentCode . '/title');
                    $methods[] = [
                        'label' => $paymentTitle,
                        'value' => $paymentCode
                    ];
                }
            } else {
                $paymentTitle = $this->scopeConfig->getValue('payment/' . $paymentCode . '/title');
                $methods[] = [
                    'label' => $paymentTitle,
                    'value' => $paymentCode
                ];
            }

        }

        if (!$this->_options) {
            $this->_options = $methods;
        }

        return $this->_options;
    }

    /**
     * Get Option text
     *
     * @param int|string $value Value
     *
     * @return bool
     */
    public function getOptionText($value)
    {
        if (!$this->_options) {
            $this->_options = $this->getAllOptions();
        }
        foreach ($this->_options as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }
}
