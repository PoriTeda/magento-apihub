<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteria;
use Magento\Staging\Api\Data\UpdateSearchResultInterfaceFactory;
use Magento\Staging\Model\ResourceModel\Update\Collection;

/**
 * Test for Magento\Staging\Model\UpdateRepository class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Staging\Model\UpdateRepository
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Staging\Model\Update
     */
    protected $entityMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Staging\Model\ResourceModel\Update
     */
    protected $resourceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $updateMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $updateFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Staging\Model\Update\Validator
     */
    protected $validatorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject searchResultsFactoryMock
     */
    protected $searchResultsFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $versionHistoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $collectionProcessor;

    protected function setUp()
    {
        $this->entityMock = $this->createMock(\Magento\Staging\Model\Update::class);
        $this->resourceMock = $this->createMock(\Magento\Staging\Model\ResourceModel\Update::class);
        $this->validatorMock = $this->createMock(\Magento\Staging\Model\Update\Validator::class);
        $this->updateMock = $this->createMock(\Magento\Staging\Model\Update::class);
        $this->updateFactoryMock = $this->createPartialMock(\Magento\Staging\Model\UpdateFactory::class, ['create']);
        $this->versionHistoryMock = $this->createMock(\Magento\Staging\Model\VersionHistoryInterface::class);
        $this->searchResultsFactoryMock = $this->getMockBuilder(UpdateSearchResultInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->collectionProcessor = $this->createMock(
            \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface::class
        );
        $this->model = $objectManager->getObject(
            \Magento\Staging\Model\UpdateRepository::class,
            [
                'resource' => $this->resourceMock,
                'validator' => $this->validatorMock,
                'updateFactory' => $this->updateFactoryMock,
                'versionHistory' => $this->versionHistoryMock,
                'searchResultFactory' => $this->searchResultsFactoryMock,
                'collectionProcessor' => $this->collectionProcessor
            ]
        );
    }

    /**
     * test save new permanent update
     */
    public function testSaveNewPermanent()
    {
        $this->validatorMock->expects($this->once())->method('validateCreate')->with($this->entityMock);
        $this->entityMock->expects($this->once())->method('getId')->willReturn(null);
        $startTime = date('m/d/y', time());
        //getIdForEntity
        $this->entityMock->expects($this->any())->method('getStartTime')->willReturn($startTime);
        $this->updateFactoryMock->expects($this->any())->method('create')->willReturn($this->updateMock);
        $this->resourceMock->expects($this->any())->method('load')->with($this->updateMock, strtotime($startTime));
        $this->updateMock->expects($this->any())->method('getId')->willReturn(null);

        $this->entityMock->expects($this->once())->method('setId')->with(strtotime($startTime));
        $this->entityMock->expects($this->once())->method('isObjectNew')->with(true);
        $this->entityMock->expects($this->once())->method('getEndTime')->willReturn(null);
        $this->entityMock->expects($this->once())->method('getRollbackId')->willReturn(null);
        $this->resourceMock->expects($this->once())->method('save')->with($this->entityMock);

        $this->assertEquals($this->entityMock, $this->model->save($this->entityMock));
    }

    /**
     * @expectedException \Magento\Framework\Exception\CouldNotSaveException
     */
    public function testSaveStartTimeActiveCampaign()
    {
        $startTime = date('m/d/y', time());
        $this->validatorMock->expects($this->once())->method('validateUpdate')->with($this->entityMock);
        $this->entityMock->expects($this->any())->method('getId')->willReturn(strtotime($startTime));
        //getIdForEntity
        $this->entityMock->expects($this->any())->method('getStartTime')->willReturn($startTime);
        $this->updateFactoryMock->expects($this->any())->method('create')->willReturn($this->updateMock);
        $this->resourceMock->expects($this->any())->method('load')->with($this->updateMock, strtotime($startTime));
        $this->updateMock->expects($this->any())->method('getId')->willReturn(strtotime($startTime) - 1);

        $this->versionHistoryMock->expects($this->any())
            ->method('getCurrentId')
            ->willReturn(strtotime($startTime) + 1);

        $this->assertNull($this->model->save($this->entityMock));

        $this->expectExceptionMessage(
            "The start time can't be changed while the update is active. "
            . "Please wait until the update is complete and try again."
        );
    }

    public function testGetList()
    {
        $collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collectionMock->expects($this->once())
            ->method('setSearchCriteria')
            ->with($searchCriteriaMock);
        $this->collectionProcessor->expects($this->once())
            ->method('process')
            ->with($searchCriteriaMock, $collectionMock);
        $this->searchResultsFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);

        $list = $this->model->getList($searchCriteriaMock);
        $this->assertSame($collectionMock, $list);
    }

    /**
     * test edit existed permanent update with changed Start Time
     */
    public function testSaveExistedPermanent()
    {
        $this->validatorMock->expects($this->once())->method('validateupdate')->with($this->entityMock);
        $oldStartTime = date('m/d/y', time());
        $startTime = date('m/d/y', time() + 24 * 60 * 60);
        $oldId = strtotime($oldStartTime);
        $newId = strtotime($startTime);
        $this->entityMock->expects($this->any())->method('getId')->willReturn($oldId);
        $this->entityMock->expects($this->any())->method('getStartTime')->willReturn($startTime);

        $oldEntityMock = $this->createMock(\Magento\Staging\Model\Update::class);
        $oldEntityMock->expects($this->once())->method('getStartTime')->willReturn($oldStartTime);
        $oldEntityMock->expects($this->once())->method('getId')->willReturn(strtotime($oldStartTime));
        $this->updateFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnOnConsecutiveCalls(
                $oldEntityMock,
                $this->updateMock
            );
        $this->resourceMock->expects($this->any())->method('load');
        $this->updateMock->expects($this->any())->method('getId')->willReturn(null);

        //$this->entityMock->expects($this->once())->method('setOldId')->with(strtotime($oldStartTime));
        $this->entityMock->expects($this->once())->method('getEndTime')->willReturn(null);
        $this->entityMock->expects($this->once())->method('setId')->willReturn($newId);
        $this->entityMock->expects($this->once())->method('getRollbackId')->willReturn(null);
        $this->resourceMock->expects($this->once())->method('save')->with($this->entityMock);

        $this->assertEquals($this->entityMock, $this->model->save($this->entityMock));
    }

    /**
     * @expectedException \Magento\Framework\Exception\CouldNotDeleteException
     * @expectedExceptionMessage The active update can't be deleted.
     */
    public function testDeleteActiveUpdate()
    {
        $savedVersionId = 123;
        $this->entityMock->expects($this->once())
            ->method('getId')
            ->willReturn($savedVersionId);
        $this->versionHistoryMock->expects($this->once())
            ->method('getCurrentId')
            ->willReturn($savedVersionId);
        $this->entityMock->expects($this->never())
            ->method('delete');

        $this->model->delete($this->entityMock);
    }

    /**
     * Test delete method with rollback assigned to update.
     *
     * @return void
     */
    public function testDeleteUpdateWithRollBackAssigned(): void
    {
        $savedVersionHistoryId = strtotime('+5 minutes');
        $savedVersionId = strtotime('+15 minutes');
        $rollbackId = strtotime('+45 minutes');
        $this->entityMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($savedVersionId);
        $this->entityMock->expects($this->once())
            ->method('getRollbackId')
            ->willReturn($rollbackId);
        $this->versionHistoryMock->expects($this->once())
            ->method('getCurrentId')
            ->willReturn($savedVersionHistoryId);
        $this->resourceMock->expects($this->atleastOnce())
            ->method('getMaxIdByTime')
            ->willReturn($rollbackId + 1);
        $this->resourceMock->expects($this->once())->method('delete');
        $this->resourceMock->expects($this->atLeastOnce())
            ->method('isRollbackAssignedToUpdates')
            ->with($rollbackId, [$savedVersionId])
            ->willReturn(true);
        $this->resourceMock->expects($this->never())->method('load');

        $this->assertEquals(true, $this->model->delete($this->entityMock));
    }

    /**
     * Test delete method without rollback assigned to update.
     *
     * @return void
     */
    public function testDeleteUpdateWithoutRollBackAssigned(): void
    {
        $savedVersionHistoryId = strtotime('+5 minutes');
        $savedVersionId = strtotime('+15 minutes');
        $rollbackId = strtotime('+45 minutes');
        $this->entityMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($savedVersionId);
        $this->entityMock->expects($this->once())
            ->method('getRollbackId')
            ->willReturn($rollbackId);
        $this->versionHistoryMock->expects($this->once())
            ->method('getCurrentId')
            ->willReturn($savedVersionHistoryId);
        $this->resourceMock->expects($this->atleastOnce())
            ->method('getMaxIdByTime')
            ->willReturn($rollbackId + 1);
        $this->resourceMock->expects($this->exactly(2))->method('delete');
        $this->resourceMock->expects($this->atLeastOnce())
            ->method('isRollbackAssignedToUpdates')
            ->with($rollbackId, [$savedVersionId])
            ->willReturn(false);
        $this->resourceMock->expects($this->atLeastOnce())
            ->method('load')
            ->with($this->updateMock, $rollbackId);
        $this->updateMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($savedVersionId);
        $this->updateMock->expects($this->atLeastOnce())
            ->method('getRollbackId')
            ->willReturn(false);
        $this->updateFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->updateMock);

        $this->assertEquals(true, $this->model->delete($this->entityMock));
    }
}
