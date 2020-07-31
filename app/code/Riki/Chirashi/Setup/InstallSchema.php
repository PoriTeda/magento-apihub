<?php
// @codingStandardsIgnoreFile
namespace Riki\Chirashi\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    protected $_eavSetupFactory;
    protected $_eavSetup;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $_eavAttribute;

    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $eavSetup,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
    ){
        $this->_eavSetup = $eavSetup;
        $this->_eavSetupFactory = $eavSetupFactory;
        $this->_eavAttribute = $eavAttribute;
    }

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $connection = $setup->getConnection();

        $eavSetup = $this->_eavSetupFactory->create(['setup' => $this->_eavSetup]);

        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'chirashi');

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, 'chirashi', [
                'group' => 'Basic',
                'type' => 'int',
                'input' => 'boolean',
                'label' => 'Chirashi',
                'visible' => true,
                'required' => false,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'is_used_in_grid' => false,
                'unique' => false,
                'apply_to' => '',
                'default' => 0,
                'sort_order' => 1000,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE
            ]
        );


        $table = $setup->getTable('quote_item');
        if(!$connection->tableColumnExists($table, 'chirashi')){
            $connection->addColumn($table, 'chirashi', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                'default'   =>  0,
                'comment' => 'Is chirashi?',
            ]);
        }

        $table = $setup->getTable('sales_order_item');
        if(!$connection->tableColumnExists($table, 'chirashi')){
            $connection->addColumn($table, 'chirashi', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                'default'   =>  0,
                'comment' => 'Is chirashi?',
            ]);
        }

        $table = $setup->getTable('sales_shipment_item');
        if(!$connection->tableColumnExists($table, 'chirashi')){
            $connection->addColumn($table, 'chirashi', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                'default'   =>  0,
                'comment' => 'Is chirashi?',
            ]);
        }

        $installer->endSetup();
    }
}
