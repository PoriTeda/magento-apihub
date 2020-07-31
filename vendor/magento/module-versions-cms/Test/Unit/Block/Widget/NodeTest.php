<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Test\Unit\Block\Widget;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\VersionsCms\Model\CurrentNodeResolverInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NodeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\VersionsCms\Block\Widget\Node
     */
    protected $nodeWidget;

    /**
     * @var \Magento\Store\Model\Store|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeMock;

    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\Node|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $nodeMock;

    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\NodeFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $hierarchyNodeFactoryMock;

    /**
     * @var string
     */
    protected $nodeLabel = 'Node Label';

    /**
     * @var \Magento\Framework\View\Element\Template\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contextMock;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var CurrentNodeResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $currentNodeResolverMock;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManagerMock;

    /**
     * @var \Magento\Framework\Event\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventManagerMock;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfigMock;

    /**
     * @var \Magento\Framework\Escaper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $escaperMock;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->setMethods(['getId', '__wakeup'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->nodeMock = $this->getMockBuilder(\Magento\VersionsCms\Model\Hierarchy\Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->hierarchyNodeFactoryMock = $this->getMockBuilder(\Magento\VersionsCms\Model\Hierarchy\NodeFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManagerMock = $this->getMockForAbstractClass(\Magento\Store\Model\StoreManagerInterface::class);
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMockForAbstractClass();

        $this->eventManagerMock = $this->getMockBuilder(\Magento\Framework\Event\ManagerInterface::class)
            ->getMockForAbstractClass();

        $this->scopeConfigMock = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->getMockForAbstractClass();

        $this->escaperMock = $this->getMockBuilder(\Magento\Framework\Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder(\Magento\Framework\View\Element\Template\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->contextMock->expects($this->any())
            ->method('getStoreManager')
            ->willReturn($this->storeManagerMock);
        $this->contextMock->expects($this->any())
            ->method('getEventManager')
            ->willReturn($this->eventManagerMock);
        $this->contextMock->expects($this->any())
            ->method('getEscaper')
            ->willReturn($this->escaperMock);
        $this->contextMock->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($this->scopeConfigMock);

        $this->currentNodeResolverMock = $this->getMockBuilder(CurrentNodeResolverInterface::class)
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->nodeWidget = $objectManagerHelper->getObject(
            \Magento\VersionsCms\Block\Widget\Node::class,
            [
                'hierarchyNodeFactory' => $this->hierarchyNodeFactoryMock,
                'currentNodeResolver' => $this->currentNodeResolverMock,
                'context' => $this->contextMock,
            ]
        );
    }

    /**
     * @param int $storeId
     * @param array $data
     * @param string $value
     * @return void
     *
     * @dataProvider getLabelDataProvider
     */
    public function testGetLabel($storeId, $data, $value)
    {
        $this->emulateToHtmlMethod();
        $this->storeMock->expects($this->any())
            ->method('getId')
            ->willReturn($storeId);

        $this->nodeWidget->setData($data);
        $this->assertEquals($value, $this->nodeWidget->getLabel());
    }

    /**
     * @return array
     */
    public function getLabelDataProvider()
    {
        return [
            [
                $storeId = 1,
                $data = ['anchor_text_1' => 'value_1'],
                $value = 'value_1'
            ],
            [
                $storeId = 1,
                $data = ['anchor_text_1' => 'value_1', 'anchor_text_0' => 'value_0'],
                $value = 'value_1'
            ],
            [
                $storeId = 1,
                $data = ['anchor_text_1' => 'value_1', 'anchor_text_0' => 'value_0', 'anchor_text' => 'value'],
                $value = 'value_1'
            ],
            [
                $storeId = 1,
                $data = ['anchor_text_0' => 'value_0', 'anchor_text' => 'value'],
                $value = 'value_0'
            ],
            [
                $storeId = 1,
                $data = ['anchor_text_2' => 'value_2', 'anchor_text' => 'value'],
                $value = 'value'
            ],
            [
                'storeId' => 1,
                'data' => ['anchor_text' => null, 'anchor_text_1' => null],
                'value' => $this->nodeLabel
            ]
        ];
    }

    /**
     * @param int $storeId
     * @param array $data
     * @param string $value
     * @return void
     *
     * @dataProvider getTitleDataProvider
     */
    public function testGetTitle($storeId, $data, $value)
    {
        $nodeId = 1;
        $this->nodeWidget->setData(['node_id' => $nodeId]);
        $this->hierarchyNodeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->nodeMock);
        $this->nodeMock->expects($this->once())
            ->method('load')
            ->with($nodeId)
            ->willReturnSelf();
        $this->nodeMock->expects($this->any())
            ->method('getLabel')
            ->willReturn($this->nodeLabel);
        $this->nodeWidget->toHtml();

        $this->storeMock->expects($this->any())
            ->method('getId')
            ->willReturn($storeId);

        $this->nodeWidget->setData($data);
        $this->assertEquals($value, $this->nodeWidget->getTitle());
    }

    /**
     * @return array
     */
    public function getTitleDataProvider()
    {
        return [
            [
                $storeId = 1,
                $data = ['title_1' => 'value_1'],
                $value = 'value_1'
            ],
            [
                $storeId = 1,
                $data = ['title_1' => 'value_1', 'title_0' => 'value_0'],
                $value = 'value_1'
            ],
            [
                $storeId = 1,
                $data = ['title_1' => 'value_1', 'title_0' => 'value_0', 'title' => 'value'],
                $value = 'value_1'
            ],
            [
                $storeId = 1,
                $data = ['title_0' => 'value_0', 'title' => 'value'],
                $value = 'value_0'
            ],
            [
                $storeId = 1,
                $data = ['title_2' => 'value_2', 'title' => 'value'],
                $value = 'value'
            ],
            [
                'storeId' => 1,
                'data' => ['title' => null, 'title_1' => null],
                'value' => $this->nodeLabel
            ]
        ];
    }

    /**
     * @return void
     */
    public function testGetHref()
    {
        $url = 'http://localhost/';
        $this->emulateToHtmlMethod();
        $this->nodeMock->expects($this->once())
            ->method('getUrl')
            ->willReturn($url);
        $this->assertSame($url, $this->nodeWidget->getHref());
    }

    /**
     * @param int $storeId
     * @param array $data
     * @param string $value
     * @return void
     *
     * @dataProvider getNodeIdDataProvider
     */
    public function testGetNodeId($storeId, $data, $value)
    {
        $this->storeMock->expects($this->any())
            ->method('getId')
            ->willReturn($storeId);

        $this->nodeWidget->setData($data);
        $this->assertEquals($value, $this->nodeWidget->getNodeId());
    }

    /**
     * @return array
     */
    public function getNodeIdDataProvider()
    {
        return [
            [
                $storeId = 1,
                $data = ['node_id_1' => 'value_1'],
                $value = 'value_1'
            ],
            [
                $storeId = 1,
                $data = ['node_id_1' => 'value_1', 'node_id_0' => 'value_0'],
                $value = 'value_1'
            ],
            [
                $storeId = 1,
                $data = ['node_id_1' => 'value_1', 'node_id_0' => 'value_0', 'node_id' => 'value'],
                $value = 'value_1'
            ],
            [
                $storeId = 1,
                $data = ['node_id_0' => 'value_0', 'node_id' => 'value'],
                $value = 'value_0'
            ],
            [
                $storeId = 1,
                $data = ['node_id_2' => 'value_2', 'node_id' => 'value'],
                $value = 'value'
            ],
            [
                'storeId' => 1,
                'data' => ['node_id' => null, 'node_id_1' => null],
                'value' => false
            ]
        ];
    }

    /**
     * Emulate execution of current block's toHtml() method
     *
     * Helper method, that emulates execution of toHtml() method of \Magento\VersionsCms\Block\Widget\Node object.
     * Required for testGetHref and testGetLabel test iterations.
     *
     * @return void
     */
    protected function emulateToHtmlMethod()
    {
        $this->currentNodeResolverMock->expects($this->once())
            ->method('get')
            ->with($this->requestMock)
            ->willReturn($this->nodeMock);

        $this->nodeMock->expects($this->any())
            ->method('getLabel')
            ->willReturn($this->nodeLabel);

        /** @var \Magento\Framework\DataObject */
        $transportObject = new \Magento\Framework\DataObject(
            [
                'html' => '<li><a title="" ></a></li>',
            ]
        );

        $this->eventManagerMock->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnMap([
                [
                    'view_block_abstract_to_html_before',
                    [
                        'block' => $this->nodeWidget,
                    ],
                    $this->eventManagerMock
                ],
                [
                    'view_block_abstract_to_html_after',
                    [
                        'block' => $this->nodeWidget,
                        'transport' => $transportObject,
                    ],
                    $this->eventManagerMock
                ],
            ]);

        $this->nodeWidget->toHtml();
    }
}
