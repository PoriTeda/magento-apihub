<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Test\Unit\Block\Adminhtml\Customersegment;

use Magento\CustomerSegment\Block\Adminhtml\Customersegment\Edit;

class EditTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Edit
     */
    protected $model;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\CustomerSegment\Model\Segment
     */
    protected $segment;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Backend\Block\Widget\Button\ButtonList
     */
    protected $buttonList;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var \Magento\Backend\Block\Widget\Context
     */
    protected $context;

    protected function setUp()
    {
        $this->segment = $this->getMockBuilder(\Magento\CustomerSegment\Model\Segment::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getSegmentId', 'getName', '__wakeup'])
            ->getMock();

        $this->registry = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry
            ->expects($this->any())
            ->method('registry')
            ->with($this->equalTo('current_customer_segment'))
            ->willReturn($this->segment);

        $this->urlBuilder = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->getMockForAbstractClass();

        $this->buttonList = $this->getMockBuilder(\Magento\Backend\Block\Widget\Button\ButtonList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->buttonList->expects($this->any())->method('update')->willReturnSelf();
        $this->buttonList->expects($this->any())->method('add')->willReturnSelf();

        $this->request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMockForAbstractClass();
        $this->request->expects($this->any())->method('getParam')->willReturn(1);
        $this->escaper = $this->getMockBuilder(\Magento\Framework\Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this->getMockBuilder(\Magento\Backend\Block\Widget\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context
            ->expects($this->once())
            ->method('getButtonList')
            ->willReturn($this->buttonList);
        $this->context
            ->expects($this->once())
            ->method('getUrlBuilder')
            ->willReturn($this->urlBuilder);
        $this->context
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->context
            ->expects($this->once())
            ->method('getEscaper')
            ->willReturn($this->escaper);

        $this->model = new Edit(
            $this->context,
            $this->registry
        );
    }

    protected function tearDown()
    {
        unset(
            $this->model,
            $this->segment,
            $this->registry,
            $this->urlBuilder,
            $this->buttonList,
            $this->request,
            $this->escaper,
            $this->context
        );
    }

    public function testGetMatchUrl()
    {
        $this->segment
            ->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->urlBuilder
            ->expects($this->any())
            ->method('getUrl')
            ->with('*/*/match', ['id' => $this->segment->getId()])
            ->willReturn('http://some_url');

        $this->assertContains('http://some_url', (string)$this->model->getMatchUrl());
    }

    public function testGetHeaderText()
    {
        $this->segment
            ->expects($this->once())
            ->method('getSegmentId')
            ->willReturn(false);

        $this->assertEquals('New Segment', $this->model->getHeaderText());
    }

    public function testGetHeaderTextWithSegmentId()
    {
        $segmentName = 'test_segment_name';

        $this->segment
            ->expects($this->once())
            ->method('getSegmentId')
            ->willReturn(1);
        $this->segment
            ->expects($this->once())
            ->method('getName')
            ->willReturn($segmentName);

        $this->escaper
            ->expects($this->once())
            ->method('escapeHtml')
            ->willReturn($segmentName);

        $this->assertEquals(sprintf("Edit Segment '%s'", $segmentName), $this->model->getHeaderText());
    }
}
