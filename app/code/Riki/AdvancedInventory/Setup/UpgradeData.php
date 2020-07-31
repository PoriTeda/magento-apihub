<?php
namespace Riki\AdvancedInventory\Setup;

use \Riki\AdvancedInventory\Api\Data\OutOfStock\QueueExecuteInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeData extends \Riki\Framework\Setup\Version\Data implements \Magento\Framework\Setup\UpgradeDataInterface
{
    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Magento\CatalogInventory\Model\Indexer\Stock\Action\Rows
     */
    protected $inventoryIndexerRows;

    /**
     * UpgradeData constructor.
     *
     * @param \Magento\CatalogInventory\Model\Indexer\Stock\Action\RowsFactory $inventoryIndexerRowsFactory
     * @param \Magento\Framework\App\State $appState
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     */
    public function __construct(
        \Magento\CatalogInventory\Model\Indexer\Stock\Action\RowsFactory $inventoryIndexerRowsFactory,
        \Magento\Framework\App\State $appState,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig
    ) {
        $this->appState = $appState;
        $this->inventoryIndexerRows = $this->appState->emulateAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB, [$inventoryIndexerRowsFactory, 'create']);
        parent::__construct($functionCache, $logger, $resourceConnection, $deploymentConfig);
    }

    public function version202()
    {
        $conn = $this->getConnection('cataloginventory_stock_item');
        $conn->query("
            UPDATE cataloginventory_stock_item AS cataloginventory_stock_item_t
INNER JOIN
(
SELECT product_id, SUM(quantity_in_stock) AS sum_qty
FROM advancedinventory_stock
WHERE manage_stock=1
GROUP BY product_id
) AS av_stock ON cataloginventory_stock_item_t.product_id = av_stock.product_id
LEFT JOIN (SELECT * FROM advancedinventory_item WHERE advancedinventory_item.multistock_enabled=1) AS advancedinventory_item_t ON advancedinventory_item_t.product_id = cataloginventory_stock_item_t.product_id
SET cataloginventory_stock_item_t.qty = av_stock.sum_qty
WHERE cataloginventory_stock_item_t.manage_stock=1;
            ");
    }

    public function version206()
    {
        $oosTableName = $this->getTable('riki_advancedinventory_outofstock');
        $oosConn = $this->getConnection($oosTableName);

        $qiTableName = $this->getTable('quote_item');
        $qiConn = $this->getConnection($qiTableName);

        $qioTableName = $this->getTable('quote_item_option');
        $qioConn = $this->getConnection($qioTableName);

        $oiTableName = $this->getTable('sales_order_item');
        $oiConn = $this->getConnection($oiTableName);

        try {
            $anchor = 0;
            do {
                $sql = "SELECT `quote_item_id` FROM `{$oosTableName}` WHERE `generated_order_id` IS NOT NULL AND `generated_order_id` > 0 AND `quote_item_id` > {$anchor} ORDER BY quote_item_id ASC LIMIT 1000";
                $quoteItemIds = $oosConn->fetchCol($sql);
                if (!$quoteItemIds) {
                    break;
                }

                $anchor = max($quoteItemIds);

                $quoteItemIds = implode(',', $quoteItemIds);

                $sql = "SELECT `custom_price`, group_concat(`item_id`)  FROM `{$qiTableName}` WHERE `item_id` IN ({$quoteItemIds}) AND `custom_price` > 0 GROUP BY `custom_price` ORDER BY `item_id` ASC";
                $qiConn->query('SET group_concat_max_len = 100000;');
                $customPrices = $qiConn->fetchPairs($sql);
                if (!$customPrices) {
                    continue;
                }

                foreach ($customPrices as $customPrice => $quoteItemIds) {
                    $oiConn->update($this->getTable('sales_order_item'), [
                        'original_price' => $customPrice,
                        'base_original_price' => $customPrice
                    ], "quote_item_id IN ({$quoteItemIds})");

                }
            } while (1);
        } catch (\Exception $e) {
            // silence
        }

        try {
            $anchor = 0;
            do {
                $sql = "SELECT `quote_item_id` FROM `{$oosTableName}` WHERE quote_item_id > {$anchor} ORDER BY quote_item_id ASC LIMIT 1000";
                $quoteItemIds = $oosConn->fetchCol($sql);
                if (!$quoteItemIds) {
                    break;
                }

                $anchor = max($quoteItemIds);

                foreach ($quoteItemIds as $quoteItemId) {
                    $quoteItemData = [
                        'children' => [],
                        'options' => []
                    ];
                    $quoteItems = $qiConn->fetchAll("SELECT * FROM `{$qiTableName}` WHERE item_id = {$quoteItemId} OR parent_item_id = {$quoteItemId}");
                    foreach ($quoteItems as $quoteItem) {
                        if ($quoteItem['parent_item_id']) {
                            $quoteItemOptions = $qioConn->fetchAll("SELECT * FROM `{$qioTableName}` WHERE item_id = {$quoteItem['item_id']}");
                            foreach ($quoteItemOptions as $quoteItemOption) {
                                $quoteItem['options'][] = $quoteItemOption;
                            }
                            $quoteItemData['children'][] = $quoteItem;
                        } else {
                            $quoteItemData = array_merge($quoteItemData, $quoteItem);
                            $quoteItemOptions = $qioConn->fetchAll("SELECT * FROM `{$qioTableName}` WHERE item_id = {$quoteItem['item_id']}");
                            foreach ($quoteItemOptions as $quoteItemOption) {
                                $quoteItemData['options'][] = $quoteItemOption;
                            }
                        }
                    }

                    try {
                        $quoteItemData = \Zend_Json::encode($quoteItemData);
                    } catch (\Zend_Json_Exception $e) {
                        $quoteItemData = \Zend_Json::encode([]);
                    }

                    $oosConn->update($oosTableName, [
                        'quote_item_data' => $quoteItemData
                    ], "quote_item_id = {$quoteItemId}");
                }

            } while (1);
        } catch (\Exception $e) {
            //silence
        }
    }

    public function version207()
    {
        $siTableName = $this->getTable('cataloginventory_stock_item');
        $siConn = $this->getConnection($siTableName);
        $asTableName = $this->getTable('advancedinventory_stock');

        $sql = "SELECT stock_item.product_id, wh_item.wh_qty
FROM {$siTableName} AS stock_item 
INNER JOIN (SELECT product_id, sum(quantity_in_stock) as wh_qty FROM {$asTableName} GROUP BY product_id) AS wh_item ON wh_item.product_id = stock_item.product_id  
WHERE stock_item.qty <> wh_item.wh_qty;";
        $result = $siConn->fetchPairs($sql);
        if (!$result) {
            return;
        }

        foreach ($result as $productId => $qty) {
            $siConn->update($siTableName, [
                'qty' => $qty
            ], "product_id = {$productId}");
        }

        $this->inventoryIndexerRows->execute(array_keys($result));
    }

    public function version209()
    {
        $conn = $this->getConnection('riki_advancedinventory_outofstock');
        $conn->update($this->getTable('riki_advancedinventory_outofstock'), [
            'queue_execute' => new \Zend_Db_Expr('NULL')
        ], 'queue_execute = ' . QueueExecuteInterface::ERROR . ' AND generated_order_id IS NULL');
    }

    public function version220()
    {
        $conn = $this->getConnection('riki_advancedinventory_outofstock');

        $table = $this->getTable('riki_advancedinventory_outofstock');

        $select = $conn->select()
            ->from($table, ['entity_id', 'quote_item_children_qty'])
            ->where('queue_execute IS NULL')
            ->where('quote_item_children_qty IS NOT NULL')
            ->where('quote_item_children_qty !=?', '[]');

        $query = $conn->query($select);

        $insertedData = [];

        while ($row = $query->fetch()) {
            try {
                $childrenQty = \Zend_Json::decode($row['quote_item_children_qty']);

            } catch (\Exception $e) {
                continue;
            }

            foreach ($childrenQty as $childQty) {
                foreach ($childQty as $productId    =>  $qty) {
                    $insertedData[] = [
                        'parent_id' =>  $row['entity_id'],
                        'product_id' =>  $productId,
                        'qty'   =>  $qty
                    ];

                    if (count($insertedData) >= 1000) {
                        $conn->insertOnDuplicate($this->getTable('riki_advancedinventory_outofstock_children'), $insertedData);
                    }
                }
            }
        }

        if (count($insertedData)) {
            $conn->insertOnDuplicate($this->getTable('riki_advancedinventory_outofstock_children'), $insertedData);
        }

        $this->dropColumn($this->table('riki_advancedinventory_outofstock'), 'quote_item_children_qty');
    }
}
