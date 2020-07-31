<?php
namespace Riki\CatalogRule\Controller\Adminhtml\Wbs;

class MassDelete extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $filter;
    /**
     * @var \Riki\CatalogRule\Model\ResourceModel\WbsConversion\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * MassDelete constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Riki\CatalogRule\Model\ResourceModel\WbsConversion\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Riki\CatalogRule\Model\ResourceModel\WbsConversion\CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Delete courses
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $collection = $this->filter->getCollection(
            $this->collectionFactory->create()
        );

        $success = 0;
        $failed = 0;

        foreach ($collection as $item) {
            try {
                $item->delete();
                $success++;
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        if ($success) {
            $this->messageManager->addSuccess(
                __('A total of %1 record(s) have been deleted.', $success)
            );
        }

        if ($failed) {
            $this->messageManager->addError(
                __('A total of %1 record(s) could not be deleted.', $success)
            );
        }

        $resultRedirect = $this->resultFactory->create(
            \Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT
        );

        return $resultRedirect->setPath('*/*/');
    }
}
