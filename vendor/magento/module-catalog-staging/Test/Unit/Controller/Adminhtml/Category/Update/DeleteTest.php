<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Test\Unit\Controller\Adminhtml\Category\Update;

use Magento\CatalogStaging\Controller\Adminhtml\Category\Update\Delete as DeleteController;
use Magento\Staging\Model\VersionManager;

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
        $categoryId = 1;
        $updateId = 2;
        $staging = ['mode' => 'remove'];

        $this->request->method('getParam')
            ->willReturnMap([
                ['id', null, $categoryId],
                ['staging', null, $staging],
                ['update_id', null, $updateId],
            ]);
        $this->request->expects($this->once())
            ->method('setParams')
            ->with([VersionManager::PARAM_NAME => $updateId]);

        $this->stagingUpdateDelete
            ->expects($this->once())
            ->method('execute')
            ->with([
                'entityId' => $categoryId,
                'updateId' => $updateId,
                'stagingData' => $staging
            ])
            ->willReturn(true);
        $this->assertTrue($this->controller->execute());
    }
}
