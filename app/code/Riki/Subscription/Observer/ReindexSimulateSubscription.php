<?php

namespace Riki\Subscription\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;

class ReindexSimulateSubscription implements  ObserverInterface
{
    /* @var \Riki\Subscription\Model\Indexer\ProfileSimulator\Processor */
    protected $profileSimulatorProcessor;

    /* @var \Magento\Framework\App\CacheInterface */
    protected $_cache;

    /* @var \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile */
    protected $resourceModelIndexer;

    public function __construct(
        \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile $resourceModelIndexerProfile,
        \Magento\Framework\App\CacheInterface $cacheInterface,
        \Riki\Subscription\Model\Indexer\ProfileSimulator\Processor $profileSimulatorProcessor
    ) {
        $this->resourceModelIndexer = $resourceModelIndexerProfile;
        $this->_cache = $cacheInterface;
        $this->profileSimulatorProcessor = $profileSimulatorProcessor;
    }

    /**
     * Reindex simulate subscription
     *
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        return;
    }
}