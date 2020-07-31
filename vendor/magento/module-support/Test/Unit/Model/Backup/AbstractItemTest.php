<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Backup;

use \Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use \Magento\Support\Model\Backup\AbstractItem;

abstract class AbstractItemTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Support\Model\Backup\AbstractItem
     */
    protected $item;

    /**
     * @var \Magento\Support\Model\BackupFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $backupFactoryMock;

    /**
     * @var \Magento\Support\Model\Backup|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $backupMock;

    /**
     * @var \Magento\Support\Model\Backup\Cmd\Php|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cmdPhpMock;

    /**
     * @var \Magento\Support\Model\Backup\Cmd\PhpFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cmdPhpFactoryMock;

    /**
     * @var \Magento\Support\Helper\Shell|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shellHelperMock;

    /**
     * @var \Magento\Framework\Filesystem\Directory\Write|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $directoryMock;

    /**
     * @var \Magento\Framework\Filesystem|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $filesystemMock;

    /**
     * @var \Magento\Support\Model\ResourceModel\Backup\Item|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceMock;

    /**
     * @var int
     */
    protected $backupId = 1;

    /**
     * @var string
     */
    protected $backupName = 'someBackup';

    /**
     * @var string
     */
    protected $backupExtension = 'sql.gz';

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->fileName = $this->backupName . '.' . $this->backupExtension;
        $this->filePath = '/var/tmp/' . $this->fileName;

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->backupMock = $this->createMock(\Magento\Support\Model\Backup::class);
        $this->backupFactoryMock = $this->createPartialMock(\Magento\Support\Model\BackupFactory::class, ['create']);
        $this->cmdPhpMock = $this->getMockBuilder(\Magento\Support\Model\Backup\Cmd\Php::class)
            ->setMethods(['setName', 'setOutput', 'setScriptInterpreter', 'setScriptName', 'generate'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->cmdPhpFactoryMock = $this->getMockBuilder(\Magento\Support\Model\Backup\Cmd\PhpFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->shellHelperMock = $this->createMock(\Magento\Support\Helper\Shell::class);
        $this->directoryMock = $this->createMock(\Magento\Framework\Filesystem\Directory\Write::class);
        $this->resourceMock = $this->createMock(\Magento\Support\Model\ResourceModel\Backup\Item::class);
        $this->filesystemMock = $this->createMock(\Magento\Framework\Filesystem::class);
        $this->filesystemMock->expects($this->once())
            ->method('getDirectoryWrite')
            ->with(\Magento\Framework\App\Filesystem\DirectoryList::ROOT)
            ->willReturn($this->directoryMock);

        $this->createTestedItem();
        $this->item->setBackupId($this->backupId);
    }

    /**
     * @return void
     */
    abstract protected function createTestedItem();

    /**
     * @return void
     */
    public function testSetAndGetBackup()
    {
        $this->item->setBackup($this->backupMock);
        $this->assertSame($this->backupMock, $this->item->getBackup());
    }

    /**
     * @return void
     */
    public function testGetBackupWithFactory()
    {
        $this->backupMock->expects($this->once())
            ->method('load')
            ->with($this->backupId)
            ->willReturnSelf();
        $this->backupFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->backupMock);

        $this->item->getBackup();
    }

    /**
     * @return void
     */
    public function testSetAndGetCmdObject()
    {
        $this->item->setCmdObject($this->cmdPhpMock);
        $this->assertSame($this->cmdPhpMock, $this->item->getCmdObject());
    }

    /**
     * @return void
     */
    public function testGetCmdObjectWithFactory()
    {
        $phpPath = '/bin/php';
        $backupName = 'someBackup';
        $outputPath = '/var/tmp';
        $this->item->setBackup($this->backupMock);

        $this->shellHelperMock->expects($this->once())
            ->method('getUtility')
            ->with(\Magento\Support\Helper\Shell::UTILITY_PHP)
            ->willReturn($phpPath);
        $this->shellHelperMock->expects($this->once())
            ->method('getAbsoluteOutputPath')
            ->willReturn($outputPath);

        $this->backupMock->expects($this->once())
            ->method('getName')
            ->willReturn($backupName);

        $this->cmdPhpMock->expects($this->once())
            ->method('setScriptInterpreter')
            ->with($phpPath);
        $this->setCmdScriptName();
        $this->cmdPhpMock->expects($this->once())
            ->method('setName')
            ->with($backupName);
        $this->cmdPhpMock->expects($this->once())
            ->method('setOutput')
            ->with($outputPath);

        $this->cmdPhpFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->cmdPhpMock);

        $this->assertSame($this->cmdPhpMock, $this->item->getCmdObject());
    }

    /**
     * @return void
     */
    abstract protected function setCmdScriptName();

    /**
     * @return void
     */
    public function testGetCmd()
    {
        $generatedString = 'some shell command';
        $this->cmdPhpMock->expects($this->once())
            ->method('generate')
            ->willReturn($generatedString);
        $this->item->setCmdObject($this->cmdPhpMock);

        $this->assertSame($generatedString, $this->item->getCmd());
    }

    /**
     * @return void
     */
    public function testUpdateStatus()
    {
        $this->generalUpdateStatusTest();
        $this->item->setStatus(AbstractItem::STATUS_COMPLETE);
        $this->item->updateStatus();

        $this->assertSame(AbstractItem::STATUS_COMPLETE, $this->item->getStatus());
    }

    /**
     * @return void
     */
    public function testUpdateStatusFileIsNotExist()
    {
        $this->generalUpdateStatusTest();
        $this->directoryMock->expects($this->once())
            ->method('isExist')
            ->with($this->filePath)
            ->willReturn(false);

        $this->item->updateStatus();
        $this->assertSame(AbstractItem::STATUS_PROCESSING, $this->item->getStatus());
    }

    /**
     * @return void
     */
    public function testUpdateStatusFileIsLocked()
    {
        $this->generalUpdateStatusTest();
        $this->directoryMock->expects($this->once())
            ->method('isExist')
            ->with($this->filePath)
            ->willReturn(true);
        $this->shellHelperMock->expects($this->once())
            ->method('isFileLocked')
            ->with($this->filePath)
            ->willReturn(true);

        $this->item->updateStatus();
        $this->assertSame(AbstractItem::STATUS_PROCESSING, $this->item->getStatus());
    }

    /**
     * @return void
     */
    public function testUpdateStatusFileIsNotLocked()
    {
        $fileSize = 100;
        $this->generalUpdateStatusTest();
        $this->directoryMock->expects($this->once())
            ->method('isExist')
            ->with($this->filePath)
            ->willReturn(true);
        $this->shellHelperMock->expects($this->once())
            ->method('isFileLocked')
            ->with($this->filePath)
            ->willReturn(false);
        $this->shellHelperMock->expects($this->once())
            ->method('getFileSize')
            ->with($this->fileName)
            ->willReturn($fileSize);

        $this->item->updateStatus();
        $this->assertSame($fileSize, $this->item->getSize());
        $this->assertSame(AbstractItem::STATUS_COMPLETE, $this->item->getStatus());
    }

    /**
     * @return void
     */
    protected function generalUpdateStatusTest()
    {
        $this->backupMock->expects($this->any())
            ->method('getName')
            ->willReturn($this->backupName);

        $this->item->setOutputFileExtension($this->backupExtension);
        $this->item->setBackup($this->backupMock);

        $this->shellHelperMock->expects($this->any())
            ->method('getFilePath')
            ->with($this->fileName)
            ->willReturn($this->filePath);
    }

    /**
     * @return void
     */
    public function testGetName()
    {
        $this->backupMock->expects($this->any())
            ->method('getName')
            ->willReturn($this->backupName);

        $this->item->setOutputFileExtension($this->backupExtension);
        $this->item->setBackup($this->backupMock);

        $this->assertSame($this->fileName, $this->item->getName());
    }

    /**
     * @return void
     */
    public function testGetDbName()
    {
        $this->backupMock->expects($this->any())
            ->method('getData')
            ->with('db_name')
            ->willReturn($this->backupName);

        $this->item->setOutputFileExtension($this->backupExtension);
        $this->item->setBackup($this->backupMock);
    }

    /**
     * @return void
     */
    public function testLoadItemByBackupIdAndType()
    {
        $this->resourceMock->expects($this->once())
            ->method('loadItemByBackupIdAndType')
            ->with($this->item, $this->backupId, 1)
            ->willReturn($this->item);

        $this->assertSame($this->item, $this->item->loadItemByBackupIdAndType($this->backupId, 1));
    }

    /**
     * @param bool $writable
     * @param bool $readable
     * @param string $expectedResult
     * @return void
     * @dataProvider validateDataProvider
     */
    public function testValidate($writable, $readable, $expectedResult)
    {
        $outputPath = '/path';
        $this->shellHelperMock->expects($this->once())
            ->method('getOutputPath')
            ->willReturn($outputPath);

        $this->directoryMock->expects($this->once())
            ->method('isWritable')
            ->with($outputPath)
            ->willReturn($writable);
        $this->directoryMock->expects($this->any())
            ->method('isReadable')
            ->with($outputPath)
            ->willReturn($readable);

        $this->assertEquals($expectedResult, $this->item->validate());
    }

    /**
     * @return array
     */
    public function validateDataProvider()
    {
        $errorMsg = sprintf(__('Directory %s should have writable & readable permissions'), '/path');

        return [
            [
                'writable' => true,
                'readable' => true,
                'expectedResult' => '',
            ],
            [
                'writable' => false,
                'readable' => false,
                'expectedResult' => $errorMsg,
            ],
            [
                'writable' => true,
                'readable' => false,
                'expectedResult' => $errorMsg,
            ],
            [
                'writable' => false,
                'readable' => true,
                'expectedResult' => $errorMsg,
            ],
        ];
    }
}
