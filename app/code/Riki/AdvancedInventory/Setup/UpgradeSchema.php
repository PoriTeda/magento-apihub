<?php
namespace Riki\AdvancedInventory\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Riki\Framework\Setup\Version\Schema;
use Magento\Framework\DB\Ddl\Trigger;

class UpgradeSchema extends Schema implements UpgradeSchemaInterface
{
    /**
     * @var \Magento\Amqp\Model\Topology
     */
    protected $topology;

    /**
     * @var \Riki\AdvancedInventory\Model\Queue\Schema\OosQueueSchemaFactory $oosQueueSchemaFactory
     */
    protected $oosQueueSchemaFactory;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface $publisher
     */
    protected $publisher;

    /**
     * @var \Magento\Framework\DB\Ddl\TriggerFactory
     */
    protected $triggerFactory;

    /**
     * UpgradeSchema constructor.
     *
     * @param \Magento\Framework\DB\Ddl\TriggerFactory $triggerFactory
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param \Magento\Amqp\Model\Topology $topology
     * @param \Riki\AdvancedInventory\Model\Queue\Schema\OosQueueSchemaFactory $oosQueueSchemaFactory
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     */
    public function __construct(
        \Magento\Framework\DB\Ddl\TriggerFactory $triggerFactory,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Amqp\Model\Topology $topology,
        \Riki\AdvancedInventory\Model\Queue\Schema\OosQueueSchemaFactory $oosQueueSchemaFactory,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher
    ) {
        $this->triggerFactory = $triggerFactory;
        $this->topology = $topology;
        $this->oosQueueSchemaFactory = $oosQueueSchemaFactory;
        $this->publisher = $publisher;
        parent::__construct($functionCache, $logger, $resourceConnection, $deploymentConfig);
    }

    /**
     * Version 1.0.2
     *
     * @return void
     */
    public function version102()
    {
        $def = [
            [
                'entity_id',
                Table::TYPE_INTEGER,
                10,
                [
                    'primary' => true,
                    'unsigned' => true,
                    'identity' => true,
                    'nullable' => false,
                ],
                'Entity Id'
            ],
            [
                'order_type',
                Table::TYPE_SMALLINT,
                5,
                ['unsigned' => true],
                'Order type: 1(Spot), 2(Subscription), 3(Pre-order)'
            ],
            [
                'product_type',
                Table::TYPE_SMALLINT,
                5,
                ['unsigned' => true],
                'Product type: 1 (Normal product), 2(Free promo), 3(Free prize), 4(Free machine)'
            ],
            [
                'quote_id',
                Table::TYPE_INTEGER,
                10,
                ['unsigned' => true],
                'Quote id which contains out of stock product',
            ],
            [
                'original_order_id',
                Table::TYPE_INTEGER,
                10,
                ['unsigned' => true],
                'Order id which contains out of stock product',
            ],
            [
                'generated_order_id',
                Table::TYPE_INTEGER,
                10,
                ['unsigned' => true],
                'Order id which contains out of stock product',
            ],
            [
                'product_id',
                Table::TYPE_INTEGER,
                10,
                ['unsigned' => true],
                'Product id which is out of stock'
            ],
            [
                'qty',
                Table::TYPE_DECIMAL,
                '12,4',
                ['default' => 0.0000],
                'qty -> clone from quote_item'
            ],
            [
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['default' => Table::TIMESTAMP_INIT],
                'Created date'
            ],
            [
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['default' => Table::TIMESTAMP_INIT_UPDATE],
                'Created date'
            ],
        ];
        $this->createTable('riki_advancedinventory_outofstock', $def);
        $this->addForeignKey(
            'riki_advancedinventory_outofstock',
            'quote_id',
            'quote',
            'entity_id',
            Table::ACTION_RESTRICT
        );
        $this->addForeignKey(
            'riki_advancedinventory_outofstock',
            'original_order_id',
            'sales_order',
            'entity_id',
            Table::ACTION_RESTRICT
        );
        $this->addForeignKey(
            'riki_advancedinventory_outofstock',
            'generated_order_id',
            'sales_order',
            'entity_id',
            Table::ACTION_RESTRICT
        );
        $this->addForeignKey(
            'riki_advancedinventory_outofstock',
            'product_id',
            'catalog_product_entity',
            'entity_id',
            Table::ACTION_RESTRICT
        );
    }

