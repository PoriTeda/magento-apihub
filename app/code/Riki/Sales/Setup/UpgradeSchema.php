<?php

namespace Riki\Sales\Setup;

use Magento\Amqp\Model\Topology;
use Magento\Eav\Setup\EavSetupFactory;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Ddl\Trigger;

class UpgradeSchema implements UpgradeSchemaInterface {

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_checkoutConnection;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_salesConnection;

    /**
     * @var \Riki\Sales\Helper\ConnectionHelper
     */
    protected $_setupHelper;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $defaultConnection;

    /**
     * @var \Magento\Framework\DB\Ddl\TriggerFactory
     */
    protected $_triggerFactory;

    /**
     * @var Topology
     */
    protected $topology;

    /**
     * UpgradeSchema constructor.
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResource
     * @param \Magento\Sales\Model\ResourceModel\Order $orderResource
     * @param \Magento\Framework\DB\Ddl\TriggerFactory $triggerFactory
     * @param \Riki\Sales\Helper\ConnectionHelper $setupHelper
     * @param Topology $topology
     */
    public function __construct(
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        \Magento\Sales\Model\ResourceModel\Order $orderResource,
        \Magento\Framework\DB\Ddl\TriggerFactory $triggerFactory,
        \Riki\Sales\Helper\ConnectionHelper $setupHelper,
        Topology $topology
    ){
        $this->_checkoutConnection = $quoteResource->getConnection();
        $this->_salesConnection = $orderResource->getConnection();
        $this->_triggerFactory = $triggerFactory;
        $this->defaultConnection = $setupHelper->getDefaultConnection();
        $this->topology = $topology;
    }

    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context )
    {
        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();

        if (version_compare($context->getVersion(), '1.0.3') < 0) {
            $table = $setup->getTable('sales_order_status');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'color_code',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'Color Code']
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.4') < 0) {
            $table = $setup->getTable('sales_shipment_item');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'sap_trans_id',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'SAP Transaction Id']
                );
            }
        }
        if (version_compare($context->getVersion(), '1.0.5') < 0) {
            $table = $setup->getTable('sales_order');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'assignation',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'Assignation Data']
                );
            }
        }
        if (version_compare($context->getVersion(), '1.0.6') < 0) {
            $table = $setup->getTable('sales_order');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'min_export_date',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'The most early export date',

                    ]
                );
            }

            $table_item = $setup->getTable('sales_order_item');
            if ($setup->getConnection()->isTableExists($table_item) == true) {
                $setup->getConnection()->addColumn(
                    $table_item, 'export_date',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Export date',

                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '1.0.7') < 0) {
            $table = $setup->getTable('sales_order');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'subscription_profile_id',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'nullable' => true,
                        'comment' => 'Subscription Profile Main ID',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.8') < 0) {
            $table = $setup->getTable('sales_order');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'is_gift_order',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'nullable' => true,
                        'comment' => '0 not a gift order, 1 is a gift order',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.9') < 0) {
            $tableName = $installer->getTable('quote_item');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection->addColumn(
                    $tableName,
                    'discount_amount_excl_tax',
                    ['type' => Table::TYPE_DECIMAL, 'length' => '12,4', 'default' => '0.0000','comment' => 'Riki discount amount before tax']
                );
                $connection->addColumn(
                    $tableName,
                    'commission_amount',
                    ['type' => Table::TYPE_DECIMAL,'length' => '12,4', 'default' => '0.0000','comment' => 'Riki commission']
                );
                $connection->addColumn(
                    $tableName,
                    'tax_riki',
                    ['type' => Table::TYPE_DECIMAL,'length' => '12,4', 'default' => '0.0000','comment' => 'Riki tax value']
                );
            }

            $tableName = $installer->getTable('sales_order_item');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection->addColumn(
                    $tableName,
                    'discount_amount_excl_tax',
                    ['type' => Table::TYPE_DECIMAL,'length' => '12,4', 'default' => '0.0000','comment' => 'Riki discount amount before tax']
                );
                $connection->addColumn(
                    $tableName,
                    'commission_amount',
                    ['type' => Table::TYPE_DECIMAL,'length' => '12,4', 'default' => '0.0000','comment' => 'Riki commission']
                );
                $connection->addColumn(
                    $tableName,
                    'tax_riki',
                    ['type' => Table::TYPE_DECIMAL,'length' => '12,4', 'default' => '0.0000','comment' => 'Riki tax value']
                );
            }
        }


        if (version_compare($context->getVersion(), '1.1.0') < 0) {

            $setup->getConnection()->addColumn(
                $setup->getTable('quote'), 'chanel',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'    =>  255,
                    'comment' => 'Order channel [tax, call, email, postcard]',
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('quote'), 'charge_type',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'comment' => 'order type [Normal order, Free of charge - Replacement, Free of charge - Free samples]',
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('quote'), 'original_order_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'    =>  255,
                    'comment' => 'Original order id',
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('quote'), 'replacement_reason',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'comment' => 'Free of charge - Replacement reason',
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('quote'), 'free_samples_wbs',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'comment' => 'Free of charge - Free samples WBS',
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('quote_item'), 'address_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => true,
                    'comment' => 'Address Id',
                ]
            );

            $setup->getConnection()->addForeignKey(
                $setup->getFkName($setup->getTable('quote_item'), 'address_id', $setup->getTable('customer_address_entity'), 'entity_id'),
                $setup->getTable('quote_item'),
                'address_id',
                $setup->getTable('customer_address_entity'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
            );

            /// ORDER

            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order'), 'chanel',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'    =>  255,
                    'comment' => 'Order channel [tax, call, email, postcard]',
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order'), 'charge_type',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'comment' => 'order type [Normal order, Free of charge - Replacement, Free of charge - Free samples]',
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order'), 'original_order_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'    =>  255,
                    'comment' => 'Original order id',
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order'), 'replacement_reason',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'comment' => 'Free of charge - Replacement reason',
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order'), 'free_samples_wbs',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'comment' => 'Free of charge - Free samples WBS',
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order_item'), 'address_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => true,
                    'comment' => 'Address Id',
                ]
            );

            $setup->getConnection()->addForeignKey(
                $setup->getFkName($setup->getTable('sales_order_item'), 'address_id', $setup->getTable('customer_address_entity'), 'entity_id'),
                $setup->getTable('sales_order_item'),
                'address_id',
                $setup->getTable('customer_address_entity'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
            );
        }
        //create table to store shipment status and payment status for order
        if (version_compare($context->getVersion(), '1.1.1') < 0) {
            $tableName = 'riki_order_status';
            if (!$connection->isTableExists($setup->getTable($tableName))) {
                $tbl = $connection->newTable($setup->getTable($tableName))
                    ->addColumn(
                        'status_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                        null,
                        ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                        'Status ID'
                    )
                    ->addColumn(
                        'order_id',
                        Table::TYPE_INTEGER,
                        null,
                        ['unsigned' => true, 'primary' => false, 'nullable' => false],
                        'Order ID'
                    )->addColumn(
                        'order_increment_id',
                        Table::TYPE_TEXT,
                        20,
                        ['nullable' => true],
                        'Order Increment Id'
                    )->addColumn(
                        'status_payment',
                        Table::TYPE_TEXT,
                        100,
                        ['nullable' => true],
                        'Order payment status'
                    )->addColumn(
                        'status_shipment',
                        Table::TYPE_TEXT,
                        100,
                        ['nullable' => true],
                        'Order shipment status'
                    )->addColumn(
                        'status_date',
                        Table::TYPE_DATETIME,
                        null,
                        ['nullable' => true],
                        'Status date time'
                    )->setComment(
                        'Riki Order status'
                    );
                $connection->createTable($tbl);
            }
        }

        // drop column shipment status and payment status in table order grid
        if (version_compare($context->getVersion(), '1.1.2') < 0) {
            $tableName = 'sales_order_grid';
            if ($connection->tableColumnExists($setup->getTable($tableName), 'shipment_status')) {
                $connection->dropColumn($setup->getTable($tableName), 'shipment_status');
            }
            if ($connection->tableColumnExists($setup->getTable($tableName), 'payment_status')) {
                $connection->dropColumn($setup->getTable($tableName), 'payment_status');
            }
        }
        // update schema
        if (version_compare($context->getVersion(), '1.1.3') < 0) {

            if (!$connection->tableColumnExists($setup->getTable('sales_order_grid'), 'shipment_status')) {
                $connection->addColumn(
                    $setup->getTable('sales_order_grid'),
                    'shipment_status',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 100,
                        'nullable' => false,
                        'default' => '',
                        'comment' => 'Shipment Status'
                    ]
                );
            }

            if (!$connection->tableColumnExists($setup->getTable('sales_order_grid'), 'payment_status')) {
                $connection->addColumn(
                    $setup->getTable('sales_order_grid'),
                    'payment_status',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 100,
                        'nullable' => false,
                        'default' => 'not_applicable',
                        'comment' => 'Payment Status'
                    ]
                );
            }
            //add  trigger
            $trigger_table =  $setup->getTable('sales_order');
            $trigger_table_target =  $setup->getTable('sales_order_grid');
            $installer->run("
            CREATE TRIGGER auto_update_payship_order_grid AFTER UPDATE ON $trigger_table
                FOR EACH ROW
                  UPDATE $trigger_table_target
                     SET shipment_status = NEW.shipment_status,
                         payment_status = NEW.payment_status
                   WHERE entity_id = NEW.entity_id;

            ");
        }

        if (version_compare($context->getVersion(), '1.1.4') < 0) {
            $table = $setup->getTable('quote_item');
            $connection->addColumn($table,
                'booking_wbs',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Get from product attribute'
                ]
            );

            $connection->addColumn($table,
                'booking_account',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Get from product attribute'
                ]
            );

            $connection->addColumn($table,
                'booking_center',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Get from product attribute'
                ]
            );

            $connection->addColumn($table,
                'free_of_charge',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'nullable' => true,
                    'default'   =>  false,
                    'comment' => 'Free of charge'
                ]
            );

            $connection->addColumn($table,
                'distribution_channel',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 2,
                    'nullable' => true,
                    'comment' => 'Distribution channel'
                ]
            );

            //////////
            $table = $setup->getTable('quote');
            $connection->addColumn($table,
                'campaign_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Campaign id'
                ]
            );

            $connection->addColumn($table,
                'siebel_enquiry_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Siebel Enquiry ID'
                ]
            );

            $connection->addColumn($table,
                'free_of_charge',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'nullable' => true,
                    'default'   =>  false,
                    'comment' => 'Free of charge'
                ]
            );

            $connection->addColumn($table,
                'shosha_business_code',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Shosha customer code'
                ]
            );

            $connection->addColumn($table,
                'payment_agent',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => '(NICOS, JCB, VISA, ...)'
                ]
            );

            $connection->addColumn($table,
                'allowed_earned_point',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'nullable' => true,
                    'default'   =>  true,
                    'comment' => 'Allow to earn point to customer'
                ]
            );

            // for order/order item
            $table = $setup->getTable('sales_order_item');
            $connection->addColumn($table,
                'booking_wbs',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Get from product attribute'
                ]
            );

            $connection->addColumn($table,
                'booking_account',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Get from product attribute'
                ]
            );

            $connection->addColumn($table,
                'booking_center',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Get from product attribute'
                ]
            );

            $connection->addColumn($table,
                'free_of_charge',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'nullable' => true,
                    'default'   =>  false,
                    'comment' => 'Free of charge'
                ]
            );

            $connection->addColumn($table,
                'distribution_channel',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 2,
                    'nullable' => true,
                    'comment' => 'Distribution channel'
                ]
            );

            //////////
            $table = $setup->getTable('sales_order');
            $connection->addColumn($table,
                'campaign_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Campaign id'
                ]
            );

            $connection->addColumn($table,
                'siebel_enquiry_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Siebel Enquiry ID'
                ]
            );

            $connection->addColumn($table,
                'free_of_charge',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'nullable' => true,
                    'default'   =>  false,
                    'comment' => 'Free of charge'
                ]
            );

            $connection->addColumn($table,
                'shosha_business_code',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Shosha customer code'
                ]
            );

            $connection->addColumn($table,
                'payment_agent',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => '(NICOS, JCB, VISA, ...)'
                ]
            );

            $connection->addColumn($table,
                'allowed_earned_point',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'nullable' => true,
                    'default'   =>  true,
                    'comment' => 'Allow to earn point to customer'
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.1.7') < 0) {

            $connection->addColumn($setup->getTable('sales_order'),
                'created_by',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'default'   =>  'Web Order',
                    'comment' => 'Username of the admin who created the order'
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.1.8') < 0) {
            $table = $setup->getTable('quote_item');
            $connection->addColumn($table,
                'delivery_timeslot_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'nullable' => true,
                    'default'   =>  null,
                    'after' =>  'delivery_time',
                    'comment' => 'Delivery time slot ID (from time slot table)'
                ]
            );

            $connection->addColumn($table,
                'delivery_timeslot_from',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'default'   =>  null,
                    'after' =>  'delivery_timeslot_id',
                    'comment' => 'Delivery time slot ID (from time slot table)'
                ]
            );

            $connection->addColumn($table,
                'delivery_timeslot_to',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'default'   =>  null,
                    'after' =>  'delivery_timeslot_from',
                    'comment' => 'Delivery time slot ID (from time slot table)'
                ]
            );

            ////// order item
            $table = $setup->getTable('sales_order_item');
            $connection->addColumn($table,
                'delivery_timeslot_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'nullable' => true,
                    'default'   =>  null,
                    'after' =>  'delivery_time',
                    'comment' => 'Delivery time slot ID (from time slot table)'
                ]
            );

            $connection->addColumn($table,
                'delivery_timeslot_from',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'default'   =>  null,
                    'after' =>  'delivery_timeslot_id',
                    'comment' => 'Delivery time slot ID (from time slot table)'
                ]
            );

            $connection->addColumn($table,
                'delivery_timeslot_to',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'default'   =>  null,
                    'after' =>  'delivery_timeslot_from',
                    'comment' => 'Delivery time slot ID (from time slot table)'
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.1.9') < 0) {
            $connection->addColumn($setup->getTable('quote_item'),
                'next_delivery_date',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'default'   =>  null,
                    'after' =>  'delivery_date',
                    'comment' => 'Next delivery date (only for subscription)'
                ]
            );

            $connection->addColumn($setup->getTable('sales_order_item'),
                'next_delivery_date',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'default'   =>  null,
                    'after' =>  'delivery_date',
                    'comment' => 'Next delivery date (only for subscription)'
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.2.1') < 0) {

            /* append default value on charge type column for quote & order */
            $orderTableName = $setup->getTable('sales_order');
            $quoteTableName = $setup->getTable('quote');
            /* if column exists */
            if( $setup->getConnection()->tableColumnExists( $quoteTableName , 'charge_type')){
                $setup->getConnection()->modifyColumn(
                    $quoteTableName ,
                    'charge_type' ,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                        'comment' => 'order type [Normal order, Free of charge - Replacement, Free of charge - Free samples]',
                        'default' => \Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_NORMAL
                    ]
                );

            }else{
                /* column not exists then throw exception */
                $setup->endSetup();
                throw new \Magento\Framework\Exception\LocalizedException(__("Column charge_type does not exists on quote table"));
            }

            if( $setup->getConnection()->tableColumnExists( $orderTableName , 'charge_type') ){
                $setup->getConnection()->modifyColumn(
                    $orderTableName ,
                    'charge_type' ,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                        'comment' => 'order type [Normal order, Free of charge - Replacement, Free of charge - Free samples]',
                        'default' => \Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_NORMAL
                    ]
                );
            }else{
                /* column not exists then throw exception */
                $setup->endSetup();
                throw new \Magento\Framework\Exception\LocalizedException(__("Column charge_type does not exists on order table"));
            }

        }

        if (version_compare($context->getVersion(), '1.2.2') < 0) {
            $table = $setup->getTable('quote');

            if ($connection->tableColumnExists($table, 'shosha_business_code')) {
                $connection->dropColumn($table, 'shosha_business_code');
            }
        }

        if (version_compare($context->getVersion(), '1.3.0') < 0) {

            $table = $setup->getTable('sales_order');
            if($connection->tableColumnExists($table, 'free_of_charge')){
                $connection->modifyColumn($table,
                    'free_of_charge',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'nullable' => false,
                        'default'   =>  0,
                        'comment' => 'Free of charge'
                    ]
                );
            }

            if(!$connection->tableColumnExists($table, 'customer_membership')){
                $connection->addColumn($table,
                    'customer_membership',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        'comment' => 'Customer membership'
                    ]
                );
            }

            ////// order grid

            $table = $setup->getTable('sales_order_grid');

            if(!$connection->tableColumnExists($table, 'chanel')){
                $connection->addColumn($table,
                    'chanel',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length'    =>  255,
                        'comment' => 'Order channel [tax, call, email, postcard]',
                    ]
                );
            }

            if(!$connection->tableColumnExists($table, 'created_by')){
                $connection->addColumn($table,
                    'created_by',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'default'   =>  'Web Order',
                        'comment' => 'Username of the admin who created the order'
                    ]
                );
            }

            if(!$connection->tableColumnExists($table, 'shosha_business_code')){
                $connection->addColumn($table,
                    'shosha_business_code',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Shosha customer code'
                    ]
                );
            }

            if(!$connection->tableColumnExists($table, 'free_of_charge')){
                $connection->addColumn($table,
                    'free_of_charge',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'nullable' => true,
                        'default'   =>  0,
                        'comment' => 'Free of charge'
                    ]
                );
            }

            if(!$connection->tableColumnExists($table, 'payment_agent')){
                $connection->addColumn($table,
                    'payment_agent',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => '(NICOS, JCB, VISA, ...)'
                    ]
                );
            }

            if(!$connection->tableColumnExists($table, 'mm_order_id')){
                $connection->addColumn($table,
                    'mm_order_id',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        255,
                        'default' => null,
                        'comment' => 'data webapi payment-information'
                    ]
                );
            }

            if(!$connection->tableColumnExists($table, 'replacement_reason')){
                $connection->addColumn($table,
                    'replacement_reason',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        'comment' => 'Free of charge - Replacement reason',
                    ]
                );
            }

            if(!$connection->tableColumnExists($table, 'customer_membership')){
                $connection->addColumn($table,
                    'customer_membership',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        'comment' => 'Customer membership',
                    ]
                );
            }

            if(!$connection->tableColumnExists($table, 'subscription_course_id')){
                $connection->addColumn($table,
                    'subscription_course_id',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        'comment' => 'Subscription course ID',
                    ]
                );
            }

            if(!$connection->tableColumnExists($table, 'subscription_course_name')){
                $connection->addColumn($table,
                    'subscription_course_name',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        'comment' => 'Subscription course ID',
                    ]
                );
            }

            if(!$connection->tableColumnExists($table, 'collected_date')){
                $connection->addColumn($table,
                    'collected_date',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Nestle collected money date'
                    ]
                );
            }

            if(!$connection->tableColumnExists($table, 'payment_transaction_id')){
                $connection->addColumn($table,
                    'payment_transaction_id',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Transaction ID of Paygent'
                    ]
                );
            }

            if(!$connection->tableColumnExists($table, 'customer_membership')){
                $connection->addColumn($table,
                    'customer_membership',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        'comment' => 'Customer membership'
                    ]
                );
            }

            if(!$connection->tableColumnExists($table, 'subscription_course_id')){
                $connection->addColumn($table,
                    'subscription_course_id',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        'comment' => 'Subscription course ID'
                    ]
                );
            }

            if(!$connection->tableColumnExists($table, 'subscription_course_name')){
                $connection->addColumn($table,
                    'subscription_course_name',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        'comment' => 'Subscription course name'
                    ]
                );
            }

        }

        /**
         * Add column to sales_order:
         * tax_riki_total (include: riki_tax + shipping_fee_tax + payment_fee_tax + gift_wrapping_fee_tax)
         */
        if (version_compare($context->getVersion(), '1.3.1') < 0) {

            $table = $setup->getTable('sales_order');
            if (!$connection->tableColumnExists($table, 'tax_riki_total')) {
                $connection->addColumn(
                    $table,
                    'tax_riki_total',
                    ['type' => Table::TYPE_DECIMAL,'length' => '12,4', 'default' => '0.0000','comment' => 'Riki total tax value']
                );
            }
        }

        if (version_compare($context->getVersion(), '1.3.2') < 0) {

            $table = $setup->getTable('sales_order');
            if (!$connection->tableColumnExists($table, 'payment_error_code')) {
                $connection->addColumn($table,
                    'payment_error_code',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        'comment' => 'Data message payment fail',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.3.3') < 0) {
            $table = $setup->getTable('sales_order_grid');

            if(!$connection->tableColumnExists($table, 'payment_error_message')){
                $connection->addColumn($table,
                    'payment_error_message',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'comment' => 'Error message from paygent transaction',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.4.0') < 0) {

            $table = $setup->getTable('sales_order');
            if (!$connection->tableColumnExists($table, 'is_multiple_shipping')) {
                $connection->addColumn($table,
                    'is_multiple_shipping',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        null,
                        'default'   =>  0,
                        'comment' => 'Is Multiple Address Shipping',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.4.1') < 0) {

            $table = $setup->getTable('quote');

            if($connection->tableColumnExists($table, 'chanel')){
                $connection->changeColumn(
                    $table, 'chanel', 'order_channel',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length'    =>  255,
                        'comment' => 'Order channel [fax, call, email, postcard]',
                    ]
                );
            }

            $table = $setup->getTable('sales_order');

            if($connection->tableColumnExists($table, 'chanel')){
                $connection->changeColumn(
                    $table, 'chanel', 'order_channel',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length'    =>  255,
                        'comment' => 'Order channel [fax, call, email, postcard]',
                    ]
                );
            }

            $table = $setup->getTable('sales_order_grid');

            if($connection->tableColumnExists($table, 'chanel')){
                $connection->changeColumn(
                    $table, 'chanel', 'order_channel',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length'    =>  255,
                        'comment' => 'Order channel [fax, call, email, postcard]',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.4.2') < 0) {

            $table = $setup->getTable('sales_order_grid');

            if (!$connection->tableColumnExists($table, 'billing_phone')) {
                $connection->addColumn($table,
                    'billing_phone',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Billing Phone'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.4.3') < 0) {

            $table = $setup->getTable('sales_order');

            if(!$connection->tableColumnExists($table, 'updated_by')){
                $connection->addColumn($table,
                    'updated_by',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'default'   =>  '',
                        'comment' => 'Username of the admin who updated the order'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.4.4') < 0) {

            $table = $setup->getTable('quote');
            if (!$this->_checkoutConnection->tableColumnExists($table, 'is_multiple_shipping')) {
                $this->_checkoutConnection->addColumn($table,
                    'is_multiple_shipping',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        null,
                        'default'   =>  0,
                        'comment' => 'Is Multiple Address Shipping',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.4.5') < 0) {

            $this->_checkoutConnection->addColumn($setup->getTable('quote_item'),
                'foc_wbs',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Free of charge order WBS'
                ]
            );

            $this->_salesConnection->addColumn($setup->getTable('sales_order_item'),
                'foc_wbs',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Free of charge order WBS'
                ]
            );

            $this->_checkoutConnection->addColumn($setup->getTable('quote'),
                'free_delivery_wbs',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Free shipping fee WBS'
                ]
            );

            $this->_salesConnection->addColumn($setup->getTable('sales_order'),
                'free_delivery_wbs',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Free shipping fee WBS'
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.4.6') < 0) {

            $this->_checkoutConnection->addColumn($setup->getTable('quote'),
                'free_payment_wbs',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Free payment fee WBS'
                ]
            );

            $this->_salesConnection->addColumn($setup->getTable('sales_order'),
                'free_payment_wbs',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Free payment fee WBS'
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.4.7') < 0) {
            $this->_checkoutConnection->dropForeignKey(
                $setup->getTable('quote_item'),
                $setup->getFkName($setup->getTable('quote_item'), 'address_id', $setup->getTable('customer_address_entity'), 'entity_id')
            );

            $this->_salesConnection->dropForeignKey(
                $setup->getTable('sales_order_item'),
                $setup->getFkName($setup->getTable('sales_order_item'), 'address_id', $setup->getTable('customer_address_entity'), 'entity_id')
            );
        }

        if (version_compare($context->getVersion(), '1.5.0') < 0) {

            $table = $setup->getTable('sales_order_grid');

            $this->_salesConnection->addColumn(
                $table, 'original_order_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'    =>  255,
                    'comment' => 'Original order id',
                ]
            );

            $this->_salesConnection->dropIndex(
                $table,
                $setup->getIdxName(
                    'sales_order_grid',
                    [
                        'increment_id',
                        'billing_name',
                        'shipping_name',
                        'shipping_address',
                        'billing_address',
                        'customer_name',
                        'customer_email'
                    ],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
                )
            );

            $this->_salesConnection->addIndex(
                $table,
                $setup->getIdxName(
                    'sales_order_grid',
                    [
                        'increment_id',
                        'billing_name',
                        'shipping_name',
                        'shipping_address',
                        'billing_address',
                        'customer_name',
                        'customer_email',
                        'original_order_id'
                    ],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
                ),
                [
                    'increment_id',
                    'billing_name',
                    'shipping_name',
                    'shipping_address',
                    'billing_address',
                    'customer_name',
                    'customer_email',
                    'original_order_id'
                ],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
            );
        }

        if (version_compare($context->getVersion(), '1.5.1') < 0) {

            $this->_salesConnection->addColumn($setup->getTable('sales_order'),
                'sap_condition_type',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Sap condition type'
                ]
            );

            $this->_salesConnection->addColumn($setup->getTable('sales_order_item'),
                'sap_condition_type',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Sap condition type'
                ]
            );

            $this->_salesConnection->addColumn($setup->getTable('sales_order_item'),
                'account_code',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Account code'
                ]
            );
        }
        // add billing and shipping address
        if (version_compare($context->getVersion(), '1.5.2') < 0) {
            $tableName = $this->_salesConnection->getTableName('sales_order');
            $fieldName = 'billing_address';
            if(!$this->_salesConnection->tableColumnExists($tableName,$fieldName))
            {
                $this->_salesConnection->addColumn($tableName,
                    $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 1000,
                        'nullable' => true,
                        'comment' => 'Billing Address for Order'
                    ]
                );
            }
            $fieldName = 'shipping_address';
            if(!$this->_salesConnection->tableColumnExists($tableName,$fieldName))
            {
                $this->_salesConnection->addColumn($tableName,
                    $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 1000,
                        'nullable' => true,
                        'comment' => 'Shipping Address for Order'
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '1.5.3') < 0) {
            $tableName =  $this->_salesConnection->getTableName('sales_order');
            $removeField= 'billing_address_new';
            if($this->_salesConnection->tableColumnExists($tableName,$removeField))
            {
                $this->_salesConnection->dropColumn($tableName,$removeField);

            }
            $fieldName = 'billing_address';
            if(!$this->_salesConnection->tableColumnExists($tableName,$fieldName))
            {
                $this->_salesConnection->addColumn($tableName,
                    $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 1000,
                        'nullable' => true,
                        'comment' => 'Billing Address for Order'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.5.4') < 0) {

            $this->_checkoutConnection->addColumn($setup->getTable('quote_item'),
                'rule_price',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length' => '12,4',
                    'nullable' => false,
                    'default' => '0.0000',
                    'comment' => 'Calculated Rule Price'
                ]
            );

            $this->_salesConnection->addColumn($setup->getTable('sales_order_item'),
                'rule_price',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length' => '12,4',
                    'nullable' => false,
                    'default' => '0.0000',
                    'comment' => 'Calculated Rule Price'
                ]
            );
        }
// Ddit default allowed_earned_point
        if (version_compare($context->getVersion(), '1.5.5') < 0) {

            $orderTableName = $setup->getTable('sales_order');
            $quoteTableName = $setup->getTable('quote');
            /* if column exists */
            if( $this->_checkoutConnection->tableColumnExists( $quoteTableName , 'allowed_earned_point')){
                $this->_checkoutConnection->modifyColumn(
                    $quoteTableName ,
                    'allowed_earned_point' ,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'nullable' => true,
                        'comment' => 'Allow to earn point to customer',
                        'default' => 0
                    ]
                );

            }

            if( $this->_salesConnection->tableColumnExists( $orderTableName , 'allowed_earned_point') ){
                $this->_salesConnection->modifyColumn(
                    $orderTableName ,
                    'allowed_earned_point' ,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'nullable' => true,
                        'comment' => 'Allow to earn point to customer',
                        'default' => 0
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.5.5') < 0) {

            $table = $setup->getTable('sales_order');

            $this->_salesConnection->addColumn($table,
                'is_free_payment_charge_by_admin',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Set payment charge to free by manual'
                ]
            );

            $this->_salesConnection->addColumn($table,
                'is_free_shipping_by_admin',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Set shipping fee to free by manual'
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.5.6') < 0) {

            $this->_checkoutConnection->modifyColumn($setup->getTable('quote_item'),
                'rule_price',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length' => '12,4',
                    'nullable' => false,
                    'default' => '0.0000',
                    'comment' => 'Calculated Rule Price'
                ]
            );

            $this->_salesConnection->modifyColumn($setup->getTable('sales_order_item'),
                'rule_price',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length' => '12,4',
                    'nullable' => false,
                    'default' => '0.0000',
                    'comment' => 'Calculated Rule Price'
                ]
            );

            $this->_checkoutConnection->addColumn($setup->getTable('quote_item'),
                'sales_organization',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'comment' => 'Product Sales Organization'
                ]
            );

            $this->_salesConnection->addColumn($setup->getTable('sales_order_item'),
                'sales_organization',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'comment' => 'Product Sales Organization'
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.5.7') < 0) {

            $tableQuote = $this->_checkoutConnection->getTableName('quote');


            if(!$this->_checkoutConnection->tableColumnExists($tableQuote,'customer_firstnamekana')){
                $this->_checkoutConnection->addColumn($tableQuote,
                    'customer_firstnamekana',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Customer First Name Kana'
                    ]
                );
            }

            if(!$this->_checkoutConnection->tableColumnExists($tableQuote,'customer_lastnamekana')) {

                $this->_checkoutConnection->addColumn($tableQuote,
                    'customer_lastnamekana',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Customer Last Name Kana'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.5.8') < 0) {

            $tableQuote = $this->_checkoutConnection->getTableName('quote');

            if(!$this->_checkoutConnection->tableColumnExists($tableQuote,'customer_firstnamekana')) {

                $this->_checkoutConnection->addColumn($tableQuote,
                    'customer_firstnamekana',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Customer First Name Kana'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.5.9') < 0) {

            $tableSalesOrder = $this->_salesConnection->getTableName('sales_order');

            if(!$this->_salesConnection->tableColumnExists($tableSalesOrder,'subscription_order_time')) {

                $this->_salesConnection->addColumn($tableSalesOrder,
                    'subscription_order_time',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'length' => 11,
                        'nullable' => true,
                        'default' => null,
                        'comment' => 'Subscription order time'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.6.0') < 0) {

            $orderTbl = $this->_salesConnection->getTableName('sales_order');
            $tableName = $this->_salesConnection->getTableName('riki_order_version_bi_export');

            if (!$this->_salesConnection->isTableExists($tableName)) {
                $tbl = $this->_salesConnection->newTable($tableName)
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
                        'Order Entity ID'
                    );
                $this->_salesConnection->createTable($tbl);

                $this->_salesConnection->query("DROP TRIGGER IF EXISTS `new_version_order_after_insert`");
                $this->_salesConnection->query("DROP TRIGGER IF EXISTS `new_version_order_after_update`");

                $triggerInsert = $this->_triggerFactory->create();

                $triggerInsert->setName(
                    'new_version_order_after_insert'
                )->setTime(
                    Trigger::TIME_AFTER
                )->setEvent(
                    Trigger::EVENT_INSERT
                )->setTable(
                    $orderTbl
                )->addStatement("
                    INSERT INTO $tableName (entity_id) VALUES (NEW.entity_id);
                ");

                /*add new record to version table after insert data from sales_order*/
                $this->_salesConnection->createTrigger($triggerInsert);

                $triggerUpdate = $this->_triggerFactory->create();

                $triggerUpdate->setName(
                    'new_version_order_after_update'
                )->setTime(
                    Trigger::TIME_AFTER
                )->setEvent(
                    Trigger::EVENT_UPDATE
                )->setTable(
                    $orderTbl
                )->addStatement("
                    IF (SELECT COUNT(*) FROM $tableName WHERE entity_id = NEW.entity_id AND is_bi_exported = 0) = 0 THEN
                        INSERT INTO $tableName (entity_id) VALUES (NEW.entity_id);
                    END IF ; 
                ");

                /*add new record to version table after update data from sales_order*/
                $this->_salesConnection->createTrigger($triggerUpdate);

                /*insert old data for version table*/
                $select = $this->_salesConnection->select()->from($this->_salesConnection->getTableName('sales_order'), ['entity_id']);

                $this->_salesConnection->query(
                    $select->insertFromSelect($this->_salesConnection->getTableName('riki_order_version_bi_export'), ['entity_id'], false)
                );
            }
        }

        if (version_compare($context->getVersion(), '1.6.1') < 0) {
            $orderTbl = $this->_salesConnection->getTableName('sales_order');
            $tableName = $this->_salesConnection->getTableName('riki_order_version_bi_export');

            if ($this->_salesConnection->isTableExists($tableName)) {

                /*truncate data from version table*/
                $this->_salesConnection->query("TRUNCATE ". $tableName);

                /*add column created at*/
                $this->_salesConnection->addColumn($tableName,
                    'created_at',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        'nullable' => false,
                        'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT,
                        'comment' => 'Created at'
                    ]
                );

                /*add columns updated at*/
                $this->_salesConnection->addColumn($tableName,
                    'updated_at',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        'nullable' => false,
                        'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
                        'comment' => 'Updated at'
                    ]
                );

                $this->_salesConnection->query("DROP TRIGGER IF EXISTS `new_version_order_after_update`");

                $triggerUpdate = $this->_triggerFactory->create();

                $triggerUpdate->setName(
                    'new_version_order_after_update'
                )->setTime(
                    Trigger::TIME_AFTER
                )->setEvent(
                    Trigger::EVENT_UPDATE
                )->setTable(
                    $orderTbl
                )->addStatement("
                    INSERT INTO $tableName (entity_id) VALUES (NEW.entity_id);
                ");

                /*update flag to re export this record after edit order*/
                $this->_salesConnection->createTrigger($triggerUpdate);

                /*insert old data for version table*/
                $select = $this->_salesConnection->select()->from($this->_salesConnection->getTableName('sales_order'), ['entity_id', new \Zend_Db_Expr('1 as is_bi_exported')]);

                $this->_salesConnection->query(
                    $select->insertFromSelect(
                        $this->_salesConnection->getTableName('riki_order_version_bi_export'),
                        ['entity_id','is_bi_exported'],
                        false
                    )
                );
            }
        }

        if (version_compare($context->getVersion(), '1.6.2') < 0) {
            $table = $this->defaultConnection->getTableName('riki_shipment_shipping_history');
            if ($this->defaultConnection->isTableExists($table)) {
                $this->defaultConnection->addIndex(
                    $table,
                    $this->defaultConnection->getIndexName(
                        $table,
                        ['shipment_id']
                    ),
                    ['shipment_id']
                );
            }
        }

        if (version_compare($context->getVersion(), '1.6.3') < 0) {

            $connection = $this->_salesConnection;

            $table = $connection->getTableName('sales_order');
            if ($connection->isTableExists($table)) {
                $connection->addIndex(
                    $table,
                    $connection->getIndexName(
                        $table,
                        ['original_order_id']
                    ),
                    ['original_order_id']
                );
            }
        }


        if (version_compare($context->getVersion(), '1.6.4') < 0) {
            $table = $this->_salesConnection->getTableName('sales_order');
            if ($this->_salesConnection->isTableExists($table)) {
                $this->_salesConnection->addIndex(
                    $table,
                    $this->_salesConnection->getIndexName(
                        $table,
                        ['is_promotion_exported']
                    ),
                    ['is_promotion_exported']
                );
            }
        }

        if (version_compare($context->getVersion(), '1.6.5') < 0) {
            $table = $this->_salesConnection->getTableName('riki_order_status');
            if ($this->_salesConnection->isTableExists($table)) {
                $this->_salesConnection->addIndex(
                    $table,
                    $this->_salesConnection->getIndexName(
                        $table,
                        ['order_id']
                    ),
                    ['order_id']
                );
            }
        }
        if (version_compare($context->getVersion(), '1.6.6') < 0) {
            $table = $this->_salesConnection->getTableName('sales_order_grid');
            if ($this->_salesConnection->isTableExists($table)) {
                $this->_salesConnection->modifyColumn(
                    $table,'fraud_score',
                        [
                            'type'     => Table::TYPE_INTEGER,
                            'nullable' => true,
                            'comment'  => 'Fraud Check Score Calculation',
                        ]
                );
                $this->_salesConnection->addIndex(
                    $table,
                    $this->_salesConnection->getIndexName(
                        $table,
                        ['fraud_score']
                    ),
                    ['fraud_score']
                );
            }
        }
        if (version_compare($context->getVersion(), '1.6.7') < 0) {
            $table = $this->_salesConnection->getTableName('sales_order');
            if ($this->_salesConnection->isTableExists($table)) {
                $this->_salesConnection->modifyColumn(
                    $table,'fraud_score',
                    [
                        'type'     => Table::TYPE_INTEGER,
                        'nullable' => true,
                        'comment'  => 'Fraud Check Score Calculation',
                    ]
                );
                $this->_salesConnection->addIndex(
                    $table,
                    $this->_salesConnection->getIndexName(
                        $table,
                        ['fraud_score']
                    ),
                    ['fraud_score']
                );
            }
        }

        if (version_compare($context->getVersion(), '1.6.8') < 0) {
            $oiTb = $this->_salesConnection->getTableName('sales_order_item');
            if ($this->_salesConnection->isTableExists($oiTb)) {
                $idxName = $this->_salesConnection->getIndexName($oiTb, ['product_id']);
                $this->_salesConnection->addIndex($oiTb, $idxName, ['product_id']);
            }
        }

        if (version_compare($context->getVersion(), '1.6.9') < 0) {

            $this->_checkoutConnection->addColumn(
                $this->_checkoutConnection->getTableName('quote'),
                'free_shipping_fee_wbs',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'WBS - Free shipping fee by admin'
                ]
            );

            $this->_salesConnection->addColumn(
                $this->_salesConnection->getTableName('sales_order'),
                'free_shipping_fee_wbs',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'WBS - Free shipping fee by admin'
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.7.0') < 0) {
            $orderTable = $this->_salesConnection->getTableName('sales_order');
            $quoteTable = $this->_checkoutConnection->getTableName('quote');

            $fields = [
                'customer_company_name' =>  [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Customer company name'
                ],
                'customer_amb_type' =>  [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Customer Ambassador type'
                ],
                'customer_offline_customer' =>  [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'nullable' => true,
                    'default'   =>  0,
                    'comment' => 'Is offline customer?'
                ],
                'customer_key_work_ph_num' =>  [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'length' => 255,
                    'comment' => 'Customer Company phone number'
                ]
            ];

            foreach ($fields as $column =>  $definition) {
                $this->_checkoutConnection->addColumn(
                    $quoteTable,
                    $column,
                    $definition
                );

                $this->_salesConnection->addColumn(
                    $orderTable,
                    $column,
                    $definition
                );
            }
        }

        if (version_compare($context->getVersion(), '1.7.1') < 0) {
            $orderTable = $this->_salesConnection->getTableName('sales_order');
            $quoteTable = $this->_checkoutConnection->getTableName('quote');

            $fields = [
                'customer_consumer_db_id' =>  [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Customer consumer DB ID'
                ]
            ];

            foreach ($fields as $column =>  $definition) {
                $this->_checkoutConnection->addColumn(
                    $quoteTable,
                    $column,
                    $definition
                );

                $this->_salesConnection->addColumn(
                    $orderTable,
                    $column,
                    $definition
                );
            }
        }
        if (version_compare($context->getVersion(), '1.7.2') < 0) {

            $this->_checkoutConnection->addColumn($setup->getTable('quote'),
                'free_delivery_wbs',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Free shipping fee WBS'
                ]
            );

            $this->_salesConnection->addColumn($setup->getTable('sales_order'),
                'free_delivery_wbs',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Free shipping fee WBS'
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.7.3') < 0) {
            if(!$this->_checkoutConnection->tableColumnExists('quote', 'customer_b2b_flag'))
            {
                $this->_checkoutConnection->addColumn($setup->getTable('quote'),
                    'customer_b2b_flag',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                        'length' => 255,
                        'nullable' => true,
                        'default'=>0,
                        'comment' => 'B2b Flag'
                    ]
                );
            }
            if(!$this->_salesConnection->tableColumnExists('sales_order','customer_b2b_flag'))
            {
                $this->_salesConnection->addColumn($setup->getTable('sales_order'),
                    'customer_b2b_flag',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                        'nullable' => true,
                        'default'=>0,
                        'comment' => 'B2b Flag'
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '1.7.4') < 0) {
            //remove unuse field
            if($this->_checkoutConnection->tableColumnExists('quote', 'customer_b2b_flag'))
            {
                $this->_checkoutConnection->dropColumn('quote', 'customer_b2b_flag');
            }
            if($this->_salesConnection->tableColumnExists('sales_order','customer_b2b_flag'))
            {
                $this->_salesConnection->dropColumn('sales_order', 'customer_b2b_flag');
            }
            //add new field to shipment table
            if(!$this->_salesConnection->tableColumnExists('sales_shipment','customer_b2b_flag'))
            {
                $this->_salesConnection->addColumn($setup->getTable('sales_shipment'),
                    'customer_b2b_flag',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                        'nullable' => true,
                        'comment' => 'B2b Flag'
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '1.7.5') < 0) {
            if(!$this->_salesConnection->tableColumnExists('sales_order','receipt_counter'))
            {
                $this->_salesConnection->addColumn($setup->getTable('sales_order'),
                    'receipt_counter',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                        'nullable' => true,
                        'default' => 0,
                        'comment' => 'Receipt counter'
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '1.7.6') < 0) {
            if(!$this->_salesConnection->tableColumnExists('sales_order','point_for_trial'))
            {
                $this->_salesConnection->addColumn($setup->getTable('sales_order'),
                    'point_for_trial',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'length' => 1,
                        'nullable' => true,
                        'comment' => 'Shopping point for trial'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.8.0') < 0) {

            $definition = [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'comment' => 'Catalog Rule Product Price'
            ];

            $this->_checkoutConnection->modifyColumn(
                $setup->getTable('quote_item'),
                'rule_price',
                $definition,
                false
            );

            $this->_salesConnection->modifyColumn(
                $setup->getTable('sales_order_item'),
                'rule_price',
                $definition,
                false
            );
        }
        if (version_compare($context->getVersion(), '1.8.1') < 0) {
            $table = $this->_salesConnection->getTableName('sales_order_grid');
            $fieldName = 'is_stock_point';
            if($this->_salesConnection->isTableExists($table) &&
                !$this->_salesConnection->tableColumnExists($table,$fieldName)){
                $this->_salesConnection->addColumn(
                    $table, $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'comment' => 'Is stock point order',
                        'nullable' => false,
                        'default' => 0
                    ]
                );
            }
            $table = $this->_salesConnection->getTableName('sales_order');
            $fieldName = 'is_stock_point';
            if($this->_salesConnection->isTableExists($table) &&
                !$this->_salesConnection->tableColumnExists($table,$fieldName)){
                $this->_salesConnection->addColumn(
                    $table, $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'comment' => 'Is stock point order',
                        'nullable' => false,
                        'default' => 0
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '1.8.2') < 0) {
            $table = $this->_salesConnection->getTableName('sales_order_grid');

            if (!$this->_salesConnection->tableColumnExists($table, 'riki_type')) {
                $this->_salesConnection->addColumn($table,
                    'riki_type',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'SUBSCRIPTION, HANPUKAI,  ... order type'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.9.2') < 0) {
            $this->_salesConnection->addColumn(
                $this->_salesConnection->getTableName('sales_order'),
                'allow_choose_delivery_date',
                [
                    'type' => Table::TYPE_BOOLEAN,
                    'nullable' => false,
                    'comment' => 'Allow choose delivery date on checkout',
                    'default'  => 1
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.9.3') < 0) {
            $this->_salesConnection->addColumn(
                $this->_salesConnection->getTableName('sales_creditmemo'),
                'return_shipping_fee_adjusted',
                [
                    'type' => Table::TYPE_DECIMAL,
                    'length' => '12,4',
                    'comment' => 'Return Shipping Fee Adjusted'
                ]
            );
            $this->_salesConnection->addColumn($this->_salesConnection->getTableName('sales_creditmemo'),
                'return_payment_fee_adjusted', [
                'type' => Table::TYPE_DECIMAL,
                'length' => '12,4',
                'comment' => 'Return Payment Fee Adjusted'
            ]);
            $this->_salesConnection->addColumn($this->_salesConnection->getTableName('sales_creditmemo'),
                'return_point_not_retractable', [
                'type' => Table::TYPE_INTEGER,
                'size' => 11,
                'options' => array('nullable' => false, 'default' => 0),
                'comment' => 'Return point not retractable',
            ]);
            $this->_salesConnection->addColumn($this->_salesConnection->getTableName('sales_creditmemo'),
                'total_return_amount_adj', [
                'type' => Table::TYPE_DECIMAL,
                'length' => '12,4',
                'comment' => 'Total return amount adj'
            ]);
            $this->_salesConnection->addColumn($this->_salesConnection->getTableName('sales_creditmemo'),
                'total_return_point_adjusted', [
                'type' => Table::TYPE_INTEGER,
                'comment' => 'Final total return point adjusted (after calculate)'
            ]);
        }

        if (version_compare($context->getVersion(), '2.0.0') < 0) {
            $this->topology->install();
        }

        if (version_compare($context->getVersion(), '2.0.2') < 0) {
            $this->_salesConnection->addColumn(
                $this->_salesConnection->getTableName('sales_order_additional_information'),
                'shipping_reason',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'    =>  255,
                    'comment' => 'Shipping reason'
                ]
            );
            $this->_salesConnection->addColumn(
                $this->_salesConnection->getTableName('sales_order_additional_information'),
                'shipping_cause',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'    =>  255,
                    'comment' => 'Shipping cause'
                ]
            );
        }
        $setup->endSetup();
    }
}