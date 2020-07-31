<?php
// @codingStandardsIgnoreFile
namespace Riki\Fraud\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;


class MirasvitFraudCheckUpgradeSchema implements UpgradeSchemaInterface {

    /**
     * @var \Riki\Sales\Helper\ConnectionHelper
     */
    private $connectionHelper;

    /**
     * MirasvitFraudCheckUpgradeSchema constructor.
     *
     * @param \Riki\Sales\Helper\ConnectionHelper $connectionHelper
     */
    public function __construct(
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper
    ) {
        $this->connectionHelper = $connectionHelper;
    }

    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context )
    {
        $installer = $setup;

        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $this->version101();
        }

        $installer->endSetup();
    }

    private function version101()
    {
        $salesConnection = $this->connectionHelper->getSalesConnection();

        $orderGridTable = $salesConnection->getTableName('sales_order_grid');

        if ($salesConnection->isTableExists($orderGridTable)) {

            $salesConnection->addColumn(
                $orderGridTable,
                'fraud_score',
                [
                    'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment'  => 'Fraud Check Score Calculation',
                ]
            );

            $salesConnection->addColumn(
                $orderGridTable,
                'fraud_status',
                [
                    'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment'  => 'Fraud Status',
                ]
            );
        }
    }
}