    /**
     * Version 1.0.3
     *
     * @return void
     */
    public function version103()
    {
        $this->addColumn('riki_advancedinventory_outofstock', 'prize_id', [
            'type' => Table::TYPE_INTEGER,
            'unsigned' => true,
            'length' => 10,
            'comment' => 'Prize id (ref to riki_prize)',
        ]);
        $this->addForeignKey(
            'riki_advancedinventory_outofstock',
            'prize_id',
            'riki_prize',
            'prize_id',
            Table::ACTION_RESTRICT
        );

        $this->addColumn('riki_advancedinventory_outofstock_item', 'quote_item_id', [
            'type' => Table::TYPE_INTEGER,
            'unsigned' => true,
            'length' => 10,
            'comment' => 'Quote item id (ref to quote_item)'
        ]);
    }

    /**
     * Version 1.0.5
     *
     * @return void
     */
    public function version105()
    {
        $this->dropTable('riki_advancedinventory_outofstock_item');
        $this->dropColumn('riki_advancedinventory_outofstock', 'order_type');
        $this->dropColumn('riki_advancedinventory_outofstock', 'product_type');
        $this->addColumn('riki_advancedinventory_outofstock', 'quote_item_id', [
            'type' => Table::TYPE_INTEGER,
            'unsigned' => true,
            'length' => 10,
            'comment' => 'Quote item id (ref to quote_item)'
        ]);
        $this->addIndex('riki_advancedinventory_outofstock', ['quote_item_id']);
        $this->dropForeignKey('riki_advancedinventory_outofstock', [
            'riki_advancedinventory_outofstock',
            'quote_id',
            'quote',
            'entity_id',
        ]);
        $this->dropForeignKey('riki_advancedinventory_outofstock', [
            'riki_advancedinventory_outofstock',
            'original_order_id',
            'sales_order',
            'entity_id',
        ]);
        $this->dropForeignKey('riki_advancedinventory_outofstock', [
            'riki_advancedinventory_outofstock',
            'generated_order_id',
            'sales_order',
            'entity_id',
        ]);
    }

    /**
     * Version 1.0.6
     *
     * @return void
     */
    public function version106()
    {
        $this->addColumn('riki_advancedinventory_outofstock', 'product_sku', [
            'type' => Table::TYPE_TEXT,
            'length' => '64',
            'comment' => 'Product SKU'
        ]);
        $this->addColumn('riki_advancedinventory_outofstock', 'salesrule_id', [
            'type' => Table::TYPE_INTEGER,
            'length' => 10,
            'unsigned' => true,
            'comment' => 'Ref salesrule(rule_id)'
        ]);
        $this->addIndex('riki_advancedinventory_outofstock', ['salesrule_id']);
    }

    /**
     * Version 1.0.7
     *
     * @return void
     */
    public function version107()
    {
        $this->addColumn('riki_advancedinventory_outofstock', 'subscription_profile_id', [
            'type' => Table::TYPE_INTEGER,
            'length' => 10,
            'unsigned' => true,
            'comment' => 'Ref subscription_profile(profile_id)'
        ]);
        $this->addIndex('riki_advancedinventory_outofstock', ['subscription_profile_id']);
    }

    /**
     * Version 1.0.8
     */
    public function version108()
    {
        $this->addColumn('riki_advancedinventory_outofstock', 'customer_id', [
            'type' => Table::TYPE_INTEGER,
            'length' => 10,
            'unsigned' => true,
            'comment' => 'Ref customer_entity(customer_id)'
        ]);
        $this->addForeignKey(
            'riki_advancedinventory_outofstock',
            'customer_id',
            'customer_entity',
            'entity_id',
            Table::ACTION_RESTRICT
        );
    }

