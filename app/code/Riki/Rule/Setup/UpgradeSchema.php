<?php
// @codingStandardsIgnoreFile
namespace Riki\Rule\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();

        if (version_compare($context->getVersion(), '0.1.1') < 0) {
            $table = $installer->getTable('salesrule');
            if ($connection->isTableExists($table) == true) {
                if (!$connection->tableColumnExists($table, 'wbs_promo_item_free_gift')) {
                    $connection->addColumn(
                        $table,
                        'wbs_promo_item_free_gift',
                        [
                            'type' => Table::TYPE_TEXT,
                            'length' => 255,
                            'comment' => 'Promo item / Free gift WBS code'
                        ]
                    );
                }
                if (!$connection->tableColumnExists($table, 'wbs_shopping_point')) {
                    $connection->addColumn(
                        $table,
                        'wbs_shopping_point',
                        [
                            'type' => Table::TYPE_TEXT,
                            'length' => 255,
                            'comment' => 'Shopping point WBS'
                        ]
                    );
                }
                if (!$connection->tableColumnExists($table, 'wbs_free_delivery')) {
                    $connection->addColumn(
                        $table,
                        'wbs_free_delivery',
                        [
                            'type' => Table::TYPE_TEXT,
                            'length' => 255,
                            'comment' => 'Free delivery WBS code'
                        ]
                    );
                }
                if (!$connection->tableColumnExists($table, 'wbs_free_payment_fee')) {
                    $connection->addColumn(
                        $table,
                        'wbs_free_payment_fee',
                        [
                            'type' => Table::TYPE_TEXT,
                            'length' => 255,
                            'comment' => 'Free payment fee WBS code'
                        ]
                    );
                }
            }
        }

        if (version_compare($context->getVersion(), '0.1.2') < 0) {
            /**
             * Create table 'riki_sales_order_sap_booking'
             */
            $table = $connection->newTable(
                $installer->getTable('riki_sales_order_sap_booking')
            )->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                'WBS ID'
            )->addColumn(
                'order_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true],
                'Order ID'
            )->addColumn(
                'order_item_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true],
                'Order item ID, null if the WBS saved on order level'
            )->addColumn(
                'type',
                Table::TYPE_TEXT,
                45,
                [],
                'promo_item_free_gift, shopping_point, free_delivery, free_payment_fee, account_code, sap_condition_type'
            )->addColumn(
                'value',
                Table::TYPE_TEXT,
                255,
                [],
                'AC-99999999'
            )->addColumn(
                'discount_amount',
                Table::TYPE_DECIMAL,
                '12,4',
                [],
                'capture discount amount from promotion'
            )->addColumn(
                'rule_type',
                Table::TYPE_TEXT,
                45,
                [],
                'salesrule, catalogrule, productpromotion'
            )->addColumn(
                'rule_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true],
                'the rule has been applied WBS code'
            )->addForeignKey(
                $installer->getFkName('riki_sales_order_sap_booking', 'order_id', 'sales_order', 'entity_id'),
                'order_id',
                $installer->getTable('sales_order'),
                'entity_id',
                Table::ACTION_CASCADE
            )->setComment(
                'Riki Sales Order WBS'
            );
            $connection->createTable($table);
        }

        if (version_compare($context->getVersion(), '0.1.3') < 0) {
            $table = $installer->getTable('riki_sales_order_sap_booking');
            if ($connection->isTableExists($table) == true) {
                if ($connection->tableColumnExists($table, 'discount_amount')) {
                    $connection->dropColumn($table, 'discount_amount');
                }
                $connection->addIndex(
                    $table,
                    $connection->getIndexName($table, ['order_id']),
                    ['order_id']);
                $connection->addIndex(
                    $table,
                    $connection->getIndexName($table, ['order_item_id']),
                    ['order_item_id']);
            }
        }

        if (version_compare($context->getVersion(), '0.1.4') < 0) {
            $table = $installer->getTable('catalogrule');
            if ($connection->isTableExists($table) == true) {
                $connection->addColumn(
                    $table,
                    'machine_wbs',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' => 'Free machine WBS code'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '0.1.5') < 0) {
            $table = $connection->newTable(
                $installer->getTable('riki_cumulated_gift')
            )->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                'Cumulated Gift ID'
            )->addColumn(
                'consumer_db_id',
                Table::TYPE_TEXT,
                255,
                [],
                'Consumer DB ID'
            )->addColumn(
                'sku',
                Table::TYPE_TEXT,
                64,
                [],
                'SKU'
            )->addColumn(
                'wbs',
                Table::TYPE_TEXT,
                255,
                [],
                'WBS'
            )->addColumn(
                'order_number',
                Table::TYPE_TEXT,
                32,
                [],
                'Order number - Increment Id'
            )->addColumn(
                'status',
                Table::TYPE_TEXT,
                32,
                [],
                'Attached | Not attached'
            )->setComment(
                'Riki cumulated gift'
            );
            $connection->createTable($table);
        }

        if (version_compare($context->getVersion(), '0.1.6') < 0) {
            $connection->changeColumn($installer->getTable('catalogrule'),
                'name',
                'name',
                [
                    'type' => Table::TYPE_TEXT,
                ]
            );

            $connection->changeColumn($installer->getTable('salesrule'),
                'name',
                'name',
                [
                    'type' => Table::TYPE_TEXT,
                ]
            );
        }

        if (version_compare($context->getVersion(), '0.1.7') < 0) {
            $connection->changeColumn($installer->getTable('riki_cumulated_gift'),
                'status',
                'status',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 12
                ]
            );
            $connection->addIndex(
                $installer->getTable('riki_cumulated_gift'),
                $connection->getIndexName(
                    $installer->getTable('riki_cumulated_gift'),
                    ['consumer_db_id', 'status']
                ),
                ['consumer_db_id', 'status']
            );
        }
        $installer->endSetup();
    }
}