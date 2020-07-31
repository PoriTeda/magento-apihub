<?php
namespace Riki\Chirashi\Plugin\Subscription\Helper;

class OrderData
{
    /**
     * @param \Amasty\Promo\Helper\Cart $subject
     * @param \Closure $proceed
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return bool
     */
    public function aroundCanPutToOutOfStockMail(
        \Amasty\Promo\Helper\Cart $subject,
        \Closure $proceed,
        \Magento\Catalog\Api\Data\ProductInterface $product
    ) {

        $result = $proceed($product);

        if($result){
            if($product->getChirashi())
                return false;
        }

        return $result;
    }
}
