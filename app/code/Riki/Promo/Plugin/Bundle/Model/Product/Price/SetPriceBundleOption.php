<?php
namespace Riki\Promo\Plugin\Bundle\Model\Product\Price;

use Riki\Promo\Helper\Item as PromoItemHelper;

class SetPriceBundleOption
{
    /**
     * @param \Magento\Bundle\Model\Product\Price $subject
     * @param \Closure $proceed
     * @param $bundleProduct
     * @param $selectionProduct
     * @param $bundleQty
     * @param $selectionQty
     * @param bool $multiplyQty
     * @param bool $takeTierPrice
     * @return int|mixed
     */
    public function aroundGetSelectionFinalTotalPrice(
        \Magento\Bundle\Model\Product\Price $subject,
        \Closure $proceed,
        $bundleProduct,
        $selectionProduct,
        $bundleQty,
        $selectionQty,
        $multiplyQty = true,
        $takeTierPrice = true
    ) {
        if ($bundleProduct->getData(PromoItemHelper::PROMO_RULE_ID_KEY)) {
            return 0;
        }

        return $proceed($bundleProduct, $selectionProduct, $bundleQty, $selectionQty, $multiplyQty, $takeTierPrice);
    }
}
