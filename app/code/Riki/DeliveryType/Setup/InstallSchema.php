<?php
// @codingStandardsIgnoreFile
namespace Riki\DeliveryType\Setup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * Function install
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    private $eavSetupFactory;
    private $eavSetup;

    public function __construct(EavSetupFactory $eavSetupFactory, ModuleDataSetupInterface $eavSetup )
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavSetup = $eavSetup;
    }

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
        $installer->getConnection()->dropTable($installer->getTable('riki_delivery_type'));

        $table = $installer->getConnection()->newTable($installer->getTable('riki_delivery_type'))
            ->addColumn(
            'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,10,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true,'auto_increment' => true],
            'ID'
        )->addColumn(
            'code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,null,
            [ 'nullable' => false],
            'Code'
        )->addColumn(
            'name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,null,
            ['nullable' => false],
            'Name'
        )->addColumn(
            'shipping_fee',
            \Magento\Framework\DB\Ddl\Table::TYPE_FLOAT,9,
            ['nullable' => false , 'default' => 0],
            'Shipping Fee per Delivery Type'
        )->addColumn(
            'sync_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,null,
            ['nullable' => false],
            '3PL Sync Code'
        )->addColumn(
                'description',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,null,
                ['nullable' => true],
                'Description'
            );
        $installer->getConnection()->createTable($table);

      


        $installer->endSetup();


    }
}
