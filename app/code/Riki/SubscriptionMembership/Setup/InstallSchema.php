<?php
// @codingStandardsIgnoreFile
namespace Riki\SubscriptionMembership\Setup;

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

        // create subscription membership table to store membership of subscription course

        $tbl = $setup->getConnection()->newTable($setup->getTable('subscription_course_membership'))
            ->addColumn(
                'course_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false],
                'Course ID'
            )
            ->addColumn(
                'membership_id',
                Table::TYPE_SMALLINT,
                5,
                ['unsigned' => true, 'primary' => true, 'nullable' => false],
                'Membership ID'
            )
            ->addForeignKey(
                $setup->getFkName('subscription_course_membership', 'course_id', 'subscription_course', 'course_id'),
                'course_id',
                $setup->getTable('subscription_course'),
                'course_id',
                Table::ACTION_CASCADE
            )
            ->setComment(
                'Subscription course membership'
            );
        $setup->getConnection()->createTable($tbl);

        $installer->endSetup();
    }
}