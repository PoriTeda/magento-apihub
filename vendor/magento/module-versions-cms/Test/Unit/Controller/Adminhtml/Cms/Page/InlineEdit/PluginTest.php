<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Test\Unit\Controller\Adminhtml\Cms\Page\InlineEdit;

use \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory as NodeCollectionFactory;

class PluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\VersionsCms\Controller\Adminhtml\Cms\Page\InlineEdit\Plugin
     */
    protected $plugin;

    /**
     * @var \Magento\Cms\Controller\Adminhtml\Page\InlineEdit|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $inlineEditController;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var NodeCollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $nodeCollectionFactoryMock;

    /**
     * @var \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $nodeCollectionMock;

    /**
     * @var \Magento\Cms\Model\ResourceModel\Page\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cmsPageCollectionFactoryMock;

    /**
     * @var \Magento\Cms\Model\ResourceModel\Page\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cmsPageCollectionMock;

    /**
     * @var \Magento\Cms\Model\Page|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $pageMock;

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->inlineEditController = $this->getMockBuilder(\Magento\Cms\Controller\Adminhtml\Page\InlineEdit::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->nodeCollectionFactoryMock = $this->getMockBuilder(
            \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->cmsPageCollectionFactoryMock = $this->getMockBuilder(
            \Magento\Cms\Model\ResourceModel\Page\CollectionFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->cmsPageCollectionMock = $this->getMockBuilder(\Magento\Cms\Model\ResourceModel\Page\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getItemById'])
            ->getMock();
        $this->nodeCollectionMock = $this->getMockBuilder(
            \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection::class
        )->disableOriginalConstructor()
            ->setMethods(['load', 'joinPageExistsNodeInfo', 'getData'])
            ->getMock();
        $this->pageMock = $this->getMockBuilder(\Magento\Cms\Model\Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'setData'])
            ->getMock();

        $this->plugin = $this->objectManager->getObject(
            \Magento\VersionsCms\Controller\Adminhtml\Cms\Page\InlineEdit\Plugin::class,
            [
                'nodeCollectionFactory' => $this->nodeCollectionFactoryMock,
                'cmsPageCollectionFactory' => $this->cmsPageCollectionFactoryMock
            ]
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBeforeSetCmsPageDataWithDefaultPage()
    {
        $pageId = 1;
        $expectsPageData = [
            'page_id' => '1',
            'title' => '404 Not Found',
            'page_layout' => '1column',
            'meta_keywords' => 'Page keywords',
            'meta_description' => 'Page description',
            'identifier' => 'no-route',
            'content' => '404 Not Found Content goes here...',
        ];
        $pageData = [
            'page_id' => '1',
            'title' => '404 Not Found',
            'page_layout' => '1column',
            'meta_keywords' => 'Page keywords',
            'meta_description' => 'Page description',
        ];
        $nodeCollectionData = [
            [
                'node_id' => '1',
                'parent_node_id' => null,
                'page_id' => null,
                'identifier' => 'my-node',
                'label' => 'My Node',
                'level' => '1',
                'sort_order' => '0',
                'request_url' => 'my-node',
                'xpath' => '1',
                'scope' => 'default',
                'scope_id' => '0',
                'page_exists' => '0',
                'current_page' => '0'
            ],
            [
                'node_id' => '17',
                'parent_node_id' => null,
                'page_id' => null,
                'identifier' => 'node-2',
                'label' => 'Node 2',
                'level' => '1',
                'sort_order' => '1',
                'request_url' => 'node-2',
                'xpath' => '17',
                'scope' => 'default',
                'scope_id' => '0',
                'page_exists' => '0',
                'current_page' => '0'
            ],
            [
                'node_id' => '27',
                'parent_node_id' => '1',
                'page_id' => null,
                'identifier' => 'your-node',
                'label' => 'Your Node',
                'level' => '2',
                'sort_order' => '0',
                'request_url' => 'my-node/your-node',
                'xpath' => '1/27',
                'scope' => 'default',
                'scope_id' => '0',
                'page_exists' => '0',
                'current_page' => '0'
            ],
            [
                'node_id' => '29',
                'parent_node_id' => null,
                'page_id' => null,
                'identifier' => 'node-3',
                'label' => 'Node 3',
                'level' => '1',
                'sort_order' => '2',
                'request_url' => 'node-3',
                'xpath' => '29',
                'scope' => 'default',
                'scope_id' => '0',
                'page_exists' => '0',
                'current_page' => '0'
            ],
            [
                'node_id' => '32',
                'parent_node_id' => '27',
                'page_id' => '36',
                'identifier' => null,
                'label' => null,
                'level' => '3',
                'sort_order' => '0',
                'request_url' => 'my-node/your-node/your-node-page',
                'xpath' => '1/27/32',
                'scope' => 'default',
                'scope_id' => '0',
                'page_exists' => '0',
                'current_page' => '0'
            ],
            [
                'node_id' => '33',
                'parent_node_id' => '1',
                'page_id' => '33',
                'identifier' => null,
                'label' => null,
                'level' => '2',
                'sort_order' => '1',
                'request_url' => 'my-node/page-100',
                'xpath' => '1/33',
                'scope' => 'default',
                'scope_id' => '0',
                'page_exists' => '0',
                'current_page' => '0'
            ],
            [
                'node_id' => '34',
                'parent_node_id' => '17',
                'page_id' => '34',
                'identifier' => null,
                'label' => null,
                'level' => '2',
                'sort_order' => '0',
                'request_url' => 'node-2/page-200',
                'xpath' => '17/34',
                'scope' => 'default',
                'scope_id' => '0',
                'page_exists' => '0',
                'current_page' => '0'
            ],
            [
                'node_id' => '35',
                'parent_node_id' => '29',
                'page_id' => '35',
                'identifier' => null,
                'label' => null,
                'level' => '2',
                'sort_order' => '0',
                'request_url' => 'node-3/page-300',
                'xpath' => '29/35',
                'scope' => 'default',
                'scope_id' => '0',
                'page_exists' => '0',
                'current_page' => '0'
            ]
        ];
        // @codingStandardsIgnoreStart
        $resultData = [
            'nodes_data' => '{"1":{"node_id":"1","page_id":null,"parent_node_id":null,"label":"My Node","sort_order":"0","current_page":false,"page_exists":false},"17":{"node_id":"17","page_id":null,"parent_node_id":null,"label":"Node 2","sort_order":"1","current_page":false,"page_exists":false},"27":{"node_id":"27","page_id":null,"parent_node_id":"1","label":"Your Node","sort_order":"0","current_page":false,"page_exists":false},"29":{"node_id":"29","page_id":null,"parent_node_id":null,"label":"Node 3","sort_order":"2","current_page":false,"page_exists":false},"32":{"node_id":"32","page_id":"36","parent_node_id":"27","label":"Page 0","sort_order":"0","current_page":false,"page_exists":false},"33":{"node_id":"33","page_id":"33","parent_node_id":"1","label":"Page 1","sort_order":"1","current_page":false,"page_exists":false},"34":{"node_id":"34","page_id":"34","parent_node_id":"17","label":"Page 2","sort_order":"0","current_page":false,"page_exists":false},"35":{"node_id":"35","page_id":"35","parent_node_id":"29","label":"Page 3","sort_order":"0","current_page":false,"page_exists":false},"_0":{"node_id":"_0","page_id":1,"parent_node_id":null,"label":"404 Not Found","current_page":true}}',
            'node_ids' => ''
        ];
        // @codingStandardsIgnoreEnd
        $this->pageMock->expects($this->once())->method('getId')->willReturn($pageId);
        $this->cmsPageCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->cmsPageCollectionMock);
        $this->cmsPageCollectionMock->expects($this->once())->method('load')->willReturnSelf();
        $page0 = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getData']);
        $page1 = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getData']);
        $page2 = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getData']);
        $page3 = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getData']);
        $page404 = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getData']);
        $this->cmsPageCollectionMock->expects($this->any())->method('getItemById')->willReturnMap(
            [
                ['36', $page0],
                ['33', $page1],
                ['34', $page2],
                ['35', $page3],
                [1, $page404]
            ]
        );
        $page0->expects($this->once())->method('getData')->with('title')->willReturn('Page 0');
        $page1->expects($this->once())->method('getData')->with('title')->willReturn('Page 1');
        $page2->expects($this->once())->method('getData')->with('title')->willReturn('Page 2');
        $page3->expects($this->once())->method('getData')->with('title')->willReturn('Page 3');
        $page404->expects($this->once())->method('getData')->with('title')->willReturn('404 Not Found');
        $this->nodeCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->nodeCollectionMock);
        $this->nodeCollectionMock->expects($this->once())->method('load')->willReturnSelf();
        $this->nodeCollectionMock->expects($this->once())->method('joinPageExistsNodeInfo')->willReturnSelf();
        $this->nodeCollectionMock->expects($this->once())->method('getData')->willReturn($nodeCollectionData);
        $this->pageMock->expects($this->once())->method('setData')->with($resultData)->willReturnSelf();
        $this->plugin->beforeSetCmsPageData($this->inlineEditController, $this->pageMock, $expectsPageData, $pageData);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBeforeSetCmsPageDataWithCustomPage()
    {
        $pageId = 36;
        $expectsPageData = [
            'page_id' => '1',
            'title' => '404 Not Found',
            'page_layout' => '1column',
            'meta_keywords' => 'Page keywords',
            'meta_description' => 'Page description',
            'identifier' => 'no-route',
            'content' => '404 Not Found Content goes here...',
        ];
        $pageData = [
            'page_id' => '1',
            'title' => '404 Not Found',
            'page_layout' => '1column',
            'meta_keywords' => 'Page keywords',
            'meta_description' => 'Page description',
        ];
        $nodeCollectionData = [
            [
                'node_id' => '1',
                'parent_node_id' => null,
                'page_id' => null,
                'identifier' => 'my-node',
                'label' => 'My Node',
                'level' => '1',
                'sort_order' => '0',
                'request_url' => 'my-node',
                'xpath' => '1',
                'scope' => 'default',
                'scope_id' => '0',
                'page_exists' => '0',
                'current_page' => '0'
            ],
            [
                'node_id' => '17',
                'parent_node_id' => null,
                'page_id' => null,
                'identifier' => 'node-2',
                'label' => 'Node 2',
                'level' => '1',
                'sort_order' => '1',
                'request_url' => 'node-2',
                'xpath' => '17',
                'scope' => 'default',
                'scope_id' => '0',
                'page_exists' => '0',
                'current_page' => '0'
            ],
            [
                'node_id' => '27',
                'parent_node_id' => '1',
                'page_id' => null,
                'identifier' => 'your-node',
                'label' => 'Your Node',
                'level' => '2',
                'sort_order' => '0',
                'request_url' => 'my-node/your-node',
                'xpath' => '1/27',
                'scope' => 'default',
                'scope_id' => '0',
                'page_exists' => '1',
                'current_page' => '0'
            ],
            [
                'node_id' => '29',
                'parent_node_id' => null,
                'page_id' => null,
                'identifier' => 'node-3',
                'label' => 'Node 3',
                'level' => '1',
                'sort_order' => '2',
                'request_url' => 'node-3',
                'xpath' => '29',
                'scope' => 'default',
                'scope_id' => '0',
                'page_exists' => '0',
                'current_page' => '0'
            ],
            [
                'node_id' => '32',
                'parent_node_id' => '27',
                'page_id' => '36',
                'identifier' => null,
                'label' => null,
                'level' => '3',
                'sort_order' => '0',
                'request_url' => 'my-node/your-node/your-node-page',
                'xpath' => '1/27/32',
                'scope' => 'default',
                'scope_id' => '0',
                'page_exists' => '0',
                'current_page' => '1'
            ],
            [
                'node_id' => '33',
                'parent_node_id' => '1',
                'page_id' => '33',
                'identifier' => null,
                'label' => null,
                'level' => '2',
                'sort_order' => '1',
                'request_url' => 'my-node/page-100',
                'xpath' => '1/33',
                'scope' => 'default',
                'scope_id' => '0',
                'page_exists' => '0',
                'current_page' => '0'
            ],
            [
                'node_id' => '34',
                'parent_node_id' => '17',
                'page_id' => '34',
                'identifier' => null,
                'label' => null,
                'level' => '2',
                'sort_order' => '0',
                'request_url' => 'node-2/page-200',
                'xpath' => '17/34',
                'scope' => 'default',
                'scope_id' => '0',
                'page_exists' => '0',
                'current_page' => '0'
            ],
            [
                'node_id' => '35',
                'parent_node_id' => '29',
                'page_id' => '35',
                'identifier' => null,
                'label' => null,
                'level' => '2',
                'sort_order' => '0',
                'request_url' => 'node-3/page-300',
                'xpath' => '29/35',
                'scope' => 'default',
                'scope_id' => '0',
                'page_exists' => '0',
                'current_page' => '0'
            ]
        ];
        // @codingStandardsIgnoreStart
        $resultData = [
            'nodes_data' => '{"1":{"node_id":"1","page_id":null,"parent_node_id":null,"label":"My Node","sort_order":"0","current_page":false,"page_exists":false},"17":{"node_id":"17","page_id":null,"parent_node_id":null,"label":"Node 2","sort_order":"1","current_page":false,"page_exists":false},"27":{"node_id":"27","page_id":null,"parent_node_id":"1","label":"Your Node","sort_order":"0","current_page":false,"page_exists":true},"29":{"node_id":"29","page_id":null,"parent_node_id":null,"label":"Node 3","sort_order":"2","current_page":false,"page_exists":false},"32":{"node_id":"32","page_id":"36","parent_node_id":"27","label":"Your node page","sort_order":"0","current_page":true,"page_exists":false},"33":{"node_id":"33","page_id":"33","parent_node_id":"1","label":"Page 1","sort_order":"1","current_page":false,"page_exists":false},"34":{"node_id":"34","page_id":"34","parent_node_id":"17","label":"Page 2","sort_order":"0","current_page":false,"page_exists":false},"35":{"node_id":"35","page_id":"35","parent_node_id":"29","label":"Page 3","sort_order":"0","current_page":false,"page_exists":false},"_0":{"node_id":"_0","page_id":36,"parent_node_id":null,"label":"Your node page","current_page":true}}',
            'node_ids' => '27'
        ];
        // @codingStandardsIgnoreEnd
        $this->pageMock->expects($this->once())->method('getId')->willReturn($pageId);
        $this->cmsPageCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->cmsPageCollectionMock);
        $this->cmsPageCollectionMock->expects($this->once())->method('load')->willReturnSelf();
        $page0 = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getData']);
        $page1 = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getData']);
        $page2 = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getData']);
        $page3 = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getData']);
        $yourNodePage = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getData']);
        $this->cmsPageCollectionMock->expects($this->any())->method('getItemById')->willReturnMap(
            [
                ['36', $page0],
                ['33', $page1],
                ['34', $page2],
                ['35', $page3],
                [36, $yourNodePage]
            ]
        );
        $page0->expects($this->once())->method('getData')->with('title')->willReturn('Your node page');
        $page1->expects($this->once())->method('getData')->with('title')->willReturn('Page 1');
        $page2->expects($this->once())->method('getData')->with('title')->willReturn('Page 2');
        $page3->expects($this->once())->method('getData')->with('title')->willReturn('Page 3');
        $yourNodePage->expects($this->once())->method('getData')->with('title')->willReturn('Your node page');
        $this->nodeCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->nodeCollectionMock);
        $this->nodeCollectionMock->expects($this->once())->method('load')->willReturnSelf();
        $this->nodeCollectionMock->expects($this->once())->method('joinPageExistsNodeInfo')->willReturnSelf();
        $this->nodeCollectionMock->expects($this->once())->method('getData')->willReturn($nodeCollectionData);
        $this->pageMock->expects($this->once())->method('setData')->with($resultData)->willReturnSelf();
        $this->plugin->beforeSetCmsPageData($this->inlineEditController, $this->pageMock, $expectsPageData, $pageData);
    }
}
