<?php
// @codingStandardsIgnoreFile
namespace Riki\Subscription\Setup;

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

        $tbl = $installer->getConnection()->newTable($installer->getTable('subscription_frequency'))
            ->addColumn(
                'frequency_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'primary' => true, 'nullable' => false],
                'Frequency ID'
            )
            ->addColumn(
                'frequency_interval',
                Table::TYPE_SMALLINT,
                2,
                ['unsigned' => true],
                'Frequency interval'
            )
            ->setComment(
                'Subscription frequency'
            );
        $installer->getConnection()->createTable($tbl);

        $tbl = $installer->getConnection()->newTable($installer->getTable('subscription_course'))
            ->addColumn(
                'course_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Course ID'
            )
            ->addColumn(
                'free_shipping',
                Table::TYPE_SMALLINT,
                2,
                ['unsigned' => true],
                'Vendor Name'
            )
            ->addColumn(
                'duration_interval',
                Table::TYPE_SMALLINT,
                2,
                ['unsigned' => true],
                'Duration Interval'
            )
            ->addColumn(
                'must_select_sku',
                Table::TYPE_SMALLINT,
                2,
                ['unsigned' => true],
                'Must Select SKU'
            )
            ->addColumn(
                'minimum_order_qty',
                Table::TYPE_SMALLINT,
                2,
                ['unsigned' => true],
                'Minimum Order Qty'
            )
            ->addColumn(
                'minimum_order_times',
                Table::TYPE_SMALLINT,
                2,
                ['unsigned' => true],
                'Minimum Order Times'
            )
            ->addColumn(
                'sales_count',
                Table::TYPE_INTEGER,
                4,
                ['unsigned' => true],
                'Sales count'
            )
            ->addColumn(
                'application_count',
                Table::TYPE_INTEGER,
                10,
                ['unsigned' => true],
                'Application count'
            )
            ->addColumn(
                'application_limit',
                Table::TYPE_INTEGER,
                10,
                ['unsigned' => true],
                'Application Limit'
            )
            ->addColumn(
                'applied_payment_method_code',
                Table::TYPE_TEXT,
                null,
                [],
                'Applied payment method code'
            )
            ->addColumn(
                'membership_type_restriction',
                Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true],
                'Membership type restriction'
            )->addColumn(
                'allow_register_on_frontend',
                Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true],
                'Allow register on frontend'
            )
            ->addColumn(
                'description',
                Table::TYPE_TEXT,
                null,
                [],
                'Description'
            )
            ->addColumn(
                'is_enable',
                Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true, 'nullable' => false, 'default' => '1'],
                'Is enable'
            )
            ->addColumn(
                'created_date',
                Table::TYPE_DATE,
                null,
                [],
                'Date of creation'
            )
            ->addColumn(
                'updated_date',
                Table::TYPE_TIMESTAMP,
                null,
                ['default' => Table::TIMESTAMP_INIT_UPDATE],
                'Date of modification'
            )
            ->addColumn(
                'skip_next_delivery',
                Table::TYPE_SMALLINT,
                2,
                ['unsigned' => true],
                'Skip next delivery'
            )
            ->setComment(
                'Subscription Course'
            );
        $installer->getConnection()->createTable($tbl);

        $tbl = $installer->getConnection()->newTable($installer->getTable('subscription_course_frequency'))
            ->addColumn(
                'course_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false],
                'Course ID'
            )
            ->addColumn(
                'frequency_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false],
                'Frequency ID'
            )
            ->addForeignKey(
                $installer->getFkName('subscription_course_frequency', 'course_id', 'subscription_course', 'course_id'),
                'course_id',
                $installer->getTable('subscription_course'),
                'course_id',
                Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName('subscription_course_frequency', 'frequency_id', 'subscription_frequency', 'frequency_id'),
                'frequency_id',
                $installer->getTable('subscription_frequency'),
                'frequency_id',
                Table::ACTION_CASCADE
            )
            ->setComment(
                'Subscription course frequency'
            );
        $installer->getConnection()->createTable($tbl);

        $installer->endSetup();
    }
}
