<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Block\Adminhtml\Report;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class ViewTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Support\Block\Adminhtml\Report\View
     */
    protected $viewReportBlock;

    /**
     * @var \Magento\Backend\Block\Widget\Context
     */
    protected $context;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $coreRegistryMock;

    /**
     * @var \Magento\Support\Model\Report|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $reportMock;

    /**
     * @var \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlBuilderMock;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    protected function setUp()
    {
        $this->coreRegistryMock = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->reportMock = $this->getMockBuilder(\Magento\Support\Model\Report::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlBuilderMock = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->getMockForAbstractClass();
        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->context = $this->objectManagerHelper->getObject(
            \Magento\Backend\Block\Widget\Context::class,
            [
                'urlBuilder' => $this->urlBuilderMock,
                'request' => $this->requestMock
            ]
        );
        $this->viewReportBlock = $this->objectManagerHelper->getObject(
            \Magento\Support\Block\Adminhtml\Report\View::class,
            [
                'context' => $this->context,
                'coreRegistry' => $this->coreRegistryMock
            ]
        );
    }

    public function testGetReport()
    {
        $this->coreRegistryMock->expects($this->once())
            ->method('registry')
            ->with('current_report')
            ->willReturn($this->reportMock);

        $this->assertSame($this->reportMock, $this->viewReportBlock->getReport());
    }

    public function testGetDownloadUrl()
    {
        $id = 1;
        $downloadUrl = '/download/url';

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->with('id', null)
            ->willReturn($id);
        $this->urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with('*/*/download', ['id' => $id])
            ->willReturn($downloadUrl);

        $this->assertEquals($downloadUrl, $this->viewReportBlock->getDownloadUrl());
    }
}
