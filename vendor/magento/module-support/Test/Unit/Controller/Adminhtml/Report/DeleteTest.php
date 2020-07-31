<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Controller\Adminhtml\Report;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;

class DeleteTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Support\Model\ReportFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $reportFactoryMock;

    /**
     * @var \Magento\Support\Model\Report|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $reportMock;

    /**
     * @var \Magento\Framework\Controller\ResultFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var \Magento\Backend\Model\View\Result\Redirect|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultRedirectMock;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManagerMock;

    /**
     * @var \Magento\Backend\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Support\Controller\Adminhtml\Report\Delete
     */
    protected $deleteAction;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->reportMock = $this->createMock(\Magento\Support\Model\Report::class);

        $this->reportFactoryMock = $this->createPartialMock(\Magento\Support\Model\ReportFactory::class, ['create']);
        $this->reportFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->reportMock);

        $this->resultRedirectMock = $this->createMock(\Magento\Backend\Model\View\Result\Redirect::class);
        $this->resultRedirectMock->expects($this->once())
            ->method('setPath')
            ->with('*/*/index')
            ->willReturnSelf();
        $this->resultFactoryMock = $this->createMock(\Magento\Framework\Controller\ResultFactory::class);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->resultRedirectMock);

        $this->messageManagerMock = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);

        $this->contextMock = $this->createMock(\Magento\Backend\App\Action\Context::class);
        $this->contextMock->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);
        $this->contextMock->expects($this->any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->deleteAction = $this->objectManagerHelper->getObject(
            \Magento\Support\Controller\Adminhtml\Report\Delete::class,
            [
                'context' => $this->contextMock,
                'reportFactory' => $this->reportFactoryMock
            ]
        );
    }

    /**
     * @param int $id
     * @return void
     */
    protected function setIdReportForTests($id)
    {
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id', 0)
            ->willReturn($id);
        $this->reportMock->expects($this->once())
            ->method('load')
            ->with($id)
            ->willReturnSelf();
        $this->reportMock->expects($this->once())
            ->method('getId')
            ->willReturn($id);
    }

    /**
     * @return void
     */
    public function testExecuteWithoutReport()
    {
        $this->setIdReportForTests(0);

        $this->reportMock->expects($this->never())
            ->method('delete');

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('Unable to find a system report to delete.'))
            ->willReturnSelf();

        $this->assertSame($this->resultRedirectMock, $this->deleteAction->execute());
    }

    /**
     * @return void
     */
    public function testExecuteMainFlow()
    {
        $this->setIdReportForTests(1);
        $this->reportMock->expects($this->once())
            ->method('delete');

        $this->messageManagerMock->expects($this->never())
            ->method('addError')
            ->willReturnSelf();
        $this->messageManagerMock->expects($this->once())
            ->method('addSuccess')
            ->with(__('The system report has been deleted.'))
            ->willReturnSelf();

        $this->assertSame($this->resultRedirectMock, $this->deleteAction->execute());
    }

    /**
     * @return void
     */
    public function testExecuteGetWithLocalizedException()
    {
        $errorMsg = 'Test error';
        $this->setIdReportForTests(1);
        $this->reportMock->expects($this->once())
            ->method('delete')
            ->willThrowException(new LocalizedException(__($errorMsg)));
        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with($errorMsg)
            ->willReturnSelf();
        $this->assertSame($this->resultRedirectMock, $this->deleteAction->execute());
    }

    /**
     * @return void
     */
    public function testExecuteGetWithException()
    {
        $e = new \Exception();
        $this->setIdReportForTests(1);
        $this->reportMock->expects($this->once())
            ->method('delete')
            ->willThrowException($e);
        $this->messageManagerMock->expects($this->once())
            ->method('addException')
            ->with(
                $e,
                __('An error occurred while deleting the system report. Please review log and try again.')
            )
            ->willReturnSelf();
        $this->assertSame($this->resultRedirectMock, $this->deleteAction->execute());
    }
}
