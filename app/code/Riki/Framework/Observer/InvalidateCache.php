<?php
namespace Riki\Framework\Observer;

class InvalidateCache implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Riki\Framework\Helper\Cache\AppCache
     */
    protected $appCache;

    /**
     * InvalidateCache constructor.
     *
     * @param \Riki\Framework\Helper\Cache\AppCache $appCache
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     */
    public function __construct(
        \Riki\Framework\Helper\Cache\AppCache $appCache,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache
    ) {
        $this->functionCache = $functionCache;
        $this->appCache = $appCache;
    }

    /**
     * Invalidate cache
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $object = $observer->getEvent()->getObject();
        if ($object instanceof \Magento\Framework\Model\AbstractModel) {
            $cacheTag = get_class($object) . '_' . $object->getId();


            $this->functionCache->invalidateByCacheTag($cacheTag);
            $this->appCache->invalidateByCacheTag($cacheTag);
        }
    }
}