<?php
namespace Riki\Customer\Controller\Adminhtml\CategoryEnquiry;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Riki\Customer\Model\ResourceModel\CategoryEnquiry\CollectionFactory;

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
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory
    )
    {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Customer::category_delete');
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
        $collectionSize = $collection->getSize();
        foreach ($collection as $item){;
            $item->delete();
        }
        $this->messageManager->addSuccessMessage(__('A total of %1 record(s)', $collectionSize));
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}