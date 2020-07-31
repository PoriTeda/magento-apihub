<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Model\Operation\Update;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Staging\Model\Operation\Update\RescheduleUpdate;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\HydratorPool;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionManager;

/**
 * Class RescheduleUpdateTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RescheduleUpdateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|MetadataPool
     */
    private $metadataPool;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|HydratorPool
     */
    private $hydratorPool;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TypeResolver
     */
    private $typeResolver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var RescheduleUpdate
     */
    private $rescheduleUpdate;

    protected function setUp()
    {
        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataPool = $this->getMockBuilder(MetadataPool::class)->disableOriginalConstructor()->getMock();
        $this->hydratorPool = $this->getMockBuilder(HydratorPool::class)->disableOriginalConstructor()->getMock();
        $this->typeResolver = $this->getMockBuilder(TypeResolver::class)->disableOriginalConstructor()->getMock();
        $this->updateRepository = $this->getMockBuilder(UpdateRepositoryInterface::class)->getMockForAbstractClass();
        $this->rescheduleUpdate = new RescheduleUpdate(
            $this->resourceConnection,
            $this->metadataPool,
            $this->hydratorPool,
            $this->typeResolver,
            $this->updateRepository
        );
    }

    public function testReschedule()
    {
        $originVersion = 1;
        $targetVersion = 256;
        $updateMock = $this->getMockBuilder(UpdateInterface::class)->getMockForAbstractClass();
        $updateTargetMock = $this->getMockBuilder(UpdateInterface::class)->getMockForAbstractClass();
        $metadataMock = $this->getMockBuilder(EntityMetadataInterface::class)->getMockForAbstractClass();
        $metadataMock->expects($this->atLeastOnce())->method('getIdentifierField')->willReturn('id');
        $hydratorMock = $this->getMockBuilder(HydratorInterface::class)->getMockForAbstractClass();
        $adapterMock = $this->getMockBuilder(AdapterInterface::class)->getMockForAbstractClass();
        $selectMock = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->setMethods([
                "from",
                "where",
                "order",
                "limit",
                "setPart",
            ])
            ->getMock();
        $selectMock->expects($this->any())->method("from")->willReturnSelf();
        $selectMock->expects($this->any())->method("order")->willReturnSelf();
        $selectMock->expects($this->any())->method("limit")->willReturnSelf();
        $selectMock->expects($this->any())->method("setPart")->willReturnSelf();
        $this->updateRepository->expects($this->exactly(2))->method('get')->withConsecutive(
            [$originVersion],
            [$targetVersion]
        )->willReturnOnConsecutiveCalls($updateMock, $updateTargetMock);
        $this->metadataPool->expects($this->atLeastOnce())->method('getMetadata')->willReturn($metadataMock);
        $this->hydratorPool->expects($this->atLeastOnce())->method('getHydrator')->willReturn($hydratorMock);
        $this->resourceConnection->expects($this->atLeastOnce())
            ->method('getConnectionByName')
            ->willReturn($adapterMock);
        $adapterMock->expects($this->atLeastOnce())->method('select')->willReturn($selectMock);
        $updateMock->expects($this->atLeastOnce())->method('getId')->willReturn($originVersion);
        $updateTargetMock->expects($this->atLeastOnce())->method('getId')->willReturn($targetVersion);
        $selectMock->expects($this->atLeastOnce())->method("where")
            ->withConsecutive(
                ['t.created_in < ?', $originVersion],
                ['t.id = ?'],
                ['t.created_in != ?', $originVersion],
                ['t.created_in > ?', $originVersion],
                ['t.id = ?'],
                ['t.created_in != ?', $originVersion],
                ['t.created_in < ?', $targetVersion],
                ['t.id = ?'],
                ['t.created_in != ?', $originVersion]
            )
            ->willReturnSelf();
        $this->rescheduleUpdate->reschedule($originVersion, $targetVersion, new \stdClass());
    }
}
