<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VersionsCms\Test\Unit\Model\Hierarchy;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\VersionsCms\Model\Hierarchy\Node;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class NodeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Node
     */
    protected $node;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $nodeResourceMock;

    /**
     * @var \Magento\VersionsCms\Helper\Hierarchy|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $hierarchyHelperMock;

    /**
     * @var AbstractCollection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $abstractCollectionMock;

    protected function setUp()
    {
        $this->nodeResourceMock = $this->getMockBuilder(\Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->hierarchyHelperMock = $this->getMockBuilder(\Magento\VersionsCms\Helper\Hierarchy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->abstractCollectionMock = $this->getMockBuilder(AbstractCollection::class)
            ->setMethods([
                'joinPageExistsNodeInfo',
                'applyPageExistsOrNodeIdFilter',
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->node = $this->objectManagerHelper->getObject(
            \Magento\VersionsCms\Model\Hierarchy\Node::class,
            [
                'resource' => $this->nodeResourceMock,
                'cmsHierarchy' => $this->hierarchyHelperMock,
                'resourceCollection' => $this->abstractCollectionMock
            ]
        );
    }

    /**
     * @param array $nodeData
     * @param array $preparedNodeData
     * @param array|null $remove
     *
     * @dataProvider collectTreeDataProvider
     */
    public function testCollectTreeSuccess(
        array $nodeData,
        array $preparedNodeData,
        array $remove = null
    ) {
        $id = 111;
        $requestUrl = 'request/url';

        $this->prepareCollectTree($nodeData, $preparedNodeData);
        $this->persistTreeSuccess($id, $requestUrl, $remove);

        $this->assertSame(
            $this->node,
            $this->node->collectTree([$nodeData], $remove)
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Please correct the node data.
     */
    public function testCollectTreeValidationFailure()
    {
        $data = [[]];
        $this->node->collectTree($data, null);
    }

    /**
     * @param array $nodeData
     * @param array $preparedNodeData
     * @param array|null $remove
     *
     * @dataProvider collectTreeDataProvider
     * @expectedException \Exception
     * @expectedExceptionMessage bad result
     */
    public function testCollectTreeDatabaseFailure(
        array $nodeData,
        array $preparedNodeData,
        array $remove = null
    ) {
        $id = 111;
        $requestUrl = 'request/url';

        $this->prepareCollectTree($nodeData, $preparedNodeData);
        $this->persistTreeFailure($id, $requestUrl, $remove);

        $this->node->collectTree([$nodeData], $remove);
    }

    /**
     * @return array
     */
    public function collectTreeDataProvider()
    {
        return [
            'data set #1' => [
                'nodeData' => [
                    'node_id' => '111',
                    'page_id' => '222',
                    'label' => 'some label',
                    'identifier' => 'identifier',
                    'level' => '8',
                    'sort_order' => '13',
                    'parent_node_id' => '333'
                ],
                'preparedNodeData' => [
                    'node_id' => 111,
                    'page_id' => 222,
                    'label' => null,
                    'identifier' => null,
                    'level' => 8,
                    'sort_order' => 13,
                    'request_url' => 'identifier',
                    'scope' => Node::NODE_SCOPE_DEFAULT,
                    'scope_id' => Node::NODE_SCOPE_DEFAULT_ID
                ],
                'remove' => null
            ],
            'data set #2' => [
                'nodeData' => [
                    'node_id' => '_111',
                    'page_id' => '',
                    'label' => 'some label',
                    'identifier' => 'identifier',
                    'level' => '8',
                    'sort_order' => '13',
                    'parent_node_id' => null
                ],
                'preparedNodeData' => [
                    'node_id' => null,
                    'page_id' => null,
                    'label' => 'some label',
                    'identifier' => 'identifier',
                    'level' => 8,
                    'sort_order' => 13,
                    'request_url' => 'identifier',
                    'scope' => Node::NODE_SCOPE_DEFAULT,
                    'scope_id' => Node::NODE_SCOPE_DEFAULT_ID
                ],
                'remove' => ['444', '555']
            ]
        ];
    }

    /**
     * @param array $nodeData
     * @param array $preparedNodeData
     */
    protected function prepareCollectTree(
        array $nodeData,
        array $preparedNodeData
    ) {
        $this->hierarchyHelperMock->expects($this->any())
            ->method('copyMetaData')
            ->with($nodeData, $preparedNodeData)
            ->willReturn($preparedNodeData);
    }

    /**
     * @param int $id
     * @param string $requestUrl
     * @param array|null $remove
     */
    protected function persistTreeSuccess(
        $id,
        $requestUrl,
        array $remove = null
    ) {
        $this->preparePersistTree($id, $requestUrl, $remove);
        $this->nodeResourceMock->expects($this->any())
            ->method('save')
            ->willReturnSelf();
        $this->nodeResourceMock->expects($this->once())
            ->method('addEmptyNode')
            ->with(Node::NODE_SCOPE_DEFAULT, Node::NODE_SCOPE_DEFAULT_ID)
            ->willReturnSelf();
        $this->nodeResourceMock->expects($this->once())
            ->method('commit')
            ->willReturnSelf();
    }

    /**
     * @param int $id
     * @param string $requestUrl
     * @param array|null $remove
     */
    protected function persistTreeFailure(
        $id,
        $requestUrl,
        array $remove = null
    ) {
        $this->preparePersistTree($id, $requestUrl, $remove);
        $this->nodeResourceMock->expects($this->any())
            ->method('save')
            ->willReturnSelf();
        $this->nodeResourceMock->expects($this->once())
            ->method('addEmptyNode')
            ->with(Node::NODE_SCOPE_DEFAULT, Node::NODE_SCOPE_DEFAULT_ID)
            ->willThrowException(new \Exception('bad result'));
        $this->nodeResourceMock->expects($this->never())
            ->method('commit')
            ->willReturnSelf();
        $this->nodeResourceMock->expects($this->once())
            ->method('rollback')
            ->willReturnSelf();
    }

    /**
     * @param int $id
     * @param string $requestUrl
     * @param array|null $remove
     */
    protected function preparePersistTree($id, $requestUrl, array $remove = null)
    {
        $this->node->setData(Node::NODE_ID, $id);
        $this->node->setData(Node::REQUEST_URL, $requestUrl);

        $this->nodeResourceMock->expects($this->once())
            ->method('beginTransaction')
            ->willReturnSelf();
        $this->nodeResourceMock->expects($this->any())
            ->method('dropNodes')
            ->with($remove)
            ->willReturnSelf();
    }

    /**
     * Test append page to nodes.
     *
     * @dataProvider appendPageToNodesDataProvider
     * @param array $storeIds
     * @param int $setScopeCallTimes
     * @param int $setScopeIdCallTimes
     * @return void
     */
    public function testAppendPageToNodes($storeIds, $setScopeCallTimes, $setScopeIdCallTimes)
    {
        $node = $this->getMockBuilder(Node::class)
            ->setMethods([
                'getId', 'getPageExists', 'getLevel',
                'getRequestUrl', 'getXpath', 'setScope', 'setScopeId',
                'addData', 'setParentNodeId', 'unsetData', 'setLevel',
                'setSortOrder', 'setRequestUrl', 'setXpath', 'save'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $node->expects($this->any())
            ->method('getId')
            ->willReturn(0);
        $node->expects($this->any())
            ->method('getPageExists')
            ->willReturn(null);
        $node->expects($this->any())
            ->method('getLevel')
            ->willReturn(1);
        $node->expects($this->any())
            ->method('getRequestUrl')
            ->willReturn('requestUrl');
        $node->expects($this->any())
            ->method('getXpath')
            ->willReturn(1);
        $node->expects($this->exactly($setScopeCallTimes))
            ->method('setScope');
        $node->expects($this->exactly($setScopeIdCallTimes))
            ->method('setScopeId');

        // These methods will return self, so we setup them in loop
        $nodeMethodsReturnSelf = [
            'addData', 'setParentNodeId', 'unsetData', 'setLevel', 'setSortOrder', 'setRequestUrl', 'setXpath', 'save'
        ];
        foreach ($nodeMethodsReturnSelf as $method) {
            $node->expects($this->any())->method($method)->willReturnSelf();
        }

        $parentNodes = [$node];
        $this->abstractCollectionMock->expects($this->any())
            ->method('joinPageExistsNodeInfo')
            ->willReturnSelf();
        $this->abstractCollectionMock->expects($this->any())
            ->method('applyPageExistsOrNodeIdFilter')
            ->willReturn($parentNodes);

        $page = $this->getMockBuilder(\Magento\Cms\Model\Page::class)
            ->setMethods(['getStores', 'getId', 'getIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $page->expects($this->any())
            ->method('getId')
            ->willReturn(10);
        $page->expects($this->any())
            ->method('getIdentifier')
            ->willReturn(20);
        $page->expects($this->any())
            ->method('getStores')
            ->willReturn($storeIds);

        $nodes = [2];

        $this->node->appendPageToNodes($page, $nodes);
    }

    /**
     * Data provider for testAppendPageToNode test.
     *
     * @return array
     */
    public function appendPageToNodesDataProvider()
    {
        return [
            'store_id equals "0"' => [
                [0, 0],
                0,
                2
            ],
            'store_id not equals "0"' => [
                [2, 3, 1],
                3,
                3
            ],
        ];
    }
}
