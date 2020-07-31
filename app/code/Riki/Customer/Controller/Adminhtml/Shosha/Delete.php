<?php
namespace Riki\Customer\Controller\Adminhtml\Shosha;
use Magento\Backend\App\Action;

class Delete extends Action{

    protected $_model;
    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollection
     */
    protected $_customerRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @param Action\Context $context
     * @param \Riki\Customer\Model\Shosha $model
     */
    public function __construct(
        Action\Context $context,
        \Riki\Customer\Model\Shosha $model,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
        $this->_model = $model;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_customerRepository    = $customerRepository;
        parent::__construct($context);
    }
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Customer::shoshacustomer_delete');
    }
    /*
     * Delete action
     * @return \Magento\Framework\Controller\ResultInterface
     *
     * */
    public function execute()
    {
        // TODO: Implement execute() method.
        $id = $this->getRequest()->getParam('id');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if($id){
            try{
                $model = $this->_model;
                $model->load($id);
                /** @var \Magento\Framework\Api\SearchCriteriaBuilder $filter */
                $filter = $this->_searchCriteriaBuilder->addFilter('shosha_business_code', $model->getData('shosha_business_code'));
                $customerBusinessCode = $this->_customerRepository->getList($filter->create());

                if ($customerBusinessCode->getTotalCount()) {
                    $this->messageManager->addError(__('This business code is used by customers, you can\'t delete this business code'));
                }
                else{
                    $model->delete();
                    $this->messageManager->addSuccess(__('Shosha business code deleted'));
                }

                return $resultRedirect->setPath('*/*/');
            }catch (\Exception $e){
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*.*/edit',['id' => $id]);
            }
        }
        $this->messageManager->addError(__('Shosha business code does not exist'));
        return $resultRedirect->setPath('*/*/');
    }
}