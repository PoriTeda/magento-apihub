<?php


namespace Riki\StockPoint\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    protected $contextSetup;

    /**
     * @var \Riki\ArReconciliation\Setup\SetupHelper
     */
    protected $setupHelper;

    /**
     * InstallSchema constructor.
     * @param \Riki\ArReconciliation\Setup\SetupHelper $setupHelper
     */
    public function __construct(
        \Riki\ArReconciliation\Setup\SetupHelper $setupHelper
    ) {
        $this->setupHelper = $setupHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $this->contextSetup = $context;
        $tableStockPoint = $this->setupHelper->getSalesConnection()->newTable(
            $setup->getTable('stock_point')
        );

        $tableStockPoint->addColumn(
            'stock_point_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            10,
            ['identity' => true,'nullable' => false,'primary' => true,'unsigned' => true],
            'Entity ID'
        );

        $tableStockPoint->addColumn(
            'external_stock_point_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [],
            'external_stock_point_id'
        );

        $tableStockPoint->addColumn(
            'firstname',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'firstname'
        );

        $tableStockPoint->addColumn(
            'firstname_kana',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'firstname_kana'
        );

        $tableStockPoint->addColumn(
            'lastname',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'lastname'
        );

        $tableStockPoint->addColumn(
            'lastname_kana',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'lastname_kana'
        );

        $tableStockPoint->addColumn(
            'street',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'street'
        );

        $tableStockPoint->addColumn(
            'region_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [],
            'region_id'
        );

        $tableStockPoint->addColumn(
            'postcode',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            10,
            [],
            'postcode'
        );

        $tableStockPoint->addColumn(
            'telephone',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            20,
            [],
            'telephone'
        );
        $tableStockPoint->addIndex(
            $setup->getIdxName($setup->getTable('stock_point'), ['stock_point_id']),
            ['stock_point_id']
        );

        $tableStockPointProfile = $this->setupHelper->getSalesConnection()->newTable(
            $setup->getTable('stock_point_profile_bucket')
        );

        $tableStockPointProfile->addColumn(
            'profile_bucket_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            10,
            ['identity' => true,'nullable' => false,'primary' => true,'unsigned' => true],
            'profile_bucket_id'
        );

        $tableStockPointProfile->addColumn(
            'stock_point_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [],
            'stock_point_id'
        );

        $tableStockPointProfile->addColumn(
            'external_profile_bucket_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [],
            'external_profile_bucket_id'
        );

        $stockProfileDelivery = $this->setupHelper->getSalesConnection()->newTable(
            $setup->getTable('stock_point_delivery_bucket')
        );

        $stockProfileDelivery->addColumn(
            'delivery_bucket_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            10,
            ['identity' => true,'nullable' => false,'primary' => true,'unsigned' => true],
            'delivery_bucket_id'
        );

        $stockProfileDelivery->addColumn(
            'profile_bucket_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [],
            'profile_bucket_id'
        );

        $stockProfileDelivery->addColumn(
            'delivery_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            null,
            [],
            'delivery_date'
        );

        $stockProfileDelivery->addColumn(
            'export_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            null,
            [],
            'export_date'
        );

        $stockProfileDelivery->addColumn(
            'firstname',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'firstname'
        );

        $stockProfileDelivery->addColumn(
            'firstname_kana',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'firstname_kana'
        );

        $stockProfileDelivery->addColumn(
            'lastname',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'lastname'
        );

        $stockProfileDelivery->addColumn(
            'lastname_kana',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'lastname_kana'
        );

        $stockProfileDelivery->addColumn(
            'street',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'street'
        );

        $stockProfileDelivery->addColumn(
            'region_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [],
            'region_id'
        );

        $stockProfileDelivery->addColumn(
            'postcode',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            10,
            [],
            'postcode'
        );

        $stockProfileDelivery->addColumn(
            'telephone',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            20,
            [],
            'telephone'
        );

         $stockProfileDelivery->addIndex(
             $setup->getIdxName($setup->getTable('stock_point_delivery_bucket'), ['delivery_bucket_id']),
             ['delivery_bucket_id']
         );

        $this->setupHelper->getSalesConnection()->createTable($tableStockPoint);
        $this->setupHelper->getSalesConnection()->createTable($tableStockPointProfile);
        $this->setupHelper->getSalesConnection()->createTable($stockProfileDelivery);
    }
}
