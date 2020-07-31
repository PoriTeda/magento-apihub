<?php
// @codingStandardsIgnoreFile
namespace Riki\Loyalty\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var \Riki\Sales\Helper\ConnectionHelper
     */
    protected $connectionHelper;

    /**
     * UpgradeSchema constructor.
     *
     * @param \Riki\Sales\Helper\ConnectionHelper $connectionHelper
     */
    public function __construct(\Riki\Sales\Helper\ConnectionHelper $connectionHelper)
    {
        $this->connectionHelper = $connectionHelper;
    }

    /**
     * Upgrade schema
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.3') < 0) {
            $this->upgradeToVersion103($installer);
        }
        if (version_compare($context->getVersion(), '1.0.4') < 0) {
            $this->upgradeToVersion104($installer);
        }
        if (version_compare($context->getVersion(), '1.0.5') < 0) {
            $this->upgradeToVersion105($installer);
        }
        if (version_compare($context->getVersion(), '1.0.7') < 0) {
            $this->upgradeToVersion107();
        }
        if (version_compare($context->getVersion(), '1.0.8') < 0) {
            $this->upgradeToVersion108();
        }
        if (version_compare($context->getVersion(), '1.1.1') < 0) {
            $this->upgradeToVersion111();
        }
        if (version_compare($context->getVersion(), '1.1.2') < 0) {
            $this->upgradeToVersion112();
        }
        if (version_compare($context->getVersion(), '1.1.3') < 0) {
            $this->upgradeToVersion113();
        }
        if (version_compare($context->getVersion(), '1.1.4') < 0) {
            $this->upgradeToVersion114();
        }
        if (version_compare($context->getVersion(), '1.1.5') < 0) {
            $this->upgradeToVersion115();
        }

        if (version_compare($context->getVersion(), '1.1.6') < 0) {
            $this->upgradeToVersion116();
        }

        if (version_compare($context->getVersion(), '1.1.7') < 0) {
            $connection = $this->connectionHelper->getSalesConnection();

            $connection->dropIndex(
                'riki_reward_point',
                $installer->getIdxName('riki_reward_point', ['reward_id'])
            );

            $connection->addIndex(
                'riki_reward_point',
                $installer->getIdxName('riki_reward_point', ['status']),
                ['status']
            );

            $connection->addIndex(
                'riki_reward_point',
                $installer->getIdxName('riki_reward_point', ['created_at']),
                ['created_at']
            );

            $connection->addIndex(
                'riki_reward_point',
                $installer->getIdxName('riki_reward_point', ['updated_at']),
                ['updated_at']
            );
        }
        if (version_compare($context->getVersion(), '1.1.8') < 0) {
            $this->upgradeToVersion118();
        }

        $installer->endSetup();
    }

    /**
     * @return $this
     */
    public function upgradeToVersion112()
    {
        $connection = $this->connectionHelper->getSalesConnection();
        $table = $connection->getTableName('riki_reward_point');
        if (!$connection->isTableExists($table)) {
            return $this;
        }
        $connection->addColumn(
            $table,
            'level',
            [
                'type' => Table::TYPE_SMALLINT,
                'comment' => 'Promotion level',
                'default' => null
            ]
        );
        return $this;
    }

    /**
     * @return $this
     */
    private function upgradeToVersion111()
    {
        $connection = $this->connectionHelper->getSalesConnection();
        $table = $connection->getTableName('riki_reward_point');

        if (!$connection->isTableExists($table)) {
            return $this;
        }

        $connection->addColumn(
            $table,
            'sales_rule_id',
            [
                'type' => Table::TYPE_INTEGER,
                'comment' => 'Sales Rule Id'
            ]
        );

        return $this;
    }

    /**
     * @return $this
     */
    private function upgradeToVersion108()
    {
        $connection = $this->connectionHelper->getCheckoutConnection();
        $table = $connection->getTableName('riki_reward_quote');
        $quote = $connection->getTableName('quote');
        //delete abandoned quote in riki_reward_quote
        $sql = $connection->select()->from(
            $table, ['id']
        )->joinLeft($quote, "$table.quote_id = $quote.entity_id", 'entity_id'
        )->where('entity_id is NULL');
        $connection->query($connection->deleteFromSelect($sql, $table));
        $connection->addColumn(
            $table,
            'created_at',
            [
                'type' => Table::TYPE_TIMESTAMP,
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT,
                'comment' => 'Created At'
            ]
        );
        $connection->addColumn(
            $table,
            'updated_at',
            [
                'type' => Table::TYPE_TIMESTAMP,
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT_UPDATE,
                'comment' => 'Updated At'
            ]
        );
        $connection->addForeignKey(
            $connection->getForeignKeyName($table, 'quote_id', $connection->getTableName('quote'), 'entity_id'),
            $table,
            'quote_id',
            $connection->getTableName('quote'),
            'entity_id',
            Table::ACTION_CASCADE
        );
        return $this;
    }

    /**
     * @return $this
     */
    private function upgradeToVersion107()
    {
        $connection = $this->connectionHelper->getSalesConnection();
        $table = $connection->getTableName('riki_reward_point');
        if (!$connection->isTableExists($table)) {
            return $this;
        }
        $connection->addColumn(
            $table,
            'qty',
            [
                'type' => Table::TYPE_DECIMAL,
                'comment' => 'Qty Ordered',
                'nullable' => false,
                'default' => 1.0000
            ]
        );
        return $this;
    }

    /**
     * @param $installer
     * @return $this
     */
    private function upgradeToVersion105($installer)
    {
        $connection = $installer->getConnection();
        $table = $installer->getTable('riki_reward_point');
        if (!$connection->isTableExists($table)) {
            return $this;
        }
        $connection->addColumn(
            $table,
            'sku',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Product SKU'
            ]
        );
        return $this;
    }
    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $installer
     * @return $this
     */
    private function upgradeToVersion103($installer)
    {
        $connection = $installer->getConnection();
        $table = $installer->getTable('riki_reward_quote');
        if (!$connection->isTableExists($table)) {
            return $this;
        }
        if ($connection->tableColumnExists($table, 'points_refund')) {
            $connection->dropColumn($table, 'points_refund');
        }
        $connection->addColumn(
            $table,
            'reward_user_setting',
            [
                'type' => Table::TYPE_SMALLINT,
                'comment' => 'Reward User Setting'
            ]
        );
        $connection->addColumn(
            $table,
            'reward_user_redeem',
            [
                'type' => Table::TYPE_INTEGER,
                'unsigned' => true,
                'comment' => 'Default point use'
            ]
        );
        $connection->dropIndex($table, $installer->getIdxName($table, ['quote_id']));
        $connection->addIndex(
            $table, $installer->getIdxName($table, ['quote_id']), ['quote_id'], AdapterInterface::INDEX_TYPE_UNIQUE
        );
        return $this;
    }

    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $installer
     * @return $this
     */
    private function upgradeToVersion104($installer)
    {
        $connection = $installer->getConnection();
        $table = $installer->getTable('riki_reward_point');
        if (!$connection->isTableExists($table)) {
            return $this;
        }
        $connection->addColumn(
            $table,
            'created_at',
            [
                'type' => Table::TYPE_TIMESTAMP,
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT,
                'comment' => 'Creation Time'
            ]
        );
        $connection->addColumn(
            $table,
            'updated_at',
            [
                'type' => Table::TYPE_TIMESTAMP,
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT_UPDATE,
                'comment' => 'Update Time'
            ]
        );
        return $this;
    }

    /**
     * added index
     *
     * @return $this
     */
    private function upgradeToVersion113()
    {
        /** @var  $connection */
        $connection = $this->connectionHelper->getCheckoutConnection();
        $table = $connection->getTableName('riki_reward_point');
        if ($connection->isTableExists($table)) {
             $connection->addIndex(
                 $table,
                 $connection->getIndexName(
                     $table,
                     ['customer_code', 'status']
                 ),
                 ['customer_code', 'status']
             );
        }

        return $this;
    }

    private function upgradeToVersion114()
    {
        /** @var  $connection */
        $connection = $this->connectionHelper->getSalesConnection();
        $table = $connection->getTableName('riki_reward_point');
        if ($connection->isTableExists($table)) {
            $connection->addIndex(
                $table,
                $connection->getIndexName(
                    $table,
                    ['customer_code', 'status']
                ),
                ['customer_code', 'status']
            );
        }


        return $this;
    }
    private function upgradeToVersion115()
    {
        /** @var  $connection */
        $connection = $this->connectionHelper->getSalesConnection();
        $table = $connection->getTableName('riki_reward_point');
        if ($connection->isTableExists($table)) {
            $connection->addIndex(
                $table,
                $connection->getIndexName(
                    $table,
                    ['order_no', 'status'],AdapterInterface::INDEX_TYPE_INDEX
                ),
                ['order_no', 'status'], AdapterInterface::INDEX_TYPE_INDEX
            );
        }
        return $this;
    }

    private function upgradeToVersion116()
    {
        /** @var  $connection */
        $connection = $this->connectionHelper->getSalesConnection();
        $table = $connection->getTableName('riki_reward_point');
        if ($connection->isTableExists($table)) {
            $connection->addIndex(
                $table,
                $connection->getIndexName(
                    $table,
                    ['order_item_id', 'level','status'],AdapterInterface::INDEX_TYPE_INDEX
                ),
                ['order_item_id', 'level', 'status'], AdapterInterface::INDEX_TYPE_INDEX
            );
        }
        return $this;
    }

    private function upgradeToVersion118()
    {
        /** @var  $connection */
        $connection = $this->connectionHelper->getSalesConnection();
        $table = $connection->getTableName('riki_reward_point');
        if ($connection->isTableExists($table)) {
            $connection->addColumn(
                $table,
                'point_for_trial',
                [
                    'type' => Table::TYPE_SMALLINT,
                    'length' => 1,
                    'nullable' => true,
                    'default' => null,
                    'comment' => 'Shopping point for trial'
                ]
            );
        }
        return $this;
    }
    
}