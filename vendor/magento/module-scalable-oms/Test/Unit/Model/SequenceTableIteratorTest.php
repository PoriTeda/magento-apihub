<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ScalableOms\Test\Unit\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\ScalableOms\Model\SequenceTableIterator;

/**
 * Checks that iterator iterates over sales sequence tables.
 */
class SequenceTableIteratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var ResourceConnection | \PHPUnit_Framework_MockObject_MockObject
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
     * Run iteration over iterator with given parameters.
     *
     * @dataProvider  sequenceTableIteratorDataProvider()
     *
     * @param array $expectedIterator
     * @param array $expectedStatement
     */
    public function testIterator(array $expectedIterator, array $expectedStatement)
    {
        $expectedStatementIndexes = array_keys(current($expectedStatement));
        $metaTableName = 'sales_sequence_meta';

        /** @var SequenceTableIterator $sequenceTableIterator */
        $sequenceTableIterator = $this->objectManagerHelper->getObject(
            SequenceTableIterator::class,
            ['resourceConnection' => $this->resourceMock]
        );

        $this->resourceMock->expects($this->once())
            ->method('getTableName')
            ->with($metaTableName)
            ->willReturn($metaTableName);

        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $selectMock->expects($this->once())
            ->method('from')
            ->with($metaTableName, $expectedStatementIndexes)
            ->willReturnSelf();

        $statementMock = $this->getMockBuilder(\Zend_Db_Statement_Interface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $statementMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn($expectedStatement);

        $connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->getMockForAbstractClass();

        $connectionMock
            ->expects($this->once())
            ->method('select')
            ->willReturn($selectMock);

        $connectionMock->expects($this->once())
            ->method('query')
            ->with($selectMock)
            ->willReturn($statementMock);

        $this->resourceMock
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($connectionMock);

        $this->assertEquals(
            new \ArrayIterator($expectedIterator),
            $sequenceTableIterator->getInnerIterator()
        );
    }

    /**
     * DataProvider for test
     *
     * @return array
     */
    public function sequenceTableIteratorDataProvider()
    {
        return [
            [
                ['sequence_test_table_1'],
                [
                    ['entity_type' => 'test_table', 'store_id' => 1],
                ]
            ],
            [
                ['sequence_test_table_0', 'sequence_test_table_1', 'sequence_test_table_2'],
                [
                    ['entity_type' => 'test_table', 'store_id' => 0],
                    ['entity_type' => 'test_table', 'store_id' => 1],
                    ['entity_type' => 'test_table', 'store_id' => 2],
                ]
            ],
        ];
    }
}
