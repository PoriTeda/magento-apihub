<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Test\Unit\Controller\Adminhtml\Category\Update;

use Magento\CatalogStaging\Controller\Adminhtml\Category\Update\Save as SaveController;

class SaveTest extends \PHPUnit\Framework\TestCase
{
    /** @var SaveController */
    protected $controller;

    /** @var \Magento\Backend\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var \Magento\Staging\Model\Entity\Update\Save|\PHPUnit_Framework_MockObject_MockObject */
    protected $stagingUpdateSave;

    /** @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManager;

    public function setUp()
    {
        $this->context = $this->getMockBuilder(\Magento\Backend\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->setMethods([
                'getPostValue',
                'getParam',
                'getModuleName',
                'setModuleName',
                'getActionName',
                'setActionName',
                'setParams',
                'getParams',
                'getCookie',
                'isSecure',
            ])
            ->getMock();
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->stagingUpdateSave = $this->getMockBuilder(\Magento\Staging\Model\Entity\Update\Save::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->setMethods(['getCode', 'setCurrentStore', 'getStore'])
            ->getMockForAbstractClass();
        $this->controller = new SaveController($this->context, $this->stagingUpdateSave, $this->storeManager);
    }

    public function testExecute()
    {
        $categoryId = 1;
        $entityData = [];
        $staging = [];
        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(
                ['entity_id'],
                ['staging']
            )
            ->willReturnOnConsecutiveCalls(
                $categoryId,
                $staging
            );
        $this->request->expects($this->once())
            ->method('getPostValue')
            ->willReturn($entityData);
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturnSelf();

        $this->stagingUpdateSave
            ->expects($this->once())
            ->method('execute')
            ->with([
                'entityId' => $categoryId,
                'stagingData' => $staging,
                'entityData' => $entityData
            ])
            ->willReturn(true);
        $this->assertTrue($this->controller->execute());
    }
}
