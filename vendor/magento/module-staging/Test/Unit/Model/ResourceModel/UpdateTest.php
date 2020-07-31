<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\ResourceModel;

use Magento\Staging\Model\ResourceModel\Update as StagingUpdate;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;

/**
 * Tests for the \Magento\Staging\Model\ResourceModel\Update class.
 */
class UpdateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var StagingUpdate
     */
    private $update;

    /**
     * @var ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resourceConnectionMock;

    /**
     * @var AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connectionMock;

    /**
     * @var int
     */
    private $rollbackId;

    /**
     * @var int
     */
    private $updateId;
    
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->rollbackId = 123;
        $this->updateId = 321;
        $defaultTableName = 'default_table';

        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection', 'getTableName'])
            ->getMock();
        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['select', 'fetchOne'])
            ->getMockForAbstractClass();
        $this->resourceConnectionMock->expects($this->atLeastOnce())
            ->method('getConnection')
            ->with(ResourceConnection::DEFAULT_CONNECTION)
            ->willReturn($this->connectionMock);
        $this->resourceConnectionMock->expects($this->atLeastOnce())
            ->method('getTableName')
            ->willReturn($defaultTableName);
        $select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->setMethods(['from', 'where', 'limit'])
            ->getMock();
        $select->expects($this->atLeastOnce())
            ->method('from')
            ->with($defaultTableName)
            ->willReturnSelf();
        $select->expects($this->exactly(2))
            ->method('where')
            ->withConsecutive(
                ['rollback_id = ?', $this->rollbackId],
                ['id NOT IN (?)', [$this->updateId]]
            )
            ->willReturnSelf();
        $select->expects($this->atLeastOnce())
            ->method('limit')
            ->with(1)
            ->willReturnSelf();
        $this->connectionMock->expects($this->atLeastOnce())
            ->method('select')
            ->willReturn($select);

        $this->update = $objectManager->getObject(
            StagingUpdate::class,
            [
                'resources' => $this->resourceConnectionMock,
            ]
        );
    }

    /**
     * Tests isRollbackAssignedToUpdates() method.
     *
     * @dataProvider rollbackAssignedToUpdatesDataProvider
     * @param string|bool $value
     * @param bool $expectedValue
     * @return void
     */
    public function testIsRollbackAssignedToUpdates($value, bool $expectedValue): void
    {
        $this->connectionMock->expects($this->atLeastOnce())
            ->method('fetchOne')
            ->willReturn($value);

        $this->assertEquals(
            $expectedValue,
            $this->update->isRollbackAssignedToUpdates($this->rollbackId, [$this->updateId])
        );
    }

    /**
     * @return array
     */
    public function rollbackAssignedToUpdatesDataProvider(): array
    {
        return [
            ['string', true],
            [false, false],
        ];
    }
}
