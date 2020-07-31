<?php

namespace Wyomind\PointOfSale\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{

    /**
     * @version 6.0.1
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        unset($context);
        $installer = $setup;
        $installer->startSetup();
        $installer->getConnection()->dropTable($installer->getTable('pointofsale')); // drop if exists


        $installer->getConnection()->dropTable($installer->getTable('pointofsale'));


        $pointofsale = $installer->getConnection()
                ->newTable($installer->getTable('pointofsale'))
                ->addColumn(
                    'place_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    11,
                    [ 'identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Place ID'
                )
                ->addColumn(
                    'customer_group',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [ 'identity' => false, 'unsigned' => true, 'nullable' => false, 'default' => 0, 'primary' => false],
                    'Customer group'
                )
                ->addColumn(
                    'store_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [ 'identity' => false, 'unsigned' => true, 'nullable' => false, 'default' => 0, 'primary' => false],
                    'Store views'
                )
                ->addColumn(
                    'position',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    5,
                    [ 'identity' => false, 'unsigned' => true, 'nullable' => false, 'default' => 0, 'primary' => false],
                    'Store position'
                )->addColumn(
                    'store_code',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [ 'identity' => false, 'nullable' => false, 'primary' => false],
                    'Store code'
                )->addColumn(
                    'name',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [ 'identity' => false, 'nullable' => false, 'primary' => false],
                    'Store name?'
                )->addColumn(
                    'address_line_1',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [ 'identity' => false, 'nullable' => true, 'primary' => false],
                    'Address 1'
                )->addColumn(
                    'address_line_2',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [ 'identity' => false, 'nullable' => true, 'primary' => false],
                    'Address 2'
                )->addColumn(
                    'city',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [ 'identity' => false, 'nullable' => true, 'primary' => false],
                    'Store City'
                )->addColumn(
                    'state',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    [ 'identity' => false, 'nullable' => true, 'primary' => false],
                    'Store State'
                )->addColumn(
                    'postal_code',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    [ 'identity' => false, 'nullable' => true, 'primary' => false],
                    'Store zipcode'
                )->addColumn(
                    'country_code',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    [ 'identity' => false, 'nullable' => true, 'primary' => false],
                    'Store country code'
                )->addColumn(
                    'main_phone',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    [ 'identity' => false, 'nullable' => true, 'primary' => false],
                    'Store phone'
                )->addColumn(
                    'email',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [ 'identity' => false, 'nullable' => true, 'primary' => false],
                    'Store email'
                )->addColumn(
                    'hours',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    500,
                    [ 'identity' => false, 'nullable' => true, 'primary' => false],
                    'Store opening hours'
                )
                ->addColumn(
                    'description',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    1500,
                    [ 'identity' => false, 'nullable' => true, 'primary' => false],
                    'Store description?'
                )
                ->addColumn(
                    'longitude',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    20,
                    [ 'identity' => false, 'nullable' => true, 'primary' => false],
                    'Store location, longitude'
                )
                ->addColumn(
                    'latitude',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    20,
                    [ 'identity' => false, 'nullable' => true, 'primary' => false],
                    'Store location, latitude'
                )
                ->addColumn(
                    'status',
                    \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    1,
                    [ 'identity' => false, 'unsigned' => true, 'nullable' => false, 'default' => 0, 'primary' => false],
                    'Store status'
                )
                ->addColumn(
                    'image',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [ 'identity' => false, 'nullable' => true, 'primary' => false],
                    'Store image'
                )->addIndex(
                    $installer->getIdxName('pointofsale', ['place_id']),
                    ['place_id']
                )
                ->setComment('Point of sales and Warehouses');




        $installer->getConnection()->createTable($pointofsale);





        $installer->endSetup();
    }
}
