<?php
namespace Riki\Customer\Controller\Adminhtml\Shosha;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Riki\Customer\Model\ResourceModel\Shosha\CollectionFactory;

class MassDelete extends \Magento\Backend\App\Action
{
    /*
     * @var Filter
     * */
    protected $filter;
    /*
     * @var CollectionFactory
     * */
    protected $collectionFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollection
     */
    protected $_customerRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * MassDelete constructor.
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_customerRepository    = $customerRepository;
        parent::__construct($context);
    }
    /**
     * Execute action
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     *
     * */
    public function execute()
    {
        // TODO: Implement execute() method.
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $iDeleted = 0;
        foreach ($collection as $item){
            try {
                /** @var \Magento\Framework\Api\SearchCriteriaBuilder $filter */
                $filter = $this->_searchCriteriaBuilder->addFilter('shosha_business_code', $item->getData('shosha_business_code'));
                $customerBusinessCode = $this->_customerRepository->getList($filter->create());

                if ($customerBusinessCode->getTotalCount()) {
                    $this->messageManager->addError(__('This business code %1 is used by customers, you can\'t delete this business code',$item->getData('shosha_business_code')));
                }
                else{
                    $item->delete();
                    $iDeleted++;
                    $this->messageManager->addSuccess(__('Shosha business code deleted'));
                }

            } catch (\Exception $e) {
                $this->messageManager->addError(__('An error has occurred.'));
            }
        }
        if($iDeleted){
            $this->messageManager->addSuccessMessage(__('A total of %1 record(s)', $iDeleted));
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Customer::shoshacustomer_delete');
    }

}