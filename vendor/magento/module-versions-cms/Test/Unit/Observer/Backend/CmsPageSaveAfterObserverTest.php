<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Test\Unit\Observer\Backend;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class CmsPageSaveAfterObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\VersionsCms\Helper\Hierarchy|MockObject
     */
    protected $cmsHierarchyMock;

    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\Node|MockObject
     */
    protected $hierarchyNodeMock;

    /**
     * @var \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node|MockObject
     */
    protected $hierarchyNodeResourceMock;

    /**
     * @var \Magento\Framework\Event\Observer|MockObject
     */
    protected $eventObserverMock;

    /**
     * @var \Magento\Cms\Model\Page|MockObject
     */
    protected $pageMock;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\VersionsCms\Observer\Backend\CmsPageSaveAfterObserver
     */
    protected $observer;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->cmsHierarchyMock = $this->createMock(\Magento\VersionsCms\Helper\Hierarchy::class);
        $this->hierarchyNodeMock = $this->createMock(\Magento\VersionsCms\Model\Hierarchy\Node::class);
        $this->hierarchyNodeResourceMock = $this->createMock(
            \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node::class
        );
        $this->eventObserverMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->pageMock = $this->getMockBuilder(\Magento\Cms\Model\Page::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'dataHasChangedFor',
                'getAppendToNodes',
                'getNodesSortOrder',
            ])
            ->getMock();

        $this->observer = $this->objectManagerHelper->getObject(
            \Magento\VersionsCms\Observer\Backend\CmsPageSaveAfterObserver::class,
            [
                'cmsHierarchy' => $this->cmsHierarchyMock,
                'hierarchyNode' => $this->hierarchyNodeMock,
                'hierarchyNodeResource' => $this->hierarchyNodeResourceMock,
            ]
        );
    }

    /**
     * @return void
     */
    public function testCmsPageSaveAfter()
    {
        /** @var \Magento\Framework\Event|MockObject $eventMock */
        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getObject']);
        $eventMock->expects($this->once())
            ->method('getObject')
            ->willReturn($this->pageMock);
        $this->eventObserverMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);

        $appendToNodes = ['node 1', 'node2'];

        $this->cmsHierarchyMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->pageMock->expects($this->once())
            ->method('dataHasChangedFor')
            ->with('identifier')
            ->willReturn(true);
        $this->hierarchyNodeMock->expects($this->once())
            ->method('updateRewriteUrls')
            ->with($this->pageMock)
            ->willReturnSelf();
        $this->pageMock->expects($this->once())
            ->method('getAppendToNodes')
            ->willReturn($appendToNodes);
        $this->hierarchyNodeMock->expects($this->once())
            ->method('appendPageToNodes')
            ->with($this->pageMock, $appendToNodes)
            ->willReturnSelf();
        $this->pageMock->expects($this->once())
            ->method('getNodesSortOrder')
            ->willReturn([1 => 'node 1']);
        $this->hierarchyNodeResourceMock->expects($this->once())
            ->method('updateSortOrder')
            ->with(1, 'node 1')
            ->willReturnSelf();

        $this->assertSame(
            $this->observer,
            $this->observer->execute($this->eventObserverMock)
        );
    }

    /**
     * @return void
     */
    public function testCmsPageSaveAfterWithCmsHierarchyDisabled()
    {
        /** @var \Magento\Framework\Event|MockObject $eventMock */
        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getObject']);
        $eventMock->expects($this->once())
            ->method('getObject')
            ->willReturn($this->pageMock);
        $this->eventObserverMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);

        $this->cmsHierarchyMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->assertSame(
            $this->observer,
            $this->observer->execute($this->eventObserverMock)
        );
    }
}
