<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\ForeignKey\Test\Unit\Config;

use Magento\Framework\ForeignKey\Config\DbReader;

class DbReaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DbReader
     */
    private $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $deploymentConfig;

    protected function setUp()
    {
        $this->connectionFactoryMock =
            $this->createMock(\Magento\Framework\Model\ResourceModel\Type\Db\ConnectionFactory::class);
        $this->deploymentConfig = $this->createMock(\Magento\Framework\App\DeploymentConfig::class);
        $this->model = new DbReader($this->connectionFactoryMock, $this->deploymentConfig);
    }

    public function testRead()
    {
        $dbConfig = [
            'default' => [
                'host' => '127.0.0.1',
                'dbname' => 'magento',
                'username' => 'root',
                'password' => 'root',
                'model' => 'mysql4',
                'initStatements' => 'SET NAMES utf8;',
                'active' => '1',
            ]
        ];
        $this->deploymentConfig->expects($this->once())->method('get')->with('db/connection')->willReturn($dbConfig);
        $connection = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->connectionFactoryMock->expects($this->once())
            ->method('create')
            ->with($dbConfig['default'])
            ->willReturn($connection);

        $testForeignKey = [
            'FK_NAME' => 'some_fk',
            'TABLE_NAME' => 'some_table',
            'REF_TABLE_NAME' => 'some_table_two',
            'COLUMN_NAME' => 'field_one',
            'REF_COLUMN_NAME' => 'field_two',
            'ON_DELETE' => 'CASCADE',
        ];
        $connection->method('getTables')
            ->willReturn([$testForeignKey['TABLE_NAME']]);
        $connection->method('getForeignKeys')
            ->with($testForeignKey['TABLE_NAME'])
            ->willReturn([$testForeignKey]);

        $expected = [
            [
                'name' => 'some_fk',
                'delete_strategy' => 'DB CASCADE',
                'table_name' => 'some_table',
                'reference_table_name' => 'some_table_two',
                'field_name' => 'field_one',
                'reference_field_name' => 'field_two',
                'connection' => 'default',
                'reference_connection' => 'default'
            ]
        ];
        $actual = $this->model->read();
        $this->assertEquals($expected, array_values($actual));
    }
}
