<?php

namespace Riki\AdvancedInventory\Observer;

class RevertAssignationData implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Riki\AdvancedInventory\Model\Assignation
     */
    protected $modelAssignation;
    /**
     * @var \Riki\PageCache\Indexer\CacheContext
     */
    protected $cacheContext;

    /**
     * RevertAssignationData constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\AdvancedInventory\Model\Assignation $modelAssignation
     * @param \Riki\PageCache\Indexer\CacheContext $cacheContext
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Riki\AdvancedInventory\Model\Assignation $modelAssignation,
        \Riki\PageCache\Indexer\CacheContext $cacheContext
    ) {
        $this->logger = $logger;
        $this->modelAssignation = $modelAssignation;
        $this->cacheContext = $cacheContext;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        if (!$order instanceof \Magento\Sales\Model\Order || !$order->getId()) {
            return;
        }

        if (!empty($order->getData('assignation'))) {
            $assignTo = $this->buildAssignationData($order->getData('assignation'));
            if (!empty($assignTo)) {
                $this->modelAssignation->updateStockByAssignationData(true, $assignTo, [],[], false);

                $this->cacheContext->resetEntities();
            }
        }
    }

    /**
     * Build assignation data based on json data
     *
     * @param $assignation
     * @return bool|mixed
     */
    public function buildAssignationData($assignation)
    {
        try {
            $inventory = \Zend_Json::decode($assignation);
            return $inventory;
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
        return false;
    }
}
