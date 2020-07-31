<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Test\Unit\Observer;

use Magento\VersionsCms\Model\CurrentNodeResolverInterface;
use Magento\VersionsCms\Model\Hierarchy\NodeFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddCmsToTopmenuItemsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\VersionsCms\Observer\AddCmsToTopmenuItems
     */
    private $observer;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var CurrentNodeResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $currentNodeResolverMock;

    /**
     * @var NodeFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $hierarchyNodeFactoryMock;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManagerMock;

    /**
     * @var \Magento\Store\Api\Data\StoreInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeMock;

    protected function setUp()
    {
        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMockForAbstractClass();

        $this->hierarchyNodeFactoryMock = $this->getMockBuilder(NodeFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->currentNodeResolverMock = $this->getMockBuilder(CurrentNodeResolverInterface::class)
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->getMockForAbstractClass();

        $this->storeManagerMock = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->getMockForAbstractClass();
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->observer = $objectManagerHelper->getObject(
            \Magento\VersionsCms\Observer\AddCmsToTopmenuItems::class,
            [
                'hierarchyNodeFactory' => $this->hierarchyNodeFactoryMock,
                'storeManager' => $this->storeManagerMock,
                'currentNodeResolver' => $this->currentNodeResolverMock,
            ]
        );
    }

    /**
     * @dataProvider getExecuteDataProvider
     * @param \PHPUnit_Framework_MockObject_MockObject|null $currentNode
     */
    public function testExecute(
        $currentNode
    ) {
        $storeId = 1;
        $nodeId = 1;
        $nodeLabel = 'label';
        $nodeUrl = 'url';
        $parentNodeId = 1;
        $pageId = 1;

        $hierarchyNode1Data = [
            'data' => [
                'scope' => \Magento\VersionsCms\Model\Hierarchy\Node::NODE_SCOPE_STORE,
                'scope_id' => $storeId,
            ],
        ];

        $hierarchyNode1 = $this->getMockBuilder(\Magento\VersionsCms\Model\Hierarchy\Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $hierarchyNode1->expects($this->once())
            ->method('getHeritage')
            ->willReturnSelf();
        $hierarchyNode1->expects($this->once())
            ->method('getNodesData')
            ->willReturn([
                [
                    'node_id' => $nodeId,
                    'parent_node_id' => $parentNodeId,
                ]
            ]);

        $hierarchyNode2 = $this->getMockBuilder(\Magento\VersionsCms\Model\Hierarchy\Node::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'load',
                'getParentNodeId',
                'getTopMenuExcluded',
                'getPageId',
                'getPageIsActive',
                'getLabel',
                'getUrl',
            ])
            ->getMock();
        $hierarchyNode2->expects($this->once())
            ->method('load')
            ->with($nodeId)
            ->willReturnSelf();
        $hierarchyNode2->expects($this->any())
            ->method('getParentNodeId')
            ->willReturn($parentNodeId);
        $hierarchyNode2->expects($this->once())
            ->method('getTopMenuExcluded')
            ->willReturn(false);
        $hierarchyNode2->expects($this->once())
            ->method('getPageId')
            ->willReturn($pageId);
        $hierarchyNode2->expects($this->once())
            ->method('getPageIsActive')
            ->willReturn(true);
        $hierarchyNode2->expects($this->once())
            ->method('getLabel')
            ->willReturn($nodeLabel);
        $hierarchyNode2->expects($this->once())
            ->method('getUrl')
            ->willReturn($nodeUrl);

        $this->hierarchyNodeFactoryMock->expects($this->at(0))
            ->method('create')
            ->willReturnMap([
                [$hierarchyNode1Data, $hierarchyNode1],
                [[], $hierarchyNode2],
            ]);

        $this->hierarchyNodeFactoryMock->expects($this->at(1))
            ->method('create')
            ->willReturn($hierarchyNode2);

        $this->storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        $this->currentNodeResolverMock->expects($this->once())
            ->method('get')
            ->with($this->requestMock)
            ->willReturn($currentNode);

        $eventObserverMock = $this->getEventObserverMock();
        $this->observer->execute($eventObserverMock);
    }

    /**
     * Data Provider for testExecute() method
     *
     * @return array
     */
    public function getExecuteDataProvider()
    {
        $currentNodeMock = $this->getMockBuilder(\Magento\VersionsCms\Api\Data\HierarchyNodeInterface::class)
            ->getMockForAbstractClass();
        $currentNodeMock->expects($this->once())
            ->method('getXpath')
            ->willReturn('1/2');

        return [
            [$currentNodeMock],
            [null],
        ];
    }

    /**
     * Create Event Observer mock object
     *
     * Helper method, that provides unified logic of creation of Event Observer mock object,
     * required to implement test iterations.
     *
     * Used to avoid creation test methods with too many rows of code.
     *
     * @return \Magento\Framework\Event\Observer|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getEventObserverMock()
    {
        $topMenuRootNodeId = 1;

        $treeMock = $this->getMockBuilder(\Magento\Framework\Data\Tree::class)
            ->disableOriginalConstructor()
            ->getMock();

        $topMenuRootNodeMock = $this->getMockBuilder(\Magento\Framework\Data\Tree\Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $topMenuRootNodeMock->expects($this->once())
            ->method('getTree')
            ->willReturn($treeMock);
        $topMenuRootNodeMock->expects($this->once())
            ->method('getId')
            ->willReturn($topMenuRootNodeId);

        $eventObserverMock = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getRequest',
                'getMenu',
            ])
            ->getMock();
        $eventObserverMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $eventObserverMock->expects($this->once())
            ->method('getMenu')
            ->willReturn($topMenuRootNodeMock);

        return $eventObserverMock;
    }
}
