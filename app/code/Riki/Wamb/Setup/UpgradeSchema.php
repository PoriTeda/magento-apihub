<?php

namespace Riki\Wamb\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema extends \Riki\Framework\Setup\Version\Schema implements UpgradeSchemaInterface
{
    public function version101()
    {
        $this->addColumn('riki_wamb_rule', 'data_category', [
            'type' => Table::TYPE_TEXT,
            'comment' => 'Category IDs'
        ]);
        $this->addColumn('riki_wamb_rule', 'data_course', [
            'type' => Table::TYPE_TEXT,
            'comment' => 'Course IDs'
        ]);
    }

    public function version102()
    {
        $this->addIndex('riki_wamb_rule', ['name'], null, AdapterInterface::INDEX_TYPE_UNIQUE);
        $this->addColumn('riki_wamb_customer', 'order_id', [
            'type' => Table::TYPE_INTEGER,
            'comment' => 'Order id (Red sales_order(order_id))'
        ]);
        $this->addColumn('riki_wamb_customer', 'rule_id', [
            'type' => Table::TYPE_INTEGER,
            'comment' => 'Rule id (Red riki_wamb_rule(rule_id))'
        ]);
    }

    public function version103()
    {
        $connect = $this->getConnection();
        $sqlQuery = $connect->select()->from('authorization_role');
        $sqlQuery->where("user_id= 0 AND user_type=2 and role_type ='G' AND role_id <>1 ");
        $groups = $connect->fetchAll($sqlQuery);
        if (is_array($groups) && count($groups) > 0) {

            $dataRolesDeny = [
                'Riki_Wamb::Rule' => 'deny',
                'Riki_Wamb::Rule_delete' => 'deny',
                'Riki_Wamb::Rule_save' => 'deny',
                'Riki_Wamb::Rule_view' => 'deny',
            ];

            $dataRoles = [
                'DS-OWNER' => [
                    'Riki_Wamb::Rule' => 'allow',
                    'Riki_Wamb::Rule_delete' => 'allow',
                    'Riki_Wamb::Rule_save' => 'allow',
                    'Riki_Wamb::Rule_view' => 'allow',
                ],
                'TS' => [
                    'Riki_Wamb::Rule' => 'allow',
                    'Riki_Wamb::Rule_delete' => 'allow',
                    'Riki_Wamb::Rule_save' => 'allow',
                    'Riki_Wamb::Rule_view' => 'allow',
                ],
                'DS-VD' => [
                    'Riki_Wamb::Rule' => 'allow',
                    'Riki_Wamb::Rule_delete' => 'deny',
                    'Riki_Wamb::Rule_save' => 'allow',
                    'Riki_Wamb::Rule_view' => 'allow',
                ]
            ];

            foreach ($groups as $groupItems) {
                $roleName = trim($groupItems['role_name']);
                $roleId = trim($groupItems['role_id']);
                if (array_key_exists($roleName, $dataRoles)) {
                    $arrData = [];
                    foreach ($dataRoles[$roleName] as $key => $value) {
                        $arrData[] = [
                            'role_id' => $roleId,
                            'resource_id' => trim($key),
                            'permission' => trim($value)
                        ];
                    }

                    //insert resource allow
                    $this->getConnection()->insertMultiple('authorization_rule', $arrData);
                } else {
                    $arrData = [];
                    foreach ($dataRolesDeny as $key => $value) {
                        $arrData[] = [
                            'role_id' => $roleId,
                            'resource_id' => trim($key),
                            'permission' => trim($value)
                        ];
                    }
                    //insert resource deny
                    $this->getConnection()->insertMultiple('authorization_rule', $arrData);
                }
            }
        }
    }

    public function version104()
    {
        $this->dropForeignKey('riki_wamb_customer_history', ['riki_wamb_customer_history', 'customer_id', 'riki_wamb_customer', 'customer_id']);
        $this->addForeignKey('riki_wamb_customer_history', 'customer_id', 'customer_entity', 'entity_id');
        $this->getConnection('riki_wamb_customer_history')->renameTable('riki_wamb_customer_history', 'riki_wamb_history');

        $this->dropTable('riki_wamb_customer');
        $def = [
            [
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'nullable' => false,
                    'primary' => true,
                    'unsigned' => true,
                ],
                'Customer ID'
            ],
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
                'order_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                    'primary' => true,
                    'unsigned' => true,
                ],
                'Order ID (Ref sales_order(order_id))'
            ],
            [
                'rule_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                    'primary' => true,
                    'unsigned' => true,
                ],
                'Rule ID (Ref riki_wamb_rule (rule_id))'
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
        $this->createTable('riki_wamb_entity', $def);
        $this->addIndex('riki_wamb_entity', ['consumer_db_id']);
        $this->addIndex('riki_wamb_entity', ['order_id']);
        $this->addForeignKey('riki_wamb_entity', 'rule_id', 'riki_wamb_rule', 'rule_id');
        $this->addForeignKey('riki_wamb_entity', 'customer_id', 'customer_entity', 'entity_id');
    }

    public function version105()
    {
        $this->changeColumn('riki_wamb_entity', 'entity_id', 'register_id', [
            'type' => Table::TYPE_INTEGER,
            'length' => null,
            'identity' => true,
            'nullable' => false,
            'primary' => true,
            'unsigned' => true,
            'comment' => 'ID'
        ]);
        $this->getConnection('riki_wamb_entity')
            ->renameTable('riki_wamb_entity', 'riki_wamb_register');
    }
}
