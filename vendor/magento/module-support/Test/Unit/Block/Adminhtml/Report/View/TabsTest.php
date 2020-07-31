<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Block\Adminhtml\Report\View;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class TabsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Support\Block\Adminhtml\Report\View\Tabs
     */
    protected $reportTabsBlock;

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

    protected function setUp()
    {
        $this->coreRegistryMock = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->reportMock = $this->getMockBuilder(\Magento\Support\Model\Report::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->reportTabsBlock = $this->objectManagerHelper->getObject(
            \Magento\Support\Block\Adminhtml\Report\View\Tabs::class,
            [
                'coreRegistry' => $this->coreRegistryMock
            ]
        );
    }

    public function testGetReportDataIsSet()
    {
        $this->reportTabsBlock->setData('report', $this->reportMock);

        $this->coreRegistryMock->expects($this->never())
            ->method('registry');

        $this->assertSame($this->reportMock, $this->reportTabsBlock->getReport());
    }

    public function testGetReport()
    {
        $this->coreRegistryMock->expects($this->once())
            ->method('registry')
            ->with('current_report')
            ->willReturn($this->reportMock);

        $this->assertSame($this->reportMock, $this->reportTabsBlock->getReport());
    }
}
