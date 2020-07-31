<?php
// @codingStandardsIgnoreFile
namespace Riki\Subscription\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Riki\Subscription\Model\Constant;
use Riki\Subscription\Model\ProductCart\ProductCart;
use Riki\Subscription\Model\Profile\Profile as Profile;
use Riki\SubscriptionCourse\Model\Course;
use Riki\SubscriptionCourse\Helper\Data as SubCourseHelper;

class UpgradeSchema implements UpgradeSchemaInterface
{
    protected $_subCourseHelper;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resourceConnection;
    /**
     * @var \Riki\Subscription\Api\GenerateOrder\ProfileBuilderInterface
     */
    protected $profileBuilder;
    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $publisher;
    /**
     * @var \Riki\Subscription\Model\Profile\Order\ProfileOrderFactory
     */
    protected $profileOrderFactory;

    public function __construct(
        SubCourseHelper $subCourseHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Amqp\Model\Topology $topology,
        \Riki\Subscription\Api\GenerateOrder\ProfileBuilderInterface $profileBuilderInterface,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Riki\Subscription\Model\Profile\Order\ProfileOrderFactory $profileOrderFactory
    ) {
        $this->_resourceConnection = $resourceConnection;
        $this->_subCourseHelper = $subCourseHelper;
        $this->topology = $topology;
        $this->profileBuilder = $profileBuilderInterface;
        $this->publisher = $publisher;
        $this->profileOrderFactory = $profileOrderFactory;
    }

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $connection = $setup->getConnection();

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $table = $setup->getTable('subscription_course');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->run("ALTER TABLE {$table} ADD COLUMN duration_unit ENUM('week','month')
                NOT NULL AFTER free_shipping");
            }
            $table = $setup->getTable('subscription_frequency');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->run("ALTER TABLE {$table} ADD COLUMN frequency_unit ENUM('week','month')
                NOT NULL AFTER frequency_id");
            }
        }

        if (version_compare($context->getVersion(), '1.0.2') < 0) {
            $table = $setup->getTable('subscription_course');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->run("ALTER TABLE {$table} ADD COLUMN course_name VARCHAR(255)
                NULL AFTER course_id");
            }
        }

        if (version_compare($context->getVersion(), '1.0.3') < 0) {
            $table = $setup->getTable('subscription_course');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'launch_date', ['type' => Table::TYPE_TIMESTAMP, 'comment' => 'Launch date']
                );
                $setup->getConnection()->addColumn(
                    $table, 'close_date', ['type' => Table::TYPE_TIMESTAMP, 'comment' => 'Close date']
                );
                $setup->getConnection()->addColumn(
                    $table, 'meta_title', ['type' => Table::TYPE_TEXT, 'length' => '255', 'comment' => 'Meta']
                );
                $setup->getConnection()->addColumn(
                    $table, 'meta_keywords', ['type' => Table::TYPE_TEXT, 'comment' => 'Meta']
                );
                $setup->getConnection()->addColumn(
                    $table, 'meta_description', ['type' => Table::TYPE_TEXT, 'comment' => 'Meta']
                );
                $setup->getConnection()->modifyColumn(
                    $table, 'created_date', ['type' => Table::TYPE_TIMESTAMP, 'default' => Table::TIMESTAMP_INIT]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.4') < 0) {
            $tbl = $setup->getConnection()->newTable($setup->getTable('subscription_course_website'))
                ->addColumn(
                    'course_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Course ID'
                )
                ->addColumn(
                    'website_id',
                    Table::TYPE_SMALLINT,
                    5,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Frequency ID'
                )
                ->addForeignKey(
                    $setup->getFkName('subscription_course_website', 'course_id', 'subscription_course', 'course_id'),
                    'course_id',
                    $setup->getTable('subscription_course'),
                    'course_id',
                    Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $setup->getFkName('subscription_course_website', 'website_id', 'store_website', 'website_id'),
                    'website_id',
                    $setup->getTable('store_website'),
                    'website_id',
                    Table::ACTION_CASCADE
                )
                ->setComment(
                    'Subscription course website'
                );
            $setup->getConnection()->createTable($tbl);
        }

        if (version_compare($context->getVersion(), '1.0.5') < 0) {
            $table = $setup->getTable('subscription_course');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->run("ALTER TABLE {$table} ADD COLUMN penalty_fee DECIMAL(12,4)
                NULL DEFAULT 0.0000");
            }
        }

        if (version_compare($context->getVersion(), '1.0.6') < 0) {
            $tbl = $setup->getConnection()->newTable($setup->getTable('subscription_course_payment'))
                ->addColumn(
                    'course_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Course ID'
                )
                ->addColumn(
                    'payment_id',
                    Table::TYPE_SMALLINT,
                    5,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Payment ID, define hard code in Model/Course'
                )
                ->addForeignKey(
                    $setup->getFkName('subscription_course_payment', 'course_id', 'subscription_course', 'course_id'),
                    'course_id',
                    $setup->getTable('subscription_course'),
                    'course_id',
                    Table::ACTION_CASCADE
                )
                ->setComment(
                    'Subscription course payment'
                );
            $setup->getConnection()->createTable($tbl);
        }

        if (version_compare($context->getVersion(), '1.0.7') < 0) {
            $table = $setup->getTable('subscription_course');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->changeColumn(
                    $table, 'skip_next_delivery', 'allow_skip_next_delivery', ['type' => Table::TYPE_SMALLINT, 'default' => 1]
                );
                $setup->getConnection()->addColumn(
                    $table, 'allow_change_next_delivery_date', ['type' => Table::TYPE_SMALLINT, 'default' => 1, 'comment' => 'Allow to chance next delivery date']
                );
                $setup->getConnection()->addColumn(
                    $table, 'allow_change_payment_method', ['type' => Table::TYPE_SMALLINT, 'default' => 1, 'comment' => 'Allow to chance payment method']
                );
                $setup->getConnection()->addColumn(
                    $table, 'allow_change_address', ['type' => Table::TYPE_SMALLINT, 'default' => 1, 'comment' => 'Allow to chance address']
                );
                $setup->getConnection()->addColumn(
                    $table, 'allow_change_product', ['type' => Table::TYPE_SMALLINT, 'default' => 1, 'comment' => 'Allow to chance product']
                );
                $setup->getConnection()->addColumn(
                    $table, 'allow_change_qty', ['type' => Table::TYPE_SMALLINT, 'default' => 1, 'comment' => 'Allow to chance quantity']
                );
                $setup->run("ALTER TABLE {$table} ADD COLUMN sales_value_count DECIMAL(12,4)
                NULL DEFAULT 0.0000");
                $setup->run("ALTER TABLE {$table} ADD COLUMN visibility ENUM('front-end','back-end')
                NULL");
            }
        }

        if (version_compare($context->getVersion(), '1.0.8') < 0) {
            $table = $setup->getTable('subscription_course');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->modifyColumn(
                    $table, 'must_select_sku', ['type' => Table::TYPE_TEXT, 'length' => 255]
                );
                $setup->getConnection()->modifyColumn(
                    $table, 'visibility', ['type' => Table::TYPE_SMALLINT, 'default' => 3, 'comment' => '0: None, 1: Front-end, 2: Back-end, 3: All']
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.9') < 0) {
            $table = $setup->getTable('sales_order');
            $setup->getConnection()->addColumn(
                $table,
                'riki_type',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'default' => 'SPOT',
                    'comment' => 'SPOT or SUBSCRIPTION order type',
                    'nullable' => false,
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.1.0') < 0) {
            $table = $setup->getTable('quote');
            $setup->getConnection()->addColumn(
                $table,
                'riki_course_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'comment' => 'Riki course Id save when create quote',
                    'nullable' => true,
                ]
            );

            // \Magento\Framework\DB\Ddl\Table::TYPE_TEXT
            // TYPE_BOOLEAN, TYPE_SMALLINT, TYPE_INTEGER, TYPE_BIGINT, TYPE_FLOAT, TYPE_NUMERIC, TYPE_DECIMAL, TYPE_DATE, TYPE_TIMESTAMP, TYPE_DATETIME, TYPE_TEXT, TYPE_BLOB, TYPE_VARBINARY,

            $objTable = $setup->getConnection()->newTable($setup->getTable(Profile::TABLE));
            $objTable->addColumn(
                'profile_id',
                Table::TYPE_INTEGER,
                'null',
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Automatic increase id'
            );

            $objTable->addColumn(
                'course_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'This column will connect to table subscription_course'
            );

            $objTable->addColumn(
                'customer_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'This column will connect to table customer_entity'
            );

            $objTable->addColumn(
                'frequency_unit',
                Table::TYPE_SMALLINT,
                2,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Frequency unit, 0 for week and 1 for month'
            );

            $objTable->addColumn(
                'frequency_interval',
                Table::TYPE_SMALLINT,
                2,
                ['unsigned' => true, 'nullable' => false, 'default' => 1],
                '1 week or 1 month, not 0 month'
            );

            $objTable->addColumn(
                'payment_method',
                Table::TYPE_SMALLINT,
                2,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Payment method for current profile: 1: creditcard, 2: code, 3: csv'
            );

            $objTable->addColumn(
                'shipping_fee',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                [12, 4],
                ['nullable' => false, 'default' => '0.0000'],
                'Shipping fee apply for this profile'
            );

            $objTable->addColumn(
                'shipping_condition',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '2M',
                ['nullable' => false, 'default' => ''],
                'Shipping condition text. Used to show on frontend'
            );

            $objTable->addColumn(
                'skip_next_delivery',
                Table::TYPE_SMALLINT,
                2,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'The boolean value confirm skip the next delivery or not. Default is not: 0'
            );

            $objTable->addColumn(
                'penalty_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                [12, 4],
                ['nullable' => false, 'default' => '0.0000'],
                'Policy for customer if they do not follow the rule of susbscription'
            );

            $objTable->addColumn(
                'next_delivery_date',
                Table::TYPE_DATE,
                null,
                [],
                'The next delivery date. When customer want to change the plan'
            );

            $objTable->addColumn(
                'next_order_date',
                Table::TYPE_DATE,
                null,
                [],
                'Next order date, The time order actually happen'
            );

            $objTable->addColumn(
                'status',
                Table::TYPE_SMALLINT,
                2,
                ['unsigned' => true, 'nullable' => false, 'default' => 1],
                'Status of current profile. enable or disable. Default is enable'
            );

            $objTable->addColumn(
                'order_times',
                Table::TYPE_SMALLINT,
                2,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Number time order has been created. Default will be zero'
            );

            $objTable->addColumn(
                'sales_count',
                Table::TYPE_INTEGER,
                '4',
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Total number of products that customer should purchase with subscription program'
            );

            $objTable->addColumn(
                'created_date',
                Table::TYPE_TIMESTAMP,
                null,
                [],
                'The time this profile created'
            );

            $objTable->addColumn(
                'updated_date',
                Table::TYPE_TIMESTAMP,
                null,
                ['default' => Table::TIMESTAMP_INIT_UPDATE],
                'The time this profile get updated'
            );

            /// create references
            // customer_id => customer_entity
            $objTable->addForeignKey(
                $setup->getFkName(Profile::TABLE, 'course_id', 'subscription_course', 'course_id'),
                'course_id',
                $setup->getTable(Course::TABLE),
                'course_id',
                Table::ACTION_CASCADE
            );

            //course_id => subscription_course

            $objTable->setComment('Subscription profile for each customer and their courses');

            $setup->getConnection()->createTable($objTable);
        }

        if (version_compare($context->getVersion(), '1.1.1') < 0) {
            $setup->getConnection()->addForeignKey(
                $setup->getFkName(
                    Profile::TABLE,
                    'customer_id',
                    $setup->getTable('customer_entity'),
                    'entity_id'
                ),
                $setup->getTable(Profile::TABLE),
                'customer_id',
                $setup->getTable('customer_entity'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            );
        }

        if (version_compare($context->getVersion(), '1.1.2') < 0) {
            $table = $setup->getTable('quote');
            $setup->getConnection()->addColumn(
                $table,
                Constant::RIKI_FREQUENCY_ID,
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'comment' => 'the frequency id which user choice',
                    'nullable' => true,
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.1.3') < 0) {
            /* Change type of frequency_unit in "subscription_profile"*/
            $setup->getConnection()->dropColumn($setup->getTable('subscription_profile'), 'frequency_unit');
            $table = $setup->getTable('subscription_profile');
            $setup->run("ALTER TABLE {$table} ADD COLUMN frequency_unit ENUM('week','month')
                NOT NULL AFTER customer_id");
            /* Create table: subscription_dtime*/
            $objTable = $setup->getConnection()->newTable($setup->getTable('subscription_dtime'));
            $objTable->addColumn(
                'dtime_id',
                Table::TYPE_INTEGER,
                11,
                ['identity' => true, 'unsigned' => true, 'primary' => true, 'nullable' => false],
                'Subscription Dtime ID'
            );
            $objTable->addColumn(
                'profile_id',
                Table::TYPE_INTEGER,
                11,
                ['unsigned' => true],
                'Subscription Profile ID'
            );
            $objTable->addColumn(
                'dtime',
                Table::TYPE_TEXT,
                255,
                ['unsigned' => true],
                'Delivery time'
            );
            $objTable->addColumn(
                'status',
                Table::TYPE_SMALLINT,
                2,
                ['unsigned' => true],
                'Status'
            );
            $objTable->addColumn(
                'mon',
                Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true],
                'Monday'
            );
            $objTable->addColumn(
                'tue',
                Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true],
                'Tuesday'
            );
            $objTable->addColumn(
                'wed',
                Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true],
                'Wednesday'
            );

            $objTable->addColumn(
                'thu',
                Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true],
                'Thursday'
            );

            $objTable->addColumn(
                'fri',
                Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true],
                'Friday'
            );

            $objTable->addColumn(
                'sat',
                Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true],
                'Saturday'
            );

            $objTable->addColumn(
                'sun',
                Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true],
                'Sunday'
            );
            $objTable->addColumn(
                'specialday',
                Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true],
                'Special Day'
            );
            $objTable->addColumn(
                'interval',
                Table::TYPE_TEXT,
                255,
                ['unsigned' => true],
                'Interval day (Label of time delivery schedule)'
            );
            $objTable->addColumn(
                'specialday_value',
                Table::TYPE_TEXT,
                20,
                ['unsigned' => true],
                'Special Day Value'
            );
            $objTable->setComment(
                'Subscription Profile Dtime'
            );
            /// create references
            // profile_id => profile_id
            $objTable->addForeignKey(
                $setup->getFkName('subscription_dtime', 'profile_id', 'subscription_profile', 'profile_id'),
                'profile_id',
                $setup->getTable('subscription_profile'),
                'profile_id',
                Table::ACTION_CASCADE
            );
            $setup->getConnection()->createTable($objTable);

            /* Create table: subscription_profile_product_cart */
            $tbl = $setup->getConnection()->newTable($setup->getTable('subscription_profile_product_cart'))
                ->addColumn(
                    'cart_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Subscription Profile Product Cart ID'
                )
                ->addColumn(
                    'profile_id',
                    Table::TYPE_INTEGER,
                    11,
                    ['unsigned' => true, 'nullable' => false],
                    'Subscription Profile ID'
                )
                ->addColumn(
                    'qty',
                    Table::TYPE_SMALLINT,
                    2,
                    ['unsigned' => true, 'nullable' => false],
                    'Product Qty'
                )
                ->addColumn(
                    'product_type',
                    Table::TYPE_TEXT,
                    30,
                    ['unsigned' => true, 'nullable' => false],
                    'Product Type'
                )
                ->addColumn(
                    'product_id',
                    Table::TYPE_INTEGER,
                    11,
                    ['unsigned' => true, 'nullable' => false],
                    'Product ID'
                )
                ->addColumn(
                    'product_options',
                    Table::TYPE_TEXT,
                    null,
                    ['unsigned' => true],
                    'Product Options'
                )
                ->addColumn(
                    'parent_item_id',
                    Table::TYPE_INTEGER,
                    11,
                    ['unsigned' => true],
                    'Order Item ID'
                )
                ->addColumn(
                    'created_at',
                    Table::TYPE_DATETIME,
                    null,
                    ['unsigned' => true, 'nullable' => false],
                    'Created At'
                )
                ->addColumn(
                    'billing_address_id',
                    Table::TYPE_INTEGER,
                    11,
                    ['unsigned' => true, 'nullable' => false],
                    'Billing Address ID'
                )
                ->addColumn(
                    'shipping_address_id',
                    Table::TYPE_INTEGER,
                    11,
                    ['unsigned' => true, 'nullable' => false],
                    'Shipping Address ID'
                )
                ->addColumn(
                    'updated_at',
                    Table::TYPE_DATETIME,
                    null,
                    ['unsigned' => true],
                    'Updated At'
                )
                ->setComment(
                    'Subscription Profile Product Cart'
                );
            /// create references
            // profile_id => profile_id
            $tbl->addForeignKey(
                $setup->getFkName('subscription_profile_product_cart', 'profile_id', 'subscription_profile', 'profile_id'),
                'profile_id',
                $setup->getTable('subscription_profile'),
                'profile_id',
                Table::ACTION_CASCADE
            );
            $setup->getConnection()->createTable($tbl);
        }

        if (version_compare($context->getVersion(), '1.1.4') < 0) {

            // \Magento\Framework\DB\Ddl\Table::TYPE_TEXT
            // TYPE_BOOLEAN, TYPE_SMALLINT, TYPE_INTEGER, TYPE_BIGINT, TYPE_FLOAT, TYPE_NUMERIC, TYPE_DECIMAL, TYPE_DATE, TYPE_TIMESTAMP, TYPE_DATETIME, TYPE_TEXT, TYPE_BLOB, TYPE_VARBINARY,
            $strTableName = $setup->getTable(Profile::TABLE);

            $setup->getConnection()->modifyColumn(
                $strTableName, 'payment_method', ['type' => Table::TYPE_TEXT, 'default' => '', 'nullable' => false, 'comment' => 'Use payment code instead']
            );
        }

        if (version_compare($context->getVersion(), '1.1.5') < 0) {
            $tableName = $setup->getTable(Profile::TABLE);
            $setup->getConnection()->addColumn(
                $tableName,
                'coupon_code',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'default' => '',
                    'comment' => 'Coupon code the first time',
                    'nullable' => true,
                ]
            );

            $setup->getConnection()->addColumn(
                $tableName,
                'point_allow_used',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'default' => 0,
                    'comment' => 'Used point fixed',
                    'nullable' => true,
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.1.6') < 0) {
            $table = $setup->getTable('subscription_profile_product_cart');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'gw_used', ['type' => Table::TYPE_SMALLINT, 'default' => 0, 'comment' => '0: No use Gift Wrapping, 1: Use']
                );
            }
        }

        /*
         * Every product cart have address and delivery and time slot different
         */
        if (version_compare($context->getVersion(), '1.1.7') < 0) {
            $tableName = $setup->getTable(ProductCart::TABLE);
            $setup->getConnection()->addColumn(
                $tableName,
                'delivery_date',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                    'comment' => 'Delivery date of current product',
                    'nullable' => false,
                ]
            );

            $setup->getConnection()->addColumn(
                $tableName,
                'delivery_time_slot',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'comment' => 'For example: 01:00 - 02:00',
                    'nullable' => true,
                ]
            );
        }
        /*
         * Add new table subscription_profile_version
         * Use to control the profile version
         */
        if (version_compare($context->getVersion(), '1.1.8') < 0) {
            $tbl = $setup->getConnection()->newTable($setup->getTable('subscription_profile_version'))
                ->addColumn(
                    'id',
                    Table::TYPE_BIGINT,
                    null,
                    ['identity' => true, 'unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Update ID'
                )
                ->addColumn(
                    'start_time',
                    Table::TYPE_DATETIME,
                    null,
                    ['nullable' => true],
                    'Update start time'
                )
                ->addColumn(
                    'name',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => true],
                    'Update name'
                )
                ->addColumn(
                    'description',
                    Table::TYPE_TEXT,
                    225,
                    ['nullable' => true],
                    'Update description'
                )
                ->addColumn(
                    'rollback_id',
                    Table::TYPE_BIGINT,
                    20,
                    ['unsigned' => true, 'nullable' => true],
                    'Rollback ID'
                )
                ->addColumn(
                    'is_campaign',
                    Table::TYPE_INTEGER,
                    1,
                    ['nullable' => true],
                    'Is update a campaign'
                )
                ->addColumn(
                    'is_rollback',
                    Table::TYPE_INTEGER,
                    1,
                    ['nullable' => true],
                    'Is update a rollback'
                )
                ->addColumn(
                    'moved_to',
                    Table::TYPE_BIGINT,
                    20,
                    ['unsigned' => true, 'nullable' => true],
                    'Update Id it was moved to'
                )
                ->addColumn(
                    'status',
                    Table::TYPE_BOOLEAN,
                    null,
                    ['unsigned' => true, 'nullable' => true],
                    'Status of version'
                )
                ->setComment(
                    'Subscription Profile Updates table'
                );
            $setup->getConnection()->createTable($tbl);
            /*Add delivery_type to table Subscription_profile_product_cart*/
            $tableName = $setup->getTable(ProductCart::TABLE);
            $setup->getConnection()->addColumn(
                $tableName,
                'delivery_type',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'comment' => 'Delivery type of current product',
                    'nullable' => true,
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.1.9') < 0) {
            $tableName = $setup->getTable('subscription_profile');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection = $setup->getConnection();
                $connection->addColumn(
                    $tableName,
                    'tradding_id',
                    ['type' => Table::TYPE_TEXT, 'nullable' => true, 'default' => '', 'comment' => 'Trading Id for paygent payment method']
                );
            }
        }

        // 1.2.0
        if (version_compare($context->getVersion(), '1.2.0') < 0) {
            $table = $setup->getTable('subscription_course');

            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'subscription_type', ['type' => Table::TYPE_TEXT, 'length' => '20', 'comment' => '[Subscription, Hanpukai]']
                );

                $setup->getConnection()->addColumn(
                    $table, 'hanpukai_type', ['type' => Table::TYPE_TEXT, 'length' => '20', 'comment' => '1: Fixed 2: Sequence 3: Seasonal']
                );

                $setup->getConnection()->addColumn(
                    $table, 'hanpukai_maximum_order_times', ['type' => Table::TYPE_INTEGER, 'comment' => 'Maximum order times', 'nullable' => false]
                );

                $setup->getConnection()->addColumn(
                    $table, 'hanpukai_delivery_date_allowed', [
                        'type' => Table::TYPE_BOOLEAN,
                        'comment' => 'Define if customer will select the first delivery date, or all delivery dates (including the first delivery) will be preset.',
                        'nullable' => false,
                        'unsigned' => true,
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.3.0') < 0) {
            $table = $setup->getConnection()->newTable($setup->getTable('hanpukai_fixed'))
                ->addColumn(
                    'course_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Subscription Course ID'
                )
                ->addColumn(
                    'product_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Product ID'
                )
                ->addColumn(
                    'qty',
                    Table::TYPE_DECIMAL,
                    '12,4',
                    ['nullable' => false],
                    'Number of product will be added to cart'
                )
                ->addForeignKey(
                    $setup->getFkName('hanpukai_fixed', 'course_id', 'subscription_course', 'course_id'),
                    'course_id',
                    $setup->getTable('subscription_course'),
                    'course_id',
                    Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $setup->getFkName('hanpukai_fixed', 'product_id', 'catalog_product_entity', 'entity_id'),
                    'product_id',
                    $setup->getTable('catalog_product_entity'),
                    'entity_id',
                    Table::ACTION_CASCADE
                )
                ->setComment(
                    'Hanpukai Fixed table'
                );
            $setup->getConnection()->createTable($table);

            ///
            $table = $setup->getConnection()->newTable($setup->getTable('hanpukai_sequence'))
                ->addColumn(
                    'course_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Subscription Course ID'
                )
                ->addColumn(
                    'product_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Product ID'
                )
                ->addColumn(
                    'delivery_number',
                    Table::TYPE_SMALLINT,
                    null,
                    ['unsigned' => true, 'nullable' => false],
                    'The delivery number when this product will be sent'
                )
                ->addColumn(
                    'qty',
                    Table::TYPE_DECIMAL,
                    '12,4',
                    ['nullable' => false],
                    'Number of product will be added to cart'
                )
                ->addForeignKey(
                    $setup->getFkName('hanpukai_sequence', 'course_id', 'subscription_course', 'course_id'),
                    'course_id',
                    $setup->getTable('subscription_course'),
                    'course_id',
                    Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $setup->getFkName('hanpukai_sequence', 'product_id', 'catalog_product_entity', 'entity_id'),
                    'product_id',
                    $setup->getTable('catalog_product_entity'),
                    'entity_id',
                    Table::ACTION_CASCADE
                )
                ->setComment(
                    'Hanpukai Sequence table'
                );
            $setup->getConnection()->createTable($table);

            ///
            $table = $setup->getConnection()->newTable($setup->getTable('hanpukai_month'))
                ->addColumn(
                    'course_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Subscription Course ID'
                )
                ->addColumn(
                    'product_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Product ID'
                )
                ->addColumn(
                    'delivery_month',
                    Table::TYPE_DATE,
                    null,
                    ['nullable' => false],
                    'The YEAR-MONTH time when this product will be sent.'
                )
                ->addColumn(
                    'qty',
                    Table::TYPE_DECIMAL,
                    '12,4',
                    ['nullable' => false],
                    'Number of product will be added to cart'
                )
                ->addForeignKey(
                    $setup->getFkName('hanpukai_month', 'course_id', 'subscription_course', 'course_id'),
                    'course_id',
                    $setup->getTable('subscription_course'),
                    'course_id',
                    Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $setup->getFkName('hanpukai_month', 'product_id', 'catalog_product_entity', 'entity_id'),
                    'product_id',
                    $setup->getTable('catalog_product_entity'),
                    'entity_id',
                    Table::ACTION_CASCADE
                )
                ->setComment(
                    'Hanpukai Month table'
                );
            $setup->getConnection()->createTable($table);
        }

        if (version_compare($context->getVersion(), '1.4.0') < 0) {
            $table = $setup->getTable('subscription_profile');

            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'create_order_flag', ['type' => Table::TYPE_BOOLEAN, 'comment' => 'Create order flag', 'default' => 0]
                );
            }
        }

        //change column trading_id to trading_id
        if (version_compare($context->getVersion(), '1.4.1') < 0) {
            $tableName = $setup->getTable('subscription_profile');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection = $setup->getConnection();
                $connection->changeColumn(
                    $tableName,
                    'tradding_id',
                    'trading_id',
                    ['type' => Table::TYPE_TEXT, 'nullable' => true, 'default' => '', 'comment' => 'Trading Id for paygent payment method']
                );
            }
        }

        if (version_compare($context->getVersion(), '1.4.2') < 0) {
            $table = $setup->getTable('subscription_profile_product_cart');

            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'unit_case', ['type' => Table::TYPE_TEXT, 255, 'comment' => 'Unit Case', 'default' => null]
                );

                $setup->getConnection()->addColumn(
                    $table, 'unit_qty', ['type' => Table::TYPE_INTEGER, 10, 'comment' => 'Unit Quantity', 'default' => 1]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.4.3') < 0) {
            $setup->getConnection()->dropTable($setup->getTable('hanpukai_month'));
        }

        if (version_compare($context->getVersion(), '1.4.4') < 0) {
            $table = $setup->getTable('subscription_profile_product_cart');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'gw_id', ['type' => Table::TYPE_SMALLINT, 'default' => null, 'comment' => 'Id Gift Wrapping of Item']
                );
            }
        }

        // add field for hanpukai
        if (version_compare($context->getVersion(), '1.4.5') < 0) {
            $table = $setup->getTable('subscription_course');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'hanpukai_delivery_date_from', ['type' => Table::TYPE_TIMESTAMP, 'comment' => 'Hanpukai Delivery Date From']
                );
                $setup->getConnection()->addColumn(
                    $table, 'hanpukai_delivery_date_to', ['type' => Table::TYPE_TIMESTAMP, 'comment' => 'Hanpukai Delivery Date To']
                );
                $setup->getConnection()->addColumn(
                    $table, 'hanpukai_first_delivery_date', ['type' => Table::TYPE_TIMESTAMP, 'comment' => 'Hanpukai First Delivery Date']
                );
            }
        }

        // Change subscription course for new request
        if (version_compare($context->getVersion(), '1.4.6') < 0) {
            $table = $setup->getTable('subscription_course');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->modifyColumn($table, 'launch_date', [
                    'type' => Table::TYPE_TIMESTAMP,
                    'nullable' => false,
                    'comment' => 'Launch Date',
                ]);
            }
        }
        if (version_compare($context->getVersion(), '1.4.7') < 0) {
            $table = $setup->getTable('subscription_profile_product_cart');
            if ($connection->isTableExists($table) && $connection->tableColumnExists($table, 'application_count')) {
                $connection->dropColumn($table, 'application_count');
            }
        }
        if (version_compare($context->getVersion(), '1.4.8') < 0) {
            $table = $setup->getTable('subscription_profile_product_cart');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'gift_message_id', ['type' => Table::TYPE_SMALLINT, 'default' => null, 'comment' => 'Id Gift Message of Item']
                );
            }
        }
        // Change subscription course for new request
        if (version_compare($context->getVersion(), '1.4.8') < 0) {
            $table = $setup->getTable('subscription_profile');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'store_id', ['type' => Table::TYPE_SMALLINT, 'default' => null, 'comment' => 'Store ID', 'after' => 'customer_id']
                );
            }
        }

        if (version_compare($context->getVersion(), '1.4.9') < 0) {
            $table = $setup->getTable('subscription_course');

            if ($connection->tableColumnExists($table, 'free_shipping')) {
                $connection->dropColumn($table, 'free_shipping');
            }

            if ($connection->tableColumnExists($table, 'allow_register_on_frontend')) {
                $connection->dropColumn($table, 'allow_register_on_frontend');
            }

            $table = $setup->getTable('subscription_course_category');

            if ($connection->tableColumnExists($table, 'priority')) {
                $connection->dropColumn($table, 'priority');
            }
        }

        if (version_compare($context->getVersion(), '1.5.0') < 0) {
            $table = $setup->getTable(Profile::TABLE);

            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'course_name') && $connection->tableColumnExists($table, 'course_id')) {
                $connection->addColumn($table,
                    'course_name',
                    [
                        'type' => Table::TYPE_TEXT,
                        null,
                        'comment' => 'Subscription course name',
                        'after' => 'course_id',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.5.1') < 0) {
            $table = $setup->getTable('subscription_course_product');

            if ($connection->isTableExists($table)) {
                $this->_subCourseHelper->deleteAllProductOfSubCourse();
            }
        }

        if (version_compare($context->getVersion(), '1.5.2.1') < 0) {
            // add column to table subscription_profile
            $table = $setup->getTable('subscription_profile');
            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'type')) {
                $connection->addColumn($table,
                    'type',
                    [
                        'type' => Table::TYPE_TEXT,
                        null,
                        'comment' => 'Subscription Profile Type: main, temp',
                        'after' => 'create_order_flag',
                    ]
                );
            }

            // create new table subscription_profile_link
            $table = $connection
                ->newTable($setup->getTable('subscription_profile_link'))
                ->addColumn(
                    'link_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Link ID'
                )
                ->addColumn(
                    'profile_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    10,
                    ['unsigned' => true, 'nullable' => false],
                    'Profile ID'
                )
                ->addColumn(
                    'linked_profile_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    10,
                    ['unsigned' => true, 'nullable' => false],
                    'Linked Profile Id'
                )
                ->addColumn(
                    'link_type_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => true],
                    'Link Type Id'
                )->addForeignKey(
                    $setup->getFkName('subscription_profile_link',
                        'profile_id', 'subscription_profile', 'profile_id'),
                    'profile_id',
                    $setup->getTable('subscription_profile'),
                    'profile_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->addIndex('index_link_id', 'link_id')
                ->addIndex('index_profile_id', 'profile_id')
                ->setComment('Subscription Profile Link For Three Delivery Logic');
            $connection->createTable($table);
        }
        if (version_compare($context->getVersion(), '1.5.3') < 0) {
            $table = $setup->getTable(Profile::TABLE);

            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'earn_point_on_order') && $connection->tableColumnExists($table, 'course_id')) {
                $connection->addColumn($table,
                    'earn_point_on_order',
                    [
                        'type' => Table::TYPE_BOOLEAN,
                        null,
                        'comment' => 'Earn point on order',
                        'after' => 'type',
                    ]
                );
            }
        }

        // setup subscription machine - 3.1.2
        if (version_compare($context->getVersion(), '1.5.4') < 0) {
            $table = $setup->getTable('hanpukai_fixed');

            if ($connection->isTableExists($table)) {
                $connection->addColumn(
                    $table, 'unit_case',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 'length' => 255, 'default' => 'EA', 'comment' => 'Unit Case']
                );
                $connection->addColumn(
                    $table, 'unit_qty',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL, '12,4', 'default' => 1.0, 'comment' => 'Unit Quantity']
                );
            }

            $table = $setup->getTable('hanpukai_sequence');

            if ($connection->isTableExists($table)) {
                $connection->addColumn(
                    $table, 'unit_case',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 'length' => 255, 'default' => 'EA', 'comment' => 'Unit Case']
                );
                $connection->addColumn(
                    $table, 'unit_qty',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL, '12,4', 'default' => 1.0, 'comment' => 'Unit Quantity']
                );
            }
        }

        if (version_compare($context->getVersion(), '1.5.5') < 0) {
            $table = $setup->getTable('subscription_profile');

            if ($connection->isTableExists($table)) {
                $connection->addColumn(
                    $table, 'sales_value_count',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 'length' => 11, 'comment' => 'Sales Amount Count', 'after' => 'sales_count']
                );
            }
        }

        if (version_compare($context->getVersion(), '1.5.6') < 0) {
            $table = $setup->getTable('subscription_profile_link');
            if ($connection->isTableExists($table)) {
                $connection->addColumn(
                    $table, 'change_type',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'length' => 11,
                        'comment' => 'Apply for this subscription or apply all',
                        'after' => 'link_type_id',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.5.7') < 0) {
            $tbl = $setup->getConnection()
                ->newTable($setup->getTable('subscription_machine'))
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
                    'Product ID. Backup when SKU has been changed. Good for select product object'
                )
                ->addColumn(
                    'is_free',
                    Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => false, 'default' => '0'],
                    'Is free, the item has price is zero'
                )
                ->addColumn(
                    'discount_amount',
                    Table::TYPE_DECIMAL,
                    [12, 4],
                    ['nullable' => false, 'default' => '0.0000'],
                    'Discount amount support by percent now'
                )
                ->addColumn(
                    'wbs',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'WBS code'
                )
                ->addColumn(
                    'sort_order',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                    'Sort Order'
                )
                ->addColumn(
                    'is_active',
                    Table::TYPE_SMALLINT,
                    null,
                    ['unsigned' => true, 'default' => '1'],
                    'Is Active'
                )
                ->addIndex(
                    $setup->getIdxName('subscription_machine', ['wbs']),
                    ['wbs']
                )
                ->setComment(
                    'Subscription machine'
                );
            $setup->getConnection()->createTable($tbl);

            $table = $setup->getTable('subscription_profile');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'old_profile_id', ['type' => Table::TYPE_TEXT, 255, 'comment' => 'Old profile id', 'default' => null]
                );
            }
            $table = $setup->getTable('subscription_profile_product_cart');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'old_product_id', ['type' => Table::TYPE_TEXT, 255, 'comment' => 'Old product id', 'default' => null]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.5.8') < 0) {
            $table = $setup->getTable('quote_item');

            if ($connection->isTableExists($table)) {
                $connection->addColumn(
                    $table, 'is_riki_machine',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 'length' => 6, 'default' => 0, 'comment' => 'Is riki machine product, we need to know machine in a cart']
                );
            }
        }

        if (version_compare($context->getVersion(), '1.5.9') < 0) {
            $table = $setup->getTable('sales_order_item');

            if ($connection->isTableExists($table)) {
                $connection->addColumn(
                    $table, 'is_riki_machine',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 'length' => 6, 'default' => 0, 'comment' => 'Is riki machine product, we need to know machine in a cart']
                );
            }
        }

        if (version_compare($context->getVersion(), '1.6.0') < 0) {

            $table = $setup->getTable('subscription_profile_product_cart');

            if($connection->isTableExists($table)){
                $connection->addColumn(
                    $table, 'is_skip_seasonal',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,'comment' => 'Seasonal skip optional']
                );
                $connection->addColumn(
                    $table, 'skip_from',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,  'comment' => 'Skip seasonal product from']
                );
                $connection->addColumn(
                    $table, 'skip_to',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE, 'comment' => 'Skip seasonal product to']
                );
            }
        }

        if (version_compare($context->getVersion(), '1.6.1') < 0) {
            $table = $setup->getTable('subscription_profile_product_cart');
            if($connection->isTableExists($table)){
                $connection->addColumn(
                    $table, 'is_spot',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'default' => 0,
                        'comment' => 'Check is spot product. 0 not sport, 1 is spot'
                    ]
                );
            }
        }

        // remove foreign key for Split DB
        if (version_compare($context->getVersion(), '1.6.2') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            $tableFixed = $setup->getTable('hanpukai_fixed');
            if($salesConnection->isTableExists($tableFixed)){
                $salesConnection->dropForeignKey(
                    $tableFixed,
                    $setup->getFkName($tableFixed, 'product_id', 'catalog_product_entity', 'entity_id')
                );
            }
            $tableSequence = $setup->getTable('hanpukai_sequence');
            if($salesConnection->isTableExists($tableSequence)){
                $salesConnection->dropForeignKey(
                    $tableSequence,
                    $setup->getFkName($tableSequence, 'product_id', 'catalog_product_entity', 'entity_id')
                );
            }

            $checkoutCollection = $this->_resourceConnection->getConnection('checkout');
            $table = $checkoutCollection->getTableName('quote');
            $checkoutCollection->addColumn(
                $table,
                'riki_hanpukai_qty',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'comment' => 'Riki hanpukai qty save when create quote',
                    'nullable' => true,
                    'default' => null
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.6.3') < 0) {
            $table = $setup->getTable('subscription_profile');
            if($connection->isTableExists($table)){
                $connection->addColumn(
                    $table, 'hanpukai_qty',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'comment' => 'Quantity of Hanpukai',
                        'after' => 'course_name'
                    ]
                );
            }
        }


        if (version_compare($context->getVersion(), '1.6.4') < 0) {
            $table = $setup->getTable('subscription_profile');
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            if($salesConnection->isTableExists($table)){
                $salesConnection->addColumn(
                    $table, 'admin_created_by',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' => 'User admin create profile',
                        'default' => null
                    ]
                );
                $salesConnection->addColumn(
                    $table, 'admin_updated_by',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' => 'User admin update profile',
                        'default' => null
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.6.5') < 0) {
            $table = $setup->getTable('subscription_profile');
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            if($salesConnection->isTableExists($table)){
                $salesConnection->dropColumn($table,'admin_created_by');
                $salesConnection->addColumn(
                    $table, 'created_user',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' => 'User admin create profile',
                        'default' => null
                    ]
                );
                $salesConnection->dropColumn($table,'admin_updated_by');
                $salesConnection->addColumn(
                    $table, 'updated_user',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' => 'User admin update profile',
                        'default' => null
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.6.6.1') < 0) {
            $checkoutCollection = $this->_resourceConnection->getConnection('checkout');
            $table = $checkoutCollection->getTableName('quote');
            if (!$checkoutCollection->tableColumnExists($table, 'riki_hanpukai_qty')) {
                $checkoutCollection->addColumn(
                    $table,
                    'riki_hanpukai_qty',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'comment' => 'Riki hanpukai qty save when create quote',
                        'nullable' => true,
                        'default' => null
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.6.6') < 0) {
            $table = $setup->getTable('subscription_course');
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            if($salesConnection->isTableExists($table)){
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

        if (version_compare($context->getVersion(), '1.6.7') < 0) {
            $table = $setup->getTable('subscription_profile_product_cart');
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            if($salesConnection->isTableExists($table)){
                $salesConnection->addColumn(
                    $table, 'is_addition',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'default' => 0,
                        'comment' => 'Is Addition Category',
                        'nullable' => true,
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.6.8') < 0) {
            $table = $setup->getTable('quote_item');
            $checkoutConnection = $this->_resourceConnection->getConnection('checkout');
            if($checkoutConnection->isTableExists($table)){
                $checkoutConnection->addColumn(
                    $table, 'is_addition',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'default' => 0,
                        'comment' => 'Is Addition Category',
                        'nullable' => true,
                    ]
                );
            }
            $table = $setup->getTable('sales_order_item');
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            if($salesConnection->isTableExists($table)){
                $salesConnection->addColumn(
                    $table, 'is_addition',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'default' => 0,
                        'comment' => 'Is Addition Category',
                        'nullable' => true,
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '1.6.9') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            $salesConnection->modifyColumn(
                $salesConnection->getTableName('subscription_profile'),
                'payment_method',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'default' => NULL
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.6.10') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            $tbl = $salesConnection->newTable($setup->getTable('subscription_profile_simulate_cache'))
                ->addColumn(
                    'profile_simulate_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Profile Simulate ID'
                )
                ->addColumn(
                    'profile_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true],
                    'Profile ID'
                )
                ->addColumn(
                    'customer_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true],
                    'Customer ID'
                )
                ->addColumn(
                    'data_serialized',
                    Table::TYPE_TEXT,
                    '2M',
                    [],
                    'Data serialized'
                )
                ->addIndex(
                    $setup->getIdxName('subscription_profile_simulate_cache', ['profile_id']),
                    ['profile_id']
                )
                ->addIndex(
                    $setup->getIdxName('subscription_profile_simulate_cache', ['customer_id']),
                    ['customer_id']
                )
                ->setComment(
                    'Subscription profile simulate data'
                );
            $salesConnection->createTable($tbl);
        }
        /*add flag publish message queue*/
        if (version_compare($context->getVersion(), '1.7.0') < 0) {
            $table = $setup->getTable('subscription_profile');
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            if($salesConnection->isTableExists($table)){
                $salesConnection->addColumn(
                    $table, 'publish_message',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'default' => 0,
                        'comment' => 'Published to message queue',
                        'nullable' => true,
                        'after' =>'create_order_flag'
                    ]
                );
            }
            $table = $setup->getTable('queue');
            if($setup->tableExists($table)){
                $setup->run("DELETE FROM {$table} WHERE name = 'sender_queue_subscription_profile_generate_order' ");
                $setup->getConnection('default')->insert($setup->getTable('queue'), ['name' => 'sender_queue_subscription_profile_generate_order']);            }
        }

        if (version_compare($context->getVersion(), '1.7.1') < 0) {
            $tableName = $setup->getTable('subscription_profile_simulate_cache');
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            if ($salesConnection->isTableExists($tableName)) {
                $salesConnection->addColumn(
                    $tableName, 'delivery_number',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'default' => 0,
                        'comment' => 'Delivery Number For Subscription Hanpukai',
                        'nullable' => true,
                        'after' => 'profile_id'
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '1.7.2') < 0) {
            $table = $setup->getTable('subscription_profile');
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            if($salesConnection->isTableExists($table)){
                $salesConnection->addColumn(
                    $table, 'hanpukai_qty',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'comment' => 'Quantity of Hanpukai',
                        'after' => 'course_name'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.7.4') < 0) {
            $table = $setup->getTable('subscription_profile');
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            if($salesConnection->isTableExists($table)){
                if(!$salesConnection->tableColumnExists($table,'last_authorization_failed_date')){
                    $salesConnection->addColumn(
                        $table, 'last_authorization_failed_date',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                            'nullable' => true,
                            'comment' => 'Last date authorization failed profile'
                        ]
                    );
                }
                if(!$salesConnection->tableColumnExists($table,'authorization_failed_time')){
                    $salesConnection->addColumn(
                        $table, 'authorization_failed_time',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                            'default' => 0,
                            'comment' => 'Number authorization failed profile',
                            'nullable' => true,
                        ]
                    );
                }

            }
        }
        if (version_compare($context->getVersion(), '1.7.5') < 0) {
            $table = $setup->getTable('queue');
            if($setup->tableExists($table)){
                $setup->run("DELETE FROM {$table} WHERE name = 'sender_queue_subscription_profile_edited_order' ");
                $setup->getConnection('default')->insert($setup->getTable('queue'), ['name' => 'sender_queue_subscription_profile_edited_order']);
            }
        }

        if (version_compare($context->getVersion(), '1.7.6') < 0) {
            $table = $setup->getTable('subscription_profile');
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            if($salesConnection->isTableExists($table)){
                if(!$salesConnection->tableColumnExists($table,'order_channel')){
                    $salesConnection->addColumn(
                        $table, 'order_channel',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                            'length' => 255,
                            'comment' => 'Order Channel',
                            'nullable' => true,
                        ]
                    );
                }
            }
        }
        if (version_compare($context->getVersion(), '1.7.7') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            $select = $salesConnection->select()
                ->from(['p'=>'subscription_profile'],['profile_id'])
                ->join(['c' =>'subscription_course'],'p.course_id = c.course_id',[])
                ->where("c.subscription_type='hanpukai' and p.hanpukai_qty is null");

            $salesConnection->query('update subscription_profile as p1,('.$select.') as p2 SET p1.hanpukai_qty = 1 WHERE p1.profile_id = p2.profile_id');
        }



        if (version_compare($context->getVersion(), '1.7.8') < 0) {

            $table = $setup->getTable('subscription_profile_version');
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            if($salesConnection->isTableExists($table)){

                $salesConnection->addIndex(
                    $table,
                    $connection->getIndexName($table, ['rollback_id' ], true),
                    ['rollback_id','moved_to'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                );
            }
        }

        if (version_compare($context->getVersion(), '1.7.9') < 0) {

            $table = $setup->getTable('subscription_profile');
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            if($salesConnection->isTableExists($table)){

                $salesConnection->addColumn(
                    $table, 'random_trading',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'length' => 10,
                        'default' => null,
                        'comment' => 'Number authorization profile',
                        'nullable' => true,
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '1.8.0') < 0) {

            $table = $setup->getTable('subscription_profile');
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            if ($salesConnection->isTableExists($table)) {

                $salesConnection->modifyColumn(
                    $table,
                    'random_trading',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 12,
                        'nullable' => false,
                        'default' => null,
                        'nullable' => true,
                        'comment' => 'Random Trading'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.8.1') < 0) {
            $defaultConnection = $this->_resourceConnection->getConnection('default');
            $sql = "SELECT
                REPLACE(
                REPLACE(body,'{\"items\":[{\"profile_id\":',''),'}]}','') AS profile_id
                FROM queue_message AS qm
                JOIN queue_message_status AS qms ON qm.id=qms.message_id
                WHERE qm.topic_name = 'profile.generate.order' AND qms.`status` = 2";
            $result = $defaultConnection->fetchAll($sql);
            foreach ($result as $row){
                $profileCreateOrder =  $this->profileOrderFactory->create();
                $profileCreateOrder->setProfileId($row['profile_id']);
                $profileItemBuilder = $this->profileBuilder->setItems([$profileCreateOrder]);
                $this->publisher->publish('profile.generate.order', $profileItemBuilder);
            }
            $setup->getConnection()->delete($setup->getTable('queue'), ['name = ?' => 'sender_queue_subscription_profile_generate_order']);
            $this->topology->install();
        }

        if (version_compare($context->getVersion(), '1.8.2') < 0) {
            $setup->getConnection()->delete($setup->getTable('queue'), ['name = ?' => 'sender_queue_subscription_profile_edited_order']);
            $this->topology->install();
        }

        if (version_compare($context->getVersion(), '1.8.3') < 0) {
            $this->topology->install();
        }

        if (version_compare($context->getVersion(), '1.8.4') < 0) {
            $this->topology->install();
        }
        if (version_compare($context->getVersion(), '1.8.5') < 0) {
            $table = $setup->getTable('subscription_profile');
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            if ($salesConnection->isTableExists($table)) {

                $salesConnection->modifyColumn(
                    $table,
                    'random_trading',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 25,
                        'default' => null,
                        'nullable' => true,
                        'comment' => 'Random Trading'
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '1.8.6') < 0) {
            $table = $setup->getTable('subscription_profile');
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            if ($salesConnection->isTableExists($table)) {

                $salesConnection->modifyColumn(
                    $table,
                    'type',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 20,
                        'default' => null,
                        'nullable' => true,
                        'comment' => 'Subscription Profile Type: main, temp'
                    ]
                );

                $salesConnection->addIndex(
                    $table,
                    $connection->getIndexName($table, ['type'], true),
                    ['type']
                );

                $salesConnection->addIndex(
                    $table,
                    $salesConnection->getIndexName(
                        $table,
                        [
                            'publish_message',
                            'type'
                        ]
                    ),
                    [
                        'publish_message',
                        'type'
                    ]
                );
            }
        }

        /*add flag reindex*/
        if (version_compare($context->getVersion(), '1.8.7') < 0) {
            $table = $setup->getTable('subscription_profile');
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            if($salesConnection->isTableExists($table) && !$salesConnection->tableColumnExists($table,'reindex_flag')){
                $salesConnection->addColumn(
                    $table, 'reindex_flag',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'default' => 0,
                        'comment' => 'Published to message queue reindex',
                        'nullable' => true,
                        'after' =>'create_order_flag'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.8.8') < 0) {
            $table = $setup->getTable('subscription_profile_version');
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            if ($salesConnection->isTableExists($table)) {

                $salesConnection->addIndex(
                    $table,
                    $connection->getIndexName($table, ['moved_to', 'status'], true),
                    ['moved_to', 'status']);
            }
        }

        //set index key for shipping_address,billing address on table subscription_profile_product_cart
        if (version_compare($context->getVersion(), '1.8.9') < 0) {
            $table = $setup->getTable('subscription_profile_product_cart');
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            if ($salesConnection->isTableExists($table)) {

                $salesConnection->addIndex(
                    $table,
                    $connection->getIndexName($table, ['shipping_address_id'], true),
                    ['shipping_address_id']
                );
                $salesConnection->addIndex(
                    $table,
                    $connection->getIndexName($table, ['billing_address_id'], true),
                    ['billing_address_id']
                );
            }
        }
        //add column data_generate_delivery_date
        if (version_compare($context->getVersion(), '1.9.0') < 0) {
            $table = $setup->getTable('subscription_profile');
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            $salesConnection->addColumn($table,
                'data_generate_delivery_date',
                [
                    'type' => Table::TYPE_TEXT,
                    null,
                    'comment' => 'Save data for change delivery date',
                    'after' => 'random_trading',
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.9.1') < 0) {
            $table = $setup->getTable('quote');
            $checkoutConnection = $this->_resourceConnection->getConnection('checkout');
            $checkoutConnection->addColumn($table, 'n_delivery',
                [
                    'type' => Table::TYPE_INTEGER,
                    'comment' => 'N-th delivery of profile',
                    'after' => 'riki_frequency_id',
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.9.2') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            $profileTable = $salesConnection->getTableName('subscription_profile');

            if ($salesConnection->isTableExists($profileTable)) {

                $specifiedWarehouseIdColumn = 'specified_warehouse_id';

                if (!$salesConnection->tableColumnExists($profileTable, $specifiedWarehouseIdColumn)) {
                    $salesConnection->addColumn($profileTable, $specifiedWarehouseIdColumn, [
                            'type' => Table::TYPE_INTEGER,
                            'comment' => 'Specified Warehouse',
                            'nullable' => true
                        ]
                    );
                }
            }
        }

        if (version_compare($context->getVersion(), '1.9.3') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');

            $orderTable = $salesConnection->getTableName('sales_order');

            $salesConnection->addColumn(
                $orderTable,
                'is_incomplete_generate_profile_order',
                [
                    'type' => Table::TYPE_BOOLEAN,
                    'default'   =>  0,
                    'comment' => 'Flag to check generate profile order was completed'
                ]
            );

            $salesConnection->addIndex(
                $orderTable,
                $salesConnection->getIndexName($orderTable, ['is_incomplete_generate_profile_order']),
                ['is_incomplete_generate_profile_order']
            );
        }

        if (version_compare($context->getVersion(), '1.10.0') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');

            $salesConnection->modifyColumn(
                $salesConnection->getTableName('subscription_profile'),
                'created_date',
                [
                    'type' => Table::TYPE_TIMESTAMP,
                    'default' => Table::TIMESTAMP_INIT,
                    'comment' => 'Created Time'
                ],
                false
            );

            $salesConnection->modifyColumn(
                $salesConnection->getTableName('subscription_profile_product_cart'),
                'created_at',
                [
                    'type' => Table::TYPE_TIMESTAMP,
                    'default' => Table::TIMESTAMP_INIT,
                    'comment' => 'Created Time'
                ],
                false
            );

            $salesConnection->modifyColumn(
                $salesConnection->getTableName('subscription_profile_product_cart'),
                'updated_at',
                [
                    'type' => Table::TYPE_TIMESTAMP,
                    'default' => Table::TIMESTAMP_INIT_UPDATE,
                    'comment' => 'Updated Time'
                ],
                false
            );
        }

        if (version_compare($context->getVersion(), '1.10.1') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');

            // Add new column next_delivery_date_calculation_option to table subscription_course
            $subCourseTable = $salesConnection->getTableName('subscription_course');
            if (!$salesConnection->tableColumnExists($subCourseTable, 'next_delivery_date_calculation_option')) {
                $salesConnection->addColumn(
                    $subCourseTable,
                    'next_delivery_date_calculation_option',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 20,
                        'default' => 'day_of_month',
                        'comment' => '[day_of_week, day_of_month]',
                    ]
                );
            }

            // Add new column day_of_week to table subscription_profile
            $subProfileTable = $salesConnection->getTableName('subscription_profile');
            if (!$salesConnection->tableColumnExists($subProfileTable, 'day_of_week')) {
                $salesConnection->addColumn(
                    $subProfileTable,
                    'day_of_week',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 9,
                        'comment' => '[Mon, Tue, Wed, Thu, Fri, Sat, Sun]',
                    ]
                );
            }

            // Add new column week_of_month to table subscription_profile
            if (!$salesConnection->tableColumnExists($subProfileTable, 'nth_weekday_of_month')) {
                $salesConnection->addColumn(
                    $subProfileTable,
                    'nth_weekday_of_month',
                    [
                        'type' => Table::TYPE_SMALLINT,
                        'length' => 5,
                        'comment' => '[1 => "1st", 2 => "2nd", 3 => "3rd", 4 => "4th", 5 => "Last"]',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.10.2') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            if (!$salesConnection->tableColumnExists(
                'subscription_profile_simulate_cache',
                'is_invalid'
                )
            ) {
                $salesConnection->addColumn(
                    'subscription_profile_simulate_cache',
                    'is_invalid',
                    [
                        'type' => Table::TYPE_BOOLEAN,
                        'default'   =>  0,
                        'comment' => 'This row is remove when reindex'
                    ]
                );
            }

        }
        if (version_compare($context->getVersion(), '1.10.3', '<')) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            // create table subscription campaign
            $campaignTableName = 'subscription_multiple_category_campaign';
            if (!$salesConnection->isTableExists($campaignTableName)) {
                $campaignTable = $salesConnection->newTable($salesConnection->getTableName($campaignTableName))
                    ->addColumn(
                        'campaign_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        10,
                        [
                            'identity' => true,
                            'unsigned' => true,
                            'nullable' => false,
                            'primary' => true,
                            'auto_increment' => true
                        ],
                        'Multiple category campaign ID'
                    )->addColumn(
                        'name',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        255,
                        [ 'nullable' => false],
                        'Multiple category campaign name'
                    )->addColumn(
                        'type',
                        \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                        5,
                        ['nullable' => false, 'unsigned' => true, 'default' =>0],
                        'Multiple category campaign type'
                    )->addColumn(
                        'created_at',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        null,
                        [ 'nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                        'Created at'
                    )->addColumn(
                        'updated_at',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        null,
                        [ 'nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                        'Updated at'
                    );
                $salesConnection->createTable($campaignTable);
            }
            // create table subscription seasonal campaign category
            $categoryTableName = 'subscription_multiple_category_campaign_category';
            if (!$salesConnection->isTableExists($categoryTableName)) {
                $categoryTable = $salesConnection->newTable(
                    $salesConnection->getTableName($categoryTableName)
                )->addColumn(
                    'campaign_id',
                    Table::TYPE_INTEGER,
                    10,
                    ['unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Multiple category Campaign ID'
                )
                    ->addColumn(
                        'category_id',
                        Table::TYPE_INTEGER,
                        10,
                        ['unsigned' => true, 'nullable' => false, 'primary' => true],
                        'Category ID'
                    )
                    ->addForeignKey(
                        $salesConnection->getForeignKeyName(
                            $categoryTableName,
                            'campaign_id',
                            $campaignTableName,
                            'campaign_id'
                        ),
                        'campaign_id',
                        $salesConnection->getTableName($campaignTableName),
                        'campaign_id',
                        Table::ACTION_CASCADE
                    )
                    ->setComment(
                        'Multiple category campaign category'
                    );
                ;
                $salesConnection->createTable($categoryTable);
            }
            //create table subscription_multiple_category_campaign_excluded_course
            $subscriptionCampaignCourseTableName = 'subscription_multiple_category_campaign_excluded_course';
            $subscriptionCourseTableName = 'subscription_course';
            if (!$salesConnection->isTableExists($subscriptionCampaignCourseTableName)) {
                $campaignCourseTable = $salesConnection->newTable(
                    $salesConnection->getTableName($subscriptionCampaignCourseTableName)
                )->addColumn(
                    'campaign_id',
                    Table::TYPE_INTEGER,
                    10,
                    ['unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Multiple category Campaign ID'
                )
                    ->addColumn(
                        'course_id',
                        Table::TYPE_INTEGER,
                        10,
                        ['unsigned' => true, 'nullable' => false, 'primary' => true],
                        'Course ID'
                    )
                    ->addForeignKey(
                        $salesConnection->getForeignKeyName(
                            $subscriptionCampaignCourseTableName,
                            'campaign_id',
                            $campaignTableName,
                            'campaign_id'
                        ),
                        'campaign_id',
                        $salesConnection->getTableName($campaignTableName),
                        'campaign_id',
                        Table::ACTION_CASCADE
                    )
                    ->addForeignKey(
                        $salesConnection->getForeignKeyName(
                            $subscriptionCampaignCourseTableName,
                            'course_id',
                            $subscriptionCourseTableName,
                            'course_id'
                        ),
                        'course_id',
                        $salesConnection->getTableName($subscriptionCourseTableName),
                        'course_id',
                        Table::ACTION_CASCADE
                    )
                    ->setComment(
                        'Subscription Multiple category Campaign excluded course'
                    );
                ;
                $salesConnection->createTable($campaignCourseTable);
            }
        }
        if (version_compare($context->getVersion(), '1.10.5') < 0) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            $salesConnection->modifyColumn(
                'subscription_profile',
                'disengagement_reason',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 255,
                    'comment' => 'Disengaged reason'
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.10.6', '<')) {
            $salesConnection = $this->_resourceConnection->getConnection('sales');
            // create table subscription landing page
            $landingPageTableName = 'subscription_landing_page';
            if (!$salesConnection->isTableExists($landingPageTableName)) {
                $landingPageTable = $salesConnection->newTable($salesConnection->getTableName($landingPageTableName))
                    ->addColumn(
                        'landing_page_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        10,
                        [
                            'identity' => true,
                            'unsigned' => true,
                            'nullable' => false,
                            'primary' => true,
                            'auto_increment' => true
                        ],
                        'Landing page ID'
                    )->addColumn(
                        'name',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        255,
                        [ 'nullable' => false],
                        'Landing page name'
                    )->addColumn(
                        'created_at',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        null,
                        [ 'nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                        'Created at'
                    )->addColumn(
                        'updated_at',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        null,
                        [ 'nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                        'Updated at'
                    );
                $salesConnection->createTable($landingPageTable);
            }
            // create table subscription landing page category
            $categoryTableName = 'subscription_landing_category';
            if (!$salesConnection->isTableExists($categoryTableName)) {
                $categoryTable = $salesConnection->newTable(
                    $salesConnection->getTableName($categoryTableName)
                )->addColumn(
                    'landing_page_id',
                    Table::TYPE_INTEGER,
                    10,
                    ['unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Landing page ID'
                )
                    ->addColumn(
                        'category_id',
                        Table::TYPE_INTEGER,
                        10,
                        ['unsigned' => true, 'nullable' => false, 'primary' => true],
                        'Category ID'
                    )
                    ->addForeignKey(
                        $salesConnection->getForeignKeyName(
                            $categoryTableName,
                            'landing_page_id',
                            $landingPageTableName,
                            'landing_page_id'
                        ),
                        'landing_page_id',
                        $salesConnection->getTableName($landingPageTableName),
                        'landing_page_id',
                        Table::ACTION_CASCADE
                    )
                    ->setComment(
                        'Landing page category'
                    );
                ;
                $salesConnection->createTable($categoryTable);
            }
            //create table subscription_landing_page_excluded_course
            $excludedCourseTableName = 'subscription_landing_exclude_course';
            $subscriptionCourseTableName = 'subscription_course';
            if (!$salesConnection->isTableExists($excludedCourseTableName)) {
                $excludedCourseTable = $salesConnection->newTable(
                    $salesConnection->getTableName($excludedCourseTableName)
                )
                    ->addColumn(
                    'landing_page_id',
                    Table::TYPE_INTEGER,
                    10,
                    ['unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Landing page ID'
                )
                    ->addColumn(
                        'course_id',
                        Table::TYPE_INTEGER,
                        10,
                        ['unsigned' => true, 'nullable' => false, 'primary' => true],
                        'Course ID'
                    )
                    ->addForeignKey(
                        $salesConnection->getForeignKeyName(
                            $excludedCourseTableName,
                            'landing_page_id',
                            $landingPageTableName,
                            'landing_page_id'
                        ),
                        'landing_page_id',
                        $salesConnection->getTableName($landingPageTableName),
                        'landing_page_id',
                        Table::ACTION_CASCADE
                    )
                    ->addForeignKey(
                        $salesConnection->getForeignKeyName(
                            $excludedCourseTableName,
                            'course_id',
                            $subscriptionCourseTableName,
                            'course_id'
                        ),
                        'course_id',
                        $salesConnection->getTableName($subscriptionCourseTableName),
                        'course_id',
                        Table::ACTION_CASCADE
                    )
                    ->setComment(
                        'Subscription Landing page excluded course'
                    );
                ;
                $salesConnection->createTable($excludedCourseTable);
            }
        }
        $setup->endSetup();
    }
}
