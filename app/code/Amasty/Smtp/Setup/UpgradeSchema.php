<?php
namespace Amasty\Smtp\Setup;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Upgrading process
     *
     * @param SchemaSetupInterface $setup Setup Object
     * @param ModuleContextInterface $context Context Object
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    )
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $tableName = $installer->getTable('amasty_amsmtp_log');
            $fieldName = 'relation_entity_type';
            if (!$installer->getConnection()->tableColumnExists($tableName, $fieldName)) {
                $installer->getConnection()->addColumn(
                    $tableName,
                    $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'length' => 255,
                        'comment' => 'Relation Entity Type'
                    ]
                );
            }

            $fieldName = 'relation_entity_id';
            if (!$installer->getConnection()->tableColumnExists($tableName, $fieldName)) {
                $installer->getConnection()->addColumn(
                    $tableName,
                    $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'length' => 50,
                        'comment' => 'Relation Entity ID'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.2') < 0) {
            /**
             * Create table 'amasty_amsmtp_log_new'
             */
            $table = $installer->getConnection()
               ->newTable($installer->getTable('amasty_amsmtp_log_new'))
               ->addColumn(
                   'id',
                   \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                   null,
                   ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
               )
               ->addColumn(
                   'created_at',
                   \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                   null,
                   ['nullable' => false]
               )
               ->addColumn(
                   'subject',
                   \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                   255,
                   ['nullable' => false]
               )
               ->addColumn(
                   'body',
                   \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                   null,
                   ['nullable' => false]
               )
               ->addColumn(
                   'recipient_email',
                   \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                   120,
                   ['nullable' => false]
               )
               ->addColumn(
                   'status',
                   \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                   null,
                   ['nullable' => false]
               )
                ->addColumn(
                    'relation_entity_type',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => true],
                    'Relation Entity Type'
                )
                ->addColumn(
                    'relation_entity_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['nullable' => true],
                    'Relation Entity ID'
                )->addIndex(
                    $setup->getIdxName(
                        $installer->getTable('amasty_amsmtp_log_new'),
                        ['subject', 'body', 'recipient_email'],
                        AdapterInterface::INDEX_TYPE_FULLTEXT
                    ),
                    ['subject', 'body', 'recipient_email'],
                    ['type' => AdapterInterface::INDEX_TYPE_FULLTEXT]
                )->setComment('Amasty SMTP Log Table');
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
