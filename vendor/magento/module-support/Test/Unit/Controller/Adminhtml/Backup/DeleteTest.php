<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Controller\Adminhtml\Backup;

use \Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class DeleteTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Support\Model\BackupFactory|\PHPUnit_Framework_MockObject_MockObject;
     */
    protected $backupFactoryMock;

    /**
     * @var \Magento\Support\Model\Backup|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $backupMock;

    /**
     * @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManagerMock;

    /**
     * @var \Magento\Backend\Model\View\Result\Redirect|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $redirectMock;

    /**
     * @var \Magento\Framework\Controller\ResultFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \Magento\Support\Controller\Adminhtml\Backup\Delete
     */
    protected $deleteAction;

    /**
     * @var \Magento\Backend\App\Action\Context
     */
    protected $context;

    /**
     * @var int
     */
    protected $id = 1;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->messageManagerMock = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);
        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id', 0)
            ->willReturn($this->id);

        $this->backupMock = $this->createMock(\Magento\Support\Model\Backup::class);
        $this->backupMock->expects($this->once())
            ->method('load')
            ->with($this->id)
            ->willReturnSelf();
        $this->backupFactoryMock = $this->createPartialMock(\Magento\Support\Model\BackupFactory::class, ['create']);
        $this->backupFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->backupMock);

        $this->redirectMock = $this->createMock(\Magento\Backend\Model\View\Result\Redirect::class);
        $this->redirectMock->expects($this->once())
            ->method('setPath')
            ->with('*/*/index')
            ->willReturnSelf();
        $this->resultFactoryMock = $this->createMock(\Magento\Framework\Controller\ResultFactory::class);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT, [])
            ->willReturn($this->redirectMock);

        $this->context = $this->objectManagerHelper->getObject(
            \Magento\Backend\App\Action\Context::class,
            [
                'messageManager' => $this->messageManagerMock,
                'resultFactory' => $this->resultFactoryMock,
                'request' => $this->requestMock
            ]
        );
        $this->deleteAction = $this->objectManagerHelper->getObject(
            \Magento\Support\Controller\Adminhtml\Backup\Delete::class,
            [
                'context' => $this->context,
                'backupFactory' => $this->backupFactoryMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $this->backupMock->expects($this->once())
            ->method('getId')
            ->willReturn($this->id);
        $this->backupMock->expects($this->once())
            ->method('delete')
            ->willReturnSelf();
        $this->messageManagerMock->expects($this->once())
            ->method('addSuccess')
            ->with(__('The backup has been deleted.'))
            ->willReturnSelf();

        $this->assertSame($this->redirectMock, $this->deleteAction->execute());
    }

    /**
     * @return void
     */
    public function testExecuteWrongId()
    {
        $this->backupMock->expects($this->once())
            ->method('getId')
            ->willReturn(0);
        $this->backupMock->expects($this->never())->method('delete');
        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('Wrong param id'))
            ->willReturnSelf();

        $this->assertSame($this->redirectMock, $this->deleteAction->execute());
    }

    /**
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $eText = 'Some error';
        $e = new \Magento\Framework\Exception\LocalizedException(__($eText));
        $this->backupMock->expects($this->once())
            ->method('getId')
            ->willReturn($this->id);
        $this->backupMock->expects($this->once())
            ->method('delete')
            ->willThrowException($e);
        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with($eText)
            ->willReturnSelf();

        $this->assertSame($this->redirectMock, $this->deleteAction->execute());
    }

    /**
     * @return void
     */
    public function testExecuteWithException()
    {
        $e = new \Exception();
        $this->backupMock->expects($this->once())
            ->method('getId')
            ->willReturn($this->id);
        $this->backupMock->expects($this->once())
            ->method('delete')
            ->willThrowException($e);
        $this->messageManagerMock->expects($this->once())
            ->method('addException')
            ->with($e, __('Cannot delete backup'))
            ->willReturnSelf();

        $this->assertSame($this->redirectMock, $this->deleteAction->execute());
    }
}