    public function version109()
    {
        $this->addColumn('riki_advancedinventory_outofstock', 'store_id', [
            'type' => Table::TYPE_SMALLINT,
            'length' => 5,
            'unsigned' => true,
            'comment' => 'Ref store(store_id)'
        ]);
        $this->addForeignKey(
            'riki_advancedinventory_outofstock',
            'store_id',
            'store',
            'store_id'
        );
        $this->addColumn('riki_advancedinventory_outofstock', 'queue_execute', [
            'type' => Table::TYPE_SMALLINT,
            'length' => 1,
            'unsigned' => true,
            'comment' => 'Flag to detected queue to execute out of stock in case Product Back In Stock. ' .
                '1 - Waiting, 2 - Success, 3 - Error'
        ]);
    }

    public function version200()
    {
        $this->addColumn('riki_advancedinventory_outofstock', 'machine_customer_id', [
            'type' => Table::TYPE_INTEGER,
            'length' => 10,
            'unsigned' => true,
            'comment' => 'Ref riki_machine_customer(id)'
        ]);
        $this->addIndex('riki_advancedinventory_outofstock', ['machine_customer_id']);
    }

    public function version201()
    {
        $this->dropColumn('riki_advancedinventory_outofstock', 'machine_customer_id');
        $this->addColumn('riki_advancedinventory_outofstock', 'machine_sku_id', [
            'type' => Table::TYPE_INTEGER,
            'length' => 10,
            'unsigned' => true,
            'comment' => 'Ref riki_machine_sku(id)'
        ]);
        $this->addIndex('riki_advancedinventory_outofstock', ['machine_sku_id']);
    }

    /**
     * Update RbMQ config to RbMQ server
     */
    public function version204()
    {
        $this->topology->install();
    }

    /**
     * Migrate old data waiting for cron generate OOS to RbMQ
     */
    public function version205()
    {
        $connection = $this->getConnection('riki_advancedinventory_outofstock');
        $oosTableName = $this->getTable('riki_advancedinventory_outofstock');
        $querySelectWaitingOos = "SELECT entity_id FROM $oosTableName 
WHERE generated_order_id IS NULL AND queue_execute = 1;";
        $resultWaitingOos =  $connection->fetchCol($querySelectWaitingOos);
        if (empty($resultWaitingOos)) {
            return;
        }
        if ($resultWaitingOos) {
            try {
                foreach ($resultWaitingOos as $OosId) {
                    $outOfStockSchema = $this->oosQueueSchemaFactory->create();
                    $outOfStockSchema->setOosModelId($OosId);
                    $this->publisher->publish('oos.order.generate', $outOfStockSchema);
                }
            } catch (\Exception $e) {
                $connection->update($oosTableName, [
                    'queue_execute' => new \Zend_Db_Expr('NULL')
                ], "entity_id = $OosId");
            }
        }
    }

    public function version206()
    {
        $this->addColumn('riki_advancedinventory_outofstock', 'quote_item_data', [
            'type' => Table::TYPE_TEXT,
            'comment' => 'quote_item data on json format'
        ]);
    }

