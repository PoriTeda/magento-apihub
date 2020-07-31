<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\ForeignKey\Test\Unit\Strategy;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class RestrictTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionMock;

    /**
     * @var \Magento\Framework\ForeignKey\Strategy\Restrict
     */
    protected $strategy;

    protected function setUp()
    {
        $this->connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $objectManager = new ObjectManager($this);
        $this->strategy = $objectManager->getObject(\Magento\Framework\ForeignKey\Strategy\Restrict::class);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testProcess()
    {
        $constraintMock = $this->createMock(\Magento\Framework\ForeignKey\ConstraintInterface::class);
        $condition = 'cond1';

        $this->strategy->process($this->connectionMock, $constraintMock, $condition);

        $this->expectExceptionMessage(
            "The row couldn't be updated because a foreign key constraint failed. Verify the constraint and try again."
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testLockAffectedDataException()
    {
        $table = 'sampleTable';
        $condition = 'sampleCondition';
        $fields = [3, 75, 56, 67];
        $affectedData = 'some data';

        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $this->connectionMock->expects($this->once())->method('select')->willReturn($selectMock);

        $selectMock->expects($this->once())->method('forUpdate')->with(true)->willReturnSelf();
        $selectMock->expects($this->once())->method('from')->with($table, $fields)->willReturnSelf();
        $selectMock->expects($this->once())->method('where')->with($condition)->willReturnSelf();

        $this->connectionMock->expects($this->once())->method('fetchAssoc')->willReturn($affectedData);

        $this->strategy->lockAffectedData($this->connectionMock, $table, $condition, $fields);

        $this->expectExceptionMessage(
            "The row couldn't be updated because a foreign key constraint failed. Verify the constraint and try again."
        );
    }

    public function testLockAffectedData()
    {
        $table = 'sampleTable';
        $condition = 'sampleCondition';
        $fields = [3, 75, 56, 67];
        $affectedData = null;

        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $this->connectionMock->expects($this->once())->method('select')->willReturn($selectMock);

        $selectMock->expects($this->once())->method('forUpdate')->with(true)->willReturnSelf();
        $selectMock->expects($this->once())->method('from')->with($table, $fields)->willReturnSelf();
        $selectMock->expects($this->once())->method('where')->with($condition)->willReturnSelf();

        $this->connectionMock->expects($this->once())->method('fetchAssoc')->willReturn($affectedData);

        $result = $this->strategy->lockAffectedData($this->connectionMock, $table, $condition, $fields);
        $this->assertEquals($affectedData, $result);
    }
}
