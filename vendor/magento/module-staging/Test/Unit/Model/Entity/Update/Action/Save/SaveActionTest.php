<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Model\Entity\Update\Action\Save;

use Magento\Staging\Model\Entity\Update\Action\Save\SaveAction;
use Magento\Staging\Model\EntityStaging;
use Magento\Staging\Controller\Adminhtml\Entity\Update\Service;
use Magento\Staging\Model\Update\UpdateValidator;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveActionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Staging\Model\VersionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManager;

    /**
     * @var \Magento\Staging\Model\Entity\HydratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityHydrator;

    /**
     * @var EntityStaging|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityStaging;

    /**
     * @var Service|\PHPUnit_Framework_MockObject_MockObject
     */
    private $updateService;

    /**
     * @var \Magento\Staging\Api\UpdateRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $updateRepository;

    /**
     * @var \Magento\Framework\EntityManager\MetadataPool|\PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataPool;

    /**
     * @var \Magento\Staging\Model\UpdateFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $updateFactory;

    /**
     * @var SaveAction
     */
    private $saveAction;

    /** @var  UpdateValidator|\PHPUnit_Framework_MockObject_MockObject  */
    private $updateValidator;

    public function setUp()
    {
        $this->updateService = $this->getMockBuilder(Service::class)
            ->disableOriginalCOnstructor()
            ->getMock();

        $this->versionManager = $this->getMockBuilder(\Magento\Staging\Model\VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityHydrator = $this->getMockBuilder(\Magento\Staging\Model\Entity\HydratorInterface::class)
            ->getMockForAbstractClass();

        $this->entityStaging = $this->getMockBuilder(EntityStaging::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->updateRepository = $this->getMockBuilder(\Magento\Staging\Api\UpdateRepositoryInterface::class)
            ->getMockForAbstractClass();

        $this->metadataPool = $this->getMockBuilder(\Magento\Framework\EntityManager\MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateValidator = $this->getMockBuilder(UpdateValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateFactory = $this->getMockBuilder(\Magento\Staging\Model\UpdateFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->saveAction = new SaveAction(
            $this->updateService,
            $this->versionManager,
            $this->entityHydrator,
            $this->entityStaging,
            $this->updateRepository,
            $this->metadataPool,
            $this->updateFactory,
            $this->updateValidator
        );
    }

    /**
     * Checks the creation of new update
     *
     * @dataProvider createUpdateDataProvider
     * @param int $updateId
     */
    public function testExecuteCreateUpdate(
        $updateId
    ) {
        $newUpdateId = 32;
        $params = [
            'stagingData' => isset($updateId) ? ['update_id' => $updateId] : [],
            'entityData' => [],
        ];

        $updateMock = $this->getMockBuilder(\Magento\Staging\Model\Update::class)
            ->disableOriginalConstructor()
            ->getMock();
        $updateMock->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($newUpdateId);
        $updateMock->expects($this->once())
            ->method('setIsCampaign')
            ->with(false)
            ->willReturnSelf();

        $this->updateFactory->expects($this->once())
            ->method('create')
            ->willReturn($updateMock);

        $hydratorMock = $this->getMockBuilder(\Magento\Framework\EntityManager\HydratorInterface::class)
            ->getMockForAbstractClass();
        $hydratorMock->expects($this->once())
            ->method('hydrate')
            ->with($updateMock, $params['stagingData'])
            ->willReturnSelf();

        $this->metadataPool->expects($this->once())
            ->method('getHydrator')
            ->with(\Magento\Staging\Api\Data\UpdateInterface::class)
            ->willReturn($hydratorMock);

        $this->versionManager->expects($this->once())
            ->method('setCurrentVersionId')
            ->with($newUpdateId);

        $this->updateRepository->expects($this->once())
            ->method('save')
            ->willReturnSelf();

        $entity = new \stdClass();
        $this->entityHydrator->expects($this->once())
            ->method('hydrate')
            ->with($params['entityData'])
            ->willReturn($entity);
        $this->entityStaging->expects($this->once())
            ->method('schedule')
            ->with($entity, $newUpdateId, []);

        $this->assertTrue($this->saveAction->execute($params));
    }

    /**
     * Data required to test new update creation
     *
     * @return array
     */
    public function createUpdateDataProvider()
    {
        return [
            ['update_id' => null],
            ['update_id' => ''],
        ];
    }

    /**
     * Checks the editing of existed update for case when no 'EndTime' parameter was changed
     */
    public function testExecuteEditUpdateWithNoEndTimeChanged()
    {
        $updateId = 1;

        $startDateTime = new \DateTime();
        $startDateTime->add(new \DateInterval('P1D'));

        $endDateTime = new \DateTime();
        $endDateTime->add(new \DateInterval('P2D'));
        $currentEndDateTime = $endDateTime->format('Y-m-d H:i:s');

        $stagingData = [
            'update_id' => 1,
            'start_time' => 1,
            'end_time' => $currentEndDateTime,
        ];

        $params = [
            'stagingData' => $stagingData,
            'entityData' => [],
        ];

        $updateMock = $this->getMockBuilder(\Magento\Staging\Model\Update::class)
            ->disableOriginalConstructor()
            ->getMock();

        $updateMock->expects($this->any())
            ->method('getId')
            ->willReturn($updateId);

        $this->updateRepository->expects($this->atLeastOnce())
            ->method('get')
            ->with($updateId)
            ->willReturn($updateMock);

        $hydratorMock = $this->getMockBuilder(\Magento\Framework\EntityManager\HydratorInterface::class)
            ->getMockForAbstractClass();
        $hydratorMock->expects($this->once())
            ->method('hydrate')
            ->with($updateMock, $params['stagingData'])
            ->willReturnSelf();

        $this->metadataPool->expects($this->once())
            ->method('getHydrator')
            ->with(\Magento\Staging\Api\Data\UpdateInterface::class)
            ->willReturn($hydratorMock);

        $this->versionManager->expects($this->once())
            ->method('setCurrentVersionId')
            ->with($updateId);
        $this->updateRepository->expects($this->once())
            ->method('save')
            ->willReturnSelf();

        $this->assertTrue($this->saveAction->execute($params));
    }

    /**
     * Checks the editing of existed update for case when 'EndTime' parameter was changed
     */
    public function testExecuteEditUpdateWithEndTimeChanged()
    {
        $updateId = 1;

        $startDateTime = new \DateTime();
        $startDateTime->add(new \DateInterval('P1D'));

        $endDateTime = new \DateTime();
        $endDateTime->add(new \DateInterval('P2D'));

        $endDateTimeChanged = new \DateTime();
        $endDateTimeChanged->add(new \DateInterval('P3D'));
        $currentEndDateTimeChanged = $endDateTimeChanged->format('Y-m-d H:i:s');

        $stagingData = [
            'update_id' => 1,
            'start_time' => 1,
            'end_time' => $currentEndDateTimeChanged,
        ];

        $params = [
            'stagingData' => $stagingData,
            'entityData' => [],
        ];

        $updateMock = $this->getMockBuilder(\Magento\Staging\Model\Update::class)
            ->disableOriginalConstructor()
            ->getMock();
        $updateMock->expects($this->any())
            ->method('getId')
            ->willReturn($updateId);

        $this->updateRepository->expects($this->atLeastOnce())
            ->method('get')
            ->with($updateId)
            ->willReturn($updateMock);

        $hydratorMock = $this->getMockBuilder(\Magento\Framework\EntityManager\HydratorInterface::class)
            ->getMockForAbstractClass();

        $this->metadataPool->expects($this->once())
            ->method('getHydrator')
            ->with(\Magento\Staging\Api\Data\UpdateInterface::class)
            ->willReturn($hydratorMock);

        $this->updateRepository->expects($this->once())
            ->method('save')
            ->willReturnSelf();

        $entity = new \stdClass();
        $this->entityHydrator->expects($this->any())
            ->method('hydrate')
            ->with($params['entityData'])
            ->willReturn($entity);
        $this->entityStaging->expects($this->any())
            ->method('schedule')
            ->with($entity, $updateId, ['origin_in' => $updateId]);

        $this->assertTrue($this->saveAction->execute($params));
    }
}
