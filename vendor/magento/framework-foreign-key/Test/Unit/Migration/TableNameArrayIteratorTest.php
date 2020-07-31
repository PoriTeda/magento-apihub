<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\ForeignKey\Test\Unit\Migration;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ForeignKey\Migration\TableNameArrayIterator;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Checks that current value modification delegated to resource.
 */
class TableNameArrayIteratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resourceMock;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->resourceMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Run test iteration over iterator.
     */
    public function testIterate()
    {
        $tableNameWithoutPrefix = 'test_table';
        $tableNameList = [$tableNameWithoutPrefix];
        $prefix = 'pre_';
        $expectedTable = $prefix . $tableNameWithoutPrefix;
        /** @var TableNameArrayIterator $tableNameIterator */
        $tableNameIterator = $this->objectManagerHelper->getObject(
            TableNameArrayIterator::class,
            [
                'resourceConnection' => $this->resourceMock,
                'tableNames' => $tableNameList
            ]
        );
        $this->resourceMock->expects($this->once())
            ->method('getTableName')
            ->with($tableNameWithoutPrefix)
            ->willReturn($expectedTable);
        foreach ($tableNameIterator as $tableName) {
            $this->assertEquals($expectedTable, $tableName);
        }
    }
}
