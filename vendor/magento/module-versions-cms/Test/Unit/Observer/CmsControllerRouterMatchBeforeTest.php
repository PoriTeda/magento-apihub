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
class CmsControllerRouterMatchBeforeTest extends \PHPUnit\Framework\TestCase
{
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

    /**
     * @var CurrentNodeResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $currentNodeResolverMock;

    /**
     * @var \Magento\VersionsCms\Helper\Hierarchy|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cmsHierarchyMock;

    /**
     * @var \Magento\VersionsCms\Observer\AddCmsToTopmenuItems
     */
    private $observer;

    protected function setUp()
    {
        $this->cmsHierarchyMock = $this->getMockBuilder(\Magento\VersionsCms\Helper\Hierarchy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->hierarchyNodeFactoryMock = $this->getMockBuilder(NodeFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->storeMock = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->getMockForAbstractClass();

        $this->storeManagerMock = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->getMockForAbstractClass();
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->currentNodeResolverMock = $this->getMockBuilder(CurrentNodeResolverInterface::class)
            ->getMockForAbstractClass();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->observer = $objectManagerHelper->getObject(
            \Magento\VersionsCms\Observer\CmsControllerRouterMatchBefore::class,
            [
                'cmsHierarchy' => $this->cmsHierarchyMock,
                'hierarchyNodeFactory' => $this->hierarchyNodeFactoryMock,
                'storeManager' => $this->storeManagerMock,
                'currentNodeResolver' => $this->currentNodeResolverMock,
            ]
        );
    }

    public function testExecute()
    {
        $identifier = 'identifier';
        $storeId = 1;
        $nodeId = 1;
        $pageId = 1;

        $condition = new \Magento\Framework\DataObject(['identifier' => $identifier, 'continue' => true]);

        $this->cmsHierarchyMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $eventMock = $this->getMockBuilder(\Magento\Framework\Event::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCondition',
            ])
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getCondition')
            ->willReturn($condition);

        $eventObserverMock = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $eventObserverMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);

        $hierarchyNode1 = $this->getMockBuilder(\Magento\VersionsCms\Model\Hierarchy\Node::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getHeritage',
                'loadByRequestUrl',
                'checkIdentifier',
                'getId',
                'getPageId',
                'getPageIsActive',
                'getPageIdentifier',
            ])
            ->getMock();
        $hierarchyNode1->expects($this->once())
            ->method('getHeritage')
            ->willReturnSelf();
        $hierarchyNode1->expects($this->once())
            ->method('loadByRequestUrl')
            ->with($identifier)
            ->willReturnSelf();
        $hierarchyNode1->expects($this->once())
            ->method('checkIdentifier')
            ->with($identifier, $this->storeMock)
            ->willReturn(false);
        $hierarchyNode1->expects($this->once())
            ->method('getId')
            ->willReturn($nodeId);
        $hierarchyNode1->expects($this->once())
            ->method('getPageId')
            ->willReturn($pageId);
        $hierarchyNode1->expects($this->once())
            ->method('getPageIsActive')
            ->willReturn(true);
        $hierarchyNode1->expects($this->once())
            ->method('getPageIdentifier')
            ->willReturn($identifier);

        $hierarchyNode1Data = [
            'data' => [
                'scope' => \Magento\VersionsCms\Model\Hierarchy\Node::NODE_SCOPE_STORE,
                'scope_id' => $storeId,
            ],
        ];

        $this->storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        $this->hierarchyNodeFactoryMock->expects($this->once())
            ->method('create')
            ->with($hierarchyNode1Data)
            ->willReturn($hierarchyNode1);

        $this->observer->execute($eventObserverMock);
    }
}
