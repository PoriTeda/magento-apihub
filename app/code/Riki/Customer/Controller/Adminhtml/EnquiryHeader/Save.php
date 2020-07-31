<?php
namespace Riki\Customer\Controller\Adminhtml\EnquiryHeader;
use Magento\Backend\App\Action;
class Save extends Action
{
    /**
     * @var \Riki\Customer\Model\EnquiryHeader\
     *
     */
    protected $_model;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Riki\Customer\Model\EnquiryHeader
     */
    protected $modelEnquiryHeader;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezoneInterface;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $stdTimezone;

    /**
     * Save constructor.
     * @param Action\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Riki\Customer\Model\EnquiryHeader $modelEnquiryHeader
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Riki\Customer\Model\EnquiryHeader $modelEnquiryHeader,
        \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone
    ) {
        parent::__construct($context);
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->authSession = $authSession;
        $this->orderFactory = $orderFactory;
        $this->customerFactory = $customerFactory;
        $this->modelEnquiryHeader = $modelEnquiryHeader;
        $this->stdTimezone = $stdTimezone;
    }
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Customer::enquiryheader_save');
    }

    /**
     * @return $this|mixed
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        $timeNow = $this->dateTime->gmtDate();

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        /**
         * Check current order id difference input order id
         */
        $currentSaleOrderId =  $this->getRequest()->getParam('current_order_id');
        $orderId            =  $this->getRequest()->getParam('order_id');
        $objOrder =null;
        if ($orderId !=null ){
            if ($currentSaleOrderId!=null && ($currentSaleOrderId != $orderId) ){
                //form sales order
                $this->messageManager->addError( __('Customer ID and Order Number are not matched'));
                $this->_getSession()->setFormData($data);
                return $this->redirectLinkEnquiry($resultRedirect);
            }else {
                //check order exit when input not empty
                $objOrder = $this->checkOrderExit($data['order_id'],$data);
                if($objOrder==null){
                    return $this->redirectLinkEnquiry($resultRedirect);
                }
            }
        }

        /**
         * check current customer id difference input customer id
         */
        $currentCustomerId = $this->getRequest()->getParam('current_customer_id');
        $customerId        = $this->getRequest()->getParam('customer_id');
        /**
         * For customer enquiry
         */
        if ($currentCustomerId !=null){
            if ($currentCustomerId != $customerId){
                $this->messageManager->addError( __('The Customer ID you input was not identical with the customer information of this page. Please reenter correct customer ID.'));
                $this->_getSession()->setFormData($data);
                return $this->redirectLinkEnquiry($resultRedirect);
            }else{
                $objCustomer = $this->checkCustomerExit($customerId,$data);
                if($objCustomer==null){
                    return $this->redirectLinkEnquiry($resultRedirect);
                }
            }
        }else{
            /**
             * For sales order,enquiry management
             */
            $objCustomer = $this->checkCustomerExit($customerId,$data);
            if($objCustomer==null){
                return $this->redirectLinkEnquiry($resultRedirect);
            }
        }





        //check relation order with customer
        /**
         * When Call Center save an enquiry,validation should be run in order to check
         * if customer ID is connected with Order number or Order number is connected with Customer ID.
         * Show error message
         * ticket 5704
         */
        if($objCustomer !=null && $objOrder !=null){
            if( ! $this->validateRelationOrderCustomer($objOrder,$data['customer_id']) ) {
                $this->messageManager->addError( __('Customer ID and Order Number are not matched'));
                $this->_getSession()->setFormData($data);
                return $this->redirectLinkEnquiry($resultRedirect);
            }
        }

        if ($data) {
            $model = $this->modelEnquiryHeader;
            //$id = $this->getRequest()->getParam('id');
            $id =  (isset($data['id'])) ? $data['id'] : null;
            if ($id !=null) {
                $model->load($id);
                $dataRecord = $model->load($id)->getData();
                $data['enquiry_updated_datetime'] = $timeNow;
                $data['increment_id'] = $dataRecord['increment_id'];
            }
            else{
                $data['enquiry_created_datetime'] = $timeNow;
                $data['enquiry_updated_datetime'] = $timeNow;
                $data['business_user_name'] = $this->authSession->getUser()->getUserName();
            }

            $model->setData($data);

            $this->_eventManager->dispatch(
                'enqueryheader_prepare_save',
                ['category' => $model, 'request' => $this->getRequest()]
            );

            try {
                if ($oEnquiry = $model->save()) {
                    /*saving increment id*/
                    if(!$id && $oEnquiry->getId()){
                        $lastId = $oEnquiry->getId();
                        $incrementId  = '5-'.sprintf('%010d', $lastId);
                        $oEnquiry->setIncrementId($incrementId);
                        $oEnquiry->save();
                    }

                    $this->messageManager->addSuccess(__('Enquiry saved'));
                    $this->_getSession()->setFormData(false);
                }else{
                    $this->messageManager->addError(__('Something went wrong while saving the enquiry'));
                }

                //redirect link when create enquiry from order
                if ($this->getRequest()->getParam('back_to_customer_profile')==1) {
                    return $resultRedirect->setPath('customer/index/edit', ['id' => $objCustomer->getId() ]);
                }

                if ($this->getRequest()->getParam('return_back_link') !=null) {
                    return $resultRedirect->setUrl($this->getRequest()->getParam('return_back_link'));
                }

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId(), '_current' => true]);
                }

                //return default
                return $resultRedirect->setPath('*/*/');

            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the enquiry'));
            }

            $this->_getSession()->setFormData($data);

            //redirect link error
            return $this->redirectLinkEnquiry($resultRedirect);
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Redirect link error when has return url
     *
     * @param $resultRedirect
     * @return mixed
     */
    public function redirectLinkEnquiry($resultRedirect){
        if ($this->getRequest()->getParam('return_back_link') !=null) {
            return $resultRedirect->setUrl($this->getRequest()->getParam('return_back_link'));
        }else{
            return $resultRedirect->setPath('*/*/edit', ['entity_id' => $this->getRequest()->getParam('id')]);
        }
    }

    /**
     * Check customer exit on system
     *
     * @param $customerId
     * @param $data
     * @return \Magento\Customer\Model\CustomerFactory
     */
    public function checkCustomerExit($customerId,$data){
        /** @var  \Magento\Customer\Model\CustomerFactory $objCustomer */
        $objCustomer = $this->customerFactory->create()->load($customerId);
        if(NULL == $objCustomer->getId()){
            $this->messageManager->addError( __('Customer Id does not exist'));
            $this->_getSession()->setFormData($data);
             return null;
        }
        return $objCustomer;
    }

    /**
     * Check order exit on system
     *
     * @param $orderId
     * @param $data
     * @return \Magento\Sales\Model\OrderFactory|null
     */
    public function checkOrderExit($orderId,$data){
        /** @var \Magento\Sales\Model\OrderFactory $objOrder */
        $objOrder = $this->orderFactory->create()->loadByIncrementId($orderId);
        if(NULL == $objOrder->getId()){
            $this->messageManager->addError( __('Order Number does not exist'));
            $this->_getSession()->setFormData($data);
            return null;
        }
        return $objOrder;
    }

    /**
     * Validate relation between order id  and customer id
     *
     * @param $objOrder
     * @param $customerId
     * @return bool
     */
    public function validateRelationOrderCustomer($objOrder,$customerId){
        if($objOrder->getCustomerId() != $customerId){
            return false;
        }
        return true;
    }
}