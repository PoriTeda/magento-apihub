<?php
// @codingStandardsIgnoreFile
namespace Riki\SubscriptionProfileDisengagement\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    )
    {
        $setup->startSetup();

        $connection = $setup->getConnection();

        if(!$setup->tableExists($setup->getTable('subscription_disengagement_reason'))){
            $table = $connection->newTable($setup->getTable('subscription_disengagement_reason'))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Entity id'
                )->addColumn(
                    'code',
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'nullable' => false
                    ],
                    'Reason code'
                )->addColumn(
                    'title',
                    Table::TYPE_TEXT,
                    null,
                    [
                        'nullable' => false
                    ],
                    'Reason title'
                )->addColumn(
                    'status',
                    Table::TYPE_BOOLEAN,
                    null,
                    [
                        'default' => 1
                    ],
                    'Status(Deleted:0, Active:1)'
                )->addIndex(
                    $setup->getIdxName('subscription_disengagement_reason', ['code'], true),
                    ['code'],
                    ['type' => 'unique']
                )->setComment('Subscription Disengagement Reason');

            $connection->createTable($table);
        }


        ///////////

        $table = $setup->getTable('subscription_profile');

        if(!$connection->tableColumnExists($table, 'disengagement_date')){
            $connection->addColumn($table, 'disengagement_date', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                'comment' => 'Disengaged at',
            ]);
        }

        if(!$connection->tableColumnExists($table, 'disengagement_reason')) {
            $connection->addColumn($table, 'disengagement_reason', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                'unsigned' => true,
                'comment' => 'Disengaged reason Id'
            ]);
        }

        if(!$connection->tableColumnExists($table, 'disengagement_user')) {
            $connection->addColumn($table, 'disengagement_user', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                'comment' => 'Admin username'
            ]);
        }

        $connection->addForeignKey(
            $setup->getFkName($setup->getTable('subscription_profile'), 'disengagement_reason', $setup->getTable('subscription_disengagement_reason'), 'id'),
            $setup->getTable('subscription_profile'),
            'disengagement_reason',
            $setup->getTable('subscription_disengagement_reason'),
            'id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        );

        $setup->endSetup();
    }

}
