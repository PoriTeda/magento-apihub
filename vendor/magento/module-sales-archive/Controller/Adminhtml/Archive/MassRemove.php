<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Controller\Adminhtml\Archive;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

/**
 * Class MassRemove
 */
class MassRemove extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_SalesArchive::remove';

    /**
     * @var \Magento\SalesArchive\Model\Archive
     */
    protected $_archiveModel;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param Filter $filter
     * @param \Magento\SalesArchive\Model\Archive $archiveModel
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Filter $filter,
        \Magento\SalesArchive\Model\Archive $archiveModel,
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->_archiveModel = $archiveModel;
        parent::__construct($context, $filter);
    }

    /**
     * Add selected orders to archive
     *
     * @param AbstractCollection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function massAction(AbstractCollection $collection)
    {
        $archivedIds = $this->_archiveModel->removeOrdersFromArchiveById($collection->getAllIds());
        $archivedCount = count($archivedIds);

        if ($archivedCount > 0) {
            $this->messageManager->addSuccess(__('We removed %1 order(s) from the archive.', $archivedCount));
        } else {
            // selected orders is not available for removing from archive
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('sales/archive/orders');

        return $resultRedirect;
    }
}
