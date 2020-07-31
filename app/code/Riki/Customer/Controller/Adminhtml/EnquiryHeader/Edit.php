<?php
namespace Riki\Customer\Controller\Adminhtml\EnquiryHeader;

use Magento\Backend\App\Action;

class Edit extends Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var \Riki\Customer\Model\EnquiryHeader
     */
    protected $_model;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * Edit constructor.
     * @param Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Customer\Model\EnquiryHeader $model
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Riki\Customer\Model\EnquiryHeader $model,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
    ) {

        $this->_resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->_model = $model;
        $this->orderFactory = $orderFactory;
        $this->customerRepository = $customerRepositoryInterface;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Customer::enquiryheader_save');
    }


    /**
     * Init actions
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('Riki_Customer::enqueryheader')
            ->addBreadcrumb(__('Enquiry'), __('Enquiry'))
            ->addBreadcrumb(__('Manage Enquiry'), __('Manage Enquiry'));
        return $resultPage;
    }

    /**
     * Process data
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        $model = $this->_model;

        // If you have got an id, it's edition
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This enquiry does not exist.'));
                /** \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $data = $this->_getSession()->getFormData(true);

        $customerId = $this->getRequest()->getParam('customerid');
        if($customerId){
            $customerDetail = $this->getCustomerById($customerId);
            $data['consumer_name'] = $customerDetail->getLastname() .' '. $customerDetail->getFirstName();
            $data['back_to_customer_profile'] = 1;
        }

        $orderId = $this->getRequest()->getParam('orderid');
        if($orderId){
            /**@var \Magento\Customer\Api\CustomerRepositoryInterface $customerDetail */
            $customerDetail = $this->getCustomerByOrderId($orderId);
            $data['order_id']      = $orderId;
            $data['customer_id']   = $customerDetail->getId();
            $data['consumer_name'] = $customerDetail->getLastname() .' '. $customerDetail->getFirstName();
            //$data['back_to_customer_profile'] = 1;
        }else{
            //set data consumer name
            /**@var \Magento\Customer\Api\CustomerRepositoryInterface $customerDetail */
            if($model->getCustomerId() !=null){
                $customerDetail  = $this->getCustomerById($model->getCustomerId());
                if($customerDetail){
                    $consumerName = $customerDetail->getLastname() .' '. $customerDetail->getFirstName();
                    $model->setData('consumer_name',$consumerName);
                }
            }
        }

        $customerId = $this->getRequest()->getParam('customerid');
        if($customerId){
            $data['customer_id'] = $customerId;
        }

        if (!empty($data)) {
            $model->setData($data);
        }

        $resultPage = $this->_initAction();
        $this->_coreRegistry->register('enqueryheader', $model);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage->addBreadcrumb(
            $id ? __('Edit Enquiry') : __('New Enquiry'),
            $id ? __('Edit Enquiry') : __('New Enquiry')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Enquiry'));
        $resultPage->getConfig()->getTitle()
            ->prepend($model->getId() ? __('Edit Enquiry') : __('New Enquiry'));

        return $resultPage;
    }

    /**
     * Get customer id by order id
     *
     * @param $orderId
     *
     * @return \Magento\Customer\Api\CustomerRepositoryInterface
     */
     public function getCustomerByOrderId($orderId){
         $orderDetail = $this->orderFactory->create()->loadByIncrementId($orderId);
         /**@var \Magento\Customer\Api\CustomerRepositoryInterface $customer */
         $customer = $this->getCustomerById($orderDetail->getCustomerId());
         return $customer;
     }

    /**
     * Get customer ID
     *
     * @param $customerId
     *
     * @return \Magento\Customer\Api\CustomerRepositoryInterface
     */
     public function getCustomerById($customerId){
         /**@var \Magento\Customer\Api\CustomerRepositoryInterface $customer */
         $customer = $this->customerRepository->getById($customerId);
         return $customer;
     }
}