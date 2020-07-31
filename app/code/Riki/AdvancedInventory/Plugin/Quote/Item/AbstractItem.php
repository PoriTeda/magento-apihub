<?php
namespace Riki\AdvancedInventory\Plugin\Quote\Item;

class AbstractItem
{

    /**
     * Do not check allow spot order for generate OOS order
     *
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $subject
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Catalog\Model\Product
     */
    public function afterGetProduct(
        \Magento\Quote\Model\Quote\Item\AbstractItem $subject,
        \Magento\Catalog\Model\Product $product
    )
    {
        $quote = $subject->getQuote();

        if (
            $product->getIsOosProduct() ||
            ($quote && $quote->getIsOosOrder())
        ) {
            $product->setData(\Riki\StockSpotOrder\Helper\Data::SKIP_CHECk_ALLOW_SPOT_ORDER_NAME, true);
        }

        return $product;
    }
}
