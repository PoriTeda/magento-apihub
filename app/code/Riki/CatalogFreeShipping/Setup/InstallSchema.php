<?php
// @codingStandardsIgnoreFile
namespace Riki\CatalogFreeShipping\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $connection = $setup->getConnection();

        if(!$setup->tableExists($setup->getTable('riki_catalog_free_shipping'))){
            $table = $connection->newTable($setup->getTable('riki_catalog_free_shipping'))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Entity Id'
                )->addColumn(
                    'website_ids',
                    Table::TYPE_TEXT,
                    null,
                    [],
                    'Website Ids'
                )->addColumn(
                    'customer_group_ids',
                    Table::TYPE_TEXT,
                    null,
                    [],
                    'Customer Group Ids'
                )->addColumn(
                    'memberships',
                    Table::TYPE_TEXT,
                    null,
                    [],
                    'Customer Memberships'
                )->addColumn(
                    'ph_code',
                    Table::TYPE_TEXT,
                    255,
                    [
                    ],
                    'Product PH Code'
                )->addColumn(
                    'sku',
                    Table::TYPE_TEXT,
                    255,
                    [
                    ],
                    'Product Sku'
                )->addColumn(
                    'wbs',
                    Table::TYPE_TEXT,
                    255,
                    [
                    ],
                    'WBS'
                )->addColumn(
                    'from_date',
                    Table::TYPE_DATETIME,
                    null,
                    [
                    ],
                    'Start Date To Apply'
                )->addColumn(
                    'to_date',
                    Table::TYPE_DATETIME,
                    null,
                    [
                    ],
                    'End Date To Apply'
                )->setComment('Catalog Product Free Shipping');

            $connection->createTable($table);
        }

        $installer->endSetup();
    }
}
