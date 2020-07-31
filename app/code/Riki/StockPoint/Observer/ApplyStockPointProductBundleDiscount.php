<?php
namespace Riki\StockPoint\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Riki\Subscription\Helper\Order\Data as SubscriptionHelperData;

class ApplyStockPointProductBundleDiscount extends ApplyStockPointDiscount
{
    /**
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();

        $price = $observer->getEvent()->getPrice();

        $selectionProduct = $observer->getEvent()->getSelectionProduct();

        if ($product->getData('final_price') === null) {
            $product->setData('stock_point_applied_discount_amount', 0);
        }

        if ($profileId = $product->getData(SubscriptionHelperData::SUBSCRIPTION_PROFILE_ID_FIELD_NAME) &&
            $product->getData(SubscriptionHelperData::IS_STOCK_POINT_PROFILE)
        ) {
            $discountRate = $this->getStockPointDiscountRate($product);
            $discountAmount = floor($price * ((int)$discountRate / 100));
            $product->setData(
                'stock_point_applied_discount_amount',
                (int)($product->getData('stock_point_applied_discount_amount'))
                + ($discountAmount*$selectionProduct->getSelectionQty())
            );
            $price -= $discountAmount;
        }

        $selectionProduct->setNewSelectionPriceValue($price);

        return $this;
    }
}
