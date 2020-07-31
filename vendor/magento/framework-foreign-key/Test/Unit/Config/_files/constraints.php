<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
return [
    [
        'name' => 'constraint_1',
        'active' => true,
        'delete_strategy' => 'CASCADE',
        'table_name' => 'entity_name_1',
        'connection' => 'resource_1',
        'reference_table_name' => 'entity_name_3',
        'field_name' => 'field_1',
        'reference_field_name' => 'field_2',
    ],
    [
        'name' => 'constraint_3',
        'active' => true,
        'delete_strategy' => 'SET NULL',
        'table_name' => 'entity_name_2',
        'connection' => 'resource_2',
        'reference_table_name' => 'entity_name_1',
        'field_name' => 'field_4',
        'reference_field_name' => 'field_5',
    ]
];
