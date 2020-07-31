<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Test\Unit\Observer;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\VersionsCms\Model\CurrentNodeResolverInterface;
use Magento\VersionsCms\Model\Hierarchy\Node as NodeMock;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AffectCmsPageRenderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\VersionsCms\Helper\Hierarchy|MockObject
     */
    protected $cmsHierarchyMock;

    /**
     * @var \Magento\Framework\App\ViewInterface|MockObject
     */
    protected $viewMock;

    /**
     * @var \Magento\Framework\Event\Observer|MockObject
     */
    protected $eventObserver;

    /**
     * @var \Magento\Framework\View\Layout\ProcessorInterface|MockObject
     */
    protected $updateMock;

    /**
     * @var \Magento\VersionsCms\Observer\AffectCmsPageRender
     */
    protected $observer;

    /**
     * @var CurrentNodeResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $currentNodeResolverMock;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->cmsHierarchyMock = $this->createMock(\Magento\VersionsCms\Helper\Hierarchy::class);
        $this->viewMock = $this->createMock(\Magento\Framework\App\ViewInterface::class);

        $this->eventObserver = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getPage',
                'getRequest',
            ])
            ->getMock();

        $this->updateMock = $this->createMock(\Magento\Framework\View\Layout\ProcessorInterface::class);

        /** @var \Magento\Framework\View\LayoutInterface|MockObject $layoutMock */
        $layoutMock = $this->createMock(\Magento\Framework\View\LayoutInterface::class);
        $layoutMock->expects($this->any())
            ->method('getUpdate')
            ->willReturn($this->updateMock);
        $this->viewMock->expects($this->any())
            ->method('getLayout')
            ->willReturn($layoutMock);

        $this->currentNodeResolverMock = $this->getMockBuilder(CurrentNodeResolverInterface::class)
            ->getMockForAbstractClass();

        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMockForAbstractClass();

        $this->observer = $this->objectManagerHelper->getObject(
            \Magento\VersionsCms\Observer\AffectCmsPageRender::class,
            [
                'cmsHierarchy' => $this->cmsHierarchyMock,
                'view' => $this->viewMock,
                'currentNodeResolver' => $this->currentNodeResolverMock,
            ]
        );
    }

    /**
     * @param NodeMock|null $node
     * @param bool $hierarchyEnabled
     * @return void
     * @dataProvider invokeWhenHierarchyDisabledOrNodeAbsentDataProvider
     */
    public function testInvokeWhenHierarchyDisabledOrNodeAbsent($node, $hierarchyEnabled)
    {
        $this->currentNodeResolverMock->expects($this->once())
            ->method('get')
            ->with($this->requestMock)
            ->willReturn($node);

        $this->cmsHierarchyMock->expects($this->any())
            ->method('isEnabled')
            ->willReturn($hierarchyEnabled);

        $this->updateMock->expects($this->never())
            ->method('getHandles');
        $this->updateMock->expects($this->never())
            ->method('addHandle');

        $this->eventObserver->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->assertSame($this->observer, $this->observer->execute($this->eventObserver));
    }

    /**
     * @return array
     */
    public function invokeWhenHierarchyDisabledOrNodeAbsentDataProvider()
    {
        return [
            ['node' => null, 'hierarchyEnabled' => true],
            ['node' => null, 'hierarchyEnabled' => false],
            ['node' => $this->getNodeMock(), 'hierarchyEnabled' => false]
        ];
    }

    /**
     * @return void
     */
    public function testInvokeWhenMenuLayoutEmpty()
    {
        $this->configureMockObjects(null, '2columns-right', []);

        $this->updateMock->expects($this->never())
            ->method('addHandle');

        $this->assertSame($this->observer, $this->observer->execute($this->eventObserver));
    }

    /**
     * @return void
     */
    public function testInvokeWhenAllowedNonIntersectLoadedHandles()
    {
        $loadedHandles = ['default', 'cms_page'];
        $menuLayout = [
            'pageLayoutHandles' => ['2columns-left', '3columns'],
            'handle' => 'menu_left_column'
        ];

        $this->configureMockObjects($menuLayout, '2columns-right', $loadedHandles);

        $this->updateMock->expects($this->never())
            ->method('addHandle');

        $this->assertSame($this->observer, $this->observer->execute($this->eventObserver));
    }

    /**
     * @return void
     */
    public function testInvoke()
    {
        $loadedHandles = ['default', 'cms_page'];
        $menuLayout = [
            'pageLayoutHandles' => ['2columns-left', '3columns'],
            'handle' => 'menu_left_column'
        ];

        $this->configureMockObjects($menuLayout, '2columns-left', $loadedHandles);

        $this->updateMock->expects($this->once())
            ->method('addHandle')
            ->with($menuLayout['handle']);

        $this->assertSame($this->observer, $this->observer->execute($this->eventObserver));
    }

    /**
     * Configure mock objects
     *
     * Helper method, that creates mock objects and applies configuration to mock objects,
     * required for test iterations.
     *
     * @param array|null $menuLayout
     * @param string $pageLayout
     * @param array $loadedHandles
     * @return void
     */
    protected function configureMockObjects($menuLayout, $pageLayout, $loadedHandles)
    {
        $nodeMock = $this->getNodeMock();
        $nodeMock->expects($this->once())
            ->method('getMenuLayout')
            ->willReturn($menuLayout);

        /** @var \Magento\Cms\Model\Page|MockObject $pageMock */
        $pageMock = $this->createMock(\Magento\Cms\Model\Page::class);
        $pageMock->expects($this->once())
            ->method('getPageLayout')
            ->willReturn($pageLayout);

        $this->currentNodeResolverMock->expects($this->once())
            ->method('get')
            ->with($this->requestMock)
            ->willReturn($nodeMock);
        $this->cmsHierarchyMock->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);
        $this->updateMock->expects($this->once())
            ->method('getHandles')
            ->willReturn($loadedHandles);
        $this->eventObserver->expects($this->once())
            ->method('getPage')
            ->willReturn($pageMock);
        $this->eventObserver->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
    }

    /**
     * Create Hierarchy Node mock object
     *
     * Helper method, that provides unified logic of creation of Hierarchy Node mock object.
     *
     * @return NodeMock|MockObject
     */
    protected function getNodeMock()
    {
        return $this->createMock(\Magento\VersionsCms\Model\Hierarchy\Node::class);
    }
}
