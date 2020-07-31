<?php
// @codingStandardsIgnoreFile
namespace Riki\SalesRule\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    protected $_quoteItemConnection;
    protected $_orderItemConnection;

    /**
     * @param \Magento\Quote\Model\ResourceModel\Quote\Item $quoteItemResource
     * @param \Magento\Sales\Model\ResourceModel\Order\Item $orderItemResource
     */
    public function __construct(
        \Magento\Quote\Model\ResourceModel\Quote\Item $quoteItemResource,
        \Magento\Sales\Model\ResourceModel\Order\Item $orderItemResource
    ){
        $this->_quoteItemConnection = $quoteItemResource->getConnection();
        $this->_orderItemConnection = $orderItemResource->getConnection();
    }

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $connection = $setup->getConnection();
        if (version_compare($context->getVersion(), '0.1.1') < 0) {
            $tableSalesOrder = $setup->getTable('sales_order');
            if ($setup->getConnection()->isTableExists($tableSalesOrder) == true) {

                if (!$connection->tableColumnExists($tableSalesOrder, 'wbs')) {
                    $setup->getConnection()->addColumn(
                        $tableSalesOrder, 'wbs', ['type' => Table::TYPE_TEXT, 'length' => 255, 'comment' => 'WBS Code']
                    );
                }

                if (!$connection->tableColumnExists($tableSalesOrder, 'account_code')) {
                    $setup->getConnection()->addColumn(
                        $tableSalesOrder, 'account_code', ['type' => Table::TYPE_TEXT, 'length' => 255, 'comment' => 'Account code']
                    );
                }
            }
        }

        if (version_compare($context->getVersion(), '0.1.2') < 0) {
            $tableSalesOrder = $setup->getTable('sales_order');
            if ($setup->getConnection()->isTableExists($tableSalesOrder) == true) {

                if (!$connection->tableColumnExists($tableSalesOrder, 'ddate_tmp')) {
                    $setup->getConnection()->addColumn(
                        $tableSalesOrder, 'ddate_tmp', ['type' => Table::TYPE_TEXT, 'length' => 255, 'comment' => 'Delivery Date Tmp']
                    );
                }

                if (!$connection->tableColumnExists($tableSalesOrder, 'dtime_tmp')) {
                    $setup->getConnection()->addColumn(
                        $tableSalesOrder, 'dtime_tmp', ['type' => Table::TYPE_TEXT, 'length' => 255, 'comment' => 'Delivery Time Tmp']
                    );
                }
            }
        }

        if (version_compare($context->getVersion(), '0.1.3') < 0) {
            $tableSalesOrder = $setup->getTable('sales_order');
            if ($setup->getConnection()->isTableExists($tableSalesOrder) == true) {

                if ($connection->tableColumnExists($tableSalesOrder, 'ddate_tmp')) {
                    $setup->getConnection()->dropColumn($tableSalesOrder, 'ddate_tmp');
                }

                if ($connection->tableColumnExists($tableSalesOrder, 'dtime_tmp')) {
                    $setup->getConnection()->dropColumn($tableSalesOrder, 'dtime_tmp');
                }

                if (!$connection->tableColumnExists($tableSalesOrder, 'min_delivery_date')) {
                    $setup->getConnection()->addColumn(
                        $tableSalesOrder, 'min_delivery_date', ['type' => Table::TYPE_TEXT, 'length' => 255, 'comment' => 'Min delivery date']
                    );
                }
            }
        }
        // add field export to DI
        if (version_compare($context->getVersion(), '0.1.4') < 0) {
            $connection = $setup->getConnection();

            $tableSalesRules = $setup->getTable('salesrule');
            if ($setup->getConnection()->isTableExists($tableSalesRules) == true) {

                if (!$connection->tableColumnExists($tableSalesRules, 'created_at')) {
                    $setup->getConnection()->addColumn(
                        $tableSalesRules, 'created_at',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        null,
                        ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                        'Creation Time'
                    );
                }

                if (!$connection->tableColumnExists($tableSalesRules, 'updated_at')) {
                    $setup->getConnection()->addColumn(
                        $tableSalesRules, 'updated_at',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        null,
                        ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
                        'Update Time'
                    );
                }
            }
        }
        //reward point
        if (version_compare($context->getVersion(), '0.1.5') < 0) {
            $connection->addColumn(
                $installer->getTable('salesrule'),
                'point_expiration_period',
                [
                    'type' => Table::TYPE_INTEGER, 'unsigned' => true,
                    'nullable' => true, 'default' => null, 'comment' => 'Point expiration (in days)'
                ]
            );
        }

        if (version_compare($context->getVersion(), '0.1.6') < 0) {
            $connection = $setup->getConnection();

            $tableSalesRules = $setup->getTable('salesrule');
            if ($setup->getConnection()->isTableExists($tableSalesRules) == true) {

                if ($connection->tableColumnExists($tableSalesRules, 'created_at')) {
                    $setup->getConnection()->dropColumn(
                        $tableSalesRules, 'created_at'
                    );
                }

                if ($connection->tableColumnExists($tableSalesRules, 'updated_at')) {
                    $setup->getConnection()->dropColumn(
                        $tableSalesRules, 'updated_at'
                    );
                    $setup->getConnection()->addColumn(
                        $tableSalesRules, 'promo_updated_at',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        null,
                        ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                        'Update Time'
                    );
                }
            }
        }

        if (version_compare($context->getVersion(), '0.2.0') < 0) {
            $this->_quoteItemConnection->addColumn(
                $setup->getTable('quote_item'), 'applied_rules_breakdown',
                [
                    'type' => Table::TYPE_TEXT,
                    'comment'   =>  'Discount amount for each rule'
                ]
            );

            $this->_orderItemConnection->addColumn(
                $setup->getTable('sales_order_item'), 'applied_rules_breakdown',
                [
                    'type' => Table::TYPE_TEXT,
                    'comment'   =>  'Discount amount for each rule'
                ]
            );
        }

        if (version_compare($context->getVersion(), '0.2.1') < 0) {
            if(!$setup->tableExists($setup->getTable('riki_order_salesrule'))){
                $table = $this->_orderItemConnection->newTable($setup->getTable('riki_order_salesrule'))
                    ->addColumn('order_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        ['unsigned' => true, 'nullable' => false, 'primary' => true, 'default' => '0'],
                        'Order Entity Id'
                    )->addColumn(
                        'salesrule_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        ['unsigned' => true, 'nullable' => false, 'primary' => true, 'default' => '0'],
                        'Salesrule Entity Id'
                    )->addColumn(
                        'description',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        [],
                        'Salesrule Description'
                    )->addIndex(
                        $setup->getIdxName('riki_order_salesrule', ['order_id']),
                        ['order_id']
                    )->addIndex(
                        $setup->getIdxName('riki_order_salesrule', ['salesrule_id']),
                        ['salesrule_id']
                    )->addForeignKey(
                        $setup->getFkName(
                            'riki_order_salesrule',
                            'order_id',
                            'sales_order',
                            'entity_id'
                        ),
                        'order_id',
                        $setup->getTable('sales_order'),
                        'entity_id',
                        \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                    )
                    ->setComment('Order Promotion List Table');

                $this->_orderItemConnection->createTable($table);
            }
        }
        if (version_compare($context->getVersion(), '0.2.2') < 0) {
            $this->_orderItemConnection->addColumn(
                $setup->getTable('sales_order_item'), 'applied_rules_catalog',
                [
                    'type' => Table::TYPE_TEXT,
                    'comment'   =>  'Catalog rule for order item'
                ]
            );
        }

        if (version_compare($context->getVersion(), '0.2.3') < 0) {
            $setup->getConnection()->dropIndex($setup->getTable('riki_rewards_salesrule'), $connection->getPrimaryKeyName('riki_rewards_salesrule'));
            $table = 'riki_rewards_salesrule';
            $setup->getConnection()->addIndex(
                $table,
                $setup->getIdxName($table, ['rule_id']),
                ['rule_id'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_PRIMARY
            );
        }

        $installer->endSetup();
    }
}