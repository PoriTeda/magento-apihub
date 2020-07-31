<?php

namespace Riki\SubscriptionMachine\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class UpgradeSchema
 * @package Riki\SubscriptionCourse\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * UpgradeSchema constructor.
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /** setup data for Captured amount calculation option */
        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $defaultConnection = $this->resourceConnection->getConnection();
            $defaultConnection
                ->addColumn('riki_machine_condition', 'payment_method', [
                    'type' => Table::TYPE_TEXT,
                    ['unsigned' => true, 'nullable' => true],
                    'comment' => 'Payment Method'
                ]);
            $defaultConnection
                ->addColumn('riki_machine_condition', 'sku_specified', [
                    'type' => Table::TYPE_BOOLEAN,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'comment' => 'SKU specified'
                ]);

            $salesConnection = $this->resourceConnection->getConnection('sales');
            $table = $setup->getTable('subscription_profile');
            if (!$salesConnection->tableColumnExists($table, 'variable_fee')) {
                $salesConnection->addColumn(
                    $table,
                    'variable_fee',
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'length' => '12,4',
                        'nullable' => true,
                        'comment' => 'Variable fee'
                    ]
                );
            }
            if (!$salesConnection->tableColumnExists($table, 'reference_profile_id')) {
                $salesConnection->addColumn(
                    $table,
                    'reference_profile_id',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'length' => 10,
                        'unsigned' => true,
                        'nullable' => true,
                        'comment' => 'Reference profile id'
                    ]
                );
            }
            if (!$salesConnection->tableColumnExists($table, 'is_monthly_fee_confirmed')) {
                $salesConnection->addColumn(
                    $table,
                    'is_monthly_fee_confirmed',
                    [
                        'type' => Table::TYPE_BOOLEAN,
                        'nullable' => true,
                        'comment' => 'Is monthly fee confirmed'
                    ]
                );
            }
        }

        $setup->endSetup();
    }
}
