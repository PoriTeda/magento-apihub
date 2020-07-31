<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CmsStaging\Test\Unit\Controller\Adminhtml\Block\Update;

use Magento\CmsStaging\Controller\Adminhtml\Block\Update\Delete as DeleteController;

class DeleteTest extends \PHPUnit\Framework\TestCase
{
    /** @var DeleteController */
    protected $controller;

    /** @var \Magento\Backend\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var \Magento\Staging\Model\Entity\Update\Delete|\PHPUnit_Framework_MockObject_MockObject */
    protected $stagingUpdateDelete;

    /** @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    public function setUp()
    {
        $this->context = $this->getMockBuilder(\Magento\Backend\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMock();
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->stagingUpdateDelete = $this->getMockBuilder(\Magento\Staging\Model\Entity\Update\Delete::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->controller = new DeleteController($this->context, $this->stagingUpdateDelete);
    }

    public function testExecute()
    {
        $blockId = 1;
        $updateId = 2;
        $staging = [];
        $this->request->expects($this->exactly(3))
            ->method('getParam')
            ->withConsecutive(
                ['block_id'],
                ['update_id'],
                ['staging']
            )
            ->willReturnOnConsecutiveCalls(
                $blockId,
                $updateId,
                $staging
            );
        $this->stagingUpdateDelete
            ->expects($this->once())
            ->method('execute')
            ->with([
                'entityId' => $blockId,
                'updateId' => $updateId,
                'stagingData' => $staging
            ])
            ->willReturn(true);
        $this->assertTrue($this->controller->execute());
    }
}
