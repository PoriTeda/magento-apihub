<?php
namespace Riki\MessageQueue\Setup;

use Magento\Amqp\Model\Topology;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    protected $topology;

    public function __construct(
        Topology $topology
    ) {
        $this->topology = $topology;
    }

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $connection = $setup->getConnection();
        $table = $connection->newTable($connection->getTableName('riki_queue_lock'))
            ->addColumn(
                'topic_name',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => false,
                ],
                'Topic name'
            )->addColumn(
                'message_key',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => false
                ],
                'Object entity id or unique key'
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
                    $connection->getTableName('riki_queue_lock'),
                    ['topic_name']
                ),
                ['topic_name']
            )->addIndex(
                $connection->getIndexName(
                    $connection->getTableName('riki_queue_lock'),
                    ['message_key']
                ),
                ['message_key']
            )->addIndex(
                $connection->getIndexName(
                    $connection->getTableName('riki_queue_lock'),
                    ['topic_name', 'message_key']
                ),
                ['topic_name', 'message_key'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            );
        $connection->createTable($table);

        $this->topology->install();

        $installer->endSetup();
    }
}
