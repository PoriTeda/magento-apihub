<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Test\Unit\Observer\Backend;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class CmsPageSaveBeforeObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Json\Helper\Data|MockObject
     */
    protected $jsonHelperMock;

    /**
     * @var \Magento\Cms\Model\Page|MockObject
     */
    protected $pageMock;

    /**
     * @var \Magento\Framework\Event\Observer|MockObject
     */
    protected $eventObserverMock;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\VersionsCms\Observer\Backend\CmsPageSaveBeforeObserver
     */
    protected $observer;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->jsonHelperMock = $this->createMock(\Magento\Framework\Json\Helper\Data::class);
        $this->eventObserverMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->pageMock = $this->getMockBuilder(\Magento\Cms\Model\Page::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getId',
                'setWebsiteRoot',
                'setNodesSortOrder',
                'setAppendToNodes',
                'getNodesData',
            ])
            ->getMock();

        $this->observer = $this->objectManagerHelper->getObject(
            \Magento\VersionsCms\Observer\Backend\CmsPageSaveBeforeObserver::class,
            [
                'jsonHelper' => $this->jsonHelperMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testCmsPageSaveBeforeNewPageAndEmptyNodesData()
    {
        $this->initEventMock();

        $this->pageMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);
        $this->pageMock->expects($this->once())
            ->method('setWebsiteRoot')
            ->with(true);
        $this->pageMock->expects($this->once())
            ->method('setNodesSortOrder')
            ->with([]);
        $this->pageMock->expects($this->once())
            ->method('setAppendToNodes')
            ->with([]);

        $this->assertSame(
            $this->observer,
            $this->observer->execute($this->eventObserverMock)
        );
    }

    /**
     * @return void
     */
    public function testCmsPageSaveBeforeOldPageWithNodesData()
    {
        $nodesJsonData = 'Some JSON data';
        $nodesData = [
            ['page_exists' => true, 'node_id' => '0_1', 'parent_node_id' => '0', 'sort_order' => 10],
            ['page_exists' => true, 'node_id' => '1', 'parent_node_id' => '1', 'sort_order' => 20]
        ];

        $this->initEventMock();

        $this->pageMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $this->pageMock->expects($this->once())
            ->method('getNodesData')
            ->willReturn($nodesJsonData);

        $this->jsonHelperMock->expects($this->once())
            ->method('jsonDecode')
            ->with($nodesJsonData)
            ->willReturn($nodesData);

        $this->pageMock->expects($this->once())
            ->method('setNodesSortOrder')
            ->with([1 => 20]);
        $this->pageMock->expects($this->once())
            ->method('setAppendToNodes')
            ->with(['0_1' => '0', '1' => '0']);

        $this->assertSame(
            $this->observer,
            $this->observer->execute($this->eventObserverMock)
        );
    }

    /**
     * @return void
     */
    public function testCmsPageSaveBeforeWithException()
    {
        $nodesJsonData = 'Some JSON data';
        $this->initEventMock();

        $this->pageMock->expects($this->once())
            ->method('getNodesData')
            ->willReturn($nodesJsonData);

        $this->jsonHelperMock->expects($this->once())
            ->method('jsonDecode')
            ->with($nodesJsonData)
            ->willThrowException(new \Zend_Json_Exception());

        $this->pageMock->expects($this->once())
            ->method('setNodesSortOrder')
            ->with([]);
        $this->pageMock->expects($this->once())
            ->method('setAppendToNodes')
            ->with([]);

        $this->assertSame(
            $this->observer,
            $this->observer->execute($this->eventObserverMock)
        );
    }

    /**
     * @return void
     */
    protected function initEventMock()
    {
        /** @var \Magento\Framework\Event|MockObject $eventMock */
        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getObject']);
        $eventMock->expects($this->once())
            ->method('getObject')
            ->willReturn($this->pageMock);
        $this->eventObserverMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);
    }
}
