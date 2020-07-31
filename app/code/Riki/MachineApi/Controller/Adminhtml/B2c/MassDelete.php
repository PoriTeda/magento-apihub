<?php

namespace Riki\MachineApi\Controller\Adminhtml\B2c;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Riki\MachineApi\Model\ResourceModel\B2CMachineSkus\CollectionFactory;

/**
 * Class MassDelete
 */
class MassDelete extends \Riki\MachineApi\Controller\Adminhtml\Action
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
     * MassDelete constructor.
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
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
        $inUsed = [];
        $success = 0;
        foreach ($collection as $machineType) {
            if (!$machineType->canDelete()) {
                $inUsed[] = $machineType->getId();
                continue;
            }
            try {
                $machineType->delete();
                $success++;
            } catch (\Exception $e) {
                $this->logger->error($e);
                $this->messageManager->addError(__('An error has occurred.'));
            }
        }
        if ($inUsed) {
            $this->messageManager->addError(__('Machine Type %1 cannot be delete because it is being used.', implode(',', $inUsed)));
        }
        if ($success) {
            $this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $success));
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Subscription::machine_b2c_skus_delete');
    }
}
