<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Test\Unit\Model\Entity\Update\Action\Save;

use Magento\Staging\Model\Entity\Update\Action\Save\AssignAction;
use Magento\Staging\Model\EntityStaging;

class AssignActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Staging\Controller\Adminhtml\Entity\Update\Service|\PHPUnit_Framework_MockObject_MockObject */
    protected $updateService;

    /** @var \Magento\Staging\Model\VersionManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $versionManager;

    /** @var \Magento\Staging\Model\Entity\HydratorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $hydrator;

    /** @var \Magento\Staging\Model\Update|\PHPUnit_Framework_MockObject_MockObject */
    protected $update;

    /** @var \Magento\Staging\Model\Entity\Update\CampaignUpdater|\PHPUnit_Framework_MockObject_MockObject */
    protected $campaignUpdater;

    /** @var AssignAction */
    protected $assignAction;

    /** @var EntityStaging|\PHPUnit_Framework_MockObject_MockObject */
    private $entityStaging;

    public function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->updateService = $this->getMockBuilder(\Magento\Staging\Controller\Adminhtml\Entity\Update\Service::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManager = $this->getMockBuilder(\Magento\Staging\Model\VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->hydrator = $this->getMockBuilder(\Magento\Staging\Model\Entity\HydratorInterface::class)
            ->getMockForAbstractClass();
        $this->update = $this->getMockBuilder(\Magento\Staging\Model\Update::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->campaignUpdater = $this->getMockBuilder(\Magento\Staging\Model\Entity\Update\CampaignUpdater::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityStaging = $this->getMockBuilder(EntityStaging::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assignAction = $objectManager->getObject(
            AssignAction::class,
            [
                'updateService' => $this->updateService,
                'versionManager' => $this->versionManager,
                'entityStaging' => $this->entityStaging,
                'entityHydrator' => $this->hydrator,
                'campaignUpdater' => $this->campaignUpdater
            ]
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The required parameter is "stagingData". Set parameter and try again.
     */
    public function testExecuteWithInvalidParams()
    {
        $this->assignAction->execute([]);
    }

    public function testExecuteReassign()
    {
        $params = [
            'stagingData' => [
                'update_id' => 100500
            ],
            'entityData' => [],
        ];

        $versionId = 32;
        $this->updateService->expects($this->once())
            ->method('assignUpdate')
            ->with(['update_id' => 100500])
            ->willReturn($this->update);
        $this->versionManager->expects($this->any())
            ->method('getVersion')
            ->willReturn($this->update);
        $this->update->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($versionId);
        $this->versionManager->expects($this->once())
            ->method('setCurrentVersionId')
            ->with($versionId);
        $entity = new \stdClass();
        $this->hydrator->expects($this->once())
            ->method('hydrate')
            ->with([])
            ->willReturn($entity);
        $this->entityStaging->expects($this->once())
            ->method('schedule')
            ->with($entity, $versionId, ['copy_origin_in' => 100500])
            ->willReturn(true);
        $this->campaignUpdater->expects($this->once())
            ->method('updateCampaignStatus')
            ->with($this->update);

        $this->assertTrue($this->assignAction->execute($params));
    }

    public function testExecuteAssign()
    {
        $params = [
            'stagingData' => [
                'update_id' => ''
            ],
            'entityData' => [],
        ];

        $versionId = 32;
        $this->updateService->expects($this->once())
            ->method('assignUpdate')
            ->with(['update_id' => ''])
            ->willReturn($this->update);
        $this->versionManager->expects($this->any())
            ->method('getVersion')
            ->willReturn($this->update);
        $this->update->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($versionId);
        $this->versionManager->expects($this->once())
            ->method('setCurrentVersionId')
            ->with($versionId);
        $entity = new \stdClass();
        $this->hydrator->expects($this->once())
            ->method('hydrate')
            ->with([])
            ->willReturn($entity);
        $this->entityStaging->expects($this->once())
            ->method('schedule')
            ->with($entity, $versionId, [])
            ->willReturn(true);
        $this->campaignUpdater->expects($this->once())
            ->method('updateCampaignStatus')
            ->with($this->update);

        $this->assertTrue($this->assignAction->execute($params));
    }
}
