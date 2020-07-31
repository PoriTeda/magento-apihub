<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Magento\VersionsCms\Api\Data\HierarchyNodeInterface;
use Magento\VersionsCms\Api\Data\HierarchyNodeSearchResultsInterface;
use Magento\VersionsCms\Api\HierarchyNodeRepositoryInterface;
use Magento\VersionsCms\Model\Hierarchy\Node;
use Magento\VersionsCms\Model\Hierarchy\NodeFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CurrentNodeResolverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var NodeFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $hierarchyNodeFactoryMock;

    /**
     * @var StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManagerMock;

    /**
     * @var \Magento\Store\Api\Data\StoreInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeMock;

    /**
     * @var HierarchyNodeRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $hierarchyNodeRepositoryMock;

    /**
     * @var SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var \Magento\VersionsCms\Model\CurrentNodeResolver
     */
    private $model;

    protected function setUp()
    {
        $this->hierarchyNodeFactoryMock = $this->getMockBuilder(NodeFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->storeMock = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->getMockForAbstractClass();

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMockForAbstractClass();
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->hierarchyNodeRepositoryMock = $this->getMockBuilder(HierarchyNodeRepositoryInterface::class)
            ->getMockForAbstractClass();

        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new \Magento\VersionsCms\Model\CurrentNodeResolver(
            $this->hierarchyNodeFactoryMock,
            $this->storeManagerMock,
            $this->hierarchyNodeRepositoryMock,
            $this->searchCriteriaBuilderMock
        );
    }

    public function testGet()
    {
        $pageId = 1;
        $storeId = 1;
        $nodeId = 1;

        $requestUrl = 'test';

        $requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMockForAbstractClass();
        $requestMock->expects($this->once())
            ->method('getParam')
            ->with('page_id', false)
            ->willReturn($pageId);

        $this->storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        $nodeData = [
            'data' => [
                'scope' => Node::NODE_SCOPE_STORE,
                'scope_id' => $storeId,
            ],
        ];

        $nodeMock = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nodeMock->expects($this->once())
            ->method('getHeritage')
            ->willReturnSelf();
        $nodeMock->expects($this->once())
            ->method('getScope')
            ->willReturn(Node::NODE_SCOPE_STORE);
        $nodeMock->expects($this->once())
            ->method('getScopeId')
            ->willReturn($storeId);
        $nodeMock->expects($this->once())
            ->method('loadByRequestUrl')
            ->with($requestUrl)
            ->willReturnSelf();
        $nodeMock->expects($this->once())
            ->method('getId')
            ->willReturn($nodeId);

        $this->hierarchyNodeFactoryMock->expects($this->once())
            ->method('create')
            ->with($nodeData)
            ->willReturn($nodeMock);

        $searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilderMock->expects($this->exactly(3))
            ->method('addFilter')
            ->willReturnMap([
                [HierarchyNodeInterface::PAGE_ID, $pageId, 'eq', $this->searchCriteriaBuilderMock],
                [HierarchyNodeInterface::SCOPE, Node::NODE_SCOPE_STORE, 'eq', $this->searchCriteriaBuilderMock],
                [HierarchyNodeInterface::SCOPE_ID, $storeId, 'eq', $this->searchCriteriaBuilderMock],
            ]);
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);

        $nodes = [
            [
                'page_id' => $pageId,
                'request_url' => $requestUrl,
            ]
        ];

        $hierarchyNodeSearchResultsMock = $this->getMockBuilder(HierarchyNodeSearchResultsInterface::class)
            ->getMockForAbstractClass();
        $hierarchyNodeSearchResultsMock->expects($this->once())
            ->method('getItems')
            ->willReturn($nodes);

        $this->hierarchyNodeRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($hierarchyNodeSearchResultsMock);

        $this->assertEquals($nodeMock, $this->model->get($requestMock));
    }

    public function testGetNull()
    {
        $pageId = 1;
        $storeId = 1;

        $requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMockForAbstractClass();
        $requestMock->expects($this->once())
            ->method('getParam')
            ->with('page_id', false)
            ->willReturn($pageId);

        $this->storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        $nodeData = [
            'data' => [
                'scope' => Node::NODE_SCOPE_STORE,
                'scope_id' => $storeId,
            ],
        ];

        $nodeMock = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nodeMock->expects($this->once())
            ->method('getHeritage')
            ->willReturnSelf();
        $nodeMock->expects($this->once())
            ->method('getScope')
            ->willReturn(Node::NODE_SCOPE_STORE);
        $nodeMock->expects($this->once())
            ->method('getScopeId')
            ->willReturn($storeId);

        $this->hierarchyNodeFactoryMock->expects($this->once())
            ->method('create')
            ->with($nodeData)
            ->willReturn($nodeMock);

        $searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilderMock->expects($this->exactly(3))
            ->method('addFilter')
            ->willReturnMap([
                [HierarchyNodeInterface::PAGE_ID, $pageId, 'eq', $this->searchCriteriaBuilderMock],
                [HierarchyNodeInterface::SCOPE, Node::NODE_SCOPE_STORE, 'eq', $this->searchCriteriaBuilderMock],
                [HierarchyNodeInterface::SCOPE_ID, $storeId, 'eq', $this->searchCriteriaBuilderMock],
            ]);
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);

        $hierarchyNodeSearchResultsMock = $this->getMockBuilder(HierarchyNodeSearchResultsInterface::class)
            ->getMockForAbstractClass();
        $hierarchyNodeSearchResultsMock->expects($this->once())
            ->method('getItems')
            ->willReturn(null);

        $this->hierarchyNodeRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($hierarchyNodeSearchResultsMock);

        $this->assertNull($this->model->get($requestMock));
    }
}
