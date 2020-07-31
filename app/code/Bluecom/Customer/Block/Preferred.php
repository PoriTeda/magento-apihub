<?php

namespace Bluecom\Customer\Block;

class Preferred extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Payment\Model\Config
     */
    protected $paymentConfig;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Preferred constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context         Context
     * @param \Magento\Payment\Model\Config                    $paymentConfig   Config
     * @param \Magento\Customer\Model\Session                  $customerSession Session
     * @param array                                            $data            array
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->paymentConfig = $paymentConfig;
        $this->customerSession = $customerSession;
    }

    /**
     * Get list payment methods active
     *
     * @return array
     */
    public function getListPaymentActive()
    {
        $payments = $this->paymentConfig->getActiveMethods();
        $methods = [];
        //get customer group
        $customer = $this->customerSession->getCustomer();
        $customerGroup = $customer->getGroupId();
        $b2bFlag = $customer->getData('b2b_flag') && $customer->getData('shosha_business_code');
        foreach ($payments as $paymentCode => $paymentModel) {
            if ($paymentCode == 'free') {
                // Not Show Zero Subtotal Checkout
                continue;
            }
            if (!isset($b2bFlag) && $paymentCode == \Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE) {
                continue;
            }
            $path = 'payment/' . $paymentCode . '/customergroup';
            //get config customer group allow with payment
            $paymentAllowGroup = $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $paymentAllowGroup = explode(',', $paymentAllowGroup);
            //in array allow customer group
            if (in_array($customerGroup, $paymentAllowGroup)) {
                $paymentTitle = $this->_scopeConfig->getValue('payment/' . $paymentCode . '/title');
                $methods[$paymentCode] = [
                    'label' => $paymentTitle,
                    'value' => $paymentCode
                ];
            }
        }
        return $methods;
    }

    /**
     * Get preferred payment method
     * 
     * @return mixed
     */
    public function getPreferredPaymentMethod()
    {
        $customer = $this->customerSession->getCustomer();
        return $customer->getPreferredPaymentMethod();
    }

}