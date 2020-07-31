<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Test\Unit\Model\Entity\Update\Action\Delete;

use Magento\Staging\Model\Entity\Update\Action\Delete\SaveAction;
use Magento\Staging\Model\EntityStaging;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveActionTest extends \PHPUnit\Framework\TestCase
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

    /** @var SaveAction */
    protected $saveAction;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $intersectionMock;

    /** @var EntityStaging|\PHPUnit_Framework_MockObject_MockObject */
    private $entityStaging;

    public function setUp()
    {
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
        $this->entityBuilder = $this->getMockBuilder(\Magento\Staging\Model\Entity\Builder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityStaging = $this->getMockBuilder(EntityStaging::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->saveAction = new SaveAction(
            $this->updateService,
            $this->versionManager,
            $this->retriever,
            $this->entityStaging,
            $this->messageManager,
            $this->entityBuilder,
            'entity name'
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The required parameter is "entityId". Set parameter and try again.
     */
    public function testExecuteWithInvalidParams()
    {
        $this->saveAction->execute([]);
    }

    public function testExecute()
    {
        $params = [
            'updateId' => 1,
            'entityId' => 3,
            'stagingData' => []
        ];
        $currentVersionId = 1;
        $oldVersionId = 31;
        $newVersionId = 32;
        $this->versionManager->expects($this->at(0))
            ->method('setCurrentVersionId')
            ->with($currentVersionId);
        $this->versionManager->expects($this->any())
            ->method('getVersion')
            ->willReturn($this->update);
        $this->updateService->expects($this->once())
            ->method('createUpdate')
            ->with([])
            ->willReturn($this->update);
        $this->update->expects($this->at(0))
            ->method('getId')
            ->willReturn($oldVersionId);
        $this->update->expects($this->at(1))
            ->method('getId')
            ->willReturn($newVersionId);
        $entity = new \stdClass();
        $this->retriever->expects($this->once())
            ->method('getEntity')
            ->with(3)
            ->willReturn($entity);
        $this->entityBuilder->expects($this->once())
            ->method('build')
            ->with($entity)
            ->willReturn($entity);
        $this->entityStaging->expects($this->once())
            ->method('schedule')
            ->with($entity, $newVersionId)
            ->willReturn(true);
        $this->messageManager->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('You removed this %1 from the update and saved it in a new one.', 'entity name'));
        $this->assertTrue($this->saveAction->execute($params));
    }
}
