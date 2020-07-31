<?php
// @codingStandardsIgnoreFile
namespace Riki\Catalog\Setup;

use Magento\Eav\Setup\EavSetupFactory;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface {

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private $eavSetupFactory;

    private $eavSetup;
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $_eavAttribute;

    public function __construct(EavSetupFactory $eavSetupFactory,ModuleDataSetupInterface $eavSetup,\Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute )
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavSetup = $eavSetup;
        $this->_eavAttribute = $eavAttribute;
    }

    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context )
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '0.1.1', '<')) {
            //create table
            $tableName = 'riki_product_status';
            $table = $installer->getConnection()
                ->newTable($installer->getTable($tableName))
                ->addColumn(
                    'status_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Index Id'
                )->addColumn(
                    'status_name',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    250,
                    ['nullable' => true],
                    'Status Name'
                )->addColumn(
                    'sufficient_message',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '64k',
                    ['nullable' => true],
                    'Sufficient message'
                )->addColumn(
                    'short_message',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '64k',
                    ['nullable' => true],
                    'Short message'
                )->addColumn(
                    'outstock_message',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '64k',
                    ['nullable' => true],
                    'Out of stock message'
                )->addColumn(
                    'threshold_message',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '64k',
                    ['nullable' => true],
                    'Threshold Message'
                );
            $installer->getConnection()->createTable($table);
            //insert sample data
            $data = [
                ['1', 'Common 共通表示用', 'Sufficient message 1', 'Short message 1','Out of stock message 1','Threshold Message 1'],
                ['2', '次回入荷なし用', 'Sufficient message 2', 'Short message 2','Out of stock message 2','Threshold Message 2'],
                ['3', '在庫20管理用', 'Sufficient message 3', 'Short message 3','Out of stock message 3','Threshold Message 3'],
                ['4', 'Threshold 10 在庫10管理用', 'Sufficient message 4', 'Short message 4','Out of stock message 4','Threshold Message 4'],
                ['5', '次回入荷なし用(10管理用)', 'Sufficient message 5', 'Short message 5','Out of stock message 5','Threshold Message 5'],

            ];

            foreach ($data as $row) {
                $bind = [   'status_name' => $row[1],
                            'sufficient_message' => $row[2],
                            'short_message' => $row[3],
                            'outstock_message' => $row[4],
                            'threshold_message' => $row[5]
                        ];
                $setup->getConnection()->insert($setup->getTable($tableName), $bind);
            }
        }


        /* fix related product issues for RIKI int */
        if (version_compare($context->getVersion(), '0.1.3', '<')) {

            /* try to detect if catalog_product_link_attribute has data or not */
            $sql = <<<SQL
        SELECT count(*)
        FROM `{$installer->getTable('catalog_product_link_attribute')}`
        LIMIT 1;
SQL;
            if( $installer->getConnection()->fetchOne($sql) <= 0 ){
                /**
                 * install product link attributes
                 */
                $data = [
                    [
                        'link_type_id' => \Magento\Catalog\Model\Product\Link::LINK_TYPE_RELATED,
                        'product_link_attribute_code' => 'position',
                        'data_type' => 'int',
                    ],
                    [
                        'link_type_id' => \Magento\Catalog\Model\Product\Link::LINK_TYPE_UPSELL,
                        'product_link_attribute_code' => 'position',
                        'data_type' => 'int'
                    ],
                    [
                        'link_type_id' => \Magento\Catalog\Model\Product\Link::LINK_TYPE_CROSSSELL,
                        'product_link_attribute_code' => 'position',
                        'data_type' => 'int'
                    ],
                    [
                        'link_type_id' => \Magento\GroupedProduct\Model\ResourceModel\Product\Link::LINK_TYPE_GROUPED,
                        'product_link_attribute_code' => 'position',
                        'data_type' => 'int',
                    ],
                    [
                        'link_type_id' => \Magento\GroupedProduct\Model\ResourceModel\Product\Link::LINK_TYPE_GROUPED,
                        'product_link_attribute_code' => 'qty',
                        'data_type' => 'decimal'
                    ],
                ];
                $installer->getConnection()
                    ->insertMultiple($installer->getTable('catalog_product_link_attribute'), $data);
            }
        }
        // add option multiple products for categofry
        if (version_compare($context->getVersion(), '0.1.4', '<')) {
            /** @var EavSetup $eavSetup */
            $eavSetupAttribute = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            /**
             * Add attributes to the eav/attribute
             */
            $eavSetupAttribute->addAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                'multiple_products',
                [
                    'type' => 'int',
                    'label' => 'Multiple products',
                    'input' => 'select',
                    'required' => false,
                    'sort_order' => 100,
                    'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'General Information',
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                ]
            );


        }

        // add option back order for stock item
        if (version_compare($context->getVersion(), '0.1.6', '<')) {
            if($installer->tableExists($installer->getTable('advancedinventory_stock'))){
                $installer->getConnection()->addColumn(
                    $installer->getTable("advancedinventory_stock"),
                    "backorder_limit",
                    [
                        'type'    => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 10,
                        'default' => null,
                        'comment' => 'Backorder limit number',
                        'after'   => 'use_config_setting_for_backorders'
                    ]
                );

                $installer->getConnection()->addColumn(
                    $installer->getTable("advancedinventory_stock"),
                    "backorder_expire",
                    [
                        'type'    => \Magento\Framework\DB\Ddl\Table::TYPE_DATE, null,
                        'default' => null,
                        'comment' => 'Backorder expire date',
                        'after'   => 'backorder_limit'
                    ]
                );
            }
        }

        // Add column delivery date
        if (version_compare($context->getVersion(), '0.1.8', '<')) {
            if($installer->tableExists($installer->getTable('advancedinventory_stock'))){
                $installer->getConnection()->addColumn(
                    $installer->getTable("advancedinventory_stock"),
                    "backorder_delivery_date_allowed",
                    [
                        'type'    => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'default' => null,
                        'comment' => 'Back order delivery allowed',
                        'after'   => 'backorder_expire'
                    ]
                );

                $installer->getConnection()->addColumn(
                    $installer->getTable("advancedinventory_stock"),
                    "backorder_first_delivery_date",
                    [
                        'type'    => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'default' => null,
                        'comment' => 'Back order first delivery date',
                        'after'   => 'backorder_delivery_date_allowed'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '0.1.9') < 0) {

            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
            $connection = $installer->getConnection();

            /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);

            $shipmentExportingFlag = 'shipment_exporting_flg';
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $shipmentExportingFlag);

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $shipmentExportingFlag, [
                    'group' => 'Basic',
                    'type' => 'int',
                    'input' => 'boolean',
                    'label' => 'Shipment exporting flg',
                    'backend' => 'Magento\Customer\Model\Attribute\Backend\Data\Boolean',
                    'visible' => true,
                    'required' => false,
                    'default' => 1,
                    'is_user_defined' => false,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => false,
                    'is_used_in_grid' => true,
                    'unique' => false,
                    'apply_to' => ''
                ]
            );

            $attributeShipmentExportingFlagId = $eavSetup->getAttributeId(
                \Magento\Catalog\Model\Product::ENTITY, $shipmentExportingFlag
            );

            /*insert shipment_exporting_flg default value for current product*/
            $select = $connection->select()->from(
                $connection->getTableName('catalog_product_entity'), [
                    'entity_id',
                    new \Zend_Db_Expr($attributeShipmentExportingFlagId.' as `attribute_id`'),
                    new \Zend_Db_Expr('1 as `value`')
                ]
            );

            $connection->query(
                $select->insertFromSelect(
                    $connection->getTableName('catalog_product_entity_int'),
                    ['entity_id','attribute_id','value'],
                    false
                )
            );

            $orderedWithOtherProductFlg = 'ordered_with_other_product_flg';
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $orderedWithOtherProductFlg);

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $orderedWithOtherProductFlg, [
                    'group' => 'Basic',
                    'type' => 'int',
                    'input' => 'boolean',
                    'label' => 'Can be ordered with other product flg',
                    'backend' => 'Magento\Customer\Model\Attribute\Backend\Data\Boolean',
                    'visible' => true,
                    'required' => false,
                    'default' => 1,
                    'is_user_defined' => false,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => false,
                    'is_used_in_grid' => true,
                    'unique' => false,
                    'apply_to' => ''
                ]
            );

            $attributeOrderedWithOtherProductFlgId = $eavSetup->getAttributeId(
                \Magento\Catalog\Model\Product::ENTITY, $orderedWithOtherProductFlg
            );

            /*insert ordered_with_other_product_flg default value for current product*/
            $select2 = $connection->select()->from(
                $connection->getTableName('catalog_product_entity'), [
                    'entity_id',
                    new \Zend_Db_Expr($attributeOrderedWithOtherProductFlgId.' as `attribute_id`'),
                    new \Zend_Db_Expr('1 as `value`')
                ]
            );

            $connection->query(
                $select2->insertFromSelect(
                    $connection->getTableName('catalog_product_entity_int'),
                    ['entity_id','attribute_id','value'],
                    false
                )
            );
        }

        $installer->endSetup();
    }
}