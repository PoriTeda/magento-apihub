<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScheduledImportExport\Test\Unit\Controller\Adminhtml\Scheduled\Operation;

use Magento\Framework\Controller\ResultFactory;

class CronTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Controller\ResultFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var \Magento\Backend\Model\View\Result\Redirect|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultRedirectMock;

    protected function setUp()
    {
        $this->resultFactoryMock = $this->getMockBuilder(\Magento\Framework\Controller\ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectMock = $this->getMockBuilder(\Magento\Backend\Model\View\Result\Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT, [])
            ->willReturn($this->resultRedirectMock);
    }

    public function testCronActionFrontendAreaIsSetToDesignBeforeProcessOperation()
    {
        $designTheme = 'Magento/blank';

        $observer = $this->createPartialMock(
            \Magento\ScheduledImportExport\Model\Observer::class,
            ['processScheduledOperation']
        );

        $theme = $this->createMock(\Magento\Theme\Model\Theme::class);

        $design = $this->createPartialMock(
            \Magento\Theme\Model\View\Design::class,
            ['getArea', 'getDesignTheme', 'getConfigurationDesignTheme', 'setDesignTheme']
        );
        $design->expects($this->once())->method('getArea')
            ->willReturn('adminhtml');
        $design->expects($this->once())->method('getDesignTheme')
            ->willReturn($theme);
        $design->expects($this->once())->method('getConfigurationDesignTheme')
            ->with($this->equalTo(\Magento\Framework\App\Area::AREA_FRONTEND))
            ->willReturn($designTheme);

        $design->expects($this->at(3))->method('setDesignTheme')
            ->with($this->equalTo($designTheme), $this->equalTo(\Magento\Framework\App\Area::AREA_FRONTEND));
        $design->expects($this->at(4))->method('setDesignTheme')
            ->with($this->equalTo($theme), $this->equalTo('adminhtml'));

        $request = $this->createPartialMock(\Magento\Framework\App\Console\Request::class, ['getParam']);
        $request->expects($this->once())->method('getParam')
            ->with($this->equalTo('operation'))
            ->willReturn('2');

        $objectManagerMock = $this->createPartialMock(\Magento\Framework\ObjectManager\ObjectManager::class, ['get']);
        $objectManagerMock->expects($this->at(0))->method('get')
            ->with($this->equalTo(\Magento\Framework\View\DesignInterface::class))
            ->willReturn($design);
        $objectManagerMock->expects($this->at(1))->method('get')
            ->with($this->equalTo(\Magento\ScheduledImportExport\Model\Observer::class))
            ->willReturn($observer);

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $context = $objectManager->getObject(
            \Magento\Backend\App\Action\Context::class,
            [
                'request' => $request,
                'objectManager' => $objectManagerMock,
                'resultFactory' => $this->resultFactoryMock
            ]
        );

        /** @var \Magento\ScheduledImportExport\Controller\Adminhtml\Scheduled\Operation $instance */
        $instance = $objectManager->getObject(
            \Magento\ScheduledImportExport\Controller\Adminhtml\Scheduled\Operation\Cron::class,
            ['context' => $context]
        );

        $this->assertSame($this->resultRedirectMock, $instance->execute());
    }
}
