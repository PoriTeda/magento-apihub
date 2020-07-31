<?php

namespace Riki\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;

class ReindexShoshaCustomer implements  ObserverInterface
{

    /**
     * @var \Riki\Customer\Model\Indexer\Processor\Shosha
     */
    protected $shoshaProcessor;

    /**
     * ReindexShoshaCustomer constructor.
     * @param \Riki\Customer\Model\Indexer\Processor\Shosha $shoshaProcessor
     */
    public function __construct(
        \Riki\Customer\Model\Indexer\Processor\Shosha $shoshaProcessor
    ) {
        $this->shoshaProcessor = $shoshaProcessor;
    }

    /**
     * Reindex simulate shosha
     *
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        $shoshaId = $observer->getShoshaId();
        $this->shoshaProcessor->reindexRow($shoshaId);
    }
}