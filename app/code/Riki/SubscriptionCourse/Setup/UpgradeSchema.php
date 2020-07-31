<?php
// @codingStandardsIgnoreFile
namespace Riki\SubscriptionCourse\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resourceConnection;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->_resourceConnection = $resourceConnection;
    }

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $table = $setup->getTable('subscription_course');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'code',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 20,
                        'comment' => 'Subscription course code',
                        'nullable' => false,
                        'after' => 'course_name',
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '1.0.2') < 0) {
            $table = $setup->getTable('subscription_course');
            $setup->getConnection()->dropColumn($table, 'code');
            $setup->getConnection()->addColumn(
                $table, 'course_code',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 20,
                    'comment' => 'Subscription course code',
                    'nullable' => false,
                    'after' => 'course_name',
                ]
            );
            $setup->run("UPDATE `subscription_course`SET `course_code` 
                        = (SELECT CONCAT(SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', RAND()*36+1, 1),
                        SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', RAND()*36+1, 1),
                        SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', RAND()*36+1, 1),
                        SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', RAND()*36+1, 1),
                        SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', RAND()*36+1, 1),
                        SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', RAND()*36+1, 1),
                        SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', RAND()*36+1, 1),
                        SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', RAND()*36+1, 1)
                        ) AS LicensePlaceNumber);");

            $setup->getConnection()->addIndex(
                $table, $setup->getIdxName($table, ['course_code']), ['course_code'], AdapterInterface::INDEX_TYPE_UNIQUE
            );
        }

        if (version_compare($context->getVersion(), '1.0.3') < 0) {
            $table = $setup->getTable('subscription_course');

            $setup->getConnection()
                ->addColumn(
                    $table,
                    'navigation_path',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' => 'Subscription Navigation Path',
                        'nullable' => true,
                    ]
                );
        }

        if (version_compare($context->getVersion(), '1.0.4') < 0) {
            $table = $setup->getTable('subscription_course_category');

            $salesConnection = $this->_resourceConnection->getConnection('sales');

            $salesConnection
                ->addColumn(
                    $table,
                    'is_addition',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'default' => 0,
                        'comment' => 'Is Addition Category',
                        'nullable' => true,
                    ]
                );
        }

        if (version_compare($context->getVersion(), '1.0.4.9') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');

            $table = $salesConnection->getTableName('subscription_course');

            if($salesConnection->isTableExists($table)){
                $salesConnection->dropColumn($table, 'design');
                $salesConnection->addColumn(
                    $table, 'design',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' => 'Design of subscription course',
                        'default' => 'normal'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.5') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');


            $table = $salesConnection->getTableName('subscription_course');

            if ($salesConnection->isTableExists($table) == true) {
                $salesConnection->addColumn(
                    $table, 'additional_category_description',
                    [
                        'type' => Table::TYPE_TEXT,
                        'comment' => 'Additional category description',
                        'nullable' => true
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.6') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');

            $table = $salesConnection->getTableName('subscription_frequency');

            $salesConnection->addColumn($table, 'position',
                [
                    'type' => Table::TYPE_SMALLINT,
                    'comment' => 'Frequency Position',
                    'nullable' => true,
                    'default' => 0
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.7') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            $table = $salesConnection->getTableName('subscription_course');
            $salesConnection->update($table,['subscription_type' => 'subscription'],['subscription_type = ?'=>'Subscription']);
        }

        if (version_compare($context->getVersion(), '1.0.8') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            $select = $salesConnection->select()
                ->from(['o'=>'sales_order'],['UPPER(c.subscription_type) as new_type','o.entity_id'])
                ->join(['p'=>'subscription_profile'],'o.subscription_profile_id = p.profile_id',[])
                ->join(['c'=>'subscription_course'],'p.course_id = c.course_id',[]);

            $salesConnection->query('update sales_order as o1,('.$select.') as o2 SET o1.riki_type = o2.new_type WHERE o1.entity_id = o2.entity_id');

        }

        if (version_compare($context->getVersion(), '1.0.9') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');

            $table = $salesConnection->getTableName('subscription_course');

            $salesConnection->addColumn($table, 'point_for_trial',
                [
                    'type' => Table::TYPE_INTEGER,
                    'comment' => 'Shopping point for trial',
                    'nullable' => true,
                    'default' => null
                ]
            );

            $salesConnection->addColumn($table, 'point_for_trial_wbs',
                [
                    'type' => Table::TYPE_TEXT,
                    'comment' => 'WBS',
                    'length' => 255,
                    'nullable' => true,
                    'default' => null
                ]
            );
            $salesConnection->addColumn($table, 'point_for_trial_account_code',
                [
                    'type' => Table::TYPE_TEXT,
                    'comment' => 'Account Code',
                    'length' => 255,
                    'nullable' => true,
                    'default' => null
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.10') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');

            $table = $setup->getTable('subscription_course');
            $salesConnection->addColumn($table, 'nth_delivery_simulation',
                [
                    'type' => Table::TYPE_SMALLINT,
                    'nullable' => true,
                    'comment' => 'Nth delivery simulation.',
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.0.11') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');

            $table = $setup->getTable('subscription_course_merge_profile');
            $tbl = $salesConnection->newTable($table)
                ->addColumn(
                    'course_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Course ID'
                )
                ->addColumn(
                    'merge_profile_to',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Merge Profile To'
                )
                ->addForeignKey(
                    $setup->getFkName('subscription_course_merge_profile', 'course_id', 'subscription_course', 'course_id'),
                    'course_id',
                    $setup->getTable('subscription_course'),
                    'course_id',
                    Table::ACTION_CASCADE
                )
                ->setComment(
                    'Subscription course merge profile '
                );
            $salesConnection->createTable($tbl);
        }

        if (version_compare($context->getVersion(), '1.0.12') < 0) {
            $table = $setup->getTable('subscription_course_category');

            $salesConnection = $this->_resourceConnection->getConnection('sales');

            $salesConnection
                ->addColumn(
                    $table,
                    'profile_only',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'default' => 0,
                        'comment' => 'Categories for only subscription profile edit',
                        'nullable' => true,
                    ]
                );
        }
        /** setup data for delay payment */
        if (version_compare($context->getVersion(), '1.0.13') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            $table = $setup->getTable('subscription_course');
            if (!$salesConnection->tableColumnExists($table, 'is_delay_payment')) {
                $salesConnection->addColumn($table, 'is_delay_payment',
                    [
                        'type' => Table::TYPE_BOOLEAN,
                        'nullable' => false,
                        'comment' => 'Is delay payment.',
                        'default'  => 0
                    ]
                );
            }

            if (!$salesConnection->tableColumnExists($table, 'maximum_order_qty ')) {
                $salesConnection->addColumn($table, 'maximum_order_qty',
                    [
                        'type' => Table::TYPE_SMALLINT,
                        'nullable' => true,
                        'comment' => 'Maximum order Qty ',
                        'unsigned' => true,
                        'default'  => null
                    ]
                );
            }
        }
        /** setup data for delay payment 2nd */
        if (version_compare($context->getVersion(), '1.0.14') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            $table = $setup->getTable('subscription_course');
            if (!$salesConnection->tableColumnExists($table, 'is_shopping_point_deduction')) {
                $salesConnection->addColumn($table, 'is_shopping_point_deduction',
                    [
                        'type' => Table::TYPE_BOOLEAN,
                        'nullable' => false,
                        'comment' => 'Shopping point deduction for 1st delivery.',
                        'default'  => 0
                    ]
                );
            }

            if (!$salesConnection->tableColumnExists($table, 'payment_delay_time')) {
                $salesConnection->addColumn($table, 'payment_delay_time',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'nullable' => true,
                        'comment' => 'Payment delay time',
                        'unsigned' => true,
                        'default'  => null
                    ]
                );
            }
        }

        /** maximum amount for delay payment 2nd */
        if (version_compare($context->getVersion(), '1.0.15') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            $table = $setup->getTable('subscription_course');
            if (!$salesConnection->tableColumnExists($table, 'total_amount_threshold')) {
                $salesConnection->addColumn($table, 'total_amount_threshold',
                    [
                        'type' => Table::TYPE_FLOAT,
                        'size' => '12,4',
                        'nullable' => false,
                        'comment' => 'Total amount threshold for 2nd order.',
                        'default'  => 0
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.1.0') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');

            $salesConnection->addColumn(
                $salesConnection->getTableName('subscription_course'),
                'allow_choose_delivery_date',
                [
                    'type' => Table::TYPE_BOOLEAN,
                    'nullable' => false,
                    'comment' => 'Allow choose delivery date on checkout',
                    'default'  => 1
                ]
            );
        }


        if (version_compare($context->getVersion(), '1.2.0') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');

            $salesConnection->addColumn(
                $salesConnection->getTableName('subscription_course'),
                'is_auto_box',
                [
                    'type' => Table::TYPE_BOOLEAN,
                    'nullable' => false,
                    'comment' => 'Is autobox delay payment course',
                    'default'  => 0
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.3.0') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');

            $salesConnection->addColumn(
                $salesConnection->getTableName('subscription_course'),
                'order_total_amount_option',
                [
                    'type' => Table::TYPE_BOOLEAN,
                    'nullable' => false,
                    'comment' => 'Order total amount threshold',
                    'default'  => 0 //Only apply for the second order
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.3.1') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            $tableName = $salesConnection->getTableName('subscription_course');
            $fieldName = 'oar_condition_serialized';
            $salesConnection->addColumn(
                $tableName,
                $fieldName,
                [
                    'type' => Table::TYPE_TEXT,
                    'size' => '64k',
                    'nullable' => true,
                    'comment' => 'Order total minimum amount threshold - custom order',
                ]
            );
        }
        /** setup data for Captured amount calculation option */
        if (version_compare($context->getVersion(), '1.3.2') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            $table = $setup->getTable('subscription_course');
            if (!$salesConnection->tableColumnExists($table, 'captured_amount_calculation_option')) {
                $salesConnection->addColumn($table, 'captured_amount_calculation_option',
                    [
                        'type' => Table::TYPE_BOOLEAN,
                        'nullable' => false,
                        'comment' => 'Captured amount calculation option',
                        'default'  => 0
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.3.3') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');

            $salesConnection->addColumn(
                $salesConnection->getTableName('subscription_course'),
                'is_allow_cancel_from_frontend',
                [
                    'type' => Table::TYPE_BOOLEAN,
                    'nullable' => false,
                    'comment' => 'Is allow cancel from frontend',
                    'default' => 0,
                    'after' => 'minimum_order_qty'
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.3.4') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            $tableName = $salesConnection->getTableName('subscription_course');

            if (!$salesConnection->tableColumnExists($tableName, 'maximum_qty_restriction_option')) {
                // Add new column maximum_qty_restriction_option
                $salesConnection->addColumn(
                    $tableName,
                    'maximum_qty_restriction_option',
                    [
                        'type' => Table::TYPE_BOOLEAN,
                        'nullable' => false,
                        'comment' => 'Maximum Qty Restriction Option',
                        'default'  => 1 // Only apply for the first order
                    ]
                );
            }

            if (!$salesConnection->tableColumnExists($tableName, 'maximum_qty_restriction')) {
                // Add new column maximum_qty_restriction
                $salesConnection->addColumn(
                    $tableName,
                    'maximum_qty_restriction',
                    [
                        'type' => Table::TYPE_TEXT,
                        'size' => '64k',
                        'nullable' => true,
                        'comment' => 'Maximum Qty Restriction - custom order',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.3.5') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            $table = $setup->getTable('subscription_course');
            if (!$salesConnection->tableColumnExists($table, 'restrict_active_course')) {
                $salesConnection->addColumn($table, 'restrict_active_course',
                    [
                        'type' => Table::TYPE_BOOLEAN,
                        'nullable' => false,
                        'comment' => 'Restrict active course',
                        'default'  => 0
                    ]
                );
            }
            if (!$salesConnection->tableColumnExists($table, 'restrict_inactive_course')) {
                $salesConnection->addColumn($table, 'restrict_inactive_course',
                    [
                        'type' => Table::TYPE_BOOLEAN,
                        'nullable' => false,
                        'comment' => 'Restrict inactive course',
                        'default'  => 0
                    ]
                );
            }
        }

        /** Add column last_order_time_is_delay_payment for subscription_course table */
        if (version_compare($context->getVersion(), '1.3.6') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            $table = $setup->getTable('subscription_course');
            if (!$salesConnection->tableColumnExists($table, 'last_order_time_is_delay_payment')) {
                $salesConnection->addColumn($table, 'last_order_time_is_delay_payment',
                    [
                        'type' => Table::TYPE_SMALLINT,
                        'nullable' => true,
                        'comment' => 'Last order time is delay payment',
                        'unsigned' => true,
                        'default'  => null
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.3.7') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            $subCourseTable = $salesConnection->getTableName('subscription_course');
            if (!$salesConnection->tableColumnExists($subCourseTable, 'exclude_buffer_days')) {
                $salesConnection->addColumn(
                    $subCourseTable,
                    'exclude_buffer_days',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'length' => 10,
                        'default' => 0,
                        'comment' => 'Exclude buffer days',
                        'nullable' => true,
                    ]
                );
            }
        }
        $setup->endSetup();
    }
}
