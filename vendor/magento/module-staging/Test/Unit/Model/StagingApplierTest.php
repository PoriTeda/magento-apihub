<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config;
use Magento\Framework\Model\Entity\RepositoryFactory;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionHistoryInterface;
use Magento\Staging\Model\StagingList;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Indexer\CacheContext;
use Magento\Staging\Model\Entity\RetrieverPool;
use Magento\Staging\Model\ResourceModel\Db\DeleteObsoleteEntities;
use Magento\Staging\Model\ResourceModel\Db\GetNotIndexedEntities;
use Magento\Staging\Model\StagingApplierInterface;
use Magento\Staging\Model\StagingApplier;

/**
 * Class StagingApplierTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StagingApplierTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var StagingApplier
     */
    private $model;

    /**
     * @var CacheInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheManagerMock;

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfigCacheMock;

    /**
     * @var RepositoryFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $repositoryFactoryMock;

    /**
     * @var UpdateRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventManagerMock;

    /**
     * @var VersionHistoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $versionHistoryMock;

    /**
     * @var StagingList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stagingListMock;

    /**
     * @var CacheContext|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheContextMock;

    /**
     * @var RetrieverPool|\PHPUnit_Framework_MockObject_MockObject
     */
    private $retrieverPoolMock;

    /**
     * @var DeleteObsoleteEntities|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deleteObsoleteEntitiesMock;

    /**
     * @var GetNotIndexedEntities|\PHPUnit_Framework_MockObject_MockObject
     */
    private $getNotIndexedEntitiesMock;

    /**
     * @var StagingApplierInterface[]|\PHPUnit_Framework_MockObject_MockObject
     */
    private $appliersMock;

    protected function setUp()
    {
        $this->cacheManagerMock = $this->getMockForAbstractClass(
            CacheInterface::class,
            [],
            '',
            false,
            false,
            true
        );
        $this->appliersMock = $this->getMockForAbstractClass(
            StagingApplierInterface::class,
            [],
            '',
            false,
            false,
            true
        );
        $this->scopeConfigCacheMock = $this->createMock(Config::class);
        $this->repositoryFactoryMock = $this->createMock(RepositoryFactory::class);
        $this->updateRepositoryMock = $this->getMockForAbstractClass(
            UpdateRepositoryInterface::class,
            [],
            '',
            false,
            false,
            true
        );
        $this->eventManagerMock = $this->getMockForAbstractClass(
            ManagerInterface::class,
            [],
            '',
            false,
            false,
            true
        );
        $this->versionHistoryMock = $this->getMockForAbstractClass(
            VersionHistoryInterface::class,
            [],
            '',
            false,
            false,
            true
        );
        $this->stagingListMock = $this->createMock(StagingList::class);
        $this->cacheContextMock = $this->createMock(CacheContext::class);
        $this->retrieverPoolMock = $this->createMock(RetrieverPool::class);
        $this->deleteObsoleteEntitiesMock = $this->createMock(DeleteObsoleteEntities::class);
        $this->getNotIndexedEntitiesMock = $this->createMock(GetNotIndexedEntities::class);
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(
            StagingApplier::class,
            [
                'updateRepository' => $this->updateRepositoryMock,
                'scopeConfigCache' => $this->scopeConfigCacheMock,
                'cacheContext' => $this->cacheContextMock,
                'eventManager' => $this->eventManagerMock,
                'versionHistory' => $this->versionHistoryMock,
                'stagingList' => $this->stagingListMock,
                'deleteObsoleteEntities' => $this->deleteObsoleteEntitiesMock,
                'getNotIndexedEntities' => $this->getNotIndexedEntitiesMock,
                'cacheManager' => $this->cacheManagerMock,
                'appliers' => ['category' => $this->appliersMock],
                'retrieverPool' => $this->retrieverPoolMock,
                'repositoryFactory' => $this->repositoryFactoryMock

            ]
        );
    }

    public function testExecute()
    {
        $now = strtotime('now');
        $currentVersionId = '232232332';
        $oldVersionId = '1111111111';
        $entityType = ['category'];
        $entityIds = ['1'];
        $maximumInDB = 10;
        $this->updateRepositoryMock->expects($this->once())
            ->method('getVersionMaxIdByTime')
            ->with($now)
            ->willReturn($currentVersionId);
        $this->versionHistoryMock->expects($this->any())->method('getCurrentId')->willReturn($oldVersionId);
        $this->versionHistoryMock->expects($this->once())->method('setCurrentId');
        $this->scopeConfigCacheMock->expects($this->once())->method('clean');
        $this->stagingListMock->expects($this->any())->method('getEntityTypes')->willReturn($entityType);
        $this->getNotIndexedEntitiesMock->expects($this->once())
            ->method('execute')
            ->with($entityType[0], $oldVersionId, $currentVersionId)
            ->willReturn($entityIds);
        $this->appliersMock->expects($this->once())->method('execute')->with($entityIds);
        $object = $this->getMockBuilder(\Magento\Framework\Data\AbstractDataObject::class)
            ->setMethods(['save'])
            ->getMockForAbstractClass();
        $this->repositoryFactoryMock->expects($this->once())
            ->method('create')
            ->with($entityType[0])
            ->willReturn($object);
        $object->expects($this->once())->method('save');
        $retrieverInterfaceMock = $this->getMockForAbstractClass(
            \Magento\Staging\Model\Entity\RetrieverInterface::class,
            [],
            '',
            false,
            false,
            true
        );
        $this->retrieverPoolMock->expects($this->once())
            ->method('getRetriever')
            ->with($entityType[0])
            ->willReturn($retrieverInterfaceMock);
        $retrieverInterfaceMock->expects($this->once())->method('getEntity')->with($entityIds[0]);
        $this->eventManagerMock->expects($this->once())
            ->method('dispatch')
            ->with('clean_cache_by_tags', ['object' => $this->cacheContextMock]);
        $this->cacheContextMock->expects($this->once())->method('getIdentities');
        $this->cacheManagerMock->expects($this->once())->method('clean');
        $this->versionHistoryMock->expects($this->once())->method('getMaximumInDB')->willReturn($maximumInDB);
        $this->deleteObsoleteEntitiesMock->expects($this->once())
            ->method('execute')
            ->with($entityType[0], $currentVersionId, $maximumInDB);
        $this->model->execute();
    }
}
