<?php
// @codingStandardsIgnoreFile
namespace Riki\SubscriptionCutOffEmail\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;


class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $connection = $setup->getConnection();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            /**
             * Create table 'riki_send_mail_cut_off_date'
             */

            $table = $connection->newTable('riki_send_mail_cut_off_date')
                ->addColumn(
                    'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,10,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true,'auto_increment' => true],
                    'ID'
                )->addColumn(
                    'profile_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,11,
                    ['nullable' => true ],
                    'Profile Id'
                )->addColumn(
                    'cut_off_date',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,null,
                    ['nullable' => false ],
                    'Cut off date'
                );

            $connection->createTable($table) ;
        }

        if (version_compare($context->getVersion(), '1.0.2') < 0) {
            if ($connection->isTableExists($setup->getTable('riki_send_mail_cut_off_date'))) {
                $table = $setup->getTable('riki_send_mail_cut_off_date');
                if (!$connection->tableColumnExists($table, 'email')) {
                    $connection->addColumn(
                        $table,
                        'email',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,null,
                        ['nullable' => false ],
                        'Email'
                    );
                    $connection->addIndex(
                        $table,
                        $connection->getIndexName($table, ['profile_id' ], true),
                        ['profile_id']
                    );
                }
            }
        }
        $setup->endSetup();
    }

}