    public function version208()
    {
        $conn = $this->getConnection('advancedinventory_stock');
        $stockItemTb = $conn->getTableName('cataloginventory_stock_item');
        $stockTb = $conn->getTableName('advancedinventory_stock');
        $trgName = strtolower($conn->getTriggerName($stockTb, Trigger::TIME_AFTER, Trigger::EVENT_UPDATE));
        $conn->dropTrigger($trgName);
        $trigger = $this->triggerFactory->create()
            ->setName($trgName)
            ->setTime(Trigger::TIME_AFTER)
            ->setEvent(Trigger::EVENT_UPDATE)
            ->setTable($stockTb)
            ->addStatement(
                "UPDATE `{$stockItemTb}` SET `qty` = (SELECT SUM(`quantity_in_stock`) FROM `{$stockTb}` 
WHERE `product_id` = NEW.`product_id`) WHERE `product_id` = NEW.`product_id`"
            );
        $conn->createTrigger($trigger);
    }

    public function version210()
    {
        $conn = $this->getConnection('advancedinventory_stock');
        $stockTb = $conn->getTableName('advancedinventory_stock');
        $trgName = strtolower($conn->getTriggerName($stockTb, Trigger::TIME_AFTER, Trigger::EVENT_UPDATE));
        $conn->dropTrigger($trgName);
    }

    public function version211()
    {
        $this->addColumn('riki_advancedinventory_outofstock', 'quote_item_children_qty', [
            'type' => Table::TYPE_TEXT,
            'comment' => 'qty of children item data on json format'
        ]);
    }

    public function version220()
    {
        $table = $this->table('riki_advancedinventory_outofstock_children');

        $this->dropTable($table);

        $this->createTable($table, [
            [
                'parent_id',
                Table::TYPE_INTEGER,
                10,
                [
                    'unsigned' => true,
                    'nullable' => false,
                ],
                'Out Of Stock Item Id'
            ],
            [
                'product_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Product Id'
            ],
            [
                'qty',
                Table::TYPE_DECIMAL,
                null,
                ['default'  =>  0],
                'Qty',
            ],
        ]);

        $this->addIndex($table, ['parent_id']);
        $this->addIndex($table, ['product_id']);
        $this->addIndex($table, ['parent_id', 'product_id']);

        $this->addForeignKey($table, 'parent_id', $this->table('riki_advancedinventory_outofstock'), 'entity_id');

        $this->addIndex(
            $this->table('riki_advancedinventory_outofstock'),
            ['entity_id', 'qty', 'generated_order_id', 'quote_item_id', 'queue_execute', 'product_id']
        );
    }

    public function version230()
    {
        $connection = $this->getConnection('advancedinventory_assignation');

        $tableName = $this->table('riki_advancedinventory_reassignation');

        if ($connection->isTableExists($tableName)) {
            return;
        }

        $table = $connection->newTable(
            $tableName
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Re-assignation Entity Id'
        )->addColumn(
            'order_increment_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Order Increment Id'
        )->addColumn(
            'warehouse_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Warehouse code ("TOYO", "BIZEX", "HITACHI-TS", "LOGICALPLANT", "WH5", ...)'
        )->addColumn(
            'uploaded_by',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            40,
            ['nullable' => false],
            'The admin user that upload CSV file'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Uploaded Time'
        )->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
            'Last datetime updated for this record'
        )->addColumn(
            'message',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'In case of error, keep reason of failure'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Status (waiting, failure, success)'
        )->addIndex(
            $connection->getIndexName($tableName, ['order_increment_id']),
            ['order_increment_id']
        )->addIndex(
            $connection->getIndexName($tableName, ['warehouse_code']),
            ['warehouse_code']
        )->addIndex(
            $connection->getIndexName($tableName, ['status']),
            ['status']
        )->setComment(
            'Re-assign Warehouse History'
        );

        $connection->createTable($table);
    }

    public function version231()
    {
        $connection = $this->getConnection('advancedinventory_assignation');

        $tableName = $this->table('riki_advancedinventory_reassignation');

        if (!$connection->isTableExists($tableName)) {
            return;
        }

        $connection->modifyColumn(
            $tableName,
            'warehouse_code',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'length' => 255,
                'comment' => 'Warehouse code ("TOYO", "BIZEX", "HITACHI-TS", "LOGICALPLANT", "WH5", ...)',
            ]
        );
    }

    public function version240()
    {
        $connection = $this->getConnection('advancedinventory_assignation');

        $connection->addColumn(
            $connection->getTableName('riki_advancedinventory_outofstock'),
            'additional_data',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'comment' => 'additional data (json format)',
            ]
        );
    }
}
