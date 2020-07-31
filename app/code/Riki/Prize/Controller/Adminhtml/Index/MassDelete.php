<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Prize\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Riki\Prize\Model\ResourceModel\Prize\CollectionFactory;

/**
 * Class MassDelete
 */
class MassDelete extends \Riki\Prize\Controller\Adminhtml\Action
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(Context $context, Filter $filter, CollectionFactory $collectionFactory, \Psr\Log\LoggerInterface $logger)
    {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();
        $inUsed = [];
        $success = 0;
        /** @var \Riki\Prize\Model\Prize $prize */
        foreach ($collection as $prize) {
            if (!$prize->canDelete()) {
                $inUsed[] = $prize->getId();
                continue;
            }
            try {
                $prize->delete();
                $success++;
            } catch (\Exception $e) {
                $this->logger->error($e);
                $this->messageManager->addError(__('An error has occurred.'));
            }
        }
        if (sizeof($inUsed)) {
            $this->messageManager->addError(__('Prize has already used in: %1', implode(',', $inUsed)));
        }
        if ($success) {
            $this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $success));
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
