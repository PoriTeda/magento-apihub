<?php
// @codingStandardsIgnoreFile
namespace Riki\TimeSlots\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        /*
         * Drop table if it exists
         */
        $installer->getConnection()->dropTable($installer->getTable('riki_timeslots'));

        /*
         * Create table riki_timeslots
         */
        $table = $installer->getConnection()->newTable(
          $installer->getTable('riki_timeslots')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            10,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Time Slots ID'
        )->addColumn(
            'position',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Position'
        )->addColumn(
            'slot_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Time Slots Name'
        )->addColumn(
            'from',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Time Slots From Hours'
        )->addColumn(
            'to',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Time Slots To Hours'
        )->addColumn(
            'delivery_type_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Available Delivery Type Code'
        );
        $installer->getConnection()->createTable($table);


        $installer->endSetup();
    }
}
