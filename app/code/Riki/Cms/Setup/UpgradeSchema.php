<?php

namespace Riki\Cms\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();

        if (version_compare($context->getVersion(), '2.2.1') < 0) {
            $table = $setup->getTable('cms_block');
            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'course_code')) {
                $connection->addColumn(
                    $table,
                    'course_code',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        'nullable' => true,
                        'comment' => 'Subscription Course Code'
                    ]
                );
            }
        }

        $setup->endSetup();
    }
}
