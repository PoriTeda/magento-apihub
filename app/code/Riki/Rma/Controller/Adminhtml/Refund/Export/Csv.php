<?php
namespace Riki\Rma\Controller\Adminhtml\Refund\Export;

class Csv extends \Riki\Rma\Controller\Adminhtml\Refund
{
    const ADMIN_RESOURCE = 'Riki_Rma::rma_refund_actions_export_csv';

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * Csv constructor.
     *
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Riki\Rma\Model\RefundManagement $refundManagement
     * @param \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Riki\Rma\Model\RefundManagement $refundManagement,
        \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->fileFactory = $fileFactory;
        parent::__construct($refundManagement, $rmaRepository, $searchHelper, $logger, $registry, $context);
    }


    /**
     * Export grid to CSV format
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $fileName = 'refund.csv';
        /** @var \Magento\Backend\Block\Widget\Grid\ExportInterface $exportBlock  */
        $exportBlock = $this->_view->getLayout()->getChildBlock('admin.block.rma.refund.grid.container', 'grid');

        if ($entityIds = $this->getRequest()->getParam('internal_entity_ids')) {
            $collection = $exportBlock->getPreparedCollection();
            if ($collection) {
                $collection->addFieldToFilter('entity_id', ['in' => explode(',', $entityIds)]);
            }
        }

        return $this->fileFactory->create(
            $fileName,
            $exportBlock->getCsvFile(),
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
    }
}
