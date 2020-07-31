<?php


namespace Riki\StockPoint\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var \Riki\ArReconciliation\Setup\SetupHelper|SetupHelper
     */
    protected $setupHelper;

    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * @var ModuleDataSetupInterface
     */
    protected $eavSetup;

    /**
     * UpgradeSchema constructor.
     * @param \Riki\ArReconciliation\Setup\SetupHelper $setupHelper
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $eavSetup,
        \Riki\ArReconciliation\Setup\SetupHelper $setupHelper
    ) {
    
        $this->setupHelper = $setupHelper;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavSetup = $eavSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();

        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $salesConnection */
        $salesConnection = $this->setupHelper->getSalesConnection();
        $checkoutConnection = $this->setupHelper->getCheckoutConnection();

        if (version_compare($context->getVersion(), "1.0.0", "<")) {
            if (!$salesConnection->tableColumnExists(
                'subscription_profile',
                'stock_point_profile_bucket_id'
            )
            ) {
                $salesConnection->addColumn(
                    'subscription_profile',
                    'stock_point_profile_bucket_id',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'default' => null,
                        'length'  =>  10,
                        'comment' => 'Stock point',
                    ]
                );
            }

            if (!$salesConnection->tableColumnExists(
                'subscription_profile',
                'stock_point_delivery_type'
            )
            ) {
                $salesConnection->addColumn(
                    'subscription_profile',
                    'stock_point_delivery_type',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'default' => null,
                        'length'  => 10,
                        'comment' => 'Stock point',
                    ]
                );
            }

            if (!$salesConnection->tableColumnExists(
                'subscription_profile',
                'stock_point_delivery_information'
            )
            ) {
                $salesConnection->addColumn(
                    'subscription_profile',
                    'stock_point_delivery_information',
                    [
                        'type' => Table::TYPE_TEXT,
                        'default' => null,
                        'length'  => 255,
                        'comment' => 'Stock point',
                    ]
                );
            }

            if (!$salesConnection->tableColumnExists(
                'subscription_profile_product_cart',
                'stock_point_discount_rate'
            )
            ) {
                $salesConnection->addColumn(
                    'subscription_profile_product_cart',
                    'stock_point_discount_rate',
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'default' => null,
                        'length'  => '12,4',
                        'comment' => 'Stock point',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), "1.0.1", "<")) {
            $orderTable = $salesConnection->getTableName('sales_order');
            $stockPointBucketIdColumn = 'stock_point_delivery_bucket_id';
            $stockPointDeliveryTypeColumn = 'stock_point_delivery_type';
            $stockPointDeliveryInformationColumn = 'stock_point_delivery_information';

            if (!$salesConnection->tableColumnExists($orderTable, $stockPointBucketIdColumn)) {
                $salesConnection->addColumn(
                    $orderTable,
                    $stockPointBucketIdColumn,
                    [
                        'type' => Table::TYPE_INTEGER,
                        'default' => null,
                        'comment' => 'Stock point delivery bucket id',
                    ]
                );
            }

            if (!$salesConnection->tableColumnExists($orderTable, $stockPointDeliveryTypeColumn)) {
                $salesConnection->addColumn(
                    $orderTable,
                    $stockPointDeliveryTypeColumn,
                    [
                        'type' => Table::TYPE_SMALLINT,
                        'default' => 0,
                        'comment' => 'Stock point delivery type',
                    ]
                );
            }

            if (!$salesConnection->tableColumnExists($orderTable, $stockPointDeliveryInformationColumn)) {
                $salesConnection->addColumn(
                    $orderTable,
                    $stockPointDeliveryInformationColumn,
                    [
                        'type' => Table::TYPE_TEXT,
                        'length'  => 255,
                        'comment' => 'Stock point delivery information',
                    ]
                );
            }

            $orderItemTable = $salesConnection->getTableName('sales_order_item');
            $stockPointAppliedDiscountRateColumn = 'stock_point_applied_discount_rate';

            if (!$salesConnection->tableColumnExists($orderItemTable, $stockPointAppliedDiscountRateColumn)) {
                $salesConnection->addColumn(
                    $orderItemTable,
                    $stockPointAppliedDiscountRateColumn,
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'length' => '12,4',
                        'default' => '0.0000',
                        'comment' => 'Stock point applied discount rate'
                    ]
                );
            }

            $profileProductCartTable = $salesConnection->getTableName('subscription_profile_product_cart');
            $stockPointDiscountRateColumn = 'stock_point_discount_rate';

            if (!$salesConnection->tableColumnExists($profileProductCartTable, $stockPointDiscountRateColumn)) {
                $salesConnection->addColumn(
                    $profileProductCartTable,
                    $stockPointDiscountRateColumn,
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'length' => '12,4',
                        'default' => '0.0000',
                        'comment' => 'Stock point discount rate'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), "1.0.3", "<")) {
            $checkoutConnection = $this->setupHelper->getCheckoutConnection();
            $quoteItemTable = $checkoutConnection->getTableName('quote_item');
            $stockPointDiscountRateColumn = 'stock_point_applied_discount_rate';
            if (!$checkoutConnection->tableColumnExists($quoteItemTable, $stockPointDiscountRateColumn)) {
                $checkoutConnection->addColumn(
                    $quoteItemTable,
                    $stockPointDiscountRateColumn,
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'length' => '12,4',
                        'default' => '0.0000',
                        'comment' => 'Stock point discount rate'
                    ]
                );
            }
        }
        /**
         * Add foreignKey for stock_point_id
         */
        if (version_compare($context->getVersion(), "1.0.4", "<")) {
            if ($salesConnection->tableColumnExists(
                'stock_point_profile_bucket',
                'stock_point_id'
            )
            ) {
                $salesConnection->addIndex(
                    "stock_point",
                    $installer->getIdxName(
                        "stock_point",
                        ['external_stock_point_id']
                    ),
                    ['external_stock_point_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                );

                $salesConnection->addIndex(
                    "stock_point_profile_bucket",
                    $installer->getIdxName(
                        "stock_point_profile_bucket",
                        ['stock_point_id']
                    ),
                    ['external_profile_bucket_id', 'stock_point_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                );
                $salesConnection->changeColumn(
                    "stock_point_profile_bucket",
                    "stock_point_id",
                    "stock_point_id",
                    ['type' => Table::TYPE_INTEGER, 'nullable' => false, 'unsigned' => true]
                );

                $salesConnection->changeColumn(
                    "stock_point_profile_bucket",
                    "external_profile_bucket_id",
                    "external_profile_bucket_id",
                    ['type' => Table::TYPE_INTEGER, 'nullable' => false, 'unsigned' => true]
                );

                $salesConnection->addForeignKey(
                    $setup->getFkName("stock_point", "stock_point_id", "stock_point_profile_bucket", "stock_point_id"),
                    "stock_point_profile_bucket",
                    "stock_point_id",
                    "stock_point",
                    "stock_point_id",
                    Table::ACTION_CASCADE
                );
            }
        }

        if (version_compare($context->getVersion(), "1.0.5", "<")) {
            $quoteItemTable = $checkoutConnection->getTableName('quote_item');
            $orderItemTable = $salesConnection->getTableName('sales_order_item');

            $columnDefine = [
                'type' => Table::TYPE_DECIMAL,
                'length' => '12,4',
                'comment' => 'Stock point discount amount'
            ];

            $checkoutConnection->addColumn(
                $quoteItemTable,
                'stock_point_discount_amount',
                $columnDefine
            );

            $salesConnection->addColumn(
                $orderItemTable,
                'stock_point_discount_amount',
                $columnDefine
            );
        }

        if (version_compare($context->getVersion(), "1.0.6", "<")) {
            $quoteTable = $checkoutConnection->getTableName('quote');
            $orderTable = $salesConnection->getTableName('sales_order');
            $freeMachineOrderColumn = 'free_machine_order';

            $columnDefine = [
                'type' => Table::TYPE_BOOLEAN,
                'default' => 0,
                'comment' => 'Free machine order'
            ];

            $checkoutConnection->addColumn(
                $quoteTable,
                $freeMachineOrderColumn,
                $columnDefine
            );

            $salesConnection->addColumn(
                $orderTable,
                $freeMachineOrderColumn,
                $columnDefine
            );
        }

        if (version_compare($context->getVersion(), "1.0.7", "<")) {
            $stockPointTable = $salesConnection->getTableName('stock_point');
            $unUsageIndex = $salesConnection->getIndexName($stockPointTable, ['stock_point_id']);
            $salesConnection->dropIndex($stockPointTable, $unUsageIndex);

            $salesConnection->modifyColumn($stockPointTable, 'external_stock_point_id', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => false,
                'unsigned' => true,
                'comment' => 'External Stock Point Id'
            ]);

            $profileBucketTable = $salesConnection->getTableName('stock_point_profile_bucket');
            $uniqueColumn = 'external_profile_bucket_id';

            $salesConnection->addIndex(
                $profileBucketTable,
                $salesConnection->getIndexName($profileBucketTable, [$uniqueColumn]),
                [$uniqueColumn],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            );

            $deliveryBucketTable = $salesConnection->getTableName('stock_point_delivery_bucket');
            $removedIndex = $salesConnection->getIndexName($deliveryBucketTable, ['delivery_bucket_id']);
            $salesConnection->dropIndex($deliveryBucketTable, $removedIndex);

            $salesConnection->addIndex(
                $deliveryBucketTable,
                $salesConnection->getIndexName(
                    $deliveryBucketTable,
                    ['delivery_bucket_id', 'delivery_date']
                ),
                ['delivery_bucket_id', 'delivery_date'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            );
        }

        if (version_compare($context->getVersion(), "1.0.7", "<")) {
            $stockPointTable = $salesConnection->getTableName('stock_point');
            $salesConnection->modifyColumn($stockPointTable, 'region_id', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => false,
                'unsigned' => true,
                'comment' => 'Stock point region id'
            ]);

            $profileBucketTable = $salesConnection->getTableName('stock_point_profile_bucket');
            $deliveryBucketTable = $salesConnection->getTableName('stock_point_delivery_bucket');

            $removedIndex = $salesConnection->getIndexName($deliveryBucketTable, ['delivery_bucket_id', 'delivery_date']);
            $salesConnection->dropIndex($deliveryBucketTable, $removedIndex);

            $salesConnection->modifyColumn($deliveryBucketTable, 'region_id', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => false,
                'unsigned' => true,
                'comment' => 'Region id'
            ]);

            $salesConnection->modifyColumn($deliveryBucketTable, 'profile_bucket_id', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => true,
                'unsigned' => true,
                'comment' => 'Profile Bucket Id'
            ]);

            $salesConnection->addIndex(
                $deliveryBucketTable,
                $salesConnection->getIndexName(
                    $deliveryBucketTable,
                    ['profile_bucket_id']
                ),
                ['profile_bucket_id'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
            );

            $salesConnection->addForeignKey(
                $setup->getFkName($deliveryBucketTable, "profile_bucket_id", $profileBucketTable, "profile_bucket_id"),
                $deliveryBucketTable,
                "profile_bucket_id",
                $profileBucketTable,
                "profile_bucket_id",
                Table::ACTION_SET_NULL
            );

            $profileTable = $salesConnection->getTableName('subscription_profile');

            $salesConnection->modifyColumn($profileTable, 'stock_point_profile_bucket_id', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => true,
                'unsigned' => true,
                'comment' => 'Stock point profile bucket Id'
            ]);

            $salesConnection->modifyColumn($profileTable, 'stock_point_delivery_type', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'nullable' => true,
                'unsigned' => true,
                'comment' => 'Stock point delivery type'
            ]);

            $salesConnection->addForeignKey(
                $setup->getFkName($profileTable, "stock_point_profile_bucket_id", $profileBucketTable, "profile_bucket_id"),
                $profileTable,
                "stock_point_profile_bucket_id",
                $profileBucketTable,
                "profile_bucket_id",
                Table::ACTION_SET_NULL
            );

            $orderTable = $salesConnection->getTableName('sales_order');

            $salesConnection->modifyColumn($orderTable, 'stock_point_delivery_bucket_id', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => true,
                'default' => null,
                'unsigned' => true,
                'comment' => 'Stock point delivery bucket id'
            ]);

            $salesConnection->modifyColumn($orderTable, 'stock_point_delivery_type', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'nullable' => true,
                'unsigned' => true,
                'comment' => 'Stock point delivery type'
            ]);

            /*pass DATA TUPLE error for add foreign key*/
            $salesConnection->query("SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;");

            $salesConnection->addForeignKey(
                $setup->getFkName($orderTable, "stock_point_delivery_bucket_id", $deliveryBucketTable, "delivery_bucket_id"),
                $orderTable,
                "stock_point_delivery_bucket_id",
                $deliveryBucketTable,
                "delivery_bucket_id",
                Table::ACTION_SET_NULL
            );

            $salesConnection->query("SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=1;");

            $shipmentTable = $salesConnection->getTableName('sales_shipment');

            $salesConnection->modifyColumn($shipmentTable, 'stock_point_delivery_bucket_id', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => true,
                'default' => null,
                'unsigned' => true,
                'comment' => 'Stock point delivery bucket id'
            ]);

            /*pass DATA TUPLE error for add foreign key*/
            $salesConnection->query("SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;");

            $salesConnection->addForeignKey(
                $setup->getFkName($shipmentTable, "stock_point_delivery_bucket_id", $deliveryBucketTable, "delivery_bucket_id"),
                $shipmentTable,
                "stock_point_delivery_bucket_id",
                $deliveryBucketTable,
                "delivery_bucket_id",
                Table::ACTION_SET_NULL
            );

            /*pass tuple error*/
            $salesConnection->query("SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=1;");

            $quoteItemTable = $checkoutConnection->getTableName('quote_item');
            $orderItemTable = $salesConnection->getTableName('sales_order_item');

            $checkoutConnection->changeColumn(
                $quoteItemTable,
                'stock_point_discount_amount',
                'stock_point_applied_discount_amount',
                [
                    'type' => Table::TYPE_DECIMAL,
                    'length' => '12,4',
                    'comment' => 'Stock point applied discount amount'
                ]
            );
            $salesConnection->changeColumn(
                $orderItemTable,
                'stock_point_discount_amount',
                'stock_point_applied_discount_amount',
                [
                    'type' => Table::TYPE_DECIMAL,
                    'length' => '12,4',
                    'comment' => 'Stock point applied discount amount'
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.0.9', '<')) {
            $orderTable = $salesConnection->getTableName('sales_order');
            if (!$salesConnection->tableColumnExists($orderTable, 'stock_point_bucket_order_confirmation_status')) {
                $salesConnection->addColumn(
                    $orderTable,
                    'stock_point_bucket_order_confirmation_status',
                    [
                        'type' => Table::TYPE_BOOLEAN,
                        'default' => 0,
                        'nullable' => false,
                        'comment' => 'Stock point bucket order confirmation status',
                    ]
                );
            }
        }

        // NED-290: Update new column auto_stock_point_assign_status to table subscription_profile
        // 0 : Can be automate assign (Default value)
        // 1 : Reject
        // 2 : Auto assigned
        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $subProfileTable = $salesConnection->getTableName('subscription_profile');
            if (!$salesConnection->tableColumnExists($subProfileTable, 'auto_stock_point_assign_status')) {
                $salesConnection->addColumn(
                    $subProfileTable,
                    'auto_stock_point_assign_status',
                    [
                        'type' => Table::TYPE_SMALLINT,
                        'length' => 6,
                        'default' => 0,
                        'nullable' => false,
                        'comment' => 'Auto stock point assign status',
                    ]
                );
            }
        }


        if (version_compare($context->getVersion(), '1.1.1', '<')) {
            $stockPointTable = $salesConnection->getTableName('stock_point');
            $profileBucketTable = $salesConnection->getTableName('stock_point_profile_bucket');
            $deliveryBucketTable = $salesConnection->getTableName('stock_point_delivery_bucket');

            // Remove relationship with stock_point_profile_bucket
            $salesConnection->dropForeignKey(
                $deliveryBucketTable,
                $setup->getFkName(
                    $deliveryBucketTable,
                    'profile_bucket_id',
                    $profileBucketTable,
                    'profile_bucket_id'
                )
            );

            // Add new attribute stock_point_id to stock_point_delivery_bucket
            if (!$salesConnection->tableColumnExists($deliveryBucketTable, 'stock_point_id')) {
                $salesConnection->addColumn(
                    $deliveryBucketTable,
                    'stock_point_id',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'nullable' => true,
                        'unsigned' => true,
                        'comment' => 'Stock Point Id',
                        'after' => 'delivery_bucket_id'
                    ]
                );
            }

            // Add relationship with stock_point
            $salesConnection->addForeignKey(
                $setup->getFkName($deliveryBucketTable, "stock_point_id", $stockPointTable, "stock_point_id"),
                $deliveryBucketTable,
                "stock_point_id",
                $stockPointTable,
                "stock_point_id",
                Table::ACTION_SET_NULL
            );
        }

        if (version_compare($context->getVersion(), '1.1.2', '<')) {
            if (!$salesConnection->tableColumnExists(
                'subscription_profile_product_cart',
                'original_delivery_date'
            )
            ) {
                $salesConnection->addColumn(
                    'subscription_profile_product_cart',
                    'original_delivery_date',
                    [
                        'type' => Table::TYPE_DATE,
                        'default' => null,
                        'after' => 'delivery_date',
                        'comment' => 'Origin delivery date',
                    ]
                );
            }

            if (!$salesConnection->tableColumnExists(
                'subscription_profile_product_cart',
                'original_delivery_time_slot'
            )
            ) {
                $salesConnection->addColumn(
                    'subscription_profile_product_cart',
                    'original_delivery_time_slot',
                    [
                        'type' => Table::TYPE_TEXT,
                        'default' => null,
                        'after' => 'delivery_time_slot',
                        'comment' => 'Origin delivery time slot',
                    ]
                );
            }
        }

        $installer->endSetup();
    }
}
