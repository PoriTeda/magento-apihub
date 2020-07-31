<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Model\Operation\Update;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Staging\Model\Operation\Update\CreateEntityVersion;
use Magento\Staging\Model\Operation\Update\DefaultTemporaryUpdateProcessor;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Framework\EntityManager\TypeResolver;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DefaultTemporaryUpdateProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Staging\Model\Operation\Update\DefaultTemporaryUpdateProcessor
     */
    private $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $createEntityVersionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $entityVersionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $entityManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataPoolMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $typeResolverMock;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $objectManager;

    protected function setUp()
    {
        $this->createEntityVersionMock = $this->createMock(CreateEntityVersion::class);
        $this->entityVersionMock = $this->createMock(ReadEntityVersion::class);
        $this->entityManagerMock = $this->createMock(EntityManager::class);
        $this->metadataPoolMock = $this->createMock(MetadataPool::class);
        $this->typeResolverMock = $this->createMock(TypeResolver::class);
        $this->versionManagerMock = $this->createPartialMock(
            \Magento\Staging\Model\VersionManager\Proxy::class,
            ['setCurrentVersionId']
        );

        $this->model = new DefaultTemporaryUpdateProcessor(
            $this->typeResolverMock,
            $this->createEntityVersionMock,
            $this->entityVersionMock,
            $this->versionManagerMock,
            $this->entityManagerMock,
            $this->metadataPoolMock
        );

        $this->objectManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $reflection = new \ReflectionClass(DefaultTemporaryUpdateProcessor::class);
        $reflectionProperty = $reflection->getProperty('objectManager');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->model, $this->objectManager);
    }

    public function testProcess()
    {
        $entityType = \Magento\Staging\Api\Data\UpdateInterface::class;
        $entityMock = $this->getMockForAbstractClass($entityType, [], '', false, false);
        $metadataMock = $this->getMockForAbstractClass(EntityMetadataInterface::class, [], '', false, false);
        $hydratorMock = $this->getMockForAbstractClass(HydratorInterface::class, [], '', false, false);
        $identifierField = 'id';
        $entityId = 1;
        $entityData = [$identifierField => $entityId];
        $prevVersion = 1000;
        $versionId = 2000;
        $nextVersion = 3000;
        $rollbackId = null;

        $this->typeResolverMock->expects($this->any())
            ->method('resolve')
            ->willReturn($entityType);
        $this->metadataPoolMock->expects($this->once())->method('getHydrator')->with($entityType)
            ->willReturn($hydratorMock);
        $this->metadataPoolMock->expects($this->once())->method('getMetadata')->with($entityType)
            ->willReturn($metadataMock);
        $hydratorMock->expects($this->once())->method('extract')->with($entityMock)->willReturn($entityData);
        $metadataMock->expects($this->once())->method('getIdentifierField')->willReturn($identifierField);
        $this->entityVersionMock->expects($this->once())->method('getPreviousVersionId')->willReturn($prevVersion);
        $this->entityVersionMock->expects($this->once())->method('getNextVersionId')->willReturn($nextVersion);
        $this->versionManagerMock->expects($this->atLeastOnce())->method('setCurrentVersionId')->withConsecutive(
            [$prevVersion],
            [$rollbackId],
            [$versionId]
        );

        $previousEntity = $this->getMockForAbstractClass($entityType);
        $this->objectManager->expects(static::once())
            ->method('create')
            ->with($entityType)
            ->willReturn($previousEntity);

        $entityMock->expects(static::once())
            ->method('getId')
            ->willReturn($entityId);

        $this->entityManagerMock->expects(static::once())
            ->method('load')
            ->with($previousEntity, $entityId);
        $this->createEntityVersionMock->expects($this->once())->method('execute');

        $this->assertSame($entityMock, $this->model->process($entityMock, $versionId, $rollbackId));
    }
}
