<?php

namespace Riki\SubscriptionMachine\Controller\Adminhtml\Customer;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Riki\SubscriptionMachine\Model\ResourceModel\MachineCustomer\CollectionFactory;

/**
 * Class MassDelete
 */
class MassDelete extends \Riki\SubscriptionMachine\Controller\Adminhtml\Action
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
        foreach ($collection as $machineCustomer) {
            if (!$machineCustomer->canDelete()) {
                $inUsed[] = $machineCustomer->getId();
                continue;
            }
            try {
                $machineCustomer->delete();
                $success++;
            } catch (\Exception $e) {
                $this->logger->error($e);
                $this->messageManager->addError(__('An error has occurred.'));
            }
        }
        if (sizeof($inUsed)) {
            $this->messageManager->addError(__('Machine customer has already used in: %1', implode(',', $inUsed)));
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
        return $this->_authorization->isAllowed('Riki_SubscriptionMachine::machine_customer_delete');
    }
}
