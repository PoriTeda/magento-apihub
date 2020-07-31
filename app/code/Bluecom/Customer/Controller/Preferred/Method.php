<?php

namespace Bluecom\Customer\Controller\Preferred;

use Magento\Framework\App\Action\Context;

class Method extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session|\Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJson;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Method constructor.
     *
     * @param Context                                            $context         Context
     * @param \Magento\Checkout\Model\Session                    $checkoutSession checkoutSession
     * @param \Magento\Customer\Model\Session                    $customerSession customerSession
     * @param \Magento\Framework\Controller\Result\JsonFactory   $resultJson      JsonFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig     ScopeConfigInterface
     */
    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJson,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->resultJson = $resultJson;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Ajax return customer preferred payment method
     *
     * @return mixed
     */
    public function execute()
    {
        // check customer login
        $customer = $this->customerSession->getCustomer();
        if (!$customer->getId() || !$customer->getPreferredPaymentMethod() || $customer->getPreferredPaymentMethod() == 'free') {
            $method = false;
        } else {
            $method = $customer->getPreferredPaymentMethod();
            $paymentTitle = $this->scopeConfig->getValue('payment/' . $method . '/title');
        }

        if ($method && $method != 'free') {
            $json = ['method'=> $method , 'title'=> $paymentTitle, 'po_number'=> null, 'additional_data'=>null];
        } else {
            $customer = $this->customerSession->getCustomer();
            $b2bFlag = $customer->getData('b2b_flag') && $customer->getData('shosha_business_code');

            if (isset($b2bFlag) && $b2bFlag == 1) {
                $paymentCode = \Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE;
                $paymentTitle = $this->scopeConfig->getValue('payment/' . $paymentCode . '/title');
                $json = ['method'=> $paymentCode, 'title'=> $paymentTitle, 'po_number'=> null, 'additional_data'=>null];
            } else {
                $json = ['method'=> '', 'title'=> '', 'po_number'=> null, 'additional_data'=>null];
            }
        }
        $resultJson = $this->resultJson->create();
        return $resultJson->setData($json);
    }
}