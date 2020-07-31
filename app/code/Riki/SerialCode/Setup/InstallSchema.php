<?php
// @codingStandardsIgnoreFile
namespace Riki\SerialCode\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;
class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        //create table riki_serial_code
        $tableName = $installer->getTable('riki_serial_code');
        if (!$setup->getConnection()->isTableExists($tableName)) {
            $table = $installer->getConnection()->newTable($tableName)
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
                )
                ->addColumn('issued_point',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false, 'default' => 0],
                    'Issued point'
                )
                ->addColumn(
                    'activation_date',
                    Table::TYPE_DATETIME,
                    null,
                    ['nullable' => true, 'default' => null],
                    'Activated date'
                )
                ->addColumn(
                    'expiration_date',
                    Table::TYPE_DATETIME,
                    null,
                    ['nullable' => true, 'default' => null],
                    'Activated date'
                )
                ->addColumn(
                    'used_date',
                    Table::TYPE_DATETIME,
                    null,
                    ['nullable' => true, 'default' => null],
                    'Used date'
                )
                ->addColumn(
                    'serial_code',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Serial Code'
                )
                ->addColumn(
                    'wbs',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'WBS'
                )
                ->addColumn(
                    'account_code',
                    Table::TYPE_TEXT,
                    255,
                    ['default' => null, 'nullable' => false],
                    'Account code'
                )
                ->addColumn(
                    'status',
                    Table::TYPE_SMALLINT,
                    4,
                    ['nullable' => false, 'default' => \Riki\SerialCode\Model\Source\Status::STATUS_NOT_USED],
                    'Status'
                )
                ->addColumn(
                    'customer_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => true, 'default' => null]
                )
                ->addIndex(
                    $installer->getIdxName(
                        'riki_serial_code',
                        ['serial_code'],
                        AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    ['serial_code'],
                    ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
                );
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}