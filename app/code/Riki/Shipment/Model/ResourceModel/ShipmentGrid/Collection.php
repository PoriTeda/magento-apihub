<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category Riki
 * @package  Riki\Shipment
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */

namespace Riki\Shipment\Model\ResourceModel\ShipmentGrid;
/**
 * Class Collection
 *
 * @category Riki
 * @package  Riki\Shipment
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
    protected $_idFieldName = 'entity_id';

    /**
     * Define   resource model
     * @return  void
     */
    protected function _construct()
    {
        $this->_init(
            'Riki\Shipment\Model\ShipmentGrid',
            'Riki\Shipment\Model\ResourceModel\ShipmentGrid'
        );
        $this->_map['fields']['entity_id'] = 'main_table.entity_id';
    }

}