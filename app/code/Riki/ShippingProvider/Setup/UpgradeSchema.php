<?php
// @codingStandardsIgnoreFile
namespace Riki\ShippingProvider\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {

        $setup->startSetup();

        $connection = $setup->getConnection();

        if (version_compare($context->getVersion(), '2.0.1') < 0) {
            $quoteTable = $setup->getTable('quote');
            if ($connection->isTableExists($quoteTable) == true) {
                if (!$connection->tableColumnExists($quoteTable, 'shipping_fee_by_address')) {
                    $connection->addColumn(
                        $quoteTable,
                        'shipping_fee_by_address',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                            'length' => 255,
                            'nullable' => true,
                            'comment' => 'Shipping fee by address'
                        ]
                    );
                }
            }

            $salesOrderTable = $setup->getTable('sales_order');
            if ($connection->isTableExists($salesOrderTable) == true) {
                if (!$connection->tableColumnExists($salesOrderTable, 'shipping_fee_by_address')) {
                    $connection->addColumn(
                        $salesOrderTable,
                        'shipping_fee_by_address',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                            'length' => 255,
                            'nullable' => true,
                            'comment' => 'Shipping fee by address'
                        ]
                    );
                }
            }
        }

        if (version_compare($context->getVersion(), '2.0.2') < 0) {
            $quoteTable = $setup->getTable('quote');
            if ($connection->isTableExists($quoteTable) == true) {
                if ($connection->tableColumnExists($quoteTable, 'shipping_fee_by_address')) {
                    $connection->modifyColumn(
                        $quoteTable,
                        'shipping_fee_by_address',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                            'nullable' => true,
                            'comment' => 'Shipping fee by address'
                        ]
                    );
                }
            }

            $salesOrderTable = $setup->getTable('sales_order');
            if ($connection->isTableExists($salesOrderTable) == true) {
                if ($connection->tableColumnExists($salesOrderTable, 'shipping_fee_by_address')) {
                    $connection->modifyColumn(
                        $salesOrderTable,
                        'shipping_fee_by_address',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                            'nullable' => true,
                            'comment' => 'Shipping fee by address'
                        ]
                    );
                }
            }
        }

        $setup->endSetup();
    }
}