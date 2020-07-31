<?php

namespace Bluecom\Customer\Controller\Preferred;

class Save extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;
    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customer;
    /**
     * @var \Magento\Customer\Model\Data\Customer
     */
    protected $customerData;
    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    protected $customerResource;
    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerFactory
     */
    protected $customerResourceFactory;

    /**
     * Save constructor.
     * 
     * @param \Magento\Framework\App\Action\Context                 $context                 Context
     * @param \Magento\Framework\View\Result\PageFactory            $resultPageFactory       PageFactory
     * @param \Magento\Customer\Model\Session                       $customerSession         Session
     * @param \Magento\Framework\Data\Form\FormKey\Validator        $formKeyValidator        Validator
     * @param \Magento\Customer\Model\CustomerFactory               $customerFactory         CustomerFactory
     * @param \Magento\Customer\Model\Customer                      $customer                Customer
     * @param \Magento\Customer\Model\Data\Customer                 $customerData            Customer
     * @param \Magento\Customer\Model\ResourceModel\Customer        $customerResource        Customer
     * @param \Magento\Customer\Model\ResourceModel\CustomerFactory $customerResourceFactory CustomerFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Customer\Model\Data\Customer $customerData,
        \Magento\Customer\Model\ResourceModel\Customer $customerResource,
        \Magento\Customer\Model\ResourceModel\CustomerFactory $customerResourceFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->formKeyValidator = $formKeyValidator;
        $this->customerFactory = $customerFactory;
        $this->customer = $customer;
        $this->customerData = $customerData;
        $this->customerResource = $customerResource;
        $this->customerResourceFactory = $customerResourceFactory;
    }

    /**
     * Save function
     * 
     * @return $this
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $resultRedirect->setPath('*/preferred/view');
        }

        if ($this->getRequest()->isPost()) {
            $customerId = $this->customerSession->getCustomerId();
            $preferredMethod = $this->_request->getParam('preferred_payment');

            try {
                $customer = $this->customerFactory->create();
                $customerData = $customer->getDataModel();
                $customerData->setId($customerId);
                $customerData->setCustomAttribute('preferred_payment_method', $preferredMethod);
                $customer->updateData($customerData);
                $customerResource = $this->customerResourceFactory ->create();
                $customerResource->saveAttribute($customer, 'preferred_payment_method');
            } catch (\Exception $e) {
                $message = __('We can\'t save the data.')
                    . $e->getMessage()
                    . '<pre>' . $e->getTraceAsString() . '</pre>';
                $this->messageManager->addException($e, $message);
            }

            if ($this->messageManager->getMessages()->getCount() > 0) {
                $this->customerSession->setCustomerFormData($this->getRequest()->getPostValue());
                return $resultRedirect->setPath('*/preferred/view/');
            }

            $this->messageManager->addSuccess(__('You saved the referred payment method.'));
            return $resultRedirect->setPath('customer/account');
        }

        return $resultRedirect->setPath('*/preferred/view/');
    }
}