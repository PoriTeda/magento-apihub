<?php
// @codingStandardsIgnoreFile
namespace Riki\Checkout\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table ;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        
        if (version_compare($context->getVersion(), '1.0.2') < 0) {
            /**
             * Create table 'quote_address_item'
             */
            $table = $installer->getConnection()->newTable(
                $installer->getTable('order_address_item')
            )->addColumn(
                'address_item_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Address Item Id'
            )->addColumn(
                'parent_item_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true],
                'Parent Item Id'
            )->addColumn(
                'order_address_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Quote Address Id'
            )->addColumn(
                'order_item_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Quote Item Id'
            )->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Created At'
            )->addColumn(
                'updated_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_UPDATE],
                'Updated At'
            )->addColumn(
                'applied_rule_ids',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Applied Rule Ids'
            )->addColumn(
                'additional_data',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Additional Data'
            )->addColumn(
                'weight',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                ['default' => '0.0000'],
                'Weight'
            )->addColumn(
                'qty',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                ['nullable' => false, 'default' => '0.0000'],
                'Qty'
            )->addColumn(
                'discount_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                ['default' => '0.0000'],
                'Discount Amount'
            )->addColumn(
                'tax_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                ['default' => '0.0000'],
                'Tax Amount'
            )->addColumn(
                'row_total',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                ['nullable' => false, 'default' => '0.0000'],
                'Row Total'
            )->addColumn(
                'base_row_total',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                ['nullable' => false, 'default' => '0.0000'],
                'Base Row Total'
            )->addColumn(
                'row_total_with_discount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                ['default' => '0.0000'],
                'Row Total With Discount'
            )->addColumn(
                'base_discount_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                ['default' => '0.0000'],
                'Base Discount Amount'
            )->addColumn(
                'base_tax_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                ['default' => '0.0000'],
                'Base Tax Amount'
            )->addColumn(
                'row_weight',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                ['default' => '0.0000'],
                'Row Weight'
            )->addColumn(
                'product_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true],
                'Product Id'
            )->addColumn(
                'super_product_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true],
                'Super Product Id'
            )->addColumn(
                'parent_product_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true],
                'Parent Product Id'
            )->addColumn(
                'sku',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Sku'
            )->addColumn(
                'image',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Image'
            )->addColumn(
                'name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Name'
            )->addColumn(
                'description',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Description'
            )->addColumn(
                'is_qty_decimal',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true],
                'Is Qty Decimal'
            )->addColumn(
                'price',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                [],
                'Price'
            )->addColumn(
                'discount_percent',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                [],
                'Discount Percent'
            )->addColumn(
                'no_discount',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true],
                'No Discount'
            )->addColumn(
                'tax_percent',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                [],
                'Tax Percent'
            )->addColumn(
                'base_price',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                [],
                'Base Price'
            )->addColumn(
                'base_cost',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                [],
                'Base Cost'
            )->addColumn(
                'price_incl_tax',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                [],
                'Price Incl Tax'
            )->addColumn(
                'base_price_incl_tax',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                [],
                'Base Price Incl Tax'
            )->addColumn(
                'row_total_incl_tax',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                [],
                'Row Total Incl Tax'
            )->addColumn(
                'base_row_total_incl_tax',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                [],
                'Base Row Total Incl Tax'
            )->addColumn(
                'discount_tax_compensation_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                [],
                'Discount Tax Compensation Amount'
            )->addColumn(
                'base_discount_tax_compensation_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                [],
                'Base Discount Tax Compensation Amount'
            )->addIndex(
                $installer->getIdxName('order_address_item', ['order_address_id']),
                ['order_address_id']
            )->addIndex(
                $installer->getIdxName('order_address_item', ['parent_item_id']),
                ['parent_item_id']
            )->addIndex(
                $installer->getIdxName('order_address_item', ['order_item_id']),
                ['order_item_id']
            )->addForeignKey(
                $installer->getFkName(
                    'order_address_item',
                    'order_address_id',
                    'sales_order_address',
                    'address_id'
                ),
                'order_address_id',
                $installer->getTable('sales_order_address'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $installer->getFkName(
                    'order_address_item',
                    'parent_item_id',
                    'order_address_item',
                    'address_item_id'
                ),
                'parent_item_id',
                $installer->getTable('order_address_item'),
                'address_item_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $installer->getFkName('order_address_item', 'order_item_id', 'sales_order_item', 'item_id'),
                'order_item_id',
                $installer->getTable('sales_order_item'),
                'item_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Sales Flat Order Address Item'
            );
            $installer->getConnection()->createTable($table);
        }
        if (version_compare($context->getVersion(), '1.0.3') < 0){
            if($installer->tableExists($installer->getTable("quote_item"))
            ){
                $installer->getConnection()->dropColumn($installer->getTable("quote_item"),"slip_parent_item_id");
                $installer->getConnection()->addColumn(
                    $installer->getTable("quote_item"),
                    "slip_parent_item_id",
                    ['type' => Table::TYPE_INTEGER, 10, 'default' => null, 'comment' => 'Split Parent ID for multi checkout']
                );
            }
        }
        $installer->endSetup();
    }
}