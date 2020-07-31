<?php
namespace Riki\MessageQueue\Setup;

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
        
        if (version_compare($context->getVersion(), '1.0.2') < 0) {
            $connection->dropTable($connection->getTableName('riki_queue_lock'));

            $tableName = $connection->getTableName('riki_message_queue_lock');
            $connection->dropTable($tableName);

            $table = $connection->newTable($tableName)
                ->addColumn(
                    'lock_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'Topic name'
                )->addColumn(
                    'topic_name',
                    Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                    ],
                    'Topic name'
                )->addColumn(
                    'entity_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => false
                    ],
                    'Object entity id'
                )->addColumn(
                    'pushed_by',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Action name that push this message'
                )->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => false,
                        'default' => Table::TIMESTAMP_INIT
                    ],
                    'Created at'
                )->addIndex(
                    $connection->getIndexName(
                        $tableName,
                        ['topic_name']
                    ),
                    ['topic_name']
                )->addIndex(
                    $connection->getIndexName(
                        $tableName,
                        ['created_at']
                    ),
                    ['created_at']
                )->addIndex(
                    $connection->getIndexName(
                        $tableName,
                        ['entity_id','topic_name', 'pushed_by']
                    ),
                    ['entity_id', 'topic_name', 'pushed_by'],
                    ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
                );
            $connection->createTable($table);
        }

        $setup->endSetup();
    }
}