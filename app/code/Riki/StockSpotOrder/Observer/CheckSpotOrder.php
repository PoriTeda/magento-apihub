<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category RIKI
 * @package  Riki\StockSpotOrder
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */
namespace Riki\StockSpotOrder\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Check Sport order
 *
 * @category CheckSpotOrder
 * @package  Riki\StockSpotOrder
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */

class CheckSpotOrder implements ObserverInterface
{
    /**
     * Check sport order
     *
     * @param \Magento\Framework\Event\Observer $observer Obsever
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $allowSpot = $product->getData('allow_spot_order');
        if (!$product->getData(\Riki\StockSpotOrder\Helper\Data::SKIP_CHECk_ALLOW_SPOT_ORDER_NAME) && !$allowSpot) {
            $observer->getSalable()->setIsSalable(false);
        }

        return $this;
    }
}