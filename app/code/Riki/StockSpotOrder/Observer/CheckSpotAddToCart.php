<?php
/**
 * StockSpotOrder
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\StockSpotOrder
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\StockSpotOrder\Observer;

use Magento\Bundle\Model\ResourceModel\Indexer\Stock;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Riki\ProductStockStatus\Model\StockStatusFactory;
use Magento\CatalogInventory\Model\Stock\ItemFactory;

/**
 * StockSpotOrder
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\StockSpotOrder
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

class CheckSpotAddToCart implements ObserverInterface
{
    /**
     * Check product set allow spot order
     * If set no customer will not be able to add this product to shopping cart
     *
     * @param \Magento\Framework\Event\Observer $observer observer
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quoteItem = $observer->getEvent()->getQuoteItem();
        $product   = $observer->getEvent()->getProduct();
        $allowSpotOrder = $product->getData('allow_spot_order');
        if (!$product->getData(\Riki\StockSpotOrder\Helper\Data::SKIP_CHECk_ALLOW_SPOT_ORDER_NAME) && !$allowSpotOrder) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('You cannot add "%1" to the cart.', $quoteItem->getName())
            );
        }
        return $this;
    }
}