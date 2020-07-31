<?php
// @codingStandardsIgnoreFile
namespace Riki\FairAndSeasonalGift\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;


class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $table = $installer->getConnection()
            ->newTable($installer->getTable('riki_fair_management'))
            ->addColumn(
                'fair_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Fair ID'
            )
            ->addColumn(
                'fair_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                16,
                ['unsigned' => true, 'nullable' => false, 'unique' => true],
                'Fair Code'
            )
            ->addColumn(
                'fair_year',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                4,
                ['nullable' => false]
            )
            ->addColumn(
                'fair_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                2,
                ['nullable' => false, 'default' => 1],
                'Fair type ( 1: Otyugen, 2: Oseibo )'
            )
            ->addColumn(
                'fair_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                50,
                ['nullable' => false],
                'Fair name'
            )
            ->addColumn(
                'start_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                null,
                ['nullable' => false],
                'Fair start date'
            )
            ->addColumn(
                'end_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                null,
                ['nullable' => false],
                'Fair end date'
            )
            ->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Created At'
            )
            ->addColumn(
                'update_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
                'Created At'
            )
            ->addIndex($installer->getIdxName('riki_fair_management', ['fair_code']), 'fair_code');

        $installer->getConnection()->createTable($table);


        $table2 = $installer->getConnection()
            ->newTable($installer->getTable('riki_fair_connection'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'ID'
            )
            ->addColumn(
                'fair_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['unsigned' => true, 'nullable' => false],
                'Fair Id'
            )
            ->addColumn(
                'fair_related_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['nullable' => false]
            )
            ->addColumn(
                'fair_related_order',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                2,
                ['nullable' => false],
                'Related fair priority'
            );


        $installer->getConnection()->createTable($table2);

        $table3 = $installer->getConnection()
            ->newTable($installer->getTable('riki_fair_detail'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'ID'
            )
            ->addColumn(
                'fair_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['unsigned' => true, 'nullable' => false],
                'Fair Id'
            )
            ->addColumn(
                'product_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['nullable' => false],
                'Fair product id'
            )
            ->addColumn(
                'serial_no',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                20,
                ['nullable' => false],
                'Fair serial no'
            );

        $installer->getConnection()->createTable($table3);

        $table4 = $installer->getConnection()
            ->newTable($installer->getTable('riki_fair_recommendation'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'ID'
            )
            ->addColumn(
                'fair_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                16,
                ['unsigned' => true, 'nullable' => false],
                'Fair Id'
            )
            ->addColumn(
                'recommended_fair_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['nullable' => false],
                'Related fair id'
            )
            ->addColumn(
                'product_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['nullable' => false, 'default' => 1],
                'Fair product id'
            )
            ->addColumn(
                'recommended_product_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['nullable' => false, 'default' => 1],
                'Recommend product id'
            );

        $installer->getConnection()->createTable($table4);

        $installer->endSetup();
    }
}