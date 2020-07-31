<?php
namespace Riki\StockPoint\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class UpdateProfileDiscountRate implements ObserverInterface
{
    /**
     * @var \Riki\AdvancedInventory\Helper\OutOfStock
     */
    private $outOfStockHelper;

    /**
     * UpdateProfileDiscountRate constructor.
     * @param \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper
     */
    public function __construct(
        \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper
    ) {
        $this->outOfStockHelper = $outOfStockHelper;
    }

    /**
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $observer->getEvent()->getProfile();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        $profileItems = $profile->getProductCart();

        /** @var \Riki\Subscription\Model\ProductCart\ProductCart $profileItem */
        foreach ($profileItems as $profileItem) {
            $newDiscount = $this->getNewDiscountRate($profileItem->getProductId(), $order);

            if ($newDiscount !== null) {
                $profileItem->setData('stock_point_discount_rate', $newDiscount);
                $profileItem->save();
            }
        }
    }

    /**
     * @param $productId
     * @param \Magento\Sales\Model\Order $order
     * @return int|mixed
     */
    private function getNewDiscountRate($productId, \Magento\Sales\Model\Order $order)
    {
        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($order->getItemsCollection() as $item) {
            if ($item->getProductId() == $productId) {
                return $item->getData('stock_point_applied_discount_rate');
            }
        }

        $oosOrders = $this->outOfStockHelper->getOutOfStocksByOrder($order);

        if ($oosOrders) {
            /** @var \Riki\AdvancedInventory\Model\OutOfStock $oosOrder */
            foreach ($oosOrders as $oosOrder) {
                if ($oosOrder->getProductId() != $productId || $oosOrder->getPrizeId()) {
                    continue;
                }

                $quoteItem = $oosOrder->getQuoteItem();

                if ($quoteItem) {
                    return $quoteItem->getData('stock_point_applied_discount_rate');
                }
            }
        }

        return null;
    }
}
