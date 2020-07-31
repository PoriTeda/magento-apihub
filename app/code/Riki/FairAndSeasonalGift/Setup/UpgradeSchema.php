<?php
// @codingStandardsIgnoreFile
namespace Riki\FairAndSeasonalGift\Setup;

use Magento\Eav\Setup\EavSetupFactory;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface {


    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context )
    {
        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();
        
        if (version_compare($context->getVersion(), '0.0.2') < 0) {
            $table = $setup->getTable('riki_fair_management');
            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'mem_ids')) {
                $connection->addColumn(
                    $table,
                    'mem_ids',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        255,
                        'default' => null,
                        'comment' => 'GROUP IDS (explode with ',')'
                    ]
                );
            }

            $table = $setup->getTable('riki_fair_connection');
            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'update_at')) {
                $connection->addColumn(
                    $table,
                    'update_at',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        255,
                        'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
                        'comment' => 'Updated at'
                    ]
                );
            }

            $table = $setup->getTable('riki_fair_detail');
            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'update_at')) {
                $connection->addColumn(
                    $table,
                    'update_at',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        255,
                        'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
                        'comment' => 'Updated at'
                    ]
                );
            }

            $table = $setup->getTable('riki_fair_recommendation');
            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'update_at')) {
                $connection->addColumn(
                    $table,
                    'update_at',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        255,
                        'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
                        'comment' => 'Updated at'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '0.0.3') < 0) {
            $table = $setup->getTable('riki_fair_detail');
            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'is_recommend')) {
                $connection->addColumn(
                    $table,
                    'is_recommend',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        4,
                        'default' => 0,
                        'comment' => 'Is recommend product'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '0.0.4') < 0) {
            $table = $setup->getTable('riki_fair_management');
            if ($connection->isTableExists($table) && $connection->tableColumnExists($table, 'fair_code')) {
                $connection->dropIndex($table, $installer->getIdxName('riki_fair_management', ['fair_code']));
                $connection->addIndex( $table, 'unique_fair_code', 'fair_code', 'unique');
            }
        }

        if (version_compare($context->getVersion(), '0.0.5') < 0) {
            $fair = $setup->getTable('riki_fair_management');
            $detail = $setup->getTable('riki_fair_detail');
            $related = $setup->getTable('riki_fair_connection');
            $recomment = $setup->getTable('riki_fair_recommendation');

            $column = 'update_at';
            $newColumn = 'updated_at';

            if ($connection->isTableExists($fair) && $connection->tableColumnExists($fair, $column)) {
                $connection->changeColumn(
                    $fair, $column, $newColumn,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        255,
                        'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
                        'comment' => 'Updated at'
                    ]
                );
            }

            if ($connection->isTableExists($detail) && $connection->tableColumnExists($detail, $column)) {
                $connection->changeColumn(
                    $detail, $column, $newColumn,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        255,
                        'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
                        'comment' => 'Updated at'
                    ]
                );
            }

            if ($connection->isTableExists($related) && $connection->tableColumnExists($related, $column)) {
                $connection->changeColumn(
                    $related, $column, $newColumn,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        255,
                        'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
                        'comment' => 'Updated at'
                    ]
                );
            }

            if ($connection->isTableExists($recomment) && $connection->tableColumnExists($recomment, $column)) {
                $connection->changeColumn(
                    $recomment, $column, $newColumn,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        255,
                        'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
                        'comment' => 'Updated at'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '0.0.6') < 0) {
            $table = $setup->getTable('riki_fair_detail');
            if ($connection->isTableExists($table)) {
                $connection->renameTable('riki_fair_detail', 'riki_fair_details');
            }
        }

        $setup->endSetup();
    }
}