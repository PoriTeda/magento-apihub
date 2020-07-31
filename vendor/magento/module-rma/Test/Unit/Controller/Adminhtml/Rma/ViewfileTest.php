<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Controller\Adminhtml\Rma;

use Magento\Rma\Test\Unit\Controller\Adminhtml\RmaTest;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * @covers \Magento\Rma\Controller\Adminhtml\Rma\Viewfile
 */
class ViewfileTest extends RmaTest
{
    /**
     * @var string
     */
    protected $name = 'Viewfile';

    /**
     * @var \Magento\Framework\Controller\Result\Raw|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultRawMock;

    /**
     * @var \Magento\Framework\Filesystem|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fileSystemMock;

    /**
     * @var \Magento\Framework\Filesystem\Directory\Read|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $readDirectoryMock;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultRawFactoryMock;

    /**
     * @var \Magento\Framework\Url\DecoderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlDecoderMock;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fileFactoryMock;

    /**
     * @var \Magento\Framework\App\ResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fileResponseMock;

    /**
     * @var \Magento\Framework\Filesystem\File\Read|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fileReadMock;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    private $directoryResolverMock;

    protected function setUp()
    {
        $this->readDirectoryMock = $this->getMockBuilder(\Magento\Framework\Filesystem\Directory\Read::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fileSystemMock = $this->getMockBuilder(\Magento\Framework\Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRawFactoryMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\RawFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->resultRawMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\Raw::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlDecoderMock = $this->getMockBuilder(\Magento\Framework\Url\DecoderInterface::class)
            ->getMock();
        $this->fileFactoryMock = $this->getMockBuilder(\Magento\Framework\App\Response\Http\FileFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fileResponseMock = $this->getMockBuilder(\Magento\Framework\App\ResponseInterface::class)
            ->getMock();
        $this->fileReadMock = $this->getMockBuilder(\Magento\Framework\Filesystem\File\Read::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryResolverMock = $this->getMockBuilder(\Magento\Framework\App\Filesystem\DirectoryResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['validatePath'])
            ->getMock();
        $this->fileSystemMock->expects($this->any())
            ->method('getDirectoryRead')
            ->with(DirectoryList::MEDIA)
            ->willReturn($this->readDirectoryMock);

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getConstructArguments()
    {
        $arguments = parent::getConstructArguments();
        $arguments['filesystem'] = $this->fileSystemMock;
        $arguments['resultRawFactory'] = $this->resultRawFactoryMock;
        $arguments['urlDecoder'] = $this->urlDecoderMock;
        $arguments['fileFactory'] = $this->fileFactoryMock;
        $arguments['directoryResolver'] = $this->directoryResolverMock;
        return $arguments;
    }

    /**
     * @covers \Magento\Rma\Controller\Adminhtml\Rma\Viewfile::execute
     * @throws \Magento\Framework\Exception\NotFoundException
     * @expectedException \Magento\Framework\Exception\NotFoundException
     */
    public function testExecuteNoParamsShouldThrowException()
    {
        $this->action->execute();
    }

    /**
     * @covers \Magento\Rma\Controller\Adminhtml\Rma\Viewfile::execute
     */
    public function testExecuteGetFile()
    {
        $file = 'file';
        $fileDecoded = 'file_decoded';
        $absolutePath = 'absolute/path';

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->with('file')
            ->willReturn($file);
        $this->urlDecoderMock->expects($this->any())
            ->method('decode')
            ->with($file)
            ->willReturn($fileDecoded);
        $this->readDirectoryMock->expects($this->any())
            ->method('isExist')
            ->willReturn(true);
        $this->readDirectoryMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn($absolutePath);
        $this->fileFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                $fileDecoded,
                ['type' => 'filename', 'value' => $absolutePath],
                DirectoryList::MEDIA
            )
            ->willReturn($this->fileResponseMock);
        $this->directoryResolverMock->expects($this->atLeastOnce())->method('validatePath')
            ->with($absolutePath, DirectoryList::MEDIA)
            ->willReturn(true);
        $this->action->execute();
    }

    /**
     * @expectedException \Magento\Framework\Exception\NotFoundException
     * @expectedExceptionMessage Page not found.
     */
    public function testExecuteGetFileWithWrongPath()
    {
        $file = 'file';
        $fileDecoded = 'file_decoded';
        $absolutePath = 'absolute/path';

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->with('file')
            ->willReturn($file);
        $this->urlDecoderMock->expects($this->any())
            ->method('decode')
            ->with($file)
            ->willReturn($fileDecoded);
        $this->readDirectoryMock->expects($this->any())
            ->method('isExist')
            ->willReturn(true);
        $this->readDirectoryMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn($absolutePath);
        $this->fileFactoryMock->expects($this->never())->method('create');
        $this->directoryResolverMock->expects($this->atLeastOnce())->method('validatePath')
            ->with($absolutePath, DirectoryList::MEDIA)
            ->willReturn(false);
        $this->action->execute();
    }

    /**
     * @covers \Magento\Rma\Controller\Adminhtml\Rma\Viewfile::execute
     */
    public function testExecuteGetImage()
    {
        $file = 'file';
        $fileDecoded = 'file_decoded';
        $fileContents = 'file_contents';
        $fileStat = ['size' => 10, 'mtime' => 10];
        $absolutePath = 'absolute/path';

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['file', null, null],
                    ['image', null, $file]
                ]
            );
        $this->urlDecoderMock->expects($this->any())
            ->method('decode')
            ->with($file)
            ->willReturn($fileDecoded);
        $this->fileReadMock->expects($this->once())
            ->method('read')
            ->with($fileStat['size'])
            ->willReturn($fileContents);
        $this->readDirectoryMock->expects($this->any())
            ->method('isExist')
            ->willReturn(true);
        $this->readDirectoryMock->expects($this->any())
            ->method('openFile')
            ->willReturn($this->fileReadMock);
        $this->readDirectoryMock->expects($this->any())
            ->method('stat')
            ->with('rma_item/file_decoded')
            ->willReturn($fileStat);
        $this->resultRawMock->expects($this->any())
            ->method('setHttpResponseCode')
            ->with(200)
            ->willReturnSelf();
        $this->resultRawMock->expects($this->any())
            ->method('setHeader')
            ->willReturnSelf();
        $this->resultRawMock->expects($this->once())
            ->method('setContents')
            ->with($fileContents);
        $this->resultRawFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRawMock);
        $this->readDirectoryMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn($absolutePath);
        $this->directoryResolverMock->expects($this->atLeastOnce())->method('validatePath')
            ->with($absolutePath, DirectoryList::MEDIA)
            ->willReturn(true);

        $this->assertSame($this->resultRawMock, $this->action->execute());
    }
}
