<?php

namespace Wyomind\AdvancedInventory\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{

    protected $_coreDate = null;
    protected $_coreHelper = null;

    /**s
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $coreDate
     * @param \Wyomind\SimpleGoogleShopping\Model\ResourceModel\Store\CollectionFactory $storeCollectionFactory
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $coreDate,
        \Wyomind\Core\Helper\Data $coreHelper
    ) {
        $this->_coreDate = $coreDate;
        $this->_coreHelper = $coreHelper;
        
    }
    
    /**
     * @version 6.0.0
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        unset($context);
        
        $installer = $setup;

        $installer->startSetup();

        $installer->getConnection()->dropTable($installer->getTable('advancedinventory_item'));
        $installer->getConnection()->dropTable($installer->getTable('advancedinventory_stock'));
        $installer->getConnection()->dropTable($installer->getTable('advancedinventory_log'));
        $installer->getConnection()->dropTable($installer->getTable('advancedinventory_assignation'));

        $advancedinventoryItem = $installer->getConnection()
                ->newTable($installer->getTable('advancedinventory_item'))
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    11,
                    [ 'identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Item ID'
                )
                ->addColumn(
                    'product_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    10,
                    [ 'identity' => false, 'unsigned' => true, 'nullable' => false, 'primary' => false],
                    'Product ID'
                )
                ->addColumn(
                    'multistock_enabled',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    1,
                    [ 'identity' => false, 'unsigned' => true, 'nullable' => false, 'default' => 0, 'primary' => false],
                    'Is multistock enabled?'
                )
                ->addIndex(
                    $installer->getIdxName('advancedinventory_item', ['id', 'product_id']),
                    ['id', 'product_id']
                )
                ->addForeignKey($installer->getFkName('advancedinventory_item', 'product_id', 'catalog_product_entity', 'entity_id'), 'product_id', $installer->getTable('catalog_product_entity'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE, \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE)
                ->addIndex($installer->getIdxName('advancedinventory_item', ['product_id'], \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE), ['product_id'], ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE])
                ->setComment('Advanced Inventory Items');

        $installer->getConnection()->createTable($advancedinventoryItem);

        $advancedinventoryStock = $installer->getConnection()
                ->newTable($installer->getTable('advancedinventory_stock'))
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    11,
                    [ 'identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Stock ID'
                )
                ->addColumn(
                    'product_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    10,
                    [ 'identity' => false, 'unsigned' => true, 'nullable' => false, 'primary' => false],
                    'Product ID'
                )
                ->addColumn(
                    'item_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    11,
                    [ 'identity' => false, 'unsigned' => true, 'nullable' => false, 'default' => 0, 'primary' => false],
                    'Item ID'
                )
                ->addColumn(
                    'place_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    11,
                    [ 'identity' => false, 'unsigned' => true, 'nullable' => false, 'primary' => false],
                    'POS/WH ID'
                )
                ->addColumn(
                    'manage_stock',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    1,
                    [ 'identity' => false, 'unsigned' => true, 'nullable' => false, 'default' => 1, 'primary' => false],
                    'Is stock enabled?'
                )
                ->addColumn(
                    'quantity_in_stock',
                    \Magento\Framework\DB\Ddl\Table::TYPE_FLOAT,
                    9,
                    [ 'identity' => false, 'unsigned' => false, 'nullable' => false, 'default' => 0, 'primary' => false],
                    'Quantity in Stock?'
                )
                ->addColumn(
                    'backorder_allowed',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    1,
                    [ 'identity' => false, 'unsigned' => true, 'nullable' => false, 'default' => 0, 'primary' => false],
                    'Is backorder allowed?'
                )
                ->addColumn(
                    'use_config_setting_for_backorders',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    1,
                    [ 'identity' => false, 'unsigned' => true, 'nullable' => false, 'default' => 1, 'primary' => false],
                    'Use config settings for backorders?'
                )->addIndex(
                    $installer->getIdxName('advancedinventory_stock', ['id', 'product_id', 'item_id', 'place_id']),
                    ['id', 'product_id', 'item_id', 'place_id']
                )
                ->addIndex(
                    $installer->getIdxName('advancedinventory_stock', ['product_id', 'item_id', 'place_id'], \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE),
                    ['product_id', 'item_id', 'place_id'],
                    ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
                )
                ->addForeignKey(
                    $installer->getFkName('advancedinventory_stock', 'product_id', 'catalog_product_entity', 'entity_id'),
                    'product_id',
                    $installer->getTable('catalog_product_entity'),
                    'entity_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE,
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $installer->getFkName('advancedinventory_stock', 'item_id', 'advancedinventory_item', 'id'),
                    'item_id',
                    $installer->getTable('advancedinventory_item'),
                    'id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE,
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $installer->getFkName('advancedinventory_stock', 'place_id', 'pointofsale', 'place_id'),
                    'place_id',
                    $installer->getTable('pointofsale'),
                    'place_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE,
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->setComment('Advanced Inventory Stocks');

        $installer->getConnection()->createTable($advancedinventoryStock);

        $advancedinventoryLog = $installer->getConnection()
                ->newTable($installer->getTable('advancedinventory_log'))
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    11,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Log ID'
                )
                ->addColumn(
                    'datetime',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                    false,
                    ['identity' => false, 'nullable' => false, 'primary' => false],
                    'Log Date and time'
                )
                ->addColumn(
                    'user',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    60,
                    ['identity' => false, 'nullable' => true, 'primary' => false],
                    'User'
                )
                ->addColumn(
                    'context',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    60,
                    ['identity' => false, 'nullable' => true, 'primary' => false],
                    'Context'
                )
                ->addColumn(
                    'action',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    60,
                    ['identity' => false, 'unsigned' => true, 'nullable' => true, 'primary' => false],
                    'Action'
                )
                ->addColumn(
                    'reference',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    100,
                    ['identity' => false, 'nullable' => true, 'primary' => false],
                    'Reference'
                )->addColumn(
                    'details',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    60,
                    ['identity' => false, 'nullable' => true, 'primary' => false],
                    'Details'
                )->addIndex(
                    $installer->getIdxName('advancedinventory_log', ['id']),
                    ['id']
                )
                ->setComment('Advanced Inventory Logs');

        $installer->getConnection()->createTable($advancedinventoryLog);

        $advancedinventoryAssignation = $installer->getConnection()
                ->newTable($installer->getTable('advancedinventory_assignation'))
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    11,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Assignation ID'
                )
                ->addColumn(
                    'place_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    11,
                    [ 'identity' => false, 'unsigned' => true, 'nullable' => false, 'primary' => false],
                    'POS/WH ID'
                )
                ->addColumn(
                    'item_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    10,
                    [ 'identity' => false, 'unsigned' => true, 'nullable' => false, 'primary' => false],
                    'Order Item ID'
                )
                ->addColumn(
                    'qty_assigned',
                    \Magento\Framework\DB\Ddl\Table::TYPE_FLOAT,
                    9,
                    [ 'identity' => false, 'unsigned' => true, 'nullable' => false, 'default' => 0, 'primary' => false],
                    'Assigned Qty'
                )
                ->addColumn(
                    'qty_returned',
                    \Magento\Framework\DB\Ddl\Table::TYPE_FLOAT,
                    9,
                    [ 'identity' => false, 'unsigned' => true, 'nullable' => false, 'default' => 0, 'primary' => false],
                    'Returned Qty'
                )
                ->addIndex(
                    $installer->getIdxName('advancedinventory_assignation', [ 'item_id', 'place_id'], \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE),
                    [ 'item_id', 'place_id'],
                    ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
                )
                ->addForeignKey(
                    $installer->getFkName('advancedinventory_assignation', 'item_id', 'sales_order_item', 'item_id'),
                    'item_id',
                    $installer->getTable('sales_order_item'),
                    'item_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE,
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $installer->getFkName('advancedinventory_assignation', 'place_id', 'pointofsale', 'place_id'),
                    'place_id',
                    $installer->getTable('pointofsale'),
                    'place_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE,
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->setComment('Advanced Inventory Order Item Assignation');

        $installer->getConnection()->createTable($advancedinventoryAssignation);

        $installer->getConnection()->addColumn(
            $installer->getTable('pointofsale'),
            'manage_inventory',
            "INT(1) DEFAULT '1'"
        );
        $installer->getConnection()->addColumn(
            $installer->getTable('pointofsale'),
            'inventory_assignation_rules',
            "TEXT"
        );
        $installer->getConnection()->addColumn(
            $installer->getTable('pointofsale'),
            'inventory_notification',
            "TEXT"
        );
        $installer->getConnection()->addColumn(
            $installer->getTable('pointofsale'),
            'use_assignation_rules',
            "int(1) DEFAULT '0'"
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('pointofsale'),
            'default_stock_management',
            "int(1) DEFAULT 1"
        );
        $installer->getConnection()->addColumn(
            $installer->getTable('pointofsale'),
            'default_use_default_setting_for_backorder',
            "int(1) DEFAULT 1"
        );
        $installer->getConnection()->addColumn(
            $installer->getTable('pointofsale'),
            'default_allow_backorder',
            "int(1) DEFAULT 0"
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'assigned_to',
            'VARCHAR(255) DEFAULT 0'
        );
        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_grid'),
            'assigned_to',
            'VARCHAR(255) DEFAULT 0'
        );

        $this->_coreHelper->setDefaultConfig("advancedinventory/settings/order_notification_from_date", $this->_coreDate->gmtDate('Y-m-d'));

        $this->_coreHelper->setDefaultConfig("cataloginventory/options/can_subtract", 0);

        $installer->endSetup();
    }
}
