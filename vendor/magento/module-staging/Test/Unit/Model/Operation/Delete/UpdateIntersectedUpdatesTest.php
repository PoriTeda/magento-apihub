<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Model\Operation\Delete;

use Magento\Framework\EntityManager\EntityManager;
use Magento\Staging\Model\Entity\VersionLoader;
use Magento\Staging\Model\VersionManager;
use Magento\Framework\EntityManager\Db\DeleteRow as DeleteEntityRow;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Staging\Model\Operation\Delete\UpdateIntersectedRollbacks;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Staging\Model\Entity\Builder;
use Magento\Staging\Model\Operation\Delete\UpdateIntersectedUpdates;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Staging\Api\Data\UpdateInterface;

/**
 * Class UpdateIntersectedUpdatesTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateIntersectedUpdatesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TypeResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    private $typeResolverMock;

    /**
     * @var ReadEntityVersion|\PHPUnit_Framework_MockObject_MockObject
     */
    private $readEntityVersionMock;

    /**
     * @var VersionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManagerMock;

    /**
     * @var DeleteEntityRow|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deleteEntityRowMock;

    /**
     * @var MetadataPool|\PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataPoolMock;

    /**
     * @var UpdateIntersectedRollbacks|\PHPUnit_Framework_MockObject_MockObject
     */
    private $intersectedRollbacksMock;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityManagerMock;

    /**
     * @var Builder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $builderMock;

    /**
     * @var string
     */
    private $entityType;

    /**
     * @var AbstractModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityMock;

    /**
     * @var UpdateInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $versionMock;

    /**
     * @var EntityMetadataInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataMock;

    /**
     * @var HydratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $hydratorMock;

    /**
     * @var VersionLoader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $versionLoaderMock;

    /**
     * @var UpdateIntersectedUpdates
     */
    private $model;

    protected function setUp()
    {
        $this->entityType = 'TestModule\Api\Data\TestModuleInterface';
        $this->entityMock = $this->getMockForAbstractClass(
            AbstractModel::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $this->typeResolverMock = $this->createMock(TypeResolver::class);
        $this->versionMock = $this->getMockForAbstractClass(
            UpdateInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $this->metadataMock = $this->getMockForAbstractClass(
            EntityMetadataInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $this->hydratorMock = $this->getMockForAbstractClass(
            HydratorInterface::class,
            [],
            '',
            false,
            false,
            true
        );
        $this->readEntityVersionMock = $this->createMock(ReadEntityVersion::class);
        $this->intersectedRollbacksMock = $this->createMock(UpdateIntersectedRollbacks::class);
        $this->versionManagerMock = $this->createMock(VersionManager::class);
        $this->deleteEntityRowMock = $this->createMock(DeleteEntityRow::class);
        $this->metadataPoolMock = $this->createMock(MetadataPool::class);
        $this->entityManagerMock = $this->createMock(EntityManager::class);
        $this->builderMock = $this->createMock(Builder::class);
        $this->typeResolverMock->expects($this->any())
            ->method('resolve')
            ->with($this->entityMock)
            ->willReturn($this->entityType);
        $this->versionManagerMock->expects($this->any())->method('getCurrentVersion')->willReturn($this->versionMock);
        $this->metadataPoolMock->expects($this->any())->method('getMetadata')->with($this->entityType)->willReturn(
            $this->metadataMock
        );
        $this->metadataPoolMock->expects($this->once())->method('getHydrator')->with($this->entityType)->willReturn(
            $this->hydratorMock
        );
        $this->versionLoaderMock = $this->createMock(VersionLoader::class);
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(
            UpdateIntersectedUpdates::class,
            [
                'typeResolver' => $this->typeResolverMock,
                'intersectedRollbacks' => $this->intersectedRollbacksMock,
                'versionManager' => $this->versionManagerMock,
                'readEntityVersion' => $this->readEntityVersionMock,
                'deleteEntityRow' => $this->deleteEntityRowMock,
                'metadataPool' => $this->metadataPoolMock,
                'entityManager' => $this->entityManagerMock,
                'builder' => $this->builderMock,
                'versionLoader' => $this->versionLoaderMock,
            ]
        );
    }

    public function testExecuteTemporaryUpdate()
    {
        $rollbackId = 10;
        $entityId = 'entity_id';
        $entityData = ['created_in' => 7, $entityId => $entityId];
        $nextVersionId = 11;
        $nextVersion = ['updated_in' => 12, $entityId => 11];
        $previousVersionId = 1;
        $this->versionMock->expects($this->once())->method('getRollbackId')->willReturn($rollbackId);
        $this->hydratorMock->expects($this->once())
            ->method('extract')
            ->with($this->entityMock)
            ->willReturn($entityData);
        $this->metadataMock->expects($this->any())->method('getIdentifierField')->willReturn($entityId);
        $this->readEntityVersionMock->expects($this->once())
            ->method('getNextVersionId')
            ->with($this->entityType, $entityData['created_in'], $entityData[$entityId])
            ->willReturn($nextVersionId);
        $entityTable = 'table_name';
        $this->metadataMock->expects($this->any())->method('getEntityTable')->willReturn($entityTable);
        $adapterMock = $this->getMockForAbstractClass(
            \Magento\Framework\DB\Adapter\AdapterInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['select']
        );
        $this->metadataMock->expects($this->any())->method('getEntityConnection')->willReturn($adapterMock);
        $selectMock = $this->createPartialMock(\Magento\Framework\DB\Select::class, ['from', 'where', 'setPart']);
        $selectMock->expects($this->any())->method("from")->with(['entity_table' => $entityTable])->willReturnSelf();
        $selectMock->expects($this->at(1))->method("where")->with('created_in = ?', $nextVersionId)->willReturnSelf();
        $selectMock->expects($this->at(2))->method("where")->with(
            $entityId . ' = ?',
            $this->entityMock[$entityId]
        )
            ->willReturnSelf();
        $selectMock->expects($this->any())->method("setPart")->with('disable_staging_preview', true)->willReturnSelf();
        $adapterMock->expects($this->once())->method('select')->willReturn($selectMock);
        $adapterMock->expects($this->once())->method('fetchRow')->with($selectMock)->willReturn($nextVersion);

        $adapterMock->expects($this->once())->method('update')->with(
            $entityTable,
            ['updated_in' => $nextVersion['updated_in']],
            [
                $entityId . ' = ?' => $nextVersion[$entityId],
                'created_in = ?' => $previousVersionId
            ]
        )
            ->willReturn([]);
        $this->readEntityVersionMock->expects($this->once())
            ->method('getPreviousVersionId')
            ->with($this->entityType, $entityData['created_in'], $entityData[$entityId])
            ->willReturn($previousVersionId);
        $this->deleteEntityRowMock->expects($this->once())->method('execute')->with($this->entityType, $nextVersion);

        $this->model->execute($this->entityMock);
    }

    public function testExecutePermanentUpdate()
    {
        $entityId = 'entity_id';
        $entityData = ['created_in' => 7, $entityId => $entityId];
        $nextVersionId = 11;
        $nextVersion = ['created_in' => 12, $entityId => 11];
        $previousVersionId = 1;
        $previousPermanentId = 1;
        $nextPermanentId = 2;

        $this->hydratorMock->expects($this->once())->method('extract')->with($this->entityMock)->willReturn(
            $entityData
        );
        $this->metadataMock->expects($this->any())->method('getIdentifierField')->willReturn($entityId);
        $this->readEntityVersionMock->expects($this->once())
            ->method('getNextVersionId')
            ->with($this->entityType, $entityData['created_in'], $entityData[$entityId])
            ->willReturn($nextVersionId);
        $entityTable = 'table_name';
        $this->metadataMock->expects($this->any())->method('getEntityTable')->willReturn($entityTable);
        $adapterMock = $this->getMockForAbstractClass(
            \Magento\Framework\DB\Adapter\AdapterInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['select']
        );
        $this->metadataMock->expects($this->any())->method('getEntityConnection')->willReturn($adapterMock);
        $selectMock = $this->createPartialMock(\Magento\Framework\DB\Select::class, ['from', 'where', 'setPart']);
        $selectMock->expects($this->any())->method("from")->with(['entity_table' => $entityTable])->willReturnSelf();
        $selectMock->expects($this->at(1))->method("where")->with('created_in = ?', $nextVersionId)->willReturnSelf();
        $selectMock->expects($this->at(2))->method("where")->with(
            $entityId . ' = ?',
            $this->entityMock[$entityId]
        )
            ->willReturnSelf();
        $selectMock->expects($this->any())->method("setPart")->with('disable_staging_preview', true)->willReturnSelf();

        $adapterMock->expects($this->once())->method('select')->willReturn($selectMock);
        $adapterMock->expects($this->once())->method('fetchRow')->with($selectMock)->willReturn($nextVersion);

        $this->readEntityVersionMock->expects($this->once())
            ->method('getPreviousVersionId')
            ->with($this->entityType, $entityData['created_in'], $entityData[$entityId])
            ->willReturn($previousPermanentId);
        $adapterMock->expects($this->once())->method('update')->with(
            $entityTable,
            ['updated_in' => $nextVersion['created_in']],
            [
                $entityId . ' = ?' => $nextVersion[$entityId],
                'created_in = ?' => $previousVersionId
            ]
        )
            ->willReturn([]);
        $this->readEntityVersionMock->expects($this->once())
            ->method('getPreviousPermanentVersionId')
            ->with($this->entityType, $entityData['created_in'], $entityData[$entityId])
            ->willReturn($previousPermanentId);
        $this->readEntityVersionMock->expects($this->once())
            ->method('getNextPermanentVersionId')
            ->with($this->entityType, $entityData['created_in'], $entityData[$entityId])
            ->willReturn($nextPermanentId);
        $this->versionManagerMock->expects($this->never())->method('getVersion')->willReturn($this->versionMock);
        $this->versionMock->expects($this->never())->method('getId');
        $this->versionManagerMock->expects($this->never())->method('setCurrentVersionId');
        $prevPermanentEntity = '';
        $this->versionLoaderMock->expects($this->once())
            ->method('load')
            ->with($this->entityMock, $entityId, $previousVersionId)
            ->willReturn($prevPermanentEntity);
        $this->intersectedRollbacksMock->expects($this->once())
            ->method('execute')
            ->with($prevPermanentEntity, $nextPermanentId);

        $this->model->execute($this->entityMock);
    }
}
