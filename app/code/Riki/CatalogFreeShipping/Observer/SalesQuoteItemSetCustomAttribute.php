<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category  RIKI
 * @package   Riki_CatalogFreeShipping
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\CatalogFreeShipping\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class SalesQuoteItemSetCustomAttribute
 *
 * @category RIKI
 * @package  Riki_CatalogFreeShipping
 * @author   Nestle.co.jp <support@nestle.co.jp>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/rikibusiness/riki-ecommerce
 */
class SalesQuoteItemSetCustomAttribute implements ObserverInterface
{


    /**
     * Execute function
     *
     * @param \Magento\Framework\Event\Observer $observer Event observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product   = $observer->getProduct();
        $quoteItem = $observer->getQuoteItem();
        $quoteItem->setPhCode($product->getPhCode());
        $quoteItem->setBookingItemWbs($product->getBookingItemWbs());

    }//end execute()


}//end class
