<?php
namespace Riki\StockPoint\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Riki\Subscription\Helper\Order\Data as SubscriptionHelperData;
use Riki\Subscription\Model\ProductCart\ProductCart;

class InitProfileItemStockPointData implements ObserverInterface
{

    /**
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $observer->getEvent()->getProfile();

        /** @var \Riki\Subscription\Model\ProductCart\ProductCart $profileItem */
        $profileItem = $observer->getEvent()->getProfileItem();

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();

        $stockPointFields = [
            ProductCart::PROFILE_STOCK_POINT_DISCOUNT_RATE_KEY  =>
                $profileItem->getData('stock_point_discount_rate'),
            SubscriptionHelperData::SUBSCRIPTION_PROFILE_ID_FIELD_NAME  =>
                $profile->getProfileId(),
            SubscriptionHelperData::IS_STOCK_POINT_PROFILE  =>
                $profile->getData(SubscriptionHelperData::PROFILE_STOCK_POINT_BUCKET_ID) != false,
            ProductCart::IS_SIMULATOR_PROFILE_ITEM_KEY  =>
                true
        ];

        foreach ($stockPointFields as $field => $value) {
            $product->setData($field, null);
        }

        if ($profile->getData(SubscriptionHelperData::PROFILE_STOCK_POINT_BUCKET_ID)) {
            foreach ($stockPointFields as $field => $value) {
                $product->setData($field, $value);
            }
        }

        return $this;
    }
}
