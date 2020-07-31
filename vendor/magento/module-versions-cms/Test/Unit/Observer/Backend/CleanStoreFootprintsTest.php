<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Test\Unit\Observer\Backend;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class CleanStoreFootprintsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\NodeFactory|MockObject
     */
    protected $hierarchyNodeFactoryMock;

    /**
     * @var \Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory|MockObject
     */
    protected $widgetCollectionFactoryMock;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\VersionsCms\Observer\Backend\CleanStoreFootprints
     */
    protected $unit;

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
        $this->widgetCollectionFactoryMock = $this->createPartialMock(
            \Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory::class,
            ['create']
        );
        $this->unit = $this->objectManagerHelper->getObject(
            \Magento\VersionsCms\Observer\Backend\CleanStoreFootprints::class,
            [
                'hierarchyNodeFactory' => $this->hierarchyNodeFactoryMock,
                'widgetCollectionFactory' => $this->widgetCollectionFactoryMock,
            ]
        );
    }

    public function testCleanStoreFootprints()
    {
        $storeId = 2;

        $this->hierarchyNodeDeleteByScope();
        /** @var \Magento\Widget\Model\Widget\Instance|MockObject $widgetInstanceMock */
        $widgetInstanceMock = $this->getMockBuilder(\Magento\Widget\Model\Widget\Instance::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreIds', 'setStoreIds', 'getWidgetParameters', 'setWidgetParameters', 'save'])
            ->getMock();
        $widgetInstanceMock->expects($this->once())
            ->method('getStoreIds')
            ->willReturn([0 => 1, 1 => $storeId, 2 => 3]);
        $widgetInstanceMock->expects($this->once())
            ->method('setStoreIds')
            ->with([0 => 1, 2 => 3]);
        $widgetInstanceMock->expects($this->once())
            ->method('getWidgetParameters')
            ->willReturn([
                'anchor_text_' . $storeId => 'test',
                'title_' . $storeId => 'test',
                'node_id_' . $storeId => 'test',
                'template_' . $storeId => 'test',
                'someParameter'  => 'test'
            ]);
        $widgetInstanceMock->expects($this->once())
            ->method('setWidgetParameters')
            ->with(['someParameter'  => 'test']);
        $widgetInstanceMock->expects($this->once())
            ->method('save');

        /** @var \Magento\Widget\Model\ResourceModel\Widget\Instance\Collection|MockObject $widgetsCollectionMock */
        $widgetsCollectionMock = $this->getMockBuilder(
            \Magento\Widget\Model\ResourceModel\Widget\Instance\Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $widgetsCollectionMock->expects($this->once())
            ->method('addStoreFilter')
            ->with([$storeId, false])
            ->willReturnSelf();
        $widgetsCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('instance_type', \Magento\VersionsCms\Block\Widget\Node::class)
            ->willReturnSelf();
        $widgetsCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$widgetInstanceMock]));
        $this->widgetCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($widgetsCollectionMock);

        $this->unit->clean($storeId);
    }

    /**
     * @return void
     */
    protected function hierarchyNodeDeleteByScope()
    {
        /** @var \Magento\VersionsCms\Model\Hierarchy\Node|MockObject $hierarchyNode */
        $hierarchyNode = $this->createMock(\Magento\VersionsCms\Model\Hierarchy\Node::class);
        $hierarchyNode->expects($this->any())->method('deleteByScope');
        $this->hierarchyNodeFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($hierarchyNode);
    }
}
