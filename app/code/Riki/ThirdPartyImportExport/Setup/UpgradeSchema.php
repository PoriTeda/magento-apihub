<?php
// @codingStandardsIgnoreFile
namespace Riki\ThirdPartyImportExport\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\App\ResourceConnection;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private $eavSetupFactory;
    /**
     * @var ModuleDataSetupInterface
     */
    private $eavSetup;

    /**
     * @var  \Magento\Framework\DB\Adapter\Pdo\Mysql $_mysqlAdapter
     */
    protected $_mysqlAdapter;
    /**
     * @var Resource
     */
    protected $resource;
    /**
     * @var
     */
    protected $_connectionSales;

    /**
     * @var \Magento\Framework\DB\Ddl\TriggerFactory
     */
    protected $_triggerFactory;

    /**
     * UpgradeSchema constructor.
     *
     * @param EavSetupFactory $eavSetupFactory
     * @param ModuleDataSetupInterface $eavSetup
     * @param ResourceConnection $resource
     * @param \Magento\Amqp\Model\Topology $topology
     * @param \Magento\Framework\DB\Ddl\TriggerFactory $triggerFactory
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $eavSetup,
        ResourceConnection  $resource,
        \Magento\Amqp\Model\Topology $topology,
        \Magento\Framework\DB\Ddl\TriggerFactory $triggerFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavSetup = $eavSetup;
        $this->_mysqlAdapter = $resource->getConnection();
        $this->_connectionSales = $resource->getConnection('sales');
        $this->topology = $topology;
        $this->_triggerFactory = $triggerFactory;
    }

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {

        $setup->startSetup();

        $installer = $setup;
        $connection = $installer->getConnection();
        if (version_compare($context->getVersion(), '0.1.3') < 0) {
            $table = $installer->getTable('sales_order');
            $columns = [
                'flag_cvs' => [
                    'type' => Table::TYPE_INTEGER,
                    'length' => '1',
                    'nullable' => true,
                    'comment' => 'Flag for export order',
                    'default' => '0',
                ],
            ];
            foreach ($columns as $name => $definition) {
                $connection->addColumn($table, $name, $definition);
            }
        }

        if (version_compare($context->getVersion(), '0.2.0') < 0) {
            $schema = [
                'riki_order' => $this->getRikiOrderDefinition(),
                'riki_order_detail' => $this->getRikiOrderDetailDefinition(),
                'riki_shipping' => $this->getRikiShippingDefinition(),
                'riki_shipping_detail' => $this->getRikiShippingDetailDefinition()
            ];

            foreach ($schema as $name => $def) {
                $table = $connection->newTable($setup->getTable($name));
                foreach ($def as $col) {
                    $table->addColumn($col[0], $col[1], $col[2], $col[3], $col[4]);
                }
                $connection->createTable($table);
            }
        }

        if (version_compare($context->getVersion(), '0.2.1') < 0) {
            $table = [
                'riki_order' => $setup->getTable('riki_order'),
                'riki_order_detail' => $setup->getTable('riki_order_detail'),
                'riki_shipping' => $setup->getTable('riki_shipping'),
                'riki_shipping_detail' => $setup->getTable('riki_shipping_detail'),
            ];
            $connection->addForeignKey(
                $setup->getFkName(
                    $table['riki_order_detail'],
                    'order_no',
                    $table['riki_order'],
                    'order_no'
                ),
                $table['riki_order_detail'],
                'order_no',
                $table['riki_order'],
                'order_no'
            );
            $connection->addForeignKey(
                $setup->getFkName(
                    $table['riki_order_detail'],
                    'order_no',
                    $table['riki_order'],
                    'order_no'
                ),
                $table['riki_order_detail'],
                'order_no',
                $table['riki_order'],
                'order_no'
            );
            $connection->addForeignKey(
                $setup->getFkName(
                    $table['riki_shipping'],
                    'order_no',
                    $table['riki_order'],
                    'order_no'
                ),
                $table['riki_shipping'],
                'order_no',
                $table['riki_order'],
                'order_no'
            );
            $connection->addForeignKey(
                $setup->getFkName(
                    $table['riki_shipping_detail'],
                    'shipping_no',
                    $table['riki_shipping'],
                    'shipping_no'
                ),
                $table['riki_shipping_detail'],
                'shipping_no',
                $table['riki_shipping'],
                'shipping_no'
            );
        }
        if (version_compare($context->getVersion(), '0.2.2') < 0) {
            $table = $installer->getTable('sales_shipment');
            $columns = [
                'flag_shipment_complete' => [
                    'type' => Table::TYPE_INTEGER,
                    'length' => '1',
                    'nullable' => true,
                    'comment' => 'Flag for shipment complete export',
                    'default' => '0',
                ],
            ];
            foreach ($columns as $name => $definition) {
                $connection->addColumn($table, $name, $definition);
            }
        }


        if (version_compare($context->getVersion(), '0.2.3') < 0) {
            $table = [
                'riki_shipping' => $setup->getTable('riki_shipping'),
            ];
            $connection->changeColumn(
                $table['riki_shipping'],
                'delivery_appointed_date',
                'delivery_appointed_date',
                [
                    'type' => Table::TYPE_DATE,
                    'length' => null,
                    'comment' => 'delivery date specified by shopper nullable',
                    'nullable' => true
                ]
            );
        }

        if (version_compare($context->getVersion(), '0.2.4') < 0) {
            $table = [
                'riki_order' => $setup->getTable('riki_order'),
                'riki_order_detail' => $setup->getTable('riki_order_detail'),
                'riki_shipping' => $setup->getTable('riki_shipping'),
                'riki_shipping_detail' => $setup->getTable('riki_shipping_detail'),
            ];
            $connection->addIndex($table['riki_order'], $connection->getIndexName($table['riki_order'], ['customer_code']), ['customer_code']);
            $connection->addIndex($table['riki_shipping'], $connection->getIndexName($table['riki_shipping'], ['customer_code']), ['customer_code']);
        }

        if (version_compare($context->getVersion(), '0.2.5') < 0) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field = 'flag_export_bi';
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY,'flag_export_bi');
            //add flag export to bi
            $table = $installer->getTable('sales_order');
            $connection->addColumn($table,
                'flag_export_bi',
                [
                    'type' => Table::TYPE_INTEGER,
                    'length' => '1',
                    'nullable' => true,
                    'comment' => 'Flag for order complete export to bi',
                    'default' => '0',
                ]
            );
            $table = $installer->getTable('magento_giftwrapping');
            $connection->addColumn(
                $table,
                'flag_export_bi',
                [
                    'type' => Table::TYPE_INTEGER,
                    'length' => '1',
                    'nullable' => true,
                    'comment' => 'Flag for order complete export to bi',
                    'default' => '0',
                ]
            );

            if($installer->tableExists('riki_customer_enquiry_header')){
                $table = $installer->getTable('riki_customer_enquiry_header');
                $connection->addColumn(
                    $table,
                    'flag_export_bi',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'length' => '1',
                        'nullable' => true,
                        'comment' => 'Flag for order complete export to bi',
                        'default' => '0',
                    ]
                );
            }
            if($installer->tableExists('sales_shipment')){
                $table = $installer->getTable('sales_shipment');
                $connection->addColumn(
                    $table,
                    'flag_export_bi',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'length' => '1',
                        'nullable' => true,
                        'comment' => 'Flag for order complete export to bi',
                        'default' => '0',
                    ]
                );
            }
            /**
             * enquete
             */
            if($installer->tableExists('riki_enquete')){
                $table = $installer->getTable('riki_enquete');
                $connection->addColumn(
                    $table,
                    'flag_export_bi',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'length' => '1',
                        'nullable' => true,
                        'comment' => 'Flag for order complete export to bi',
                        'default' => '0',
                    ]
                );
            }
            if($installer->tableExists('riki_enquete_question')){
                $table = $installer->getTable('riki_enquete_question');
                $connection->addColumn(
                    $table,
                    'flag_export_bi',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'length' => '1',
                        'nullable' => true,
                        'comment' => 'Flag for order complete export to bi',
                        'default' => '0',
                    ]
                );
            }
            if($installer->tableExists('riki_enquete_question_choice')){
                $table = $installer->getTable('riki_enquete_question_choice');
                $connection->addColumn(
                    $table,
                    'flag_export_bi',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'length' => '1',
                        'nullable' => true,
                        'comment' => 'Flag for order complete export to bi',
                        'default' => '0',
                    ]
                );
            }
            if($installer->tableExists('riki_enquete_answer')){
                $table = $installer->getTable('riki_enquete_answer');
                $connection->addColumn(
                    $table,
                    'flag_export_bi',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'length' => '1',
                        'nullable' => true,
                        'comment' => 'Flag for order complete export to bi',
                        'default' => '0',
                    ]
                );
            }
            if($installer->tableExists('riki_enquete_answer_reply')){
                $table = $installer->getTable('riki_enquete_answer_reply');
                $connection->addColumn(
                    $table,
                    'flag_export_bi',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'length' => '1',
                        'nullable' => true,
                        'comment' => 'Flag for order complete export to bi',
                        'default' => '0',
                    ]
                );
            }
            if($installer->tableExists('riki_enquete_answer_reply')){
                $table = $installer->getTable('riki_enquete_answer_reply');
                $connection->addColumn(
                    $table,
                    'flag_export_bi',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'length' => '1',
                        'nullable' => true,
                        'comment' => 'Flag for order complete export to bi',
                        'default' => '0',
                    ]
                );
            }
            if($installer->tableExists('customer_entity')){
                $table = $installer->getTable('customer_entity');
                $connection->addColumn(
                    $table, 'flag_export_bi',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'length' => '1',
                        'nullable' => true,
                        'comment' => 'Flag for order complete export to bi',
                        'default' => '0',
                    ]
                );
            }
            if($installer->tableExists('catalog_product_entity')){
                $table = $installer->getTable('catalog_product_entity');
                $connection->addColumn(
                    $table, 'flag_export_bi',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'length' => '1',
                        'nullable' => true,
                        'comment' => 'Flag for order complete export to bi',
                        'default' => '0',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '0.2.6') < 0) {
            $table = [
                'riki_order' => $setup->getTable('riki_order'),
                'riki_order_detail' => $setup->getTable('riki_order_detail'),
                'riki_shipping' => $setup->getTable('riki_shipping'),
                'riki_shipping_detail' => $setup->getTable('riki_shipping_detail'),
            ];
            if ($connection->isTableExists($table['riki_order'])) {
                if ($connection->tableColumnExists($table['riki_order'], 'payment_date')) {
                    $connection->modifyColumn($table['riki_order'], 'payment_date', [
                        'type' => Table::TYPE_DATETIME,
                        'nullable' => true
                    ]);
                }
                if ($connection->tableColumnExists($table['riki_order'], 'payment_limit_date')) {
                    $connection->modifyColumn($table['riki_order'], 'payment_limit_date', [
                        'type' => Table::TYPE_DATETIME,
                        'nullable' => true
                    ]);
                }
                if ($connection->tableColumnExists($table['riki_order'], 'credit_date')) {
                    $connection->modifyColumn($table['riki_order'], 'credit_date', [
                        'type' => Table::TYPE_DATETIME,
                        'nullable' => true
                    ]);
                }
            }
            if ($connection->isTableExists($table['riki_shipping'])) {
                if ($connection->tableColumnExists($table['riki_shipping'], 'delivery_appointed_date')) {
                    $connection->modifyColumn($table['riki_shipping'], 'delivery_appointed_date', [
                        'type' => Table::TYPE_DATETIME,
                        'nullable' => true
                    ]);
                }
                if ($connection->tableColumnExists($table['riki_shipping'], 'arrival_date')) {
                    $connection->modifyColumn($table['riki_shipping'], 'arrival_date', [
                        'type' => Table::TYPE_DATETIME,
                        'nullable' => true
                    ]);
                }
                if ($connection->tableColumnExists($table['riki_shipping'], 'delivery_date')) {
                    $connection->modifyColumn($table['riki_shipping'], 'delivery_date', [
                        'type' => Table::TYPE_DATETIME,
                        'nullable' => true
                    ]);
                }
                if ($connection->tableColumnExists($table['riki_shipping'], 'shipping_direct_date')) {
                    $connection->modifyColumn($table['riki_shipping'], 'shipping_direct_date', [
                        'type' => Table::TYPE_DATETIME,
                        'nullable' => true
                    ]);
                }
                if ($connection->tableColumnExists($table['riki_shipping'], 'shipping_date')) {
                    $connection->modifyColumn($table['riki_shipping'], 'shipping_date', [
                        'type' => Table::TYPE_DATETIME,
                        'nullable' => true
                    ]);
                }
                if ($connection->tableColumnExists($table['riki_shipping'], 'return_item_date')) {
                    $connection->modifyColumn($table['riki_shipping'], 'return_item_date', [
                        'type' => Table::TYPE_DATETIME,
                        'nullable' => true
                    ]);
                }
            }
        }

        if (version_compare($context->getVersion(), '0.2.7') < 0) {
            $table = $setup->getConnection()->newTable($setup->getTable('riki_sales_order_grid'))
            ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'ID'
                )
                ->addColumn(
                    'order_no',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Order No (unique order_no)'
                )
                ->addColumn(
                    'ship_name',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => true],
                    'customer shipping name'
                )
                ->addIndex(
                    $setup->getIdxName('riki_sales_order_grid',['order_no']),
                    ['order_no']
                )
                ->addIndex(
                    $setup->getIdxName('riki_sales_order_grid',['id']),
                    ['id']
                );
            $setup->getConnection()->createTable($table);

        }

        if (version_compare($context->getVersion(), '0.2.8') < 0) {
            if ($setup->tableExists('riki_sales_order_grid')) {
                $table = $setup->getTable('riki_sales_order_grid');
                $setup->getConnection()->addColumn(
                    $table,'bill_name',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => '255',
                        'nullable' => true,
                        'comment' => 'Bill Name'
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '0.2.9') < 0) {
            if ($setup->tableExists('advancedinventory_stock')) {
                $table = $setup->getTable('advancedinventory_stock');
                $setup->getConnection()->addColumn(
                    $table,'update_at',
                    [
                        'type' => Table::TYPE_TIMESTAMP,
                        'comment' => 'Last init/update timestamp',
                        'default' => Table::TIMESTAMP_INIT_UPDATE
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '0.2.10') < 0) {
            if ($setup->tableExists('advancedinventory_stock')) {
                $trigger = $this->_triggerFactory->create();
                $trigger->setName('riki_trigger_stock_update_at');
                $trigger->setEvent(Trigger::EVENT_UPDATE);
                $trigger->setTime(Trigger::TIME_BEFORE);
                $trigger->setTable('advancedinventory_stock');
                $trigger->addStatement('
                BEGIN
                IF (NEW.quantity_in_stock <> OLD.quantity_in_stock) THEN
                SET NEW.update_at = CURRENT_TIMESTAMP();
                END IF;
                END;');
                $this->_mysqlAdapter->createTrigger($trigger);

            }
        }

        if (version_compare($context->getVersion(), '0.3.0') < 0) {
            $table = $setup->getTable('riki_order');

            if($setup->tableExists($table) && !$connection->tableColumnExists($table, 'grand_total')){
                $connection->addColumn($table, 'grand_total', [
                    'type' => Table::TYPE_DECIMAL,
                    'length' => '12,4',
                    'comment' => 'grand total',
                    'default' => 0
                ]);
            }
        }

        if (version_compare($context->getVersion(), '0.3.2') < 0) {
            $table = $setup->getTable('queue');

            if($setup->tableExists($table)){
                $setup->run("DELETE FROM {$table} WHERE name = 'inventory_qty_counter_queue' ");
                $setup->run("DELETE FROM {$table} WHERE name = 'sender_queue_next_order_subscription_profile' ");
                $setup->run("DELETE FROM {$table} WHERE name = 'sender_queue_next_order_subscription_profile_cart' ");
                $setup->run("DELETE FROM {$table} WHERE name = 'sender_queue_next_shipment_subscription_profile' ");
                $setup->run("DELETE FROM {$table} WHERE name = 'sender_queue_next_shipment_subscription_profile_detail' ");

                $aQueue = [
                    ['name' => 'sender_queue_next_order_subscription_profile'],
                    ['name' =>'sender_queue_next_order_subscription_profile_cart'],
                    ['name' =>'sender_queue_next_shipment_subscription_profile'],
                    ['name' =>'sender_queue_next_shipment_subscription_profile_detail'],
                ];
                foreach($aQueue as $queue){
                    $setup->getConnection()->insert($setup->getTable('queue'), $queue);
                }
            }
        }

        if (version_compare($context->getVersion(), '0.3.3') < 0) {

            $table = $setup->getTable('magento_rma');

            if ($setup->tableExists($table)) {

                $isCedynaExported = 'is_cedyna_exported';

                $connection = $setup->getConnection();

                if (!$connection->tableColumnExists($table, $isCedynaExported)) {
                    $connection->addColumn(
                        $table, $isCedynaExported,
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN, null,
                            'default'   =>  0,
                            'comment' => 'Is exported to Cedyna?',
                        ]
                    );
                }
            }
        }
        if (version_compare($context->getVersion(), '0.3.4') < 0) {
            $columns = [
                'is_mm_exported' => [
                    'type' => Table::TYPE_INTEGER,
                    'length' => '1',
                    'nullable' => true,
                    'comment' => 'Flag for shipment belong mm order report',
                    'default' => '0',
                ],
            ];
            foreach ($columns as $name => $definition) {
                $this->_connectionSales->addColumn('sales_shipment', $name, $definition);
            }
        }
        if (version_compare($context->getVersion(), '0.3.5') < 0) {
            $columns = [
                'is_reconciliation_exported' => [
                    'type' => Table::TYPE_INTEGER,
                    'length' => '1',
                    'nullable' => true,
                    'comment' => 'Flag for export to Reconciliation',
                    'default' => '0',
                ],
            ];
            foreach ($columns as $name => $definition) {
                $this->_connectionSales->addColumn('sales_shipment', $name, $definition);
            }
        }

        /**
         * Add flag to export order protion
         *
         */
        if (version_compare($context->getVersion(), '0.3.6') < 0) {

            $columns = [
                'is_promotion_exported' => [
                    'type' => Table::TYPE_INTEGER,
                    'length' => '1',
                    'nullable' => true,
                    'comment' => 'Flag for export promotion ',
                    'default' => '0',
                ],
            ];
            foreach ($columns as $name => $definition) {
                $this->_connectionSales->addColumn('sales_order', $name, $definition);
            }
            // reset value before 
            $table = $this->_connectionSales->getTableName('sales_order');
            $bind = [ 'is_promotion_exported' => 1 ];
            $this->_connectionSales->update($table, $bind);
        }

        /**
         * Add flag to export order protion
         *
         */
        if (version_compare($context->getVersion(), '0.3.7') < 0) {
            $setup->getConnection()->delete($setup->getTable('queue'), ['name = ?' => 'sender_queue_next_order_subscription_profile']);
            $setup->getConnection()->delete($setup->getTable('queue'), ['name = ?' => 'sender_queue_next_order_subscription_profile_cart']);
            $setup->getConnection()->delete($setup->getTable('queue'), ['name = ?' => 'sender_queue_next_shipment_subscription_profile']);
            $setup->getConnection()->delete($setup->getTable('queue'), ['name = ?' => 'sender_queue_next_shipment_subscription_profile_detail']);
            $this->topology->install();
        }

        if (version_compare($context->getVersion(), '0.3.8') < 0) {
            $setup->getConnection()->dropTable('riki_sales_order_grid');
        }

        if (version_compare($context->getVersion(), '0.3.9') < 0) {
            $this->topology->install();
        }

        if (version_compare($context->getVersion(), '0.3.10') < 0) {
            $this->version0310($setup);
        }

        if (version_compare($context->getVersion(), '0.3.11') < 0) {
            $this->version0311();
        }

        if (version_compare($context->getVersion(), '0.3.12') < 0) {
            $this->version0312();
        }

        if (version_compare($context->getVersion(), '0.3.13') < 0) {
            $this->version0313();
        }

        $setup->endSetup();
    }

    public function getRikiOrderDefinition()
    {
        return [
            ['order_no', Table::TYPE_TEXT, 16, ['nullable' => false, 'primary' => true], 'Unique id for each order. Created by system, according to sequence'],
            ['shop_code', Table::TYPE_TEXT, 16, ['nullable' => false], 'fixed value "00000000"'],
            ['order_datetime', Table::TYPE_DATETIME, null, ['nullable' => false], 'order created datetime'],
            ['customer_code', Table::TYPE_TEXT, 16, ['nullable' => false], 'customcer code(unique consumer_id)'],
            ['guest_flg', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'unsigned' => true], ' '],
            ['last_name', Table::TYPE_TEXT, 25, ['nullable' => false], 'last name of customer(billing address)'],
            ['first_name', Table::TYPE_TEXT, 25, ['nullable' => false], 'first_name name of customer(billing address)'],
            ['last_name_kana', Table::TYPE_TEXT, 40, ['nullable' => false], 'last name of customer(billing address)'],
            ['first_name_kana', Table::TYPE_TEXT, 40, ['nullable' => false], 'first name of customer(billing address)'],
            ['email', Table::TYPE_TEXT, 256, ['nullable' => false], 'email'],
            ['send_email_no', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'default' => 1, 'unsigned' => true], 'e-mail no (in current system, customer has 2 e-mail address sometime ,in this case, user choose 1 e-mail when place an order)'],
            ['postal_code', Table::TYPE_TEXT, 7, ['nullable' => false], 'zip code of customer(billing address)'],
            ['prefecture_code', Table::TYPE_TEXT, 2, ['nullable' => false], '	prefecture code ("01"-"47")'],
            ['address1', Table::TYPE_TEXT, 4, ['nullable' => false], 'prefecure name of customer(billing address)'],
            ['address2', Table::TYPE_TEXT, 100, ['nullable' => false], 'address of customer without prefecture(billing address)'],
            ['address3', Table::TYPE_TEXT, 50, [], 'basically is not used'],
            ['address4', Table::TYPE_TEXT, 100, [], 'basically is not used'],
            ['phone_number', Table::TYPE_TEXT, 16, ['nullable' => false], 'phone number (billing address)'],
            ['free_shipping_flag', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'default' => 0, 'unsigned' => true], 'free order flg(1=free order 0=normal)'],
            ['reason_code', Table::TYPE_TEXT, 4, [], 'reason code for free order'],
            ['owabijou_type', Table::TYPE_TEXT, 4, [], 'unused'],
            ['store_code', Table::TYPE_TEXT, 12, [], 'GMS code (when payment method is paid at store)'],
            ['advance_later_flg', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'unsigned' => true], 'depend on payment method (Advance:0 later:1)'],
            ['payment_method_no', Table::TYPE_INTEGER, 8, ['nullable' => false, 'unsigned' => true], 'payment method no'],
            ['payment_method_type', Table::TYPE_TEXT, 2, ['nullable' => false], 'payment method type(NO_PAYMENT:00,POINT_IN_FULL:01,CASH_ON_DELIVERY:02,BANKING:
03,CREDITCARD:04,CVS_PAYMENT:05,DIGITAL_CASH:06,CVS_INTO:07,DIRECT:08)'],
            ['payment_method_name', Table::TYPE_TEXT, 25, [], 'payment method japanese name (display at online)'],
            ['payment_commission', Table::TYPE_INTEGER, 8, ['nullable' => false, 'unsigned' => true], 'payment commission amount'],
            ['payment_commission_tax_rate', Table::TYPE_SMALLINT, 3, ['unsigned' => true], 'payment commission tax rate'],
            ['payment_commission_tax', Table::TYPE_INTEGER, 8, ['unsigned' => true], 'payment commission tax amount'],
            ['payment_commission_tax_type', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'unsigned' => true], 'payment commission tax type(NO_TAX:0,EXCLUDED:1,INCLUDED:2)'],
            ['used_point', Table::TYPE_INTEGER, 8, ['unsigned' => true], 'used point for each order'],
            ['bonus_point_amount', Table::TYPE_INTEGER, 8, ['unsigned' => true], 'bonus point amount for each user'],
            ['point_sheet_number', Table::TYPE_TEXT, 20, [], 'unused'],
            ['attach_point_mark', Table::TYPE_INTEGER, 11, ['unsigned' => true], 'unused'],
            ['payment_date', Table::TYPE_DATE, null, [], 'payment complete date'],
            ['payment_limit_date', Table::TYPE_DATE, null, [], 'cvs payment due date'],
            ['payment_status', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'unsigned' => true], 'payment status(finished:1 unfinished:0)'],
            ['payment_money', Table::TYPE_INTEGER, 8, ['unsigned' => true], 'payment_amount'],
            ['purchasing_customer_type', Table::TYPE_SMALLINT, 1, ['unsigned' => true], 'unused'],
            ['customer_group_code', Table::TYPE_TEXT, 16, [], 'unused'],
            ['data_transport_status', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'unsigned' => true], 'unused(1)'],
            ['order_status', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'unsigned' => true], 'order status(0:pre-order, 1:ordinary, 2:canceled)'],
            ['order_type', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'default' => 0, 'unsigned' => true], 'where this order created(NONE:0,POSTCARD:1,PHONE:2,FAX:3,MAIL:4,PC_FRONT:5,MOBILE_FRONT:6)'],
            ['office_order_flg', Table::TYPE_SMALLINT, 1, ['default' => 0, 'unsigned' => true], 'office order flg(0: ordinary order, 1:office order( invoice))'],
            ['client_group', Table::TYPE_TEXT, 2, ['nullable' => false], 'fixed ("90")'],
            ['caution', Table::TYPE_TEXT, 1000, [], 'caution description'],
            ['message', Table::TYPE_TEXT, 1000, [], 'message description'],
            ['payment_order_id', Table::TYPE_INTEGER, null, ['unsigned' => true], 'paygent transaction id'], //! length in document is 38?
            ['credit_date', Table::TYPE_DATE, null, [], 'creditcard authorized date'],
            ['credit_status', Table::TYPE_SMALLINT, 1, ['unsigned' => true], 'creditcard authorized status(null:normal, EXTEND_FAILURE:1, TRANSPORT_FAILURE:2)'],
            ['cvs_code', Table::TYPE_TEXT, 2, [], 'unused'],
            ['payment_recepit_no', Table::TYPE_TEXT, 50, [], 'unused'],
            ['payment_recepit_url', Table::TYPE_TEXT, 500, [], 'unused'],
            ['digital_cash_type', Table::TYPE_TEXT, 2, [], 'unused'],
            ['transfer_form_flg', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'unsigned' => true], 'slip exported flg(0: unexported, 1:exported)'],
            ['form_issue_count', Table::TYPE_SMALLINT, 1, ['nullable' => false], 'slip exported time'],
            ['creditpayment_erasing_flg', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'default' => 0, 'unsigned' => true], 'creditcard captched flg'],
            ['creditpayment_count', Table::TYPE_SMALLINT, 2, ['unsigned' => true], 'unused'],
            ['plan_no', Table::TYPE_TEXT, 16, [], 'subscription number'],
            ['plan_type', Table::TYPE_SMALLINT, 1, ['unsigned' => true], 'subscription type(0:subscription, 1:hanpukai)'],
            ['order_count', Table::TYPE_SMALLINT, 3, ['default' => 0, 'unsigned' => true], 'subscription count'],
            ['mastar_sku_code', Table::TYPE_TEXT, 24, [], 'subscription course code'],
            ['discount_rate', Table::TYPE_SMALLINT, 3, ['unsigned' => true], 'subscription discount rate'],
            ['giftaway_exchange_flg', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'default' => 0, 'unsigned' => true], 'unused'],
            ['warning_message', Table::TYPE_TEXT, 100, [], 'unused'],
            ['orm_rowid', Table::TYPE_INTEGER, null, ['nullable' => false, 'unsigned' => true], 'rowid (for hibernate)'], //! length in document is 38?
            ['created_user', Table::TYPE_TEXT, 100, ['nullable' => false], 'user who created the data'],
            ['created_datetime', Table::TYPE_DATETIME, null, ['nullable' => false], 'data created datetime'],
            ['updated_user', Table::TYPE_TEXT, 100, ['nullable' => false], 'user who updated the data'],
            ['updated_datetime', Table::TYPE_DATETIME, null, ['nullable' => false], 'data updated datetime'],
            ['credit_agency_type', Table::TYPE_SMALLINT, 1, ['unsigned' => true], 'creditcard agency type(0:Veritrans 1:Paygent null:Veritrans)'],
            ['acq_id', Table::TYPE_TEXT, 5, [], 'creditcard acquirer code(99661:JCB, 5001:NICOS)']
        ];
    }

    public function getRikiOrderDetailDefinition()
    {
        return [
            ['order_no', Table::TYPE_TEXT, 16, ['nullable' => false, 'primary' => true], 'unique id for each order. Created by system, according to oracle sequence'],
            ['shop_code', Table::TYPE_TEXT, 16, ['nullable' => false, 'primary' => true], 'fixed value "00000000"'],
            ['sku_code', Table::TYPE_TEXT, 24, ['nullable' => false, 'primary' => true], 'sku code'],
            ['commodity_code', Table::TYPE_TEXT, 16, ['nullable' => false], 'product_code (same as sku_code)'],
            ['commodity_name', Table::TYPE_TEXT, 50, ['nullable' => false], 'product name'],
            ['standard_detail1_name', Table::TYPE_TEXT, 20, [], 'unused'],
            ['standard_detail2_name', Table::TYPE_TEXT, 20, [], 'unused'],
            ['purchasing_amount', Table::TYPE_INTEGER, 8, ['nullable' => false, 'unsigned' => true], 'the amount for each purchased products'],
            ['attach_amount', Table::TYPE_INTEGER, 8, ['nullable' => false, 'default' => 0, 'unsigned' => true], 'the amount of free atached products'],
            ['unit_price', Table::TYPE_INTEGER, 8, ['nullable' => false, 'unsigned' => true], 'basic price'],
            ['retail_price', Table::TYPE_INTEGER, 8, ['nullable' => false, 'unsigned' => true], 'purchased price'],
            ['retail_tax', Table::TYPE_INTEGER, 8, ['nullable' => false, 'unsigned' => true], 'purchased price tax'],
            ['commodity_tax_rate', Table::TYPE_SMALLINT, 3, ['unsigned' => true], 'tax rate'],
            ['commodity_tax', Table::TYPE_INTEGER, 8, ['unsigned' => true], 'tax amount'],
            ['commodity_tax_type', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'unsigned' => true], 'tax type(NO_TAX:0,EXCLUDED:1,INCLUDED:2)'],
            ['commodity_kbn', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'default' => 1], 'product type(1:USUALLY)'],
            ['campaign_code', Table::TYPE_TEXT, 16, [], 'applied campaign code'],
            ['campaign_name', Table::TYPE_TEXT, 100, [], 'applied campaign name'],
            ['campaign_discount_rate', Table::TYPE_SMALLINT, 3, ['unsigned' => true], 'applied campaign discount rate'],
            ['shipping_free_campaign_flg', Table::TYPE_SMALLINT, 1, ['unsigned' => true], 'applied campaign shipping charge free flg'],
            ['applied_point_rate', Table::TYPE_INTEGER, 8, ['nullable' => false, 'unsigned' => true], 'applied point rate for price'],
            ['used_point_limit_rate', Table::TYPE_SMALLINT, 3, ['default' => 0, 'unsigned' => true], 'applied max point use rate for price'],
            ['applied_point_amount', Table::TYPE_INTEGER, 8, ['default' => 0, 'unsigned' => true], 'unused'],
            ['commodity_length', Table::TYPE_SMALLINT, 3, ['unsigned' => true], 'length'],
            ['commodity_width', Table::TYPE_SMALLINT, 3, ['unsigned' => true], 'width'],
            ['commodity_high', Table::TYPE_SMALLINT, 3, ['unsigned' => true], 'high'],
            ['commodity_weight', Table::TYPE_SMALLINT, 4, ['unsigned' => true], 'weight'],
            ['sale_organization_code', Table::TYPE_TEXT, 4, [], 'sales organization code.product(COMMODITY_HEADER) has this information.'],
            ['sap_commodity_code', Table::TYPE_TEXT, 16, [], 'sap sku code'],
            ['sap_unit_price', Table::TYPE_INTEGER, 8, ['unsigned' => true], 'sap price'],
            ['distribution_detail_flg', Table::TYPE_SMALLINT, 1, ['default' => 0, 'unsigned' => true], 'hanpukai products flg(USUALLY :0, DISTRIBUTION:1, DISCOUNT:2)'],
            ['commission_rate', Table::TYPE_DECIMAL, '5,2', ['default' => 0], 'nvoice payment commission rate'],
            ['orm_rowid', Table::TYPE_BIGINT, null, ['nullable' => false, 'unsigned' => true], 'rowid (for hibernate)'],
            ['created_user', Table::TYPE_TEXT, 100, ['nullable' => false], 'user who created the data'],
            ['created_datetime', Table::TYPE_DATETIME, null, ['nullable' => false], 'data created datetime'],
            ['updated_user', Table::TYPE_TEXT, 100, ['nullable' => false], 'user who updated the data'],
            ['updated_datetime', Table::TYPE_DATETIME, null, ['nullable' => false], 'data updated datetime']
        ];
    }

    public function getRikiShippingDefinition()
    {
        return [
            ['shipping_no', Table::TYPE_TEXT, 16, ['nullable' => false, 'primary' => true], 'unique id for each shipping. Created by system, according to oracle sequence'],
            ['order_no', Table::TYPE_TEXT, 16, ['nullable' => false], 'order number'],
            ['shop_code', Table::TYPE_TEXT, 16, ['nullable' => false], 'fixed value "00000000"'],
            ['customer_code', Table::TYPE_TEXT, 16, [], 'customcer code(unique consumer_id)'],
            ['address_no', Table::TYPE_INTEGER, 8, ['unsigned' => true], 'address_no(0:home, 1:company, other:other)'],
            ['address_last_name', Table::TYPE_TEXT, 25, ['nullable' => false], 'last name of delivery address'],
            ['address_first_name', Table::TYPE_TEXT, 25, ['nullable' => false], 'first name of delivery address'],
            ['address_last_name_kana', Table::TYPE_TEXT, 40, ['nullable' => false], 'last name of delivery address'],
            ['address_first_name_kana', Table::TYPE_TEXT, 40, ['nullable' => false], 'first name of delivery address'],
            ['postal_code', Table::TYPE_TEXT, 7, ['nullable' => false], 'zip code of delivery address'],
            ['prefecture_code', Table::TYPE_TEXT, 2, ['nullable' => false], 'delivery prefecture code ("01"-"47")'],
            ['address1', Table::TYPE_TEXT, 4, ['nullable' => false], 'prefecure name of delivery address'],
            ['address2', Table::TYPE_TEXT, 100, ['nullable' => false], 'delivery address without prefecture'],
            ['address3', Table::TYPE_TEXT, 50, [], 'basically is not used'],
            ['address4', Table::TYPE_TEXT, 100, [], 'basically is not used'],
            ['phone_number', Table::TYPE_TEXT, 16, [], 'phone number (delivery address)'],
            ['delivery_remark', Table::TYPE_TEXT, 500, [], 'delivery description registered by operator'],
            ['acquired_point', Table::TYPE_INTEGER, 8, ['unsigned' => true], 'sum of tentative/acquired shopping point'],
            ['delivery_slip_no', Table::TYPE_TEXT, 30, [], 'delivery slip no(shopper can track his/her delivery by this number)'],
            ['shipping_charge', Table::TYPE_INTEGER, 8, ['nullable' => false, 'unsigned' => true], 'delivery fee'],
            ['shipping_charge_tax_type', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'unsigned' => true], 'delivery fee tax rate'],
            ['shipping_charge_tax_rate', Table::TYPE_SMALLINT, 3, ['unsigned' => true], 'delivery fee tax mount'],
            ['shipping_charge_tax', Table::TYPE_INTEGER, 8, ['unsigned' => true], 'delivery fee tax type(NO_TAX:0,EXCLUDED:1,INCLUDED:2)'],
            ['shipping_charge_free_flg', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'unsigned' => true, 'default' => 1], 'delivery fee flg (0:free 1: not free)'],
            ['delivery_type_no', Table::TYPE_INTEGER, 8, ['nullable' => false, 'unsigned' => true], 'delivery type no, identifying warehouse and delivery type'],
            ['delivery_type_name', Table::TYPE_TEXT, 40, [], 'delivery type name'],
            ['delivery_appointed_date', Table::TYPE_DATE, null, ['nullable' => false], 'delivery date specified by shopper'],
            ['delivery_appointed_time_start', Table::TYPE_SMALLINT, 2, ['unsigned' => true], 'delivery time specified by shopper(from)'],
            ['delivery_appointed_time_end', Table::TYPE_SMALLINT, 2, ['unsigned' => true], 'delivery time specified by shopper(to)'],
            ['arrival_date', Table::TYPE_DATE, null, [], 'unused'],
            ['arrival_time_start', Table::TYPE_SMALLINT, 2, ['unsigned' => true], 'unused'],
            ['arrival_time_end', Table::TYPE_SMALLINT, 2, ['unsigned' => true], 'unused'],
            ['delivery_date', Table::TYPE_DATE, null, [], 'delivery complition date'],
            ['sitadori_kbn', Table::TYPE_SMALLINT, 1, ['unsigned' => true, 'default' => 0], 'sitadori type(DISABLED:0, ENABLED:1) long ago, when shopper provide old machine to nestle, can purchase new machine by discount price'],
            ['sitadori_commodity_type', Table::TYPE_TEXT, 2, [], 'unused'],
            ['fixed_sales_status', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'unsigned' => true], 'after shippment, this status is changed to "1:fixed"'],
            ['shipping_status', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'unsigned' => true], '(NOT_READY:0, READY:1, IN_PROCESSING:2, SHIPPED:3, CANCELLED:4,)'],
            ['shipping_direct_date', Table::TYPE_DATE, null, [], 'date when shipping order will be exported to be sent to WMS'],
            ['shipping_date', Table::TYPE_DATE, null, [], 'shipping complition date'],
            ['shipping_list_flg', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'unsigned' => true], 'shipping order exported flg(0:unexported 1:exported)'],
            ['original_shipping_no', Table::TYPE_BIGINT, 16, ['unsigned' => true], 'original shipping no (only when this shipping data is return data)'],
            ['giftaway_shipping_flg', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'unsigned' => true], 'unused'],
            ['return_item_date', Table::TYPE_DATE, null, [], 'rerurn process complition date (only when this shipping data is return data)'],
            ['return_item_loss_money', Table::TYPE_INTEGER, 8, ['unsigned' => true], 'return shipping fee, COD fee and so on (only when this shipping data is return data)'],
            ['return_item_type', Table::TYPE_SMALLINT, 1, ['unsigned' => true], 'return flg(0: NORMAL, 1:RETURN)'],
            ['sap_ren_flg', Table::TYPE_SMALLINT, 1, ['unsigned' => true], 'sap sending complition flg(1:FINISH)'],
            ['stock_ren_flg', Table::TYPE_SMALLINT, 1, ['unsigned' => true], 'unused'],
            ['ship_ren_flg', Table::TYPE_SMALLINT, 1, ['unsigned' => true], '(1:exported, 2:ongoing, 9:error)'],
            ['bill_ren_flg', Table::TYPE_SMALLINT, 1, ['unsigned' => true], 'invoice billing finish flg(0: unfinished, 1:finished)'],
            ['order_modify_flg', Table::TYPE_SMALLINT, 1, ['unsigned' => true], 'order modification flg (1:modified, null unmodified)'],
            ['orm_rowid', Table::TYPE_BIGINT, null, ['nullable' => false, 'unsigned' => true], 'rowid (for hibernate)'],
            ['created_user', Table::TYPE_TEXT, 100, ['nullable' => false], 'user who created the data'],
            ['created_datetime', Table::TYPE_DATETIME, null, ['nullable' => false], 'data created datetime'],
            ['updated_user', Table::TYPE_TEXT, 100, ['nullable' => false], 'user who updated the data'],
            ['updated_datetime', Table::TYPE_DATETIME, null, ['nullable' => false], 'data updated datetime'],
            ['warehouse_code', Table::TYPE_SMALLINT, 5, ['unsigned' => true], 'warehouse code (related to WAREHOUSE table)'],
            ['delivery_company_code', Table::TYPE_TEXT, 3, [], 'delivery company code (related to DELIVERY_COMPANY table)'],
        ];
    }

    public function getRikiShippingDetailDefinition()
    {
        return [
            ['shipping_no', Table::TYPE_TEXT, 16, ['nullable' => false, 'primary' => true], 'unique id for each shipping. Created by system, according to oracle sequence'],
            ['shipping_detail_no', Table::TYPE_BIGINT, 16, ['nullable' => false, 'primary' => true], 'sequence id in shipping no'],
            ['shop_code', Table::TYPE_TEXT, 16, ['nullable' => false], 'fixed value "00000000"'],
            ['sku_code', Table::TYPE_TEXT, 24, ['nullable' => false], 'product code'],
            ['unit_price', Table::TYPE_INTEGER, 8, ['unsigned' => true], 'basic price'],
            ['discount_price', Table::TYPE_INTEGER, 8, ['unsigned' => true], 'unused'],
            ['discount_amount', Table::TYPE_INTEGER, 8, ['unsigned' => true], 'discount amount'],
            ['retail_price', Table::TYPE_INTEGER, 8, ['unsigned' => true], 'purchased price'],
            ['retail_tax', Table::TYPE_INTEGER, 8, ['unsigned' => true], 'purchased price tax'],
            ['shipping_charge_target_flg', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'unsigned' => true], 'deivery fee free flg(1:free , 0:not free)'],
            ['purchasing_amount', Table::TYPE_INTEGER, 8, ['nullable' => false, 'unsigned' => true], 'the amount for each purchased products'],
            ['gift_code', Table::TYPE_TEXT, 16, [], 'wrapping code'],
            ['gift_name', Table::TYPE_TEXT, 40, [], 'wrapping name'],
            ['gift_price', Table::TYPE_INTEGER, 8, ['nullable' => false, 'unsigned' => true], 'wrapping price'],
            ['gift_tax_rate', Table::TYPE_SMALLINT, 3, ['unsigned' => true], 'wrapping tax rate'],
            ['gift_tax', Table::TYPE_INTEGER, 8, ['unsigned' => true], 'wrapping tax mount'],
            ['gift_tax_type', Table::TYPE_SMALLINT, 1, ['nullable' => false, 'unsigned' => true], 'wrapping tax type(NO_TAX:0,EXCLUDED:1,INCLUDED:2)'],
            ['message_card_price', Table::TYPE_INTEGER, 8, ['unsigned' => true], 'unused'],
            ['message_card_tax_type', Table::TYPE_SMALLINT, 1, ['unsigned' => true], 'unused'],
            ['message_card_tax_rate', Table::TYPE_SMALLINT, 3, ['unsigned' => true], 'unused'],
            ['message_card_tax', Table::TYPE_INTEGER, 8, ['unsigned' => true], 'unused'],
            ['message_card_code', Table::TYPE_TEXT, 16, [], 'unused'],
            ['message_card_name', Table::TYPE_TEXT, 40, [], 'unused'],
            ['message_card_addresses', Table::TYPE_TEXT, 40, [], 'unused'],
            ['message_card_text', Table::TYPE_TEXT, 200, [], 'unused'],
            ['message_card_sender', Table::TYPE_TEXT, 40, [], 'unused'],
            ['attach_type', Table::TYPE_SMALLINT, 1, ['unsigned' => true, 'default' => 0], 'free atache flg(1:free attach, 0: purchased)'],
            ['distribution_detail_flg', Table::TYPE_SMALLINT, 1, ['unsigned' => true, 'default' => 0], 'hanpukai products flg(USUALLY :0, DISTRIBUTION:1, DISCOUNT:2)'],
            ['sitadori_money', Table::TYPE_INTEGER, 8, ['unsigned' => true], 'unused'],
            ['orm_rowid', Table::TYPE_BIGINT, null, ['nullable' => false, 'unsigned' => true], 'rowid (for hibernate)'],
            ['created_user', Table::TYPE_TEXT, 100, ['nullable' => false], 'user who created the data'],
            ['created_datetime', Table::TYPE_DATETIME, null, ['nullable' => false], 'data created datetime'],
            ['updated_user', Table::TYPE_TEXT, 100, ['nullable' => false], 'user who updated the data'],
            ['updated_datetime', Table::TYPE_DATETIME, null, ['nullable' => false], 'data updated datetime'],
        ];
    }

    public function version0310($setup)
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection*/
        $connection = $this->_connectionSales;
        $tableName = $connection->getTableName('sales_order_item');
        if ($connection->isTableExists($tableName)) {
            if ($connection->tableColumnExists($tableName, 'parent_item_id')) {
                $connection->addIndex(
                    $tableName,
                    $connection->getIndexName($tableName, ['parent_item_id']),
                    ['parent_item_id']
                );
            }
        }
    }

    public function version0311()
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection*/
        $connection = $this->_connectionSales;

        $shipmentTable = $connection->getTableName('sales_shipment');
        $orderTable = $connection->getTableName('sales_order');
        $paymentTable = $connection->getTableName('sales_order_payment');

        $columnName = 'flag_export_invoice_sales_shipment';

        if ($connection->isTableExists($shipmentTable)) {
            if ($connection->tableColumnExists($shipmentTable, $columnName)) {

                /*change default value for flag_export_invoice_sales_shipment => 2*/

                $connection->modifyColumn($shipmentTable, $columnName, [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'default' => 2,
                    'comment' => 'Flag export invoice sales shipment'
                ]);

                /*update default value for flag_export_invoice_sales_shipment => 2*/
                $updateQuery = 'UPDATE '.$shipmentTable.' as ss ';
                $updateQuery.= 'JOIN '.$orderTable.' as so ON ss.order_id = so.entity_id ';
                $updateQuery.= 'JOIN '.$paymentTable.' as sop ON so.entity_id = sop.parent_id ';
                $updateQuery.= 'SET ss.flag_export_invoice_sales_shipment = 2 ';
                $updateQuery.= 'WHERE ss.flag_export_invoice_sales_shipment = 0 ';
                $updateQuery.= 'AND sop.method != "invoicedbasedpayment" ';

                $connection->query($updateQuery);
            }
        }
    }

    public function version0312()
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection*/
        $connection = $this->_connectionSales;

        $table = $connection->getTableName('riki_order_version_bi_export');

        if ($connection->isTableExists($table)) {

            $columnEntity = false;
            $columnExported = false;

            if ($connection->tableColumnExists($table, 'entity_id')) {

                $columnEntity = true;

                $connection->addIndex(
                    $table,
                    $connection->getIndexName($table,['entity_id']),
                    ['entity_id']
                );
            }

            if ($connection->tableColumnExists($table, 'is_bi_exported')) {

                $columnExported = true;

                $connection->addIndex(
                    $table,
                    $connection->getIndexName($table,['is_bi_exported']),
                    ['is_bi_exported']
                );
            }

            if ($columnEntity && $columnExported) {
                $connection->addIndex(
                    $table,
                    $connection->getIndexName($table,['entity_id','is_bi_exported']),
                    ['entity_id','is_bi_exported']
                );
            }
        }
    }

    public function version0313()
    {
        $shipmentTbl = $this->_connectionSales->getTableName('sales_shipment');
        $tableName = $this->_connectionSales->getTableName('riki_shipment_version_bi_export');

        if (!$this->_connectionSales->isTableExists($tableName)) {
            $tbl = $this->_connectionSales->newTable($tableName)
                ->addColumn(
                    'version_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Version id'
                )
                ->addColumn(
                    'entity_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'primary' => false, 'nullable' => false],
                    'Order Entity ID'
                )->addColumn(
                    'is_bi_exported',
                    Table::TYPE_BOOLEAN,
                    null,
                    ['unsigned' => true, 'primary' => false, 'nullable' => false, 'default' => 0],
                    'Flag to check export status'
                )->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['unsigned' => true, 'primary' => false, 'nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                    'Created at'
                )->addColumn(
                    'updated_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['unsigned' => true, 'primary' => false, 'nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
                    'Updated at'
                )->addIndex(
                    $this->_connectionSales->getIndexName($tableName, ['entity_id']), ['entity_id']
                )->addIndex(
                    $this->_connectionSales->getIndexName($tableName, ['is_bi_exported']), ['is_bi_exported']
                )->addIndex(
                    $this->_connectionSales->getIndexName($tableName, ['entity_id','is_bi_exported']), ['entity_id','is_bi_exported']
                );

            $this->_connectionSales->createTable($tbl);

            $this->_connectionSales->query("DROP TRIGGER IF EXISTS `new_version_shipment_after_insert`");
            $this->_connectionSales->query("DROP TRIGGER IF EXISTS `new_version_shipment_after_update`");

            $triggerInsert = $this->_triggerFactory->create();

            $triggerInsert->setName(
                'new_version_shipment_after_insert'
            )->setTime(
                Trigger::TIME_AFTER
            )->setEvent(
                Trigger::EVENT_INSERT
            )->setTable(
                $shipmentTbl
            )->addStatement(
                "INSERT INTO $tableName (entity_id) VALUES (NEW.entity_id);"
            );

            /*add new record to version table after insert data from sales_shipment*/
            $this->_connectionSales->createTrigger($triggerInsert);

            $triggerUpdate = $this->_triggerFactory->create();

            $triggerUpdate->setName(
                'new_version_shipment_after_update'
            )->setTime(
                Trigger::TIME_AFTER
            )->setEvent(
                Trigger::EVENT_UPDATE
            )->setTable(
                $shipmentTbl
            )->addStatement(
                "INSERT INTO $tableName (entity_id) VALUES (NEW.entity_id);"
            );

            /*add new record to version table after update data from sales_shipment*/
            $this->_connectionSales->createTrigger($triggerUpdate);
        }
    }
}