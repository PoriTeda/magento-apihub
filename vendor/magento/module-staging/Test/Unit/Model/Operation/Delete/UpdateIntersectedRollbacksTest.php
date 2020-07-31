<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Model\Operation\Delete;

use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\Operation\Delete\UpdateIntersectedRollbacks;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\HydratorPool;
use Magento\Staging\Model\Operation\Update\UpdateEntityVersion;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\HydratorInterface;

/**
 * Class UpdateIntersectedRollbacksTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateIntersectedRollbacksTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $readEntityVersionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManagerMock;

    /**
     * UpdateIntersectedRollbacks
     */
    private $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $entityMock;

    /**
     * @var string
     */
    private $entityType;

    /**
     * @var MetadataPool|\PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataPool;

    /**
     * @var HydratorPool|\PHPUnit_Framework_MockObject_MockObject
     */
    private $hydratorPool;

    /**
     * @var UpdateEntityVersion|\PHPUnit_Framework_MockObject_MockObject
     */
    private $updateEntityVersion;

    protected function setUp()
    {
        $this->entityType = \TestModule\Api\Data\TestModuleInterface::class;
        $this->readEntityVersionMock =
            $this->createMock(\Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion::class);
        $this->versionManagerMock =
            $this->createMock(\Magento\Staging\Model\VersionManager::class);
        $typeResolverMock = $this->createMock(\Magento\Framework\EntityManager\TypeResolver::class);
        $typeResolverMock->expects($this->any())
            ->method('resolve')
            ->willReturn($this->entityType);
        $methods = ['setCreatedIn', 'setUpdatedIn', 'setRowId'];
        $this->entityMock = $this->createPartialMock(\Magento\Framework\Model\AbstractModel::class, $methods);
        $this->metadataPool = $this->getMockBuilder(MetadataPool::class)->disableOriginalConstructor()->getMock();
        $this->hydratorPool = $this->getMockBuilder(HydratorPool::class)->disableOriginalConstructor()->getMock();
        $this->updateEntityVersion = $this->getMockBuilder(UpdateEntityVersion::class)
            ->disableOriginalConstructor()->getMock();
        $this->model = new UpdateIntersectedRollbacks(
            $typeResolverMock,
            $this->metadataPool,
            $this->hydratorPool,
            $this->readEntityVersionMock,
            $this->versionManagerMock,
            $this->updateEntityVersion
        );
    }

    public function testExecute()
    {
        $versionId = 3;
        $endVersionId = 2147483647;
        $entityId = 1;
        $createdIn = 10;
        $rollbackId = 7;
        $rowId = 2;
        $nextVersionId = 15;
        $entityData = [
            'entity_id' => $entityId,
            'created_in' => $createdIn,
        ];
        $arguments = [
            'row_id' => $rowId,
            'created_in' => $rollbackId,
            'updated_in' => $nextVersionId,
        ];

        /** @var UpdateInterface|\PHPUnit_Framework_MockObject_MockObject $versionMock */
        $versionMock = $this->getMockBuilder(\Magento\Staging\Api\Data\UpdateInterface::class)
            ->getMock();
        $versionMock->expects($this->once())->method('getId')->willReturn($versionId);

        $this->readEntityVersionMock
            ->expects($this->once())
            ->method('getRollbackVersionIds')
            ->with($this->entityType, $createdIn, $endVersionId, $entityId)
            ->willReturn([$rollbackId]);
        $this->versionManagerMock->expects($this->at(1))->method('setCurrentVersionId')->with($rollbackId);
        $this->versionManagerMock->expects($this->at(2))->method('setCurrentVersionId')->with($versionId);
        $this->versionManagerMock->expects($this->once())->method('getVersion')->willReturn($versionMock);
        $this->readEntityVersionMock
            ->expects($this->once())
            ->method('getCurrentVersionRowId')
            ->with($this->entityType, $entityId)
            ->willReturn($rowId);
        $this->readEntityVersionMock
            ->expects($this->once())
            ->method('getNextVersionId')
            ->with($this->entityType, $rollbackId, $entityId)
            ->willReturn($nextVersionId);
        $metadata = $this->getMockBuilder(EntityMetadataInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $hydrator = $this->getMockBuilder(HydratorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataPool->expects($this->once())
            ->method('getMetadata')
            ->with($this->entityType)
            ->willReturn($metadata);
        $this->hydratorPool->expects($this->once())
            ->method('getHydrator')
            ->with($this->entityType)
            ->willReturn($hydrator);
        $hydrator->expects($this->once())
            ->method('extract')
            ->with($this->entityMock)
            ->willReturn($entityData);
        $metadata->expects($this->once())
            ->method('getIdentifierField')
            ->willReturn('entity_id');
        $metadata->expects($this->once())
            ->method('getLinkField')
            ->willReturn('row_id');
        $this->updateEntityVersion->expects($this->once())
            ->method('execute')
            ->with($this->entityMock, $arguments)
            ->willReturn(true);
        $this->model->execute($this->entityMock, $endVersionId);
    }
}
