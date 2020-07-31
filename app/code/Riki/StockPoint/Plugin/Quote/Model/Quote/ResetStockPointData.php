<?php

namespace Riki\StockPoint\Plugin\Quote\Model\Quote;

use Riki\Subscription\Helper\Order\Data as SubscriptionOrderHelper;
use Riki\Subscription\Model\ProductCart\ProductCart;

class ResetStockPointData
{
    /**
     * Remove stock point data to avoid cache
     *
     * @param \Magento\Quote\Model\Quote $subject
     * @param \Magento\Catalog\Model\Product $product
     * @param null $request
     * @param string $processMode
     * @return array
     */
    public function beforeAddProduct(
        \Magento\Quote\Model\Quote $subject,
        \Magento\Catalog\Model\Product $product,
        $request = null,
        $processMode = \Magento\Catalog\Model\Product\Type\AbstractType::PROCESS_MODE_FULL
    ) {
        $stockPointFields = [
            SubscriptionOrderHelper::SUBSCRIPTION_PROFILE_ID_FIELD_NAME,
            SubscriptionOrderHelper::IS_STOCK_POINT_PROFILE,
            ProductCart::IS_SIMULATOR_PROFILE_ITEM_KEY,
            ProductCart::PROFILE_STOCK_POINT_DISCOUNT_RATE_KEY,
            'delivery_date',
            ProductCart::IS_READY_TO_CALL_DISCOUNT_API_KEY
        ];

        foreach ($stockPointFields as $field) {
            $product->setData($field, null);
        }

        return [
            $product,
            $request,
            $processMode
        ];
    }
}
