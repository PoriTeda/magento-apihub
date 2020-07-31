<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Test\Unit\Model\Entity\Update\Action\Delete;

use Magento\Staging\Model\Entity\Update\Action\Delete\AssignAction;
use Magento\Staging\Model\EntityStaging;

/**
 * Class AssignActionTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AssignActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Staging\Controller\Adminhtml\Entity\Update\Service|\PHPUnit_Framework_MockObject_MockObject */
    protected $updateService;

    /** @var \Magento\Staging\Model\VersionManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $versionManager;

    /** @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $messageManager;

    /** @var \Magento\Staging\Model\Entity\RetrieverInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $retriever;

    /** @var \Magento\Staging\Model\Update|\PHPUnit_Framework_MockObject_MockObject */
    protected $update;

    /** @var \Magento\Staging\Model\Entity\Update\CampaignUpdater|\PHPUnit_Framework_MockObject_MockObject */
    protected $campaignUpdater;

    /** @var AssignAction */
    protected $assignAction;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityBuilder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $entityHydratorMock;

    /** @var EntityStaging|\PHPUnit_Framework_MockObject_MockObject */
    private $entityStaging;

    public function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->assignAction = $this->getMockBuilder(AssignAction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateService = $this->getMockBuilder(\Magento\Staging\Controller\Adminhtml\Entity\Update\Service::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManager = $this->getMockBuilder(\Magento\Staging\Model\VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->retriever = $this->getMockBuilder(\Magento\Staging\Model\Entity\RetrieverInterface::class)
            ->getMockForAbstractClass();
        $this->update = $this->getMockBuilder(\Magento\Staging\Model\Update::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->campaignUpdater = $this->getMockBuilder(\Magento\Staging\Model\Entity\Update\CampaignUpdater::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityBuilder = $this->getMockBuilder(\Magento\Staging\Model\Entity\Builder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityStaging = $this->getMockBuilder(EntityStaging::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityHydratorMock = $this->createMock(\Magento\Staging\Model\Entity\HydratorInterface::class);
        $this->assignAction = $objectManager->getObject(
            AssignAction::class,
            [
                'updateService' => $this->updateService,
                'versionManager' => $this->versionManager,
                'entityStaging' => $this->entityStaging,
                'campaignUpdater' => $this->campaignUpdater,
                'messageManager' => $this->messageManager,
                'entityRetriever' => $this->retriever,
                'builder' => $this->entityBuilder,
                'entityName' => 'entity name'
            ]
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The required parameter is "entityId". Set parameter and try again.
     */
    public function testExecuteWithInvalidParams()
    {
        $this->assignAction->execute([]);
    }

    public function testExecute()
    {
        $params = [
            'updateId' => 1,
            'entityId' => 3,
            'stagingData' => [],
        ];
        $oldUpdateId = 32;
        $newUpdateId = 34;
        $this->versionManager->expects($this->exactly(2))
            ->method('setCurrentVersionId');
        $this->versionManager->expects($this->any())
            ->method('getVersion')
            ->willReturn($this->update);
        $entity = new \stdClass();
        $this->retriever->expects($this->once())
            ->method('getEntity')
            ->with(3)
            ->willReturn($entity);
        $this->updateService->expects($this->once())
            ->method('assignUpdate')
            ->with([])
            ->willReturn($this->update);
        $this->update->expects($this->exactly(2))
            ->method('getId')
            ->willReturnMap(
                [
                    [$oldUpdateId],
                    [$newUpdateId],
                ]
            );
        $this->entityBuilder->expects($this->once())
            ->method('build')
            ->with($entity)
            ->willReturn($entity);
        $this->campaignUpdater->expects($this->once())
            ->method('updateCampaignStatus')
            ->with($this->update);
        $this->messageManager->expects($this->once())
            ->method('addSuccess')
            ->with(__('You removed this %1 from the update and saved it in the other one.', 'entity name'));
        $this->entityStaging->expects($this->once())
            ->method('schedule')
            ->willReturn(true);

        $this->assertTrue($this->assignAction->execute($params));
    }

    public function testExecuteWithEntityData()
    {
        $params = [
            'updateId' => 1,
            'entityId' => 3,
            'stagingData' => [],
            'entityData' => [],
        ];
        $newUpdateId = 34;
        $this->versionManager->expects($this->exactly(2))
            ->method('setCurrentVersionId');
        $this->versionManager->expects($this->any())
            ->method('getVersion')
            ->willReturn($this->update);
        $entity = new \stdClass();
        $this->retriever->expects($this->once())
            ->method('getEntity')
            ->with(3)
            ->willReturn($entity);
        $this->updateService->expects($this->once())
            ->method('assignUpdate')
            ->with([])
            ->willReturn($this->update);
        $this->update->expects($this->exactly(1))
            ->method('getId')
            ->willReturnMap(
                [
                    [$newUpdateId]
                ]
            );
        $this->entityBuilder->expects($this->once())
            ->method('build')
            ->with($entity)
            ->willReturn($entity);
        $this->campaignUpdater->expects($this->never())
            ->method('updateCampaignStatus');
        $this->messageManager->expects($this->never())
            ->method('addSuccess');

        $this->assertTrue($this->assignAction->execute($params));
    }
}
