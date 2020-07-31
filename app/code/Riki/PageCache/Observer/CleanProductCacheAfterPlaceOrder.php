<?php

namespace Riki\PageCache\Observer;

class CleanProductCacheAfterPlaceOrder implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;
    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cacheManager;
    /**
     * @var \Riki\PageCache\Indexer\CacheContext
     */
    protected $cacheContext;

    /**
     * CleanProductCacheAfterPlaceOrder constructor.
     *
     * @param \Magento\Framework\Event\ManagerInterface $eventManage
     * @param \Riki\PageCache\Indexer\CacheContext $cacheContext
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManage,
        \Magento\Framework\App\CacheInterface $cacheManager,
        \Riki\PageCache\Indexer\CacheContext $cacheContext
    ) {
        $this->eventManager = $eventManage;
        $this->cacheManager = $cacheManager;
        $this->cacheContext = $cacheContext;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        if (!$order instanceof \Magento\Sales\Model\Order || !$order->getId()) {
            return;
        }

        if ($this->cacheContext->getIdentities()) {
            foreach ($this->cacheContext->getIdentities() as $identity) {
                $this->cacheManager->clean($identity);
            }

            $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->cacheContext]);
            $this->cacheContext->resetEntities();
        }
    }
}
