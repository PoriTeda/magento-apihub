<?php

/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Model\ResourceModel\Order\Item;

class Collection extends \Magento\Sales\Model\ResourceModel\Order\Item\Collection
{

    protected $_helperData = null;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot
     * @param \Wyomind\AdvancedInventory\Helper\Data $helperData
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot,
        \Wyomind\AdvancedInventory\Helper\Data $helperData,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->_helperData = $helperData;
        $this->_resource = $resource;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $entitySnapshot, $connection, $resource);
    }

    public function getAssignationByOrderId(
        $orderId,
        $itemId = false
    ) {

        $connection = $this->_resource;

        $defaultConnection = $this->_helperData->getDefaultConnection();
        
        $salesOrderItem = $connection->getTable('sales_order_item');
        $advancedinventoryAssignation = $defaultConnection.$connection->getTable('advancedinventory_assignation');
        $advancedinventoryItem = $defaultConnection.$connection->getTable("advancedinventory_item");


        $this->addFieldToSelect('item_id');
        if ($itemId) {
            $this->addFieldToFilter("main_table.item_id", ["eq" => $itemId]);
        }
        $this->addFieldToFilter("order_id", ["eq" => $orderId]);
        $or = [];

        foreach ($this->_helperData->getProductTypes() as $type) {
            $or[] = ["eq" => $type];
        }

        $this->addFieldToFilter("product_type", [$or, ['eq' => "grouped"]]);

        $this->getSelect()
                ->columns(
                    [
                            "name" => "name",
                            "sku" => "sku",
                            "order_id" => "order_id",
                            "item_id" => "item_id",
                            "product_id" => "product_id",
                            "product_type" => "product_type",
                            "qty_ordered" => "qty_ordered",
                            "qty_canceled" => new \Zend_Db_Expr("IF(ISNULL(parent_item_id),`main_table`.`qty_canceled`,(SELECT soi.qty_canceled FROM $salesOrderItem AS soi WHERE soi.item_id=main_table.parent_item_id))"),
                            "qty_refunded" => new \Zend_Db_Expr("IF(ISNULL(parent_item_id),`main_table`.`qty_refunded`,(SELECT soi.qty_refunded FROM $salesOrderItem AS soi WHERE soi.item_id=main_table.parent_item_id))"),
                        ]
                )
                ->joinLeft(
                    ['advancedinventory_assignation' => $advancedinventoryAssignation],
                    'main_table.item_id=advancedinventory_assignation.item_id',
                    [
                    "qty_unassigned" => new \Zend_Db_Expr("(qty_ordered - IF(ISNULL(parent_item_id),`main_table`.`qty_canceled`,(SELECT soi.qty_canceled FROM $salesOrderItem AS soi WHERE soi.item_id=main_table.parent_item_id)) - IF(ISNULL(parent_item_id),`main_table`.`qty_refunded`,(SELECT soi.qty_refunded FROM $salesOrderItem AS soi WHERE soi.item_id=main_table.parent_item_id))) - (SUM(IFNULL(qty_assigned,0)) - SUM(IFNULL(advancedinventory_assignation.qty_returned,0)))"),
                    "qty_to_assign" => new \Zend_Db_Expr("(qty_ordered - IF(ISNULL(parent_item_id),`main_table`.`qty_canceled`,(SELECT soi.qty_canceled FROM $salesOrderItem AS soi WHERE soi.item_id=main_table.parent_item_id)) - IF(ISNULL(parent_item_id),`main_table`.`qty_refunded`,(SELECT soi.qty_refunded FROM $salesOrderItem AS soi WHERE soi.item_id=main_table.parent_item_id)))"),
                    "qty_assigned" => new \Zend_Db_Expr("SUM(IFNULL(qty_assigned,0))"),
                    "qty_returned" => new \Zend_Db_Expr("SUM(IFNULL(advancedinventory_assignation.qty_returned,0))"),
                    ],
                    null,
                    'left'
                )
                ->joinLeft(
                    ['advancedinventory_item' => $advancedinventoryItem],
                    'main_table.product_id=advancedinventory_item.product_id',
                    [
                    "multistock_enabled" => "multistock_enabled",
                    ],
                    null,
                    'left'
                )
                ->group("main_table.item_id");
        return $this;
    }
}
