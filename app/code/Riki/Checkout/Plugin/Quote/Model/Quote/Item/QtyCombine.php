<?php
namespace Riki\Checkout\Plugin\Quote\Model\Quote\Item;

class QtyCombine
{
    /**
     * @var \Magento\Framework\App\Cache\Type\FrontendPool
     */
    protected $cachePool;

    /**
     * @var \Magento\Framework\Cache\FrontendInterface
     */
    protected $cacheInstance;

    /**
     * @var \Magento\Quote\Model\Quote\ItemFactory
     */
    protected $quoteItemFactory;

    /**
     * QtyCombine constructor.
     *
     * @param \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory
     * @param \Magento\Framework\App\Cache\Type\FrontendPool $cachePool
     */
    public function __construct(
        \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory,
        \Magento\Framework\App\Cache\Type\FrontendPool $cachePool
    ) {
        $this->quoteItemFactory = $quoteItemFactory;
        $this->cachePool = $cachePool;
        $this->cacheInstance = $cachePool->get(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
    }

    /**
     * Correct qty of combine item
     *
     * @param \Magento\Quote\Model\Quote\Item $subject
     *
     * @return array
     */
    public function beforeBeforeSave(
        \Magento\Quote\Model\Quote\Item $subject
    ) {
        if ($subject->getId() && !$subject->getData('qty_combine')) {
            $cacheKey = $cacheKey = 'qty_combine_' . $subject->getId();
            $cacheData = $this->cacheInstance->load($cacheKey);
            if ($cacheData) {
                $cacheData = \Zend_Json::decode($cacheData);
                if (isset($cacheData['updated_at'])
                    && $cacheData['updated_at'] > $subject->getData('updated_at')
                ) {
                    if (isset($cacheData['qty'])) {
                        $subject->setData('qty', $cacheData['qty']);
                    }
                }
            }
        }

        return [];
    }

    /**
     * Correct qty for combine
     *
     * @param \Magento\Quote\Model\Quote\Item $subject
     *
     * @return array
     */
    public function beforeAfterCommitCallback(\Magento\Quote\Model\Quote\Item $subject)
    {
        if ($subject->getData('qty_combine')) {
            $quoteItem = $this->quoteItemFactory->create()->load($subject->getId());
            $cacheKey = 'qty_combine_' . $quoteItem->getId();
            $cacheData = [
                'updated_at' => $quoteItem->getData('updated_at'),
                'qty' => $subject->getData('qty_combine')
            ];
            $this->cacheInstance->save(\Zend_Json::encode($cacheData), $cacheKey);
        }
        return [];
    }

    /**
     * Clean cache qty_combine
     *
     * @param array $ids
     */
    public function cleanCacheByIds($ids = [])
    {
        foreach ($ids as $id) {
            $cacheKey = 'qty_combine_' . $id;
            if ($this->cacheInstance->load($cacheKey)) {
                $this->cacheInstance->remove($cacheKey);
            }
        }
    }

    /**
     * Clean cache if item was deleted
     *
     *
     * @param \Magento\Quote\Model\Quote\Item $subject
     *
     * @return array
     */
    public function beforeAfterDelete(\Magento\Quote\Model\Quote\Item $subject)
    {
        $this->cleanCacheByIds([$subject->getId()]);
        return [];
    }
}