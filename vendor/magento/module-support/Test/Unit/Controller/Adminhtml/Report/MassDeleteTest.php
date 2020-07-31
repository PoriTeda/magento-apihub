<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Support\Test\Unit\Controller\Adminhtml\Report;

class MassDeleteTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Support\Controller\Adminhtml\Report\MassDelete */
    protected $massDeleteController;

    /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager */
    protected $objectManager;

    /** @var \Magento\Backend\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextMock;

    /** @var \Magento\Framework\Controller\ResultFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $resultFactory;

    /** @var \Magento\Backend\Model\View\Result\Redirect|\PHPUnit_Framework_MockObject_MockObject */
    protected $resultRedirect;

    /** @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $messageManagerMock;

    /** @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestMock;

    /** @var \Magento\Framework\ObjectManager\ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $objectManagerMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject $pageMock */
    protected $reportCollectionMock;

    /** @var \Magento\Support\Model\Report|\PHPUnit_Framework_MockObject_MockObject */
    protected $modelMock;

    protected $reportId = '1';

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->messageManagerMock = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);

        $this->requestMock = $this->getMockForAbstractClass(
            \Magento\Framework\App\RequestInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getParam']
        );

        $this->modelMock = $this->getMockBuilder(\Magento\Framework\ObjectManager\ObjectManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'delete'])
            ->getMock();

        $this->reportCollectionMock = $this->getMockBuilder(
            \Magento\Support\Model\ResourceModel\Report\Collection::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getAllIds'])
            ->getMock();

        $this->objectManagerMock = $this->getMockBuilder(\Magento\Framework\ObjectManager\ObjectManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $this->resultFactory = $this->getMockBuilder(\Magento\Framework\Controller\ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->resultRedirect = $this->getMockBuilder(\Magento\Backend\Model\View\Result\Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory->expects($this->atLeastOnce())
            ->method('create')
            ->with(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->resultRedirect);

        $this->contextMock = $this->createMock(\Magento\Backend\App\Action\Context::class);

        $this->contextMock->expects($this->any())->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->expects($this->any())->method('getMessageManager')->willReturn($this->messageManagerMock);
        $this->contextMock->expects($this->any())->method('getObjectManager')->willReturn($this->objectManagerMock);
        $this->contextMock->expects($this->once())->method('getResultFactory')->willReturn($this->resultFactory);

        $this->massDeleteController = $this->objectManager->getObject(
            \Magento\Support\Controller\Adminhtml\Report\MassDelete::class,
            [
                'context' => $this->contextMock,
            ]
        );
    }

    /**
     * @return void
     */
    public function testSelectedDelete()
    {
        $selected = ['1'];
        $count = 1;

        $this->requestMock->expects($this->atLeastOnce())->method('getParam')->willReturnMap(
            [
                ['selected', null, $selected],
                ['excluded', null, null]
            ]
        );

        $this->objectManagerMock->expects($this->atLeastOnce())->method('get')->willReturnMap(
            [
                [\Magento\Support\Model\ResourceModel\Report\Collection::class, $this->reportCollectionMock],
                [\Magento\Support\Model\Report::class, $this->modelMock]
            ]
        );

        $this->reportCollectionMock->expects($this->once())->method('addFieldToFilter')->with(
            'report_id',
            ['in' => $selected]
        );
        $this->reportCollectionMock->expects($this->once())->method('getAllIds')->willReturn($selected);

        $this->modelMock->expects($this->once())->method('load')->with($this->reportId)->willReturnSelf();
        $this->modelMock->expects($this->once())->method('delete')->willReturnSelf();

        $this->messageManagerMock->expects($this->once())
            ->method('addSuccess')
            ->with(__('A total of %1 record(s) have been deleted.', $count))
            ->willReturnSelf();
        $this->messageManagerMock->expects($this->never())
            ->method('addError');

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('*/*/')
            ->willReturnSelf();

        $this->assertSame($this->resultRedirect, $this->massDeleteController->execute());
    }

    /**
     * @return void
     */
    public function testExcludedDelete()
    {
        $excluded = ['2'];
        $selected = ['1', '3'];
        $count = 2;

        $this->requestMock->expects($this->atLeastOnce())->method('getParam')->willReturnMap(
            [
                ['selected', null, null],
                ['excluded', null, $excluded]
            ]
        );

        $this->objectManagerMock->expects($this->atLeastOnce())->method('get')->willReturnMap(
            [
                [\Magento\Support\Model\ResourceModel\Report\Collection::class, $this->reportCollectionMock],
                [\Magento\Support\Model\Report::class, $this->modelMock]
            ]
        );

        $this->reportCollectionMock->expects($this->once())->method('addFieldToFilter')->with(
            'report_id',
            ['nin' => $excluded]
        );
        $this->reportCollectionMock->expects($this->once())->method('getAllIds')->willReturn($selected);

        $this->modelMock->expects($this->atLeastOnce())->method('load')->willReturnSelf();
        $this->modelMock->expects($this->atLeastOnce())->method('delete')->willReturnSelf();

        $this->messageManagerMock->expects($this->once())
            ->method('addSuccess')
            ->with(__('A total of %1 record(s) have been deleted.', $count))
            ->willReturnSelf();
        $this->messageManagerMock->expects($this->never())
            ->method('addError');

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('*/*/')
            ->willReturnSelf();

        $this->assertSame($this->resultRedirect, $this->massDeleteController->execute());
    }

    /**
     * @return void
     */
    public function testDeleteAll()
    {
        $ids = ['1', '2', '3'];
        $count = 3;

        $this->requestMock->expects($this->atLeastOnce())->method('getParam')->willReturnMap(
            [
                ['selected', null, null],
                ['excluded', null, 'false']
            ]
        );

        $this->objectManagerMock->expects($this->atLeastOnce())->method('get')->willReturnMap(
            [
                [\Magento\Support\Model\ResourceModel\Report\Collection::class, $this->reportCollectionMock],
                [\Magento\Support\Model\Report::class, $this->modelMock]
            ]
        );

        $this->reportCollectionMock->expects($this->once())->method('getAllIds')->willReturn($ids);

        $this->modelMock->expects($this->atLeastOnce())->method('load')->willReturnSelf();
        $this->modelMock->expects($this->atLeastOnce())->method('delete')->willReturnSelf();

        $this->messageManagerMock->expects($this->once())
            ->method('addSuccess')
            ->with(__('A total of %1 record(s) have been deleted.', $count))
            ->willReturnSelf();
        $this->messageManagerMock->expects($this->never())
            ->method('addError');

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('*/*/')
            ->willReturnSelf();

        $this->assertSame($this->resultRedirect, $this->massDeleteController->execute());
    }

    /**
     * @return void
     */
    public function testNoItemsSelected()
    {
        $this->requestMock->expects($this->atLeastOnce())->method('getParam')->willReturnMap(
            [
                ['selected', null, null],
                ['excluded', null, null]
            ]
        );

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('An item needs to be selected. Select and try again.'))
            ->willReturnSelf();

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('*/*/')
            ->willReturnSelf();

        $this->assertSame($this->resultRedirect, $this->massDeleteController->execute());
    }

    /**
     * @return void
     */
    public function testExecuteThrowsException()
    {
        $exception = new \Exception(
            __('An error occurred during mass deletion of system reports. Please review log and try again.')
        );

        $this->requestMock->expects($this->atLeastOnce())->method('getParam')->willReturnMap(
            [
                ['selected', null, null],
                ['excluded', null, 'false']
            ]
        );

        $this->objectManagerMock->expects($this->atLeastOnce())->method('get')->willThrowException($exception);

        $this->messageManagerMock->expects($this->once())
            ->method('addException')
            ->with(
                $exception,
                __('An error occurred during mass deletion of system reports. Please review log and try again.')
            )->willReturnSelf();
        $this->messageManagerMock->expects($this->never())->method('addSuccess');

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('*/*/')
            ->willReturnSelf();

        $this->assertSame($this->resultRedirect, $this->massDeleteController->execute());
    }
}
