<?php

/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Observer;

class SaveConfig implements \Magento\Framework\Event\ObserverInterface
{

    protected $_resource = null;
    protected $_connection = null;
    protected $_coreHelper = null;
    protected $_helperData = null;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Wyomind\Core\Helper\Data $coreHelper,
        \Wyomind\AdvancedInventory\Helper\Data $helperData
    ) {
        $this->_resource = $resource;
        $this->_coreHelper = $coreHelper;
        $this->_helperData = $helperData;
    }

    protected function _getWriteConnection()
    {
        if (null === $this->_connection) {
            $this->_connection = $this->_resource->getConnection('write');
        }
        return $this->_connection;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $connection = $this->_resource->getConnection('sales');


        $date = $this->_coreHelper->getStoreConfig("advancedinventory/settings/order_notification_from_date");
        // $date_config = substr($date, 6, 4) . '-' . substr($date, 0, 2) . '-' . substr($date, 3, 2);
        $table = $this->_helperData->getSalesConnection().$this->_resource->getTableName("sales_order");

        $tableGrid = $this->_helperData->getSalesConnection().$this->_resource->getTableName("sales_order_grid");

        // Update assignation (-1) for all orders older than given date
        $connection->update($table, ["assigned_to" => '-1'], "created_at < '$date'");
        $connection->update($tableGrid, ["assigned_to" => '-1'], "created_at < '$date'");
        // Update assignation (0) for all orders not yet assigned (-1) and placed after the given date
        $connection->update($table, ["assigned_to" => '0'], "assigned_to < 0 AND created_at>='$date'");
        $connection->update($tableGrid, ["assigned_to" => '0'], "assigned_to < 0 AND created_at>='$date'");
    }
}
