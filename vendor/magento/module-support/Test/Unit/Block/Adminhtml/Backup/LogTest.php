<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Block\Adminhtml\Backup;

use \Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class LogTest extends \PHPUnit\Framework\TestCase
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
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \Magento\Support\Model\Backup|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $backupMock;

    /**
     * @var \Magento\Backend\Block\Widget\Context
     */
    protected $context;

    /**
     * @var \Magento\Support\Block\Adminhtml\Backup\Log
     */
    protected $logBlock;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->backupFactoryMock = $this->createPartialMock(\Magento\Support\Model\BackupFactory::class, ['create']);
        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->backupMock = $this->createMock(\Magento\Support\Model\Backup::class);

        $this->context = $this->objectManagerHelper->getObject(
            \Magento\Backend\Block\Widget\Context::class,
            [
                'request' => $this->requestMock
            ]
        );
        $this->logBlock = $this->objectManagerHelper->getObject(
            \Magento\Support\Block\Adminhtml\Backup\Log::class,
            [
                'context' => $this->context,
                'backupFactory' => $this->backupFactoryMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testGetHeaderText()
    {
        $headerText = __('Backup Log Details');
        $this->assertEquals($headerText, $this->logBlock->getHeaderText());
    }

    /**
     * @return void
     */
    public function testGetBackup()
    {
        $id = 1;
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id', 0)
            ->willReturn($id);

        $this->backupMock->expects($this->once())
            ->method('load')
            ->with($id)
            ->willReturnSelf();

        $this->backupFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->backupMock);

        $this->assertSame($this->backupMock, $this->logBlock->getBackup());
    }

    /**
     * @return void
     */
    public function testGetBackupWithSetBackup()
    {
        $this->requestMock->expects($this->never())->method('getParam');
        $this->backupMock->expects($this->never())->method('load');
        $this->backupFactoryMock->expects($this->never())->method('create');

        $this->logBlock->setBackup($this->backupMock);
        $this->assertSame($this->backupMock, $this->logBlock->getBackup());
    }
}
