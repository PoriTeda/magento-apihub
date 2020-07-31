<?php
// @codingStandardsIgnoreFile
namespace Riki\User\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * Upgrade
     *
     * @param SchemaSetupInterface   $setup   setup
     * @param ModuleContextInterface $context context
     *
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        //handle all possible upgrade versions

        if (version_compare($context->getVersion(), '1.0.0') < 0) {
            //create  new table if exist
            $tableName = 'admin_password_directory';
            if(!$setup->getConnection()->isTableExists($tableName)) {
                $table = $installer->getConnection()
                    ->newTable($installer->getTable($tableName))
                    ->addColumn(
                        'pw_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true,'auto_increment' => true],
                        'Id'
                    )->addColumn(
                        'ng_word',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        250,
                        ['nullable' => true],
                        'ng word'
                    )->addColumn(
                        'created_datetime',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        255,
                        [],
                        'created datetime'
                    );
                $res = $installer->getConnection()->createTable($table);
            }




        }

        $installer->endSetup();
    }
}