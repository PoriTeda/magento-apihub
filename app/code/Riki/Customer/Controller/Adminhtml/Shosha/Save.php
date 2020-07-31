<?php
namespace Riki\Customer\Controller\Adminhtml\Shosha;
use Magento\Backend\App\Action;
class Save extends Action
{

    /**
     * @var \Riki\Customer\Model\Shosha\
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
     * @var \Riki\Customer\Model\Shosha
     */
    protected $modelShosha;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollection
     */
    protected $_customerRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;
    /**
     * Save constructor.
     * @param Action\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Riki\Customer\Model\Shosha $modelShosha
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Psr\Log\LoggerInterface $loggerInterface
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Riki\Customer\Model\Shosha $modelShosha,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Psr\Log\LoggerInterface $loggerInterface,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->authSession = $authSession;
        $this->orderFactory = $orderFactory;
        $this->customerFactory = $customerFactory;
        $this->modelShosha = $modelShosha;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_customerRepository    = $customerRepository;
        $this->_logger = $loggerInterface;
        $this->resourceConnection = $resourceConnection;

    }
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Customer::shoshacustomer_save');
    }
    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        $originDate = $this->timezone->formatDateTime($this->dateTime->gmtDate(), 2);
        $timeNow = $this->dateTime->gmtDate('Y-m-d H:i:s', $originDate);

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();


        if ($data) {
            $model = $this->modelShosha;

            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $model->load($id);
                $data['updated_at'] = $timeNow;
            }
            else{
                $data['created_at'] = $timeNow;
                $data['updated_at'] = $timeNow;
            }

            //check exist business code
            if(isset($data['shosha_business_code'])){
                $aShoshaCollections = $this->modelShosha->getCollection()->addFieldToFilter('shosha_business_code',$data['shosha_business_code']);
                $aShoshaItem = null;
                foreach ($aShoshaCollections as $aShoshaCollectionItem) {
                    $aShoshaItem = $aShoshaCollectionItem;
                }

                $isDuplicateBusinessCode = false;

                if($aShoshaItem){
                    if($id){
                        if($aShoshaItem->getData('shosha_business_code') != $model->getData('shosha_business_code')){
                            $isDuplicateBusinessCode = true;
                        }
                    }
                    else{
                        $isDuplicateBusinessCode = true;
                    }
                }

                if($isDuplicateBusinessCode){
                    $this->_getSession()->setFormData($data);
                    $this->messageManager->addError(__('This business code exists already'));
                    return $resultRedirect->setPath('*/*/edit', ['entity_id' => $this->getRequest()->getParam('id')]);
                }
                else{

                    //update old customer grid flat
                    if($id && $data['shosha_business_code'] != $model->getData('shosha_business_code')){
                        $this->updateOldCustomerGridFlat($model->getData('shosha_business_code'));
                    }

                    /*flag to check this record is not exported to bi after save data*/
                    $data['is_bi_exported'] = 0;
                    /*flag to check this record is not exported to cedyna after save data*/
                    $data['is_cedyna_exported'] = 0;

                    $model->setData($data);

                    $this->_eventManager->dispatch(
                        'shoshacustomer_prepare_save',
                        ['category' => $model, 'request' => $this->getRequest()]
                    );

                    try {

                        $model->save();

                        $this->messageManager->addSuccess(__('Shosha Business Code Saved'));
                        $this->_getSession()->setFormData(false);

                        $this->_eventManager->dispatch('shoshacustomer_after_save',['shosha_id' => $model->getId()]);

                        if ($this->getRequest()->getParam('back')) {
                            return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId(), '_current' => true]);
                        }
                        return $resultRedirect->setPath('*/*/');
                    } catch (\RuntimeException $e) {
                        $this->_getSession()->setFormData($data);
                        $this->messageManager->addError($e->getMessage());
                        return $resultRedirect->setPath('*/*/edit', ['entity_id' => $this->getRequest()->getParam('id')]);
                    }
                }

            }

        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @param $shoshaBusinessCode
     */
    public function updateOldCustomerGridFlat($shoshaBusinessCode){
        if($shoshaBusinessCode){
            $connection = $this->resourceConnection->getConnection();
            $filterCustomer = $this->_searchCriteriaBuilder->addFilter('shosha_business_code', $shoshaBusinessCode,'in');
            $aCustomer = $this->_customerRepository->getList($filterCustomer->create());
            foreach($aCustomer->getItems() as $customer){
                if($connection->tableColumnExists($connection->getTableName('customer_grid_flat'),'shosha_in_charge')){
                    $connection->update($connection->getTableName('customer_grid_flat'),[
                        'shosha_cmp' => NULL,
                        'shosha_cmp_kana' => NULL,
                        'shosha_code' => NULL,
                        'shosha_dept' => NULL,
                        'shosha_dept_kana' => NULL,
                        'shosha_first_code' => NULL,
                        'shosha_in_charge' => NULL,
                        'shosha_in_charge_kana' => NULL
                    ],'entity_id = '.$customer->getId());
                }
            }
        }
    }
}