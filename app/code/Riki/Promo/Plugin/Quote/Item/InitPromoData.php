<?php
namespace Riki\Promo\Plugin\Quote\Item;

use Riki\Promo\Helper\Item;

class InitPromoData
{
    /**
     * @var \Riki\Promo\Helper\Item
     */
    protected $promoHelper;

    /**
     * AbstractItem constructor.
     * @param Item $promoHelper
     */
    public function __construct(
        Item $promoHelper
    ) {
        $this->promoHelper = $promoHelper;
    }

    /**
     * Do not check allow spot order for free gift
     *
     * @param \Magento\Quote\Model\Quote\Item $subject
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Catalog\Model\Product
     */
    public function afterGetProduct(
        \Magento\Quote\Model\Quote\Item $subject,
        \Magento\Catalog\Model\Product $product
    ) {
        if ($this->promoHelper->isPromoItem($subject)) {
            $product->setData(\Riki\StockSpotOrder\Helper\Data::SKIP_CHECk_ALLOW_SPOT_ORDER_NAME, true);
        }

        $product->setData(Item::PROMO_RULE_ID_KEY, $subject->getData(Item::PROMO_RULE_ID_KEY));

        return $product;
    }
}
