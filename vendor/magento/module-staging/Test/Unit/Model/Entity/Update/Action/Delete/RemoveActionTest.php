<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Test\Unit\Model\Entity\Update\Action\Delete;

use Magento\Staging\Model\Entity\Update\Action\Delete\RemoveAction;
use Magento\Staging\Model\EntityStaging;

class RemoveActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var RemoveAction */
    protected $removeAction;

    /** @var \Magento\Staging\Model\VersionManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $versionManager;

    /** @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $messageManager;

    /** @var \Magento\Staging\Model\Entity\RetrieverInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $retriever;

    /** @var \Magento\Staging\Model\Entity\RemoverInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $remover;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $update;

    /** @var EntityStaging|\PHPUnit_Framework_MockObject_MockObject */
    private $entityStaging;

    public function setUp()
    {
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
        $this->entityStaging = $this->getMockBuilder(EntityStaging::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->removeAction = new RemoveAction(
            $this->versionManager,
            $this->messageManager,
            $this->entityStaging,
            $this->retriever,
            'entity name'
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The required parameter is "entityId". Set parameter and try again.
     */
    public function testExecuteWithInvalidParams()
    {
        $this->removeAction->execute([]);
    }

    public function testExecute()
    {
        $params = [
            'updateId' => 1,
            'entityId' => 4
        ];
        $versionId = 1;
        $this->versionManager->expects($this->at(0))
            ->method('setCurrentVersionId')
            ->with(1);
        $this->versionManager->expects($this->any())
            ->method('getVersion')
            ->willReturn($this->update);
        $this->update->expects($this->any())
            ->method('getId')
            ->willReturn($versionId);
        $entity = new \stdClass();
        $this->retriever->expects($this->once())
            ->method('getEntity')
            ->with(4)
            ->willReturn($entity);
        $this->messageManager->expects($this->once())
            ->method('addSuccess')
            ->with(__('You removed this %1 from the update.', 'entity name'));
        $this->entityStaging->expects($this->once())
            ->method('unschedule')
            ->with($entity, $versionId)
            ->willReturn(true);
        $this->assertTrue($this->removeAction->execute($params));
    }
}
