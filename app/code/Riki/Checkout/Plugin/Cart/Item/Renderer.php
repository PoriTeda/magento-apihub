<?php
namespace Riki\Checkout\Plugin\Cart\Item;

use Riki\SubscriptionCourse\Model\Course;

class Renderer
{
    /* Riki\SubscriptionCourse\Model\Course */
    protected $subscriptionCourseModel;

    /** @var \Magento\CatalogInventory\Api\StockRegistryInterface  */
    protected $stockRegistry;

    /**
     * Renderer constructor.
     * @param Course $courseModel
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     */
    public function __construct(
        \Riki\SubscriptionCourse\Model\Course $courseModel,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
    )
    {
        $this->subscriptionCourseModel = $courseModel;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @param \Magento\Checkout\Block\Cart\Item\Renderer $subject
     * @param \Magento\Checkout\Block\Cart\Item\Renderer $result
     * @return \Magento\Checkout\Block\Cart\Item\Renderer
     */
    public function afterSetLayout(
        \Magento\Checkout\Block\Cart\Item\Renderer $subject,
        \Magento\Checkout\Block\Cart\Item\Renderer $result
    ){
        $isHanpukaiSubscription = $this->isHanpukaiSubscription($result->getCheckoutSession()->getQuote());
        $isSubscription = $this->isSubscription($result->getCheckoutSession()->getQuote());
        $result->setData('is_hanpukai_subscription', $isHanpukaiSubscription);
        $result->setData('is_subscription', $isSubscription);
        $result->setData('total_item', $this->getTotalItem($result->getCheckoutSession()->getQuote()));

        return $result;
    }

    /**
     * @param \Magento\Checkout\Block\Cart\Item\Renderer $subject
     * @param \Magento\Checkout\Block\Cart\Item\Renderer $result
     * @return \Magento\Checkout\Block\Cart\Item\Renderer
     */
    public function afterSetItem(
        \Magento\Checkout\Block\Cart\Item\Renderer $subject,
        \Magento\Checkout\Block\Cart\Item\Renderer $result
    )
    {
        $result->setData('min_qty', $this->getMinimumCartQty($result->getItem()));
        $result->setData('max_qty', $this->getMaximumCartQty($result->getItem()));

        return $result;
    }

    /**
     * Check subscription is hanpukai subscription or not
     *
     * @param $quote
     *
     * @return bool
     */
    public function isHanpukaiSubscription($quote)
    {
        if ($quote->getData('riki_course_id') != null) {
            $courseId = $quote->getData('riki_course_id');
            $courseModel = $this->subscriptionCourseModel->load($courseId);
            if ($courseModel->getData('subscription_type') == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI){
                return true;
            }
        }
        return false;
    }

    public function getTotalItem($quote)
    {
        /* @var $quote \Magento\Quote\Model\Quote */
        return count($quote->getAllItems());
    }

    /**
     * check quote is subscription
     *
     * @param $quote
     * @return int
     */
    public function isSubscription($quote)
    {
        if ($quote->getData('riki_course_id') != null) {
            return true;
        }
        return false;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return int
     */
    public function getMinimumCartQty(\Magento\Quote\Model\Quote\Item $item)
    {
        $stockItem = $this->stockRegistry->getStockItem($item->getProductId(), $item->getStore()->getWebsiteId());
        return intval($stockItem->getMinSaleQty());
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return int
     */
    public function getMaximumCartQty(\Magento\Quote\Model\Quote\Item $item)
    {
        $stockItem = $this->stockRegistry->getStockItem($item->getProductId(), $item->getStore()->getWebsiteId());
        return intval($stockItem->getMaxSaleQty());
    }
}