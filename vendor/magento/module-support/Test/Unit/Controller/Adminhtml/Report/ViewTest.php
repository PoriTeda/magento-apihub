<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Controller\Adminhtml\Report;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ViewTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Support\Controller\Adminhtml\Report\View
     */
    protected $viewAction;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Support\Model\ReportFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $reportFactoryMock;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $coreRegistryMock;

    /**
     * @var \Magento\Support\Model\DataFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataFormatterMock;

    /**
     * @var \Magento\Support\Model\Report|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $reportMock;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManagerMock;

    /**
     * @var \Magento\Framework\Controller\ResultFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var \Magento\Backend\Model\View\Result\Redirect|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultRedirectMock;

    /**
     * @var \Magento\Backend\Model\View\Result\Page|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultPageMock;

    /**
     * @var \Magento\Backend\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $timeZoneMock;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->messageManagerMock = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);
        $this->dataFormatterMock = $this->createMock(\Magento\Support\Model\DataFormatter::class);

        $this->reportMock = $this->getMockBuilder(\Magento\Support\Model\Report::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCreatedAt', 'load', 'getId'])
            ->getMock();
        $this->reportFactoryMock = $this->createPartialMock(\Magento\Support\Model\ReportFactory::class, ['create']);
        $this->reportFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->reportMock);

        $this->resultPageMock = $this->createMock(\Magento\Backend\Model\View\Result\Page::class);
        $this->resultRedirectMock = $this->createMock(\Magento\Backend\Model\View\Result\Redirect::class);
        $this->resultRedirectMock->expects($this->any())
            ->method('setPath')
            ->with('*/*/index')
            ->willReturnSelf();
        $this->resultFactoryMock = $this->createMock(\Magento\Framework\Controller\ResultFactory::class);
        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnMap([
                [ResultFactory::TYPE_PAGE, [], $this->resultPageMock],
                [ResultFactory::TYPE_REDIRECT, [], $this->resultRedirectMock]
            ]);
        $this->timeZoneMock = $this->createMock(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class);

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

        $this->viewAction = $this->objectManagerHelper->getObject(
            \Magento\Support\Controller\Adminhtml\Report\View::class,
            [
                'context' => $this->contextMock,
                'reportFactory' => $this->reportFactoryMock,
                'dataFormatter' => $this->dataFormatterMock,
                'timeZone' => $this->timeZoneMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecuteMainFlow()
    {
        $id = 1;
        $dateString = '01.01.1970 00:01';
        $sinceTimeString = '[1 minute ago]';
        $this->timeZoneMock->expects($this->once())->method('formatDateTime')->willReturn($dateString);
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($id);

        $this->reportMock->expects($this->once())
            ->method('getId')
            ->willReturn($id);
        $this->reportMock->expects($this->once())
            ->method('load')
            ->with($id);
        $this->reportMock->expects($this->once())
            ->method('getCreatedAt')
            ->willReturn($dateString);

        $this->resultPageMock->expects($this->once())
            ->method('setActiveMenu')
            ->with('Magento_Support::support_report')
            ->willReturnSelf();

        $this->dataFormatterMock->expects($this->once())
            ->method('getSinceTimeString')
            ->with($dateString)
            ->willReturn($sinceTimeString);

        /** @var \Magento\Framework\View\Page\Title|\PHPUnit_Framework_MockObject_MockObject $titleMock */
        $titleMock = $this->createMock(\Magento\Framework\View\Page\Title::class);
        $titleMock->expects($this->once())
            ->method('prepend')
            ->with($dateString . ' ' . $sinceTimeString);

        /** @var \Magento\Framework\View\Page\Config|\PHPUnit_Framework_MockObject_MockObject $configMock */
        $configMock = $this->createMock(\Magento\Framework\View\Page\Config::class);
        $configMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($titleMock);

        $this->resultPageMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($configMock);

        $this->assertSame($this->resultPageMock, $this->viewAction->execute());
    }

    /**
     * @return void
     */
    public function testExecuteWithoutReport()
    {
        $id = 0;
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($id);

        $this->reportMock->expects($this->once())
            ->method('getId')
            ->willReturn($id);
        $this->reportMock->expects($this->never())->method('load');

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('Requested system report no longer exists.'))
            ->willReturnSelf();

        $this->assertSame($this->resultRedirectMock, $this->viewAction->execute());
    }

    /**
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $e = new LocalizedException(__('Test error'));
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->willThrowException($e);
        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with($e)
            ->willReturnSelf();
        $this->assertSame($this->resultRedirectMock, $this->viewAction->execute());
    }

    /**
     * @return void
     */
    public function testExecuteWithException()
    {
        $errorMsg = 'Test error';
        $exception = new \Exception($errorMsg);
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->willThrowException($exception);
        $this->messageManagerMock->expects($this->once())
            ->method('addException')
            ->with($exception, __('Unable to read system report data to display.'))
            ->willReturnSelf();
        $this->assertSame($this->resultRedirectMock, $this->viewAction->execute());
    }
}
