<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Gift wrapping adminhtml sales order create items
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Riki\GiftWrapping\Block\Adminhtml\Order\Create;

class Link extends \Magento\GiftWrapping\Block\Adminhtml\Sales\Order\Create\Link
{

    /**
     * Check ability to display gift wrapping for order items
     *
     * @return bool
     */
    public function canDisplayGiftWrappingForItem()
    {
        $product = $this->getItem()->getProduct();
        $allowed = !$product->getTypeInstance()->isVirtual($product) ? $product->getGiftWrappingAvailable() : false;
        $storeId = $this->getItem()->getStoreId();
        $giftProduct = 0 ;
        if($product->getGiftWrapping()){
            $giftProduct = 1 ;
        }
        $giftConfig = $this->_giftWrappingData->isGiftWrappingAvailableForProduct($allowed, $storeId);
        return $giftConfig ?$giftProduct : $giftConfig ;
    }
}
