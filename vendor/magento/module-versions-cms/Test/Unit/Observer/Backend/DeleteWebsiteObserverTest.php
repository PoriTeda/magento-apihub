<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Test\Unit\Observer\Backend;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class DeleteWebsiteObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\NodeFactory|MockObject
     */
    protected $hierarchyNodeFactoryMock;

    /**
     * @var \Magento\VersionsCms\Observer\Backend\CleanStoreFootprints|MockObject
     */
    protected $cleanStoreFootprintsMock;

    /**
     * @var \Magento\Framework\Event\Observer|MockObject
     */
    protected $eventObserverMock;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\VersionsCms\Observer\Backend\DeleteWebsiteObserver
     */
    protected $observer;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->hierarchyNodeFactoryMock = $this->createPartialMock(
            \Magento\VersionsCms\Model\Hierarchy\NodeFactory::class,
            ['create']
        );
        $this->cleanStoreFootprintsMock = $this->createMock(
            \Magento\VersionsCms\Observer\Backend\CleanStoreFootprints::class
        );
        $this->eventObserverMock = $this->createMock(\Magento\Framework\Event\Observer::class);

        $this->observer = $this->objectManagerHelper->getObject(
            \Magento\VersionsCms\Observer\Backend\DeleteWebsiteObserver::class,
            [
                'hierarchyNodeFactory' => $this->hierarchyNodeFactoryMock,
                'cleanStoreFootprints' => $this->cleanStoreFootprintsMock,
            ]
        );
    }

    /**
     * @return void
     */
    public function testDeleteWebsite()
    {
        $websiteId = 1;
        $storeId = 2;

        /** @var \Magento\Store\Model\Website|MockObject $websiteMock */
        $websiteMock = $this->createPartialMock(\Magento\Store\Model\Website::class, ['getId', 'getStoreIds']);
        $websiteMock->expects($this->once())
            ->method('getId')
            ->willReturn($websiteId);
        $websiteMock->expects($this->once())
            ->method('getStoreIds')
            ->willReturn([$storeId]);

        $this->hierarchyNodeDeleteByScope($websiteId);

        /** @var \Magento\Framework\Event|MockObject $eventMock */
        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getWebsite']);
        $eventMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $this->eventObserverMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);

        $this->cleanStoreFootprintsMock->expects($this->once())->method('clean')->with($storeId);

        $this->assertSame(
            $this->observer,
            $this->observer->execute($this->eventObserverMock)
        );
    }

    /**
     * @param int $id
     * @return void
     */
    protected function hierarchyNodeDeleteByScope($id)
    {
        /** @var \Magento\VersionsCms\Model\Hierarchy\Node|MockObject $hierarchyNode */
        $hierarchyNode = $this->createMock(\Magento\VersionsCms\Model\Hierarchy\Node::class);
        $hierarchyNode->expects($this->any())
            ->method('deleteByScope')
            ->willReturnMap([
                [\Magento\VersionsCms\Model\Hierarchy\Node::NODE_SCOPE_STORE, $id],
                [\Magento\VersionsCms\Model\Hierarchy\Node::NODE_SCOPE_WEBSITE, $id]
            ]);
        $this->hierarchyNodeFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($hierarchyNode);
    }
}
