<?php
// @codingStandardsIgnoreFile
namespace Riki\SerialCode\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
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
        //Need to change customer_id to varchar
        $connection = $installer->getConnection();
        $tableName = $installer->getTable('riki_serial_code');
        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            if ($connection->isTableExists($tableName) == true) {
                $connection->modifyColumn(
                    $tableName,
                    'customer_id',
                    [
                        'type'=> Table::TYPE_TEXT, 'nullable' => true, 'default' => null,
                        'length' => 255, 'comment' => 'ID From ConsumerDB'
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '1.0.5') < 0) {
            if ($connection->isTableExists($tableName) == true) {
                $columns = [
                    'campaign_id' => [
                        'type' => Table::TYPE_TEXT,
                        'length' => '255',
                        'nullable' => true,
                        'comment' => 'Campaign ID',
                    ],
                    'campaign_limit' => [
                        'type' => Table::TYPE_INTEGER,
                        'length' => '100',
                        'nullable' => true,
                        'comment' => 'Limit for this campaign ID',
                    ],
                ];
                foreach ($columns as $name => $definition) {
                    $connection->addColumn($tableName, $name, $definition);
                }
            }
        }
        if (version_compare($context->getVersion(), '1.0.6') < 0) {
            $connection->addColumn(
                $tableName,
                'point_expiration_period',
                [
                    'type' => Table::TYPE_INTEGER, 'unsigned' => true,
                    'nullable' => false, 'default' => 0, 'comment' => 'For reward point (in days)'
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.0.7') < 0) {
            if ($connection->tableColumnExists($tableName, 'point_expiration_period')) {
                $fixTo = [
                    'type' => Table::TYPE_INTEGER, 'unsigned' => true,
                    'nullable' => false, 'default' => 0, 'comment' => 'For reward point (in days)'
                ];
                $connection->modifyColumn($tableName, 'point_expiration_period', $fixTo);
            }
        }
        if (version_compare($context->getVersion(), '1.0.8') < 0) {
            if ($connection->tableColumnExists($tableName, 'point_expiration_period')) {
                $fixTo = [
                    'type' => Table::TYPE_INTEGER, 'unsigned' => true,
                    'nullable' => true, 'default' => null, 'comment' => 'For reward point (in days)'
                ];
                $connection->modifyColumn($tableName, 'point_expiration_period', $fixTo);
            }
        }
        $installer->endSetup();
    }
}