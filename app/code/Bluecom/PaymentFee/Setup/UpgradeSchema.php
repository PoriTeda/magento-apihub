<?php
// @codingStandardsIgnoreFile
namespace Bluecom\PaymentFee\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var \Riki\ArReconciliation\Setup\SetupHelper
     */
    protected $_setupHelper;

    /**
     * InstallSchema constructor.
     * @param \Riki\ArReconciliation\Setup\SetupHelper $setupHelper
     */
    public function __construct(
        \Riki\ArReconciliation\Setup\SetupHelper $setupHelper
    ){
        $this->_setupHelper = $setupHelper;
    }

    /**
     * Upgrades DB schema for a module
     *
     * @param SchemaSetupInterface   $setup   setup
     * @param ModuleContextInterface $context context
     *
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $quoteAddressTable = 'quote_address';
        $quoteTable = 'quote';

        $salesConnection = $this->_setupHelper->getSalesConnection();

        $orderTable = 'sales_order';
        $invoiceTable = 'sales_invoice';
        $creditmemoTable = 'sales_creditmemo';

        //Setup two columns for quote, quote_address and order
        //Quote address tables
        $setup->getConnection()
            ->addColumn(
                $setup->getTable($quoteAddressTable),
                'fee',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    [12, 2],
                    'default' => 0.00,
                    'nullable' => true,
                    'comment' => 'Surcharge Fee'
                ]
            );
        $setup->getConnection()
            ->addColumn(
                $setup->getTable($quoteAddressTable),
                'base_fee',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    [12, 2],
                    'default' => 0.00,
                    'nullable' => true,
                    'comment' => 'Base Surcharge Fee'
                ]
            );
        //Quote tables
        $setup->getConnection()
            ->addColumn(
                $setup->getTable($quoteTable),
                'fee',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    [12, 2],
                    'default' => 0.00,
                    'nullable' => true,
                    'comment' => 'Surcharge Fee'

                ]
            );

        $setup->getConnection()
            ->addColumn(
                $setup->getTable($quoteTable),
                'base_fee',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    [12, 2],
                    'default' => 0.00,
                    'nullable' => true,
                    'comment' => 'Base Surcharge Fee'

                ]
            );
        //Order tables
        $salesConnection->addColumn(
                $orderTable,
                'fee',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    [12, 2],
                    'default' => 0.00,
                    'nullable' => true,
                    'comment' => 'Surcharge Fee'

                ]
            );

        $salesConnection->addColumn(
                $orderTable,
                'base_fee',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    [12, 2],
                    'default' => 0.00,
                    'nullable' => true,
                    'comment' => 'Base Surcharge Fee'

                ]
            );
        //Invoice tables
        $salesConnection->addColumn(
                $invoiceTable,
                'fee',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    [12, 2],
                    'default' => 0.00,
                    'nullable' => true,
                    'comment' => 'Surcharge Fee'

                ]
            );
        $salesConnection->addColumn(
                $invoiceTable,
                'base_fee',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    [12, 2],
                    'default' => 0.00,
                    'nullable' => true,
                    'comment' => 'Base Surcharge Fee'

                ]
            );
        //Credit memo tables
        $salesConnection->addColumn(
                $creditmemoTable,
                'fee',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    [12, 2],
                    'default' => 0.00,
                    'nullable' => true,
                    'comment' => 'Surcharge Fee'

                ]
            );
        $salesConnection->addColumn(
                $creditmemoTable,
                'base_fee',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    [12, 2],
                    'default' => 0.00,
                    'nullable' => true,
                    'comment' => 'Base Surcharge Fee'

                ]
            );
        $setup->endSetup();
    }
}