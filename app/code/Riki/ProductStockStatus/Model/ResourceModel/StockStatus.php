<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category Riki_ProductStockStatus
 * @package  Riki\ProductStockStatus\Model\ResourceModel
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */

namespace Riki\ProductStockStatus\Model\ResourceModel;
/**
 * Class     StockStatus

 * @category Riki_ProductStockStatus
 * @package  Riki\ProductStockStatus\Model\ResourceModel
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */
class StockStatus extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_product_stock_status', 'status_id');
    }
}