<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\ForeignKey\Test\Unit\Config;

use Magento\Framework\ForeignKey\Config\Processor;

class ProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Processor
     */
    private $model;

    protected function setUp()
    {
        $this->model = new \Magento\Framework\ForeignKey\Config\Processor();
    }

    /**
     * @param array $expectedResult
     * @param array $xmlConstraints
     * @param array $databaseConstraints
     * @param array $databaseTables
     * @dataProvider processDataProvider
     */
    public function testProcess(
        array $expectedResult,
        array $xmlConstraints,
        array $databaseConstraints,
        array $databaseTables
    ) {
        $this->assertEquals(
            $expectedResult,
            $this->model->process($xmlConstraints, $databaseConstraints, $databaseTables)
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\InputException
     * @expectedExceptionMessage Constraint "test_name" references table that does not exist.
     */
    public function testProcessTablePrefixes()
    {
        $xmlConstraints = [
            'test_name' => [
                'name' => 'test_name',
                'table_name' => 'test',
                'reference_table_name' => ''
            ]
        ];
        $databaseConstraints = [];
        $databaseTables = [];
        $this->model->process($xmlConstraints, $databaseConstraints, $databaseTables);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processDataProvider()
    {
        return [
            [
                'expected_result' => [
                    'constraints_by_reference_table' => [
                        'prefix.reference_table_name_1' => [
                            [
                                'name' => 'constraint_name_1',
                                'active' => true,
                                'delete_strategy' => 'CASCADE',
                                'table_name' => 'prefix.table_name_1',
                                'connection' => 'connection_1',
                                'reference_table_name' => 'prefix.reference_table_name_1',
                                'field_name' => 'field_name_1',
                                'reference_field_name' => 'reference_field_name_1',
                                'reference_connection' => 'connection_2',
                                'table_affected_fields' => ['field_name_1'],
                            ],
                        ],
                        'prefix.table_name_1' => [
                            [
                                'name' => 'constraint_name_2',
                                'active' => true,
                                'delete_strategy' => 'CASCADE',
                                'table_name' => 'prefix.table_name_3',
                                'connection' => 'connection_3',
                                'reference_table_name' => 'prefix.table_name_1',
                                'field_name' => 'field_name_3',
                                'reference_field_name' => 'field_name_1',
                                'reference_connection' => 'connection_1',
                                'table_affected_fields' => ['*'],
                            ],
                        ],
                    ],
                    'constraints_by_table' => [
                        'prefix.table_name_1' => [
                            [
                                'name' => 'constraint_name_1',
                                'active' => true,
                                'delete_strategy' => 'CASCADE',
                                'table_name' => 'prefix.table_name_1',
                                'connection' => 'connection_1',
                                'reference_table_name' => 'prefix.reference_table_name_1',
                                'field_name' => 'field_name_1',
                                'reference_field_name' => 'reference_field_name_1',
                                'reference_connection' => 'connection_2',
                                'table_affected_fields' => ['field_name_1'],
                            ],
                        ],
                        'prefix.table_name_3' => [
                            [
                                'name' => 'constraint_name_2',
                                'active' => true,
                                'delete_strategy' => 'CASCADE',
                                'table_name' => 'prefix.table_name_3',
                                'connection' => 'connection_3',
                                'reference_table_name' => 'prefix.table_name_1',
                                'field_name' => 'field_name_3',
                                'reference_field_name' => 'field_name_1',
                                'reference_connection' => 'connection_1',
                                'table_affected_fields' => ['*'],
                            ],
                        ],
                    ],
                ],
                'xml_constraints' => [
                    [
                        'name' => 'constraint_name_1',
                        'active' => true,
                        'delete_strategy' => 'CASCADE',
                        'table_name' => 'table_name_1',
                        'connection' => 'connection_1',
                        'reference_table_name' => 'reference_table_name_1',
                        'field_name' => 'field_name_1',
                        'reference_field_name' => 'reference_field_name_1',
                    ],
                    [
                        'name' => 'constraint_name_2',
                        'active' => true,
                        'delete_strategy' => 'CASCADE',
                        'table_name' => 'table_name_3',
                        'connection' => 'connection_3',
                        'reference_table_name' => 'table_name_1',
                        'field_name' => 'field_name_3',
                        'reference_field_name' => 'field_name_1',
                    ],
                ],
                'database_constraints' => [
                    'db_constraint_1_id' => [
                        'name' => 'db_constraint_name_1',
                        'active' => true,
                        'delete_strategy' => 'CASCADE',
                        'table_name' => 'prefix.table_name_2',
                        'connection' => 'connection_1',
                        'reference_connection' => 'connection_1',
                        'reference_table_name' => 'prefix.reference_table_name_1',
                        'field_name' => 'field_name_2',
                        'reference_field_name' => 'reference_field_name_1',
                    ],
                    'db_constraint_2_id' => [
                        'name' => 'db_constraint_name_2',
                        'active' => true,
                        'delete_strategy' => 'CASCADE',
                        'table_name' => 'prefix.table_name_2',
                        'connection' => 'connection_1',
                        'reference_connection' => 'connection_1',
                        'reference_table_name' => 'prefix.reference_table_name_3',
                        'field_name' => 'field_name_2',
                        'reference_field_name' => 'reference_field_name_3',
                    ],
                ],
                'database_tables' => [
                    'table_name_1' => [
                        'name' => 'table_name_1',
                        'prefixed_name' => 'prefix.table_name_1',
                        'connection' => 'connection_1',
                    ],
                    'table_name_2' => [
                        'name' => 'table_name_2',
                        'prefixed_name' => 'prefix.table_name_2',
                        'connection' => 'connection_1',
                    ],
                    'table_name_3' => [
                        'name' => 'table_name_3',
                        'prefixed_name' => 'prefix.table_name_3',
                        'connection' => 'connection_3',
                    ],
                    'reference_table_name_1' => [
                        'name' => 'reference_table_name_1',
                        'prefixed_name' => 'prefix.reference_table_name_1',
                        'connection' => 'connection_2',
                    ],
                    'reference_table_name_2' => [
                        'name' => 'reference_table_name_2',
                        'prefixed_name' => 'prefix.reference_table_name_2',
                        'connection' => 'connection_1',
                    ],
                    'reference_table_name_3' => [
                        'name' => 'reference_table_name_3',
                        'prefixed_name' => 'prefix.reference_table_name_3',
                        'connection' => 'connection_1',
                    ],
                ],
            ],
        ];
    }
}
