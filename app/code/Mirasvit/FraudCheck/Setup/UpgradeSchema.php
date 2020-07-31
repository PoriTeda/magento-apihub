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

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Mirasvit\Affiliate\Api\Data\AccountCustomerInterface;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD)
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $installer->getConnection()->addColumn(
                $installer->getTable('sales_order_grid'),
                'fraud_score',
                [
                    'type'     => Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment'  => 'Fraud Check Score Calculation',
                ]
            );

            $installer->getConnection()->addColumn(
                $installer->getTable('sales_order_grid'),
                'fraud_status',
                [
                    'type'     => Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment'  => 'Fraud Status',
                ]
            );
        } elseif (version_compare($context->getVersion(), '1.0.2') < 0) {
            $installer->getConnection()->modifyColumn(
                $installer->getTable('sales_order'),
                'fraud_score',
                [
                    'type'     => Table::TYPE_INTEGER,
                    'nullable' => true,
                    'comment'  => 'Fraud Check Score Calculation',
                ]
            );
            $installer->getConnection()->addIndex(
                $installer->getTable('sales_order'),
                $installer->getIdxName(
                    $installer->getTable('sales_order'),
                    ['fraud_score']
                ),
                ['fraud_score']
            );

            $installer->getConnection()->modifyColumn(
                $installer->getTable('sales_order_grid'),
                'fraud_score',
                [
                    'type'     => Table::TYPE_INTEGER,
                    'nullable' => true,
                    'comment'  => 'Fraud Check Score Calculation',
                ]
            );
            $installer->getConnection()->addIndex(
                $installer->getTable('sales_order_grid'),
                $installer->getIdxName(
                    $installer->getTable('sales_order'),
                    ['fraud_score']
                ),
                ['fraud_score']
            );
        }
    }
}
