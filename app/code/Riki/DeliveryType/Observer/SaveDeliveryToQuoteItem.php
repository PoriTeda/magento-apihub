<?php

namespace Riki\DeliveryType\Observer;

use Magento\Framework\Event\ObserverInterface;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;


class SaveDeliveryToQuoteItem implements ObserverInterface
{
  
    
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getProduct();
        $quoteItem = $observer->getQuoteItem();

        if ($deliveryType = $product->getData('delivery_type')) {
            $quoteItem->setDeliveryType($deliveryType);
        }

//        $quoteItem->setIsFreeShipping($product->getData('is_free_shipping'));
        return $quoteItem;
    }
}