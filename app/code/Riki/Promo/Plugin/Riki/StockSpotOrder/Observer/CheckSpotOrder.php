<?php
namespace Riki\Promo\Plugin\Riki\StockSpotOrder\Observer;

class CheckSpotOrder
{
    /**
     * Do not check allow spot order rule for free gift
     *
     * @param \Riki\StockSpotOrder\Observer\CheckSpotOrder $subject
     * @param \Magento\Framework\Event\Observer $observer
     * @return array
     */
    public function beforeExecute(
        \Riki\StockSpotOrder\Observer\CheckSpotOrder $subject,
        \Magento\Framework\Event\Observer $observer
    )
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();

        if ($product->getData(\Riki\Promo\Helper\Data::PRODUCT_GIFT_FLAG_NAME)) {
            $product->setData(\Riki\StockSpotOrder\Helper\Data::SKIP_CHECk_ALLOW_SPOT_ORDER_NAME, true);
            $observer->getEvent()->setData('product', $product);
        }

        return [$observer];
    }
}
