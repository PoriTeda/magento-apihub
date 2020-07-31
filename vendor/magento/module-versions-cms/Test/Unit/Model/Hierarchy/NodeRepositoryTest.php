<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Test\Unit\Model\Hierarchy;

use Magento\VersionsCms\Model\Hierarchy\NodeRepository;
use Magento\Framework\Api\SortOrder;

/**
 * Test for Magento\VersionsCms\Model\Hierarchy\NodeRepository
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NodeRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var NodeRepository
     */
    protected $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node
     */
    protected $nodeResource;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\VersionsCms\Model\Hierarchy\Node
     */
    protected $node;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    protected $nodeData;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\VersionsCms\Api\Data\HierarchyNodeSearchResultsInterface
     */
    protected $nodeSearchResult;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Api\DataObjectHelper
     */
    protected $dataHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Reflection\DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection
     */
    protected $collection;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $collectionProcessor;

    /**
     * Initialize repository
     */
    protected function setUp()
    {
        $this->nodeResource = $this->getMockBuilder(\Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataObjectProcessor = $this->getMockBuilder(\Magento\Framework\Reflection\DataObjectProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nodeFactory = $this->getMockBuilder(\Magento\VersionsCms\Model\Hierarchy\NodeFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $nodeDataFactory = $this->getMockBuilder(\Magento\VersionsCms\Api\Data\HierarchyNodeInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $nodeSearchResultFactory = $this->getMockBuilder(
            \Magento\VersionsCms\Api\Data\HierarchyNodeSearchResultsInterfaceFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $collectionFactory =
            $this->getMockBuilder(\Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->node = $this->getMockBuilder(\Magento\VersionsCms\Model\Hierarchy\Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->nodeData = $this->getMockBuilder(\Magento\VersionsCms\Api\DataHierarchyNodeInterface::class)
            ->getMock();
        $this->nodeSearchResult = $this->getMockBuilder(
            \Magento\VersionsCms\Api\Data\HierarchyNodeSearchResultsInterface::class
        )->getMock();
        $this->collection = $this->getMockBuilder(
            \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection::class
        )
            ->disableOriginalConstructor()
            ->setMethods([
                'addFieldToFilter',
                'getSize',
                'setCurPage',
                'setPageSize',
                'load',
                'addOrder',
                'addStoreFilter',
                'joinCmsPage',
                'joinMetaData',
                'addCmsPageInStoresColumn',
                'addLastChildSortOrderColumn',
            ])
            ->getMock();

        $nodeFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->node);
        $nodeDataFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->nodeData);
        $nodeSearchResultFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->nodeSearchResult);
        $collectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->collection);
        /**
         * @var \Magento\VersionsCms\Model\Hierarchy\NodeFactory $nodeFactory
         * @var \Magento\VersionsCms\Api\Data\HierarchyNodeInterfaceFactory $nodeDataFactory
         * @var \Magento\VersionsCms\Api\Data\HierarchyNodeSearchResultsInterfaceFactory $nodeSearchResultFactory
         * @var \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory $collectionFactory
         */

        $this->dataHelper = $this->getMockBuilder(\Magento\Framework\Api\DataObjectHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionProcessor = $this->createMock(
            \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface::class
        );

        $this->repository = new NodeRepository(
            $this->nodeResource,
            $nodeFactory,
            $nodeDataFactory,
            $collectionFactory,
            $nodeSearchResultFactory,
            $this->dataHelper,
            $this->dataObjectProcessor,
            $this->collectionProcessor
        );
    }

    /**
     * @test
     */
    public function testSave()
    {
        $this->nodeResource->expects($this->once())
            ->method('save')
            ->with($this->node)
            ->willReturnSelf();
        $this->assertEquals($this->node, $this->repository->save($this->node));
    }

    /**
     * @test
     */
    public function testDeleteById()
    {
        $nodeId = '123';

        $this->node->expects($this->once())
            ->method('getId')
            ->willReturn(true);
        $this->nodeResource->expects($this->once())
            ->method('load')
            ->with($this->node, $nodeId)
            ->willReturn($this->node);
        $this->nodeResource->expects($this->once())
            ->method('delete')
            ->with($this->node)
            ->willReturnSelf();

        $this->assertTrue($this->repository->deleteById($nodeId));
    }

    /**
     * @test
     *
     * @expectedException \Magento\Framework\Exception\CouldNotSaveException
     */
    public function testSaveException()
    {
        $this->nodeResource->expects($this->once())
            ->method('save')
            ->with($this->node)
            ->willThrowException(new \Exception());
        $this->repository->save($this->node);
    }

    /**
     * @test
     *
     * @expectedException \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function testDeleteException()
    {
        $this->nodeResource->expects($this->once())
            ->method('delete')
            ->with($this->node)
            ->willThrowException(new \Exception());
        $this->repository->delete($this->node);
    }

    /**
     * @test
     *
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetByIdException()
    {
        $nodeId = '123';

        $this->node->expects($this->once())
            ->method('getId')
            ->willReturn(false);
        $this->nodeResource->expects($this->once())
            ->method('load')
            ->with($this->node, $nodeId)
            ->willReturn($this->node);
        $this->repository->getById($nodeId);
    }

    /**
     * @test
     */
    public function testGetList()
    {
        $total = 10;

        $criteria = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteriaInterface::class)->getMock();
        /** @var \Magento\Framework\Api\SearchCriteriaInterface $criteria */
        $this->collection->addItem($this->node);
        $this->nodeSearchResult->expects($this->once())->method('setSearchCriteria')->with($criteria)->willReturnSelf();
        $this->collection->expects($this->once())->method('joinCmsPage')->willReturnSelf();
        $this->collection->expects($this->once())->method('joinMetaData')->willReturnSelf();
        $this->collection->expects($this->once())->method('addCmsPageInStoresColumn')->willReturnSelf();
        $this->collection->expects($this->once())->method('addLastChildSortOrderColumn')->willReturnSelf();
        $this->nodeSearchResult->expects($this->once())->method('setTotalCount')->with($total)->willReturnSelf();
        $this->collection->expects($this->once())->method('getSize')->willReturn($total);
        $this->node->expects($this->once())->method('getData')->willReturn(['data']);
        $this->nodeSearchResult->expects($this->once())->method('setItems')->with(['someData'])->willReturnSelf();
        $this->dataHelper->expects($this->once())
            ->method('populateWithArray');
        $this->dataObjectProcessor->expects($this->once())
            ->method('buildOutputDataArray')
            ->willReturn('someData');

        $this->assertEquals($this->nodeSearchResult, $this->repository->getList($criteria));
    }
}
