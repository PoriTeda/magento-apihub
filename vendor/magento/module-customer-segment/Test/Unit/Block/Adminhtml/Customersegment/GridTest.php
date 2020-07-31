<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Test\Unit\Block\Adminhtml\Customersegment;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObject;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GridTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Grid
     */
    protected $model;

    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $store;

    /**
     * @var \Magento\CustomerSegment\Model\SegmentFactory
     */
    protected $segmentFactory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Backend\Block\Widget\Context
     */
    protected $context;

    protected function setUp()
    {
        $this->helper = $this->createMock(\Magento\Backend\Helper\Data::class);
        $this->store = $this->createMock(\Magento\Store\Model\System\Store::class);
        $this->segmentFactory = $this->createPartialMock(
            \Magento\CustomerSegment\Model\SegmentFactory::class,
            ['create']
        );
        $this->request = $this->createMock(\Magento\Framework\App\RequestInterface::class);

        $writeInterface = $this->createMock(\Magento\Framework\Filesystem\Directory\WriteInterface::class);
        $this->filesystem = $this->createMock(\Magento\Framework\Filesystem::class);
        $this->filesystem
            ->expects($this->once())
            ->method('getDirectoryWrite')
            ->with($this->equalTo(DirectoryList::VAR_DIR))
            ->will($this->returnValue($writeInterface));

        $this->urlBuilder = $this->getMockForAbstractClass(\Magento\Framework\UrlInterface::class, [], '', false);

        $this->context = $this->getMockBuilder(\Magento\Backend\Block\Widget\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context
            ->expects($this->once())
            ->method('getUrlBuilder')
            ->will($this->returnValue($this->urlBuilder));
        $this->context
            ->expects($this->once())
            ->method('getFilesystem')
            ->will($this->returnValue($this->filesystem));
        $this->context
            ->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($this->request));

        $this->model = new \Magento\CustomerSegment\Block\Adminhtml\Customersegment\Grid(
            $this->context,
            $this->helper,
            $this->store,
            $this->segmentFactory
        );
    }

    protected function tearDown()
    {
        unset(
            $this->model,
            $this->helper,
            $this->store,
            $this->segmentFactory,
            $this->request,
            $this->filesystem,
            $this->urlBuilder,
            $this->context
        );
    }

    public function testGetRowUrl()
    {
        $this->model->setSegmentId(1);

        $object = new DataObject(['segment_id' => 1]);

        $this->urlBuilder
            ->expects($this->any())
            ->method('getUrl')
            ->with('*/*/edit', ['id' => $this->model->getSegmentId()])
            ->willReturn('http://some_url');

        $this->assertContains('http://some_url', (string)$this->model->getRowUrl($object));
    }

    public function testGetRowUrlNull()
    {
        $this->model->setIsChooserMode(true);

        $object = new DataObject(['segment_id' => 1]);

        $this->assertNull($this->model->getRowUrl($object));
    }

    public function testGetGridUrl()
    {
        $this->urlBuilder
            ->expects($this->any())
            ->method('getUrl')
            ->with('customersegment/index/grid', ['_current' => true])
            ->willReturn('http://some_url');

        $this->assertContains('http://some_url', (string)$this->model->getGridUrl());
    }

    public function testGetRowClickCallback()
    {
        $this->assertEquals('openGridRow', $this->model->getRowClickCallback());
    }

    public function testGetRowClickCallbackChooserMode()
    {
        $this->model->setIsChooserMode(true);

        $this->request
            ->expects($this->once())
            ->method('getParam')
            ->with('value_element_id')
            ->will($this->returnValue(1));

        $this->assertContains('function (grid, event) {', (string)$this->model->getRowClickCallback());
    }
}
