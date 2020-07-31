<?php


namespace Riki\Wamb\Setup;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use \Magento\Framework\DB\Ddl\Table;

class InstallSchema extends \Riki\Framework\Setup\Version\Schema implements InstallSchemaInterface
{
    public function version100()
    {
        $def = [
            [
                'rule_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'nullable' => false,
                    'primary' => true,
                    'unsigned' => true,
                    'auto_increment' => true
                ],
                'Rule ID'
            ],
            [
                'name',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => false
                ],
                'Rule name'
            ],
            [
                'is_active',
                Table::TYPE_BOOLEAN,
                null,
                [
                    'default' => 0,
                    'nullable' => false
                ],
                'Is Active'
            ],
            [
                'min_purchase_qty',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false
                ],
                'Minimum purchase qty'
            ],
            [
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                [
                    'default' => Table::TIMESTAMP_INIT_UPDATE
                ],
                'Created at'
            ],
            [
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                [
                    'default' => Table::TIMESTAMP_INIT_UPDATE
                ],
                'Updated at'
            ]
        ];
        $this->createTable('riki_wamb_rule', $def);

        $def = [
            [
                'rule_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                    'primary' => true,
                    'unsigned' => true,
                ],
                'Rule ID'
            ],
            [
                'category_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                    'primary' => true,
                    'unsigned' => true,
                ],
                'Category ID (Ref to catalog_category_entity(entity_id))'
            ],
        ];
        $this->createTable('riki_wamb_rule_category', $def);
        $this->addForeignKey('riki_wamb_rule_category', 'category_id', 'catalog_category_entity', 'entity_id');
        $this->addForeignKey('riki_wamb_rule_category', 'rule_id', 'riki_wamb_rule', 'rule_id');

        $def = [
            [
                'rule_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                    'primary' => true,
                    'unsigned' => true,
                ],
                'Rule ID'
            ],
            [
                'course_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                    'primary' => true,
                    'unsigned' => true,
                ],
                'Course ID'
            ],
        ];
        $this->createTable('riki_wamb_rule_course', $def);
        $this->addForeignKey('riki_wamb_rule_course', 'rule_id', 'riki_wamb_rule', 'rule_id');

        $def = [
            [
                'customer_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                    'primary' => true,
                    'unsigned' => true,
                ],
                'Customer ID'
            ],
            [
                'consumer_db_id',
                Table::TYPE_TEXT,
                64,
                [
                    'nullable' => false,
                ],
                'ConsumerID on KSS'
            ],
            [
                'status',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                    'default' => 0
                ],
                'Status. 1 = waiting. 2 = success. 3 = error'
            ]
        ];
        $this->createTable('riki_wamb_customer', $def);
        $this->addIndex('riki_wamb_customer', ['consumer_db_id']);
        $this->addForeignKey('riki_wamb_customer', 'customer_id', 'customer_entity', 'entity_id');

        $def = [
            [
                'history_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'nullable' => false,
                    'primary' => true,
                    'unsigned' => true,
                ],
                'History ID',
            ],
            [
                'customer_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                    'unsigned' => true,
                ],
                'Customer ID'
            ],
            [
                'consumer_db_id',
                Table::TYPE_TEXT,
                64,
                [
                    'nullable' => false,
                ],
                'ConsumerID on KSS'
            ],
            [
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                [
                    'default' => Table::TIMESTAMP_INIT_UPDATE
                ],
                'Created at'
            ],
            [
                'event',
                Table::TYPE_TEXT,
                64,
                [
                    'nullable' => false,
                ],
                'Event '
            ],
            [
                'message',
                Table::TYPE_TEXT,
                null,
                [],
                'Message'
            ],
            [
                'detail',
                Table::TYPE_TEXT,
                null,
                [],
                'Detail on JSON format'
            ],
        ];
        $this->createTable('riki_wamb_customer_history', $def);
        $this->addIndex('riki_wamb_customer_history', ['event']);
        $this->addIndex('riki_wamb_customer_history', ['consumer_db_id']);
        $this->addForeignKey('riki_wamb_customer_history', 'customer_id', 'riki_wamb_customer', 'customer_id');
    }
}
