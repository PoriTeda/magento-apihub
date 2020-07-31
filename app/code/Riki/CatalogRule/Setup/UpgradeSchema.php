<?php
// @codingStandardsIgnoreFile
namespace Riki\CatalogRule\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $courseModel;

    /**
     * UpgradeSchema constructor.
     * @param \Riki\SubscriptionCourse\Model\Course $courseModel
     */
    public function __construct(
        \Riki\SubscriptionCourse\Model\Course $courseModel
    ) {
        $this->courseModel = $courseModel;
    }

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        $connection = $setup->getConnection();
        if (version_compare($context->getVersion(), '0.1.1') < 0) {
            $table = $setup->getTable('catalogrule');
            if ($connection->isTableExists($table) == true) {

                if (!$connection->tableColumnExists($table, 'wbs')) {
                    $connection->addColumn(
                        $table,
                        'wbs',
                        [
                            'type' => Table::TYPE_TEXT,
                            'length' => 255,
                            'comment' => 'WBS Code'
                        ]
                    );
                }

                if (!$connection->tableColumnExists($table, 'account_code')) {
                    $connection->addColumn(
                        $table,
                        'account_code',
                        [
                            'type' => Table::TYPE_TEXT,
                            'length' => 255,
                            'comment' => 'Account Code'
                        ]
                    );
                }

                if (!$connection->tableColumnExists($table, 'sap_condition_type')) {
                    $connection->addColumn(
                        $table,
                        'sap_condition_type',
                        [
                            'type' => Table::TYPE_TEXT,
                            'length' => 255,
                            'comment' => 'SAP Condition Type'
                        ]
                    );
                }
            }
        }

        if (version_compare($context->getVersion(), '0.1.2') < 0) {
            $table = $setup->getTable('catalogrule');
            if ($connection->isTableExists($table) == true) {
                if (!$connection->tableColumnExists($table, 'subscription')) {
                    $connection->addColumn($table, 'subscription', [
                        'type' => Table::TYPE_SMALLINT,
                        'unsigned' => true,
                        'nullable' => true,
                        'comment' => 'Apply for subscription. 1 = Yes, 0 = No'
                    ]);
                }
                if (!$connection->tableColumnExists($table, 'subscription_delivery')) {
                    $connection->addColumn($table, 'subscription_delivery', [
                        'type' => Table::TYPE_SMALLINT,
                        'unsigned' => true,
                        'default' => 3,
                        'nullable' => true,
                        'comment' => 'Subscription delivery type, 1 = Every N delivery, 2 = On N delivery, 3 = All deliveries'
                    ]);
                }
                if (!$connection->tableColumnExists($table, 'delivery_n')) {
                    $connection->addColumn($table, 'delivery_n', [
                        'type' => Table::TYPE_SMALLINT,
                        'unsigned' => true,
                        'nullable' => true,
                        'comment' => 'Delivery number'
                    ]);
                }
            }
            if (!$connection->isTableExists($setup->getTable('catalogrule_subscription_course'))) {
                $table = $connection->newTable($setup->getTable('catalogrule_subscription_course'))
                    ->addColumn(
                        'rule_id',
                        Table::TYPE_INTEGER,
                        null,
                        ['unsigned' => true, 'primary' => true, 'nullable' => false],
                        'Rule ID'
                    )
                    ->addColumn(
                        'course_id',
                        Table::TYPE_INTEGER,
                        null,
                        ['unsigned' => true, 'primary' => true, 'nullable' => false],
                        'Course ID'
                    )
                    ->addForeignKey(
                        $setup->getFkName('catalogrule_subscription_course', 'rule_id', 'catalogrule', 'rule_id'),
                        'rule_id',
                        $setup->getTable('catalogrule'),
                        'rule_id',
                        Table::ACTION_CASCADE
                    )
                    ->addForeignKey(
                        $setup->getFkName('catalogrule_subscription_course', 'course_id', 'subscription_course', 'course_id'),
                        'course_id',
                        $setup->getTable('subscription_course'),
                        'course_id',
                        Table::ACTION_CASCADE
                    )
                    ->setComment(
                        'Relation table between catalogrule and subscription_course'
                    );

                $connection->createTable($table);
            }
            if (!$connection->isTableExists($setup->getTable('catalogrule_subscription_frequency'))) {
                $tbl = $connection->newTable($setup->getTable('catalogrule_subscription_frequency'))
                    ->addColumn(
                        'rule_id',
                        Table::TYPE_INTEGER,
                        null,
                        ['unsigned' => true, 'primary' => true, 'nullable' => false],
                        'Rule ID'
                    )
                    ->addColumn(
                        'frequency_id',
                        Table::TYPE_INTEGER,
                        null,
                        ['unsigned' => true, 'primary' => true, 'nullable' => false],
                        'Frequency ID'
                    )
                    ->addForeignKey(
                        $setup->getFkName('catalogrule_subscription_frequency', 'rule_id', 'catalogrule', 'rule_id'),
                        'rule_id',
                        $setup->getTable('catalogrule'),
                        'rule_id',
                        Table::ACTION_CASCADE
                    )
                    ->addForeignKey(
                        $setup->getFkName('catalogrule_subscription_frequency', 'frequency_id', 'subscription_frequency', 'frequency_id'),
                        'frequency_id',
                        $setup->getTable('subscription_frequency'),
                        'frequency_id',
                        Table::ACTION_CASCADE
                    )
                    ->setComment(
                        'Relation table between catalogrule and subscription_frequency'
                    );

                $connection->createTable($tbl);
            }
            if ($connection->isTableExists($setup->getTable('catalogrule_product'))) {
                $table = $setup->getTable('catalogrule_product');
                if (!$connection->tableColumnExists($table, 'course_id')) {
                    $connection->addColumn($table, 'course_id', [
                        'type' => Table::TYPE_INTEGER,
                        'unsigned' => true,
                        'nullable' => true,
                        'default' => 0,
                        'comment' => 'Course Id'
                    ]);
                }
                if (!$connection->tableColumnExists($table, 'frequency_id')) {
                    $connection->addColumn($table, 'frequency_id', [
                        'type' => Table::TYPE_INTEGER,
                        'unsigned' => true,
                        'nullable' => true,
                        'default' => 0,
                        'comment' => 'Course Id'
                    ]);
                }
            }
            if ($connection->isTableExists($setup->getTable('catalogrule_product_price'))) {
                $table = $setup->getTable('catalogrule_product_price');
                if (!$connection->tableColumnExists($table, 'course_id')) {
                    $connection->addColumn($table, 'course_id', [
                        'type' => Table::TYPE_INTEGER,
                        'unsigned' => true,
                        'nullable' => true,
                        'default' => 0,
                        'comment' => 'Course Id'
                    ]);
                }
                if (!$connection->tableColumnExists($table, 'frequency_id')) {
                    $connection->addColumn($table, 'frequency_id', [
                        'type' => Table::TYPE_INTEGER,
                        'unsigned' => true,
                        'nullable' => true,
                        'default' => 0,
                        'comment' => 'Frequency Id'
                    ]);
                }
            }

            $catalogRuleTable = $connection->getTableName('catalogrule');

            $connection->addIndex(
                $catalogRuleTable,
                $connection->getIndexName(
                    $catalogRuleTable,
                    [
                        'subscription_delivery',
                        'delivery_n'
                    ]
                ),
                [
                    'subscription_delivery',
                    'delivery_n'
                ]
            );
        }

        if (version_compare($context->getVersion(), '0.1.3') < 0) {
            if ($connection->isTableExists($setup->getTable('catalogrule_product'))) {
                $table = $setup->getTable('catalogrule_product');
                $connection->dropIndex(
                    $table,
                    $connection->getIndexName($table, [
                        'rule_id', 'from_time', 'to_time',
                        'website_id', 'customer_group_id',
                        'product_id', 'sort_order'
                    ], true)
                );
                $connection->addIndex(
                    $table,
                    $connection->getIndexName($table, [
                        'rule_id', 'from_time', 'to_time', 'website_id',
                        'customer_group_id', 'product_id', 'sort_order',
                        'course_id', 'frequency_id'
                    ], true),
                    [
                        'rule_id', 'from_time', 'to_time', 'website_id',
                        'customer_group_id', 'product_id', 'sort_order',
                        'course_id', 'frequency_id'
                    ], \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE);
            }
            if ($connection->isTableExists($setup->getTable('catalogrule_product_price'))) {
                $table = $setup->getTable('catalogrule_product_price');
                $connection->dropIndex(
                    $table,
                    $connection->getIndexName($table, [
                        'rule_date', 'website_id', 'customer_group_id', 'product_id'
                    ], \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE)
                );
                $connection->addIndex(
                    $table,
                    $connection->getIndexName($table, [
                        'rule_date', 'website_id', 'customer_group_id', 'product_id',
                        'course_id', 'frequency_id'
                    ], true),
                    [
                        'rule_date', 'website_id', 'customer_group_id', 'product_id',
                        'course_id', 'frequency_id'
                    ], \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE);
            }
        }

        if (version_compare($context->getVersion(), '0.1.4') < 0) {
            if ($connection->isTableExists($setup->getTable('catalogrule_product_price'))) {
                $table = $setup->getTable('catalogrule_product_price');
                $connection->addColumn($table, 'rule_id', [
                    'type' => Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => true,
                    'comment' => 'Catalogrule Rule Id'
                ]);
            }
        }

        if (version_compare($context->getVersion(), '0.1.5') < 0) {
            if ($connection->isTableExists($setup->getTable('catalogrule'))) {
                $table = $setup->getTable('catalogrule');
                $connection->addColumn($table, 'is_machine', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'length' => 6,
                    'default' => 0,
                    'comment' => 'Is riki machine rule?'
                ]);
            }
        }

        if (version_compare($context->getVersion(), '0.1.6') < 0) {
            if ($connection->isTableExists($setup->getTable('catalogrule'))) {
                $table = $setup->getTable('catalogrule');
                $connection->addColumn($table, 'machine_id', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => true,
                    'comment' => 'Machine id'
                ]);
            }
        }

        if (version_compare($context->getVersion(), '0.1.7') < 0) {
            $table = $setup->getTable('catalogrule');
            $connection->addColumn(
                $table,
                'promo_updated_at',
                [
                    'type' => Table::TYPE_TIMESTAMP,
                    'nullable' => false,
                    'default' => Table::TIMESTAMP_INIT_UPDATE,
                    'comment' => 'Updated At'
                ]
            );
        }

        if (version_compare($context->getVersion(), '0.1.8') < 0) {

            if ($connection->isTableExists($setup->getTable('catalogrule_product_price'))) {
                $table = $setup->getTable('catalogrule_product_price');
                $connection->dropIndex(
                    $table,
                    $connection->getIndexName($table, [
                        'rule_date', 'website_id', 'customer_group_id', 'product_id','course_id', 'frequency_id'
                    ], \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX)
                );
                $connection->addIndex(
                    $table,
                    $connection->getIndexName($table, [
                        'rule_date', 'website_id', 'customer_group_id', 'product_id','course_id', 'frequency_id', 'rule_id'
                    ], true),
                    [
                        'rule_date', 'website_id', 'customer_group_id', 'product_id','course_id', 'frequency_id', 'rule_id'
                    ], \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE);
            }
        }

        if (version_compare($context->getVersion(), '0.1.9') < 0) {

            if ($connection->isTableExists($setup->getTable('catalogrule_product_price'))) {
                $table = $setup->getTable('catalogrule_product_price');

                if (!$connection->tableColumnExists($table, 'base_price')) {
                    $connection->addColumn(
                        $table,
                        'base_price',
                        ['type' => Table::TYPE_DECIMAL,'length' => '12,4', 'default' => '0.0000','comment' => 'Base price']
                    );
                }
            }
        }

        if (version_compare($context->getVersion(), '0.2.0') < 0) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('riki_wbs_conversion'))
                ->addColumn(
                    'entity_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                    'Entity Id'
                )
                ->addColumn(
                    'old_wbs',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Old Wbs'
                )
                ->addColumn(
                    'new_wbs',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'New Wbs'
                )
                ->addColumn(
                    'from_datetime',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                    null,
                    ['nullable' => false],
                    'Conversion Start Datetime'
                )
                ->addColumn(
                    'to_datetime',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                    null,
                    ['nullable' => false],
                    'Conversion End Datetime'
                )
                ->addColumn(
                    'is_active',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => false, 'default' => 1],
                    'Is Active'
                )
                ->addColumn(
                    'created_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                    'Created At'
                )
                ->addColumn(
                    'updated_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
                    'Updated At'
                )
                ->addIndex(
                    $installer->getIdxName('riki_wbs_management', ['old_wbs']),
                    ['old_wbs'], ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
                )->addIndex(
                    $installer->getIdxName('riki_wbs_management', ['is_active']),
                    ['is_active']
                );

            $installer->getConnection()->createTable($table);
        }

        if (version_compare($context->getVersion(), '0.2.1') < 0) {
            if ($connection->isTableExists($setup->getTable('catalogrule_subscription_course'))) {
                $table = $setup->getTable('catalogrule_subscription_course');

                if (!$connection->tableColumnExists($table, 'frequency_id')) {
                    // Add new column frequency_id
                    $connection->addColumn($table, 'frequency_id', [
                        'type' => Table::TYPE_INTEGER,
                        'default' => 0,
                        'unsigned' => true,
                        'nullable' => false,
                        'comment' => 'Frequency ID'
                    ]);

                    // Add new primary key
                    $connection->addIndex(
                        $table,
                        'primary',
                        ['rule_id', 'course_id', 'frequency_id'],
                        \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_PRIMARY
                    );

                    // Get list data course frequency
                    $listCourseFrequency = $this->courseModel->getCourseFrequencyList();

                    // Get all data of catalogrule_subscription_course
                    $queryCourse = $connection->select()
                        ->from(
                            ['csc' => 'catalogrule_subscription_course']
                        )->order('rule_id', 'asc');
                    $ruleCourses = $connection->fetchAll($queryCourse);

                    // Merge data frequency_id from table catalogrule_subscription_frequency to table catalogrule_subscription_course
                    $count = 0;
                    $ruleIdUpdated = [];
                    foreach ($ruleCourses as $ruleCourse) {
                        $ruleId = $ruleCourse['rule_id'];
                        $courseId = $ruleCourse['course_id'];

                        // Get all data of catalogrule_subscription_frequency by rule_id
                        $queryFrequency = $connection->select()
                            ->from(
                                ['csc' => 'catalogrule_subscription_frequency']
                            )->where('csc.rule_id = ?', $ruleId);
                        $ruleFrequencies = $connection->fetchAll($queryFrequency);

                        foreach ($ruleFrequencies as $ruleFrequency) {
                            $frequencyId = $ruleFrequency['frequency_id'];
                            if (isset($listCourseFrequency[$courseId])
                                && in_array($frequencyId, $listCourseFrequency[$courseId])
                            ) {
                                $data[] = [
                                    'rule_id' => $ruleId,
                                    'course_id' => $courseId,
                                    'frequency_id' => $frequencyId,
                                ];

                                $count++;

                                if ($count % 1000 == 0) {
                                    $connection->insertOnDuplicate(
                                        'catalogrule_subscription_course',
                                        $data,
                                        ['rule_id']
                                    );
                                    $data = [];
                                }

                                if (!in_array($ruleId, $ruleIdUpdated)) {
                                    $ruleIdUpdated[] = $ruleId;
                                }
                            }
                        }
                    }

                    if (!empty($data)) {
                        $connection->insertOnDuplicate(
                            'catalogrule_subscription_course',
                            $data,
                            ['rule_id']
                        );
                    }

                    // Delete all of row rule_id updated that they have frequency_id = 0
                    $connection->delete(
                        'catalogrule_subscription_course',
                        $connection->quoteInto(
                            'frequency_id = 0 AND rule_id IN (?)',
                            $ruleIdUpdated
                        )
                    );
                }
            }
        }

        $installer->endSetup();
    }
}