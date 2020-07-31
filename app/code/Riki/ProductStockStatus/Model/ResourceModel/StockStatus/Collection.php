<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category Riki_Stock
 * @package  Riki\Stock\Model\ResourceModel\StockStatus
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */

namespace Riki\ProductStockStatus\Model\ResourceModel\StockStatus;
/**
 * Class Collection
 *
 * @category Riki_Stock
 * @package  Riki\Stock\Model\ResourceModel\StockStatus
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */

class Collection extends
    \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define   variables
     * @var     string
     */
    protected $_idFieldName = 'status_id';

    /**
     * Define   resource model
     * @return  void
     */
    protected function _construct()
    {
        $this->_init(
            'Riki\ProductStockStatus\Model\StockStatus',
            'Riki\ProductStockStatus\Model\ResourceModel\StockStatus'
        );
        $this->_map['fields']['status_id'] = 'main_table.status_id';
    }

}