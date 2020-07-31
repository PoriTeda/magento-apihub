<?php
namespace Riki\StockPoint\Plugin\Bundle\Model\Product\Price;

use Riki\Subscription\Helper\Order\Data as SubscriptionOrderHelper;
use Riki\Subscription\Model\ProductCart\ProductCart;

class InitStockPointDataBundleItem
{
    /**
     * copy stock point data from bundle to children
     *
     * @param \Magento\Bundle\Model\Product\Price $subject
     * @param $bundleProduct
     * @param $selectionProduct
     * @param $bundleQty
     * @param $selectionQty
     * @param bool $multiplyQty
     * @param bool $takeTierPrice
     * @return array
     */
    public function beforeGetSelectionFinalTotalPrice(
        \Magento\Bundle\Model\Product\Price $subject,
        $bundleProduct,
        $selectionProduct,
        $bundleQty,
        $selectionQty,
        $multiplyQty = true,
        $takeTierPrice = true
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
            $selectionProduct->setData($field, null);
        }

        if ($profileId = $bundleProduct->getData(SubscriptionOrderHelper::SUBSCRIPTION_PROFILE_ID_FIELD_NAME)
            && $isStockPointProfile = $bundleProduct->getData(SubscriptionOrderHelper::IS_STOCK_POINT_PROFILE)
        ) {
            foreach ($stockPointFields as $field) {
                $selectionProduct->setData($field, $bundleProduct->getData($field));
            }
        }

        return [
            $bundleProduct,
            $selectionProduct,
            $bundleQty,
            $selectionQty,
            $multiplyQty,
            $takeTierPrice
        ];
    }
}
