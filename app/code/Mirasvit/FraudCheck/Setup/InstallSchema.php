<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-fraud-check
 * @version   1.0.38
 * @copyright Copyright (C) 2019 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\FraudCheck\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $table = $installer->getConnection()->newTable(
            $installer->getTable('mst_fraud_check_rule')
        )->addColumn(
            'rule_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'identity' => true, 'primary' => true],
            'Rule Id'
        )->addColumn(
            'name',
            Table::TYPE_TEXT,
            255,
            ['unsigned' => false, 'nullable' => false],
            'Name'
        )->addColumn(
            'conditions_serialized',
            Table::TYPE_TEXT,
            '64K',
            ['unsigned' => false, 'nullable' => false],
            'Conditions Serialized'
        )->addColumn(
            'actions_serialized',
            Table::TYPE_TEXT,
            '64K',
            ['unsigned' => false, 'nullable' => false],
            'Actions Serialized'
        )->addColumn(
            'is_active',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => false, 'nullable' => false, 'default' => 0],
            'Is Active'
        )->addColumn(
            'status',
            Table::TYPE_TEXT,
            '255',
            ['unsigned' => false, 'nullable' => false],
            'Fraud Status'
        );
        $installer->getConnection()->createTable($table);

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'fraud_score',
            [
                'type'     => Table::TYPE_TEXT,
                'nullable' => true,
                'comment'  => 'Fraud Check Score Calculation',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'fraud_status',
            [
                'type'     => Table::TYPE_TEXT,
                'nullable' => true,
                'comment'  => 'Fraud Status',
            ]
        );

        $installer->getConnection()->addIndex(
            $installer->getTable('sales_order'),
            $installer->getIdxName('sales_order', ['base_grand_total']),
            ['base_grand_total']
        );

        $installer->getConnection()->addIndex(
            $installer->getTable('sales_order'),
            $installer->getIdxName('sales_order', ['customer_email']),
            ['customer_email']
        );

        $installer->getConnection()->addIndex(
            $installer->getTable('sales_order'),
            $installer->getIdxName('sales_order', ['remote_ip']),
            ['remote_ip']
        );

        $installer->endSetup();
    }
}
