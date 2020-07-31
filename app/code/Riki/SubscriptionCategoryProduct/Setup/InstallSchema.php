<?php
// @codingStandardsIgnoreFile
namespace Riki\SubscriptionCategoryProduct\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        
        $tbl = $installer->getConnection()->newTable($installer->getTable('subscription_course_category'))
            ->addColumn(
                'course_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false],
                'Course ID'
            )
            ->addColumn(
                'category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false],
                'Category ID'
            )
            ->addColumn(
                'priority',
                Table::TYPE_SMALLINT,
                2,
                ['unsigned' => true, 'nullable' => false],
                'Priority'
            )
            ->addForeignKey(
                $installer->getFkName('subscription_course_category', 'course_id', 'subscription_course', 'course_id'),
                'course_id',
                $installer->getTable('subscription_course'),
                'course_id',
                Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName('subscription_course_category', 'category_id', 'catalog_category_entity', 'entity_id'),
                'category_id',
                $installer->getTable('catalog_category_entity'),
                'entity_id',
                Table::ACTION_CASCADE
            )
            ->setComment(
                'Subscription course category'
            );
        $installer->getConnection()->createTable($tbl);

        $tbl = $installer->getConnection()->newTable($installer->getTable('subscription_course_product'))
            ->addColumn(
                'course_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false],
                'Course ID'
            )
            ->addColumn(
                'product_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false],
                'Product ID'
            )
            ->addColumn(
                'priority',
                Table::TYPE_SMALLINT,
                2,
                ['unsigned' => true, 'nullable' => false],
                'Priority'
            )
            ->addForeignKey(
                $installer->getFkName('subscription_course_product', 'course_id', 'subscription_course', 'course_id'),
                'course_id',
                $installer->getTable('subscription_course'),
                'course_id',
                Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName('subscription_course_product', 'product_id', 'catalog_product_entity', 'entity_id'),
                'product_id',
                $installer->getTable('catalog_product_entity'),
                'entity_id',
                Table::ACTION_CASCADE
            )
            ->setComment(
                'Subscription course product'
            );
        $installer->getConnection()->createTable($tbl);

        $installer->endSetup();
    }
}