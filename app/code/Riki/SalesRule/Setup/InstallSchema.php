<?php
// @codingStandardsIgnoreFile
namespace Riki\SalesRule\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;


class InstallSchema implements InstallSchemaInterface
{
    protected $_ruleSetup;

    public function __construct(
        \Riki\Rule\Setup\RuleSetup $ruleSetup
    )
    {
        $this->_ruleSetup = $ruleSetup;
    }

    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $connection = $setup->getConnection();

        $table = $setup->getTable('salesrule');

        if ($connection->isTableExists($table)) {
            $this->_ruleSetup->addTimeColumns($table);
        }

        if ($connection->isTableExists($table)) {
            if (!$connection->tableColumnExists($table, 'subscription')) {
                $connection->addColumn(
                    $table, 'subscription', ['type' => Table::TYPE_SMALLINT, 'comment' => 'Is Subscription?']
                );
            }

            if (!$connection->tableColumnExists($table, 'wbs')) {
                $connection->addColumn(
                    $table, 'wbs', ['type' => Table::TYPE_TEXT, 'length' => 255, 'comment' => 'Launch date']
                );
            }

            if (!$connection->tableColumnExists($table, 'subscription_delivery')) {
                $connection->addColumn(
                    $table, 'subscription_delivery', ['type' => Table::TYPE_SMALLINT, 'default' => 3, 'comment' => 'Subscription delivery type, 1 = Every N delivery, 2 = On N delivery, 3 = All deliveries']
                );
            }

            if (!$connection->tableColumnExists($table, 'delivery_n')) {
                $connection->addColumn(
                    $table, 'delivery_n', ['type' => Table::TYPE_SMALLINT, 'comment' => 'Delivery number']
                );
            }

            if (!$connection->tableColumnExists($table, 'free_cod_charge')) {
                $connection->addColumn(
                    $table, 'free_cod_charge', ['type' => Table::TYPE_SMALLINT, 'comment' => 'Is free COD charge?']
                );
            }

            if (!$connection->tableColumnExists($table, 'account_code')) {
                $connection->addColumn(
                    $table, 'account_code', ['type' => Table::TYPE_TEXT, 'length' => 255, 'comment' => 'Account code']
                );
            }

            if (!$connection->tableColumnExists($table, 'sap_condition_type')) {
                $connection->addColumn(
                    $table, 'sap_condition_type', ['type' => Table::TYPE_TEXT, 'length' => 255, 'comment' => 'SAP condition type']
                );
            }
        }

        if (!$connection->isTableExists($setup->getTable('salesrule_subscription_course'))) {
            $tbl = $connection->newTable($setup->getTable('salesrule_subscription_course'))
                ->addColumn(
                    'rule_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Rule ID'
                )
                ->addColumn(
                    'course_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Course ID'
                )
                ->addForeignKey(
                    $setup->getFkName('salesrule_subscription_course', 'rule_id', 'salesrule', 'rule_id'),
                    'rule_id',
                    $setup->getTable('salesrule'),
                    'rule_id',
                    Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $setup->getFkName('salesrule_subscription_course', 'course_id', 'subscription_course', 'course_id'),
                    'course_id',
                    $setup->getTable('subscription_course'),
                    'course_id',
                    Table::ACTION_CASCADE
                )
                ->setComment(
                    'Salesrule Subscription course'
                );

            $connection->createTable($tbl);
        }

        if (!$connection->isTableExists($setup->getTable('salesrule_subscription_frequency'))) {
            $tbl = $connection->newTable($setup->getTable('salesrule_subscription_frequency'))
                ->addColumn(
                    'rule_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Rule ID'
                )
                ->addColumn(
                    'frequency_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Frequency ID'
                )
                ->addForeignKey(
                    $setup->getFkName('salesrule_subscription_frequency', 'rule_id', 'salesrule', 'rule_id'),
                    'rule_id',
                    $setup->getTable('salesrule'),
                    'rule_id',
                    Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $setup->getFkName('salesrule_subscription_frequency', 'frequency_id', 'subscription_frequency', 'frequency_id'),
                    'frequency_id',
                    $setup->getTable('subscription_frequency'),
                    'frequency_id',
                    Table::ACTION_CASCADE
                )
                ->setComment(
                    'Salesrule Subscription frequency'
                );

            $connection->createTable($tbl);
        }

        if (!$connection->isTableExists($setup->getTable('riki_rewards_salesrule'))) {
            /**
             * Create table 'riki_rewards_salesrule'
             */
            $table = $connection->newTable(
                $setup->getTable('riki_rewards_salesrule')
            )->addColumn(
                'rule_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Rule Id'
            )->addColumn(
                'points_delta',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Points Delta'
            )->addColumn(
                'type_by',
                Table::TYPE_TEXT,
                NULL,
                ['nullable' => true],
                'Type by'
            )->addIndex(
                $setup->getIdxName(
                    'riki_rewards_salesrule',
                    ['rule_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['rule_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addForeignKey(
                $setup->getFkName('riki_rewards_salesrule', 'rule_id', 'salesrule', 'rule_id'),
                'rule_id',
                $setup->getTable('salesrule'),
                'rule_id',
                Table::ACTION_CASCADE
            )->setComment(
                'Riki Shopping point link to Salesrule'
            );

            $connection->createTable($table);
        }

        $setup->endSetup();
    }
}