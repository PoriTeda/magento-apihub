<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ScheduledImportExport\Test\Unit\Controller\Adminhtml\Scheduled\Operation;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\Controller\ResultFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\ScheduledImportExport\Controller\Adminhtml\Scheduled\Operation\Save */
    protected $save;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Backend\App\Action\Context
     */
    protected $context;

    /** @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var \Magento\Framework\App\Console\Request|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var \Magento\Framework\App\Console\Response|\PHPUnit_Framework_MockObject_MockObject */
    protected $response;

    /** @var \Magento\Framework\Message\Manager|\PHPUnit_Framework_MockObject_MockObject */
    protected $messageManager;

    /** @var \Magento\Backend\Model\Session|\PHPUnit_Framework_MockObject_MockObject */
    protected $session;

    /** @var \Magento\Framework\App\ActionFlag|\PHPUnit_Framework_MockObject_MockObject */
    protected $actionFlag;

    /** @var \Magento\Backend\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    protected $backendHelper;

    /** @var  \Magento\ScheduledImportExport\Model\Scheduled\Operation|\PHPUnit_Framework_MockObject_MockObject */
    protected $operation;

    /** @var \Magento\ScheduledImportExport\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    protected $scheduledHelper;

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
        $this->request = $this->createPartialMock(
            \Magento\Framework\App\Console\Request::class,
            ['getParam', 'isPost', 'getPostValue']
        );
        $this->messageManager = $this->createMock(\Magento\Framework\Message\Manager::class);
        $this->session = $this->createMock(\Magento\Backend\Model\Session::class);
        $this->actionFlag = $this->createMock(\Magento\Framework\App\ActionFlag::class);
        $this->backendHelper = $this->createMock(\Magento\Backend\Helper\Data::class);
        $this->registry = $this->createMock(\Magento\Framework\Registry::class);

        $operationFactory = $this->createPartialMock(
            \Magento\ScheduledImportExport\Model\Scheduled\OperationFactory::class,
            ['create']
        );
        $this->operation = $this->createMock(\Magento\ScheduledImportExport\Model\Scheduled\Operation::class);
        $operationFactory->expects($this->any())->method('create')->willReturn($this->operation);
        $this->scheduledHelper = $this->createMock(\Magento\ScheduledImportExport\Helper\Data::class);
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

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->context = $this->objectManagerHelper->getObject(
            \Magento\Backend\App\Action\Context::class,
            [
                'request' => $this->request,
                'messageManager' => $this->messageManager,
                'session' => $this->session,
                'actionFlag' => $this->actionFlag,
                'helper' => $this->backendHelper,
                'resultFactory' => $this->resultFactoryMock
            ]
        );
        $this->save = $this->objectManagerHelper->getObject(
            \Magento\ScheduledImportExport\Controller\Adminhtml\Scheduled\Operation\Save::class,
            [
                'context' => $this->context,
                'coreRegistry' => $this->registry,
                'operationFactory' => $operationFactory,
                'dataHelper' => $this->scheduledHelper
            ]
        );
    }

    public function testExecuteError()
    {
        $this->request->expects($this->once())->method('isPost')->willReturn(true);
        $this->request->expects($this->once())->method('getPostValue')->willReturn([]);
        $this->messageManager->expects($this->once())->method('addError');
        $this->messageManager->expects($this->never())->method('addSuccess');
        $this->assertSame($this->resultRedirectMock, $this->save->execute());
    }

    public function testExecuteSuccess()
    {
        $this->request->expects($this->once())->method('isPost')->willReturn(true);
        $this->request->expects($this->once())->method('getPostValue')->willReturn([
            'id' => 1,
            'operation_type' => 'sometype',
            'start_time' => [12, 15]
        ]);
        $this->operation->expects($this->once())->method('setData');
        $this->operation->expects($this->once())->method('save');
        $this->messageManager->expects($this->never())->method('addError');
        $successMessage = 'Some sucess message';
        $this->scheduledHelper->expects($this->once())->method('getSuccessSaveMessage')->willReturn(
            $successMessage
        );
        $this->messageManager->expects($this->once())->method('addSuccess')->with($successMessage);
        $this->assertSame($this->resultRedirectMock, $this->save->execute());
    }
}
