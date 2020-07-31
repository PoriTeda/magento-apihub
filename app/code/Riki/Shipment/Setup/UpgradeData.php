<?php

namespace Riki\Shipment\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Setup\SalesSetup;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Framework\DB\Ddl\Table;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * Sales setup factory
     *
     * @var SalesSetupFactory
     */
    protected $salesSetupFactory;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;
    /**
     * @var \Riki\Sales\Helper\ConnectionHelper
     */
    protected $connectionHelper;

    /**
     * @param SalesSetupFactory $salesSetupFactory
     * @param \Magento\Eav\Model\Config $eavConfig
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->connectionHelper = $connectionHelper;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /** @var SalesSetup $salesSetup */
        $salesConnection = $this->connectionHelper->getSalesConnection();

        if (version_compare($context->getVersion(), '2.0.0', '<')) {
            $salesConnection->addColumn(
                $salesConnection->getTableName('sales_shipment_grid'),
                'payment_agent',
                [
                    'type' => Table::TYPE_TEXT,
                    'length'   =>  255,
                    'comment' => 'Payment Agent'
                ]
            );
            $salesConnection->addColumn(
                $salesConnection->getTableName('sales_shipment_grid'),
                'is_exported_sap',
                [
                    'type' => Table::TYPE_SMALLINT,
                    'length'   =>  4,
                    'comment' => 'Is Exported Sap'
                ]
            );
            $salesConnection->addColumn(
                $salesConnection->getTableName('sales_shipment_grid'),
                'export_sap_date',
                [
                    'type' => Table::TYPE_DATE,
                    'comment' => 'Export Sap Date'
                ]
            );
        }
        
        $this->eavConfig->clear();
        $setup->endSetup();
    }
}
