<?php
namespace Riki\StockPoint\Plugin\Quote\Item\AbstractItem;

use Riki\Subscription\Helper\Order\Data as SubscriptionHelperData;
use Riki\Subscription\Model\ProductCart\ProductCart;

class InitStockPointData
{
    /**
     * Set profile id to product data
     *
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $subject
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Catalog\Model\Product
     */
    public function afterGetProduct(
        \Magento\Quote\Model\Quote\Item\AbstractItem $subject,
        \Magento\Catalog\Model\Product $product
    ) {
        $quote = $subject->getQuote();

        if ($quote) {
            $profileId = $quote->getData(SubscriptionHelperData::SUBSCRIPTION_PROFILE_ID_FIELD_NAME);
            $isStockPointProfile = $quote->getData(SubscriptionHelperData::IS_STOCK_POINT_PROFILE);
            $isSimulator = $quote->getData(SubscriptionHelperData::IS_SIMULATOR_PROFILE_NAME);
        } else {
            $profileId = null;
            $isStockPointProfile = null;
            $isSimulator = null;
        }

        //original_delivery_date for OOS item
        $deliveryDate = $subject->getDeliveryDate()?
            $subject->getDeliveryDate() : $subject->getData('original_delivery_date');

        $stockPointFields = [
            SubscriptionHelperData::SUBSCRIPTION_PROFILE_ID_FIELD_NAME  =>
                $profileId,
            SubscriptionHelperData::IS_STOCK_POINT_PROFILE  =>
                $isStockPointProfile,
            ProductCart::IS_SIMULATOR_PROFILE_ITEM_KEY  =>
                $isSimulator,
            ProductCart::PROFILE_STOCK_POINT_DISCOUNT_RATE_KEY  =>
                $subject->getData('stock_point_discount_rate'),
            'delivery_date' =>
                $deliveryDate,
            ProductCart::IS_READY_TO_CALL_DISCOUNT_API_KEY  =>
                $this->isReadyToCallApi($subject)
        ];

        foreach ($stockPointFields as $field => $value) {
            $product->setData($field, null);
        }

        if ($quote && $profileId) {
            foreach ($stockPointFields as $field => $value) {
                $product->setData($field, $value);
            }
        }

        return $product;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $subject
     * @return bool
     */
    private function isReadyToCallApi(\Magento\Quote\Model\Quote\Item\AbstractItem $subject)
    {
        if ($subject->getId() ||
            $subject->getOosUniqKey()
        ) {
            return true;
        }

        return false;
    }
}
