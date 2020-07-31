<?php
// @codingStandardsIgnoreFile
namespace Riki\Logging\Setup;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;
class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $installer = $setup;
        $connection = $installer->getConnection();
        $table = $installer->getTable('magento_logging_event');
        $columns = [
            'session_hash' => [
                'type' => Table::TYPE_TEXT,
                'length' => '100',
                'nullable' => true,
                'comment' => 'Session hash',
                'default' => '0',
            ],
        ];
        foreach ($columns as $name => $definition) {
            $connection->addColumn($table, $name, $definition);
        }
        $setup->endSetup();
    }
}