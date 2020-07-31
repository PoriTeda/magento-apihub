<?php

namespace Riki\AdvancedInventory\Helper\AdvancedInventory;

class Data extends \Wyomind\AdvancedInventory\Helper\Data
{
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_salesCollection;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $subscriptionConnection;

    /**
     * Data constructor.
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Framework\App\DeploymentConfig\Reader $configReader
     * @param \Wyomind\Core\Helper\Data $coreHelper
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\App\DeploymentConfig\Reader $configReader,
        \Wyomind\Core\Helper\Data $coreHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        parent::__construct(
            $productRepository,
            $configReader,
            $coreHelper
        );

        $this->_connection = $resourceConnection->getConnection();
        $this->_salesCollection = $resourceConnection->getConnection('sales');
        $this->subscriptionConnection = $resourceConnection->getConnection('subscription');
    }

    /**
     * @param array $itemIds
     * @return array
     */
    public function getOrderItemQtyRefundedCancelled(array $itemIds){
        if (count($itemIds)) {
            $parentItemsRefundQtySelect = $this->_salesCollection->select()
                ->from(
                    'sales_order_item',
                    ['item_id', 'qty_refunded', 'qty_canceled']
                )->where('item_id IN (?)', $itemIds);

            return $this->subscriptionConnection->fetchAll($parentItemsRefundQtySelect);
        }

        return [];
    }

    /**
     * @param array $itemIds
     * @return array
     */
    public function getSimulateOrderItemQtyRefundedCancelled(array $itemIds){
        if (count($itemIds)) {
            $tmpTableOrderItem = \Riki\Subscription\Model\Emulator\Config::getOrderItemTmpTableName();
            $parentItemsRefundQtySelect = $this->_salesCollection->select()
                ->from(
                    $tmpTableOrderItem,
                    ['item_id', 'qty_refunded', 'qty_canceled']
                )->where('item_id IN (?)', $itemIds);

            return  $this->subscriptionConnection->fetchAll($parentItemsRefundQtySelect);
        }

        return [];
    }

    /**
     * @param array $itemIds
     * @return array
     */
    public function getAdvancedInventoryQtysData(array $itemIds){
        if (count($itemIds)) {
            $advancedInventoryAssignationSelect = $this->_connection->select()->from(
                'advancedinventory_assignation',
                [
                    'item_id',
                    "qty_unassigned" => new \Zend_Db_Expr("SUM(IFNULL(qty_assigned,0)) - SUM(IFNULL(qty_returned,0))"),
                    "qty_assigned" => new \Zend_Db_Expr("SUM(IFNULL(qty_assigned,0))"),
                    "qty_returned" => new \Zend_Db_Expr("SUM(IFNULL(qty_returned,0))"),
                ]
            )->where(
                'advancedinventory_assignation.item_id IN(?)',
                $itemIds
            )->group("advancedinventory_assignation.item_id");

            return $this->_connection->fetchAll($advancedInventoryAssignationSelect);
        }

        return [];
    }

    /**
     * @param array $productIds
     * @return array
     */
    public function getAdvancedInventoryStockStatus(array $productIds){
        if (count($productIds)) {
            $advancedInventoryItemSelect = $this->_connection->select()->from(
                'advancedinventory_item',
                [
                    'product_id',
                    'multistock_enabled'
                ]
            )->where(
                'advancedinventory_item.product_id IN(?)',
                $productIds
            );

            return $this->_connection->fetchPairs($advancedInventoryItemSelect);
        }

        return [];
    }
}
