<?php
namespace Riki\Questionnaire\Controller\Adminhtml\Questions;

use Magento\Framework\Controller\ResultFactory;

/**
 * Class MassDelete
 * @package Riki\Questionnaire\Controller\Adminhtml\Questions
 */
class MassDelete extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $_filter;

    /**
     * @var \Riki\Questionnaire\Model\ResourceModel\Questionnaire\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * MassDelete constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Riki\Questionnaire\Model\ResourceModel\Questionnaire\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Riki\Questionnaire\Model\ResourceModel\Questionnaire\CollectionFactory $collectionFactory
    ) {
        $this->_filter = $filter;
        $this->_collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    /**
     * Delete courses
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $collection = $this->_filter->getCollection($this->_collectionFactory->create());
        $collectionSize = $collection->getSize();

        foreach ($collection as $item)
        {
            try {
                $item->delete();
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
            
        }
        $this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $collectionSize));

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Questionnaire::delete');
    }
}
