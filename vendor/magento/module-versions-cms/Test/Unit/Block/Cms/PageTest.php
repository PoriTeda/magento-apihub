<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Test\Unit\Block\Cms;

use Magento\Store\Model\ScopeInterface;
use Magento\VersionsCms\Model\CurrentNodeResolverInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\VersionsCms\Block\Cms\Page
     */
    private $block;

    /**
     * @var \Magento\Cms\Model\Page|\PHPUnit_Framework_MockObject_MockObject
     */
    private $pageMock;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfigMock;

    /**
     * @var \Magento\Framework\View\Page\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $pageConfigMock;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManagerMock;

    /**
     * @var \Magento\Store\Api\Data\StoreInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeMock;

    /***
     * @var CurrentNodeResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $currentNodeResolverMock;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    protected function setUp()
    {
        $this->pageMock = $this->getMockBuilder(\Magento\Cms\Model\Page::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfigMock = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->getMockForAbstractClass();

        $this->pageConfigMock = $this->getMockBuilder(\Magento\Framework\View\Page\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeMock = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->setMethods(['getBaseUrl'])
            ->getMockForAbstractClass();

        $this->storeManagerMock = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->getMockForAbstractClass();
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->currentNodeResolverMock = $this->getMockBuilder(CurrentNodeResolverInterface::class)
            ->getMockForAbstractClass();

        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMockForAbstractClass();

        $contextMock = $this->getMockBuilder(\Magento\Framework\View\Element\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($this->scopeConfigMock);
        $contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->block = $objectManager->getObject(
            \Magento\VersionsCms\Block\Cms\Page::class,
            [
                'page' => $this->pageMock,
                'context' => $contextMock,
                'pageConfig' => $this->pageConfigMock,
                'storeManager' => $this->storeManagerMock,
                'currentNodeResolver' => $this->currentNodeResolverMock,
            ]
        );
    }

    public function testAddBreadcrumbsWithDisabledBreadcrums()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('web/default/show_cms_breadcrumbs', ScopeInterface::SCOPE_STORE)
            ->willReturn(false);

        $layoutMock = $this->getMockBuilder(\Magento\Framework\View\LayoutInterface::class)
            ->getMockForAbstractClass();
        $layoutMock->expects($this->once())
            ->method('getBlock')
            ->with('page.main.title')
            ->willReturn(null);

        $this->createAdditionalMockObjects();
        $this->assertEquals($this->block, $this->block->setLayout($layoutMock));
    }

    public function testAddBreadcrumbsWithNoBreadcrumsBlock()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('web/default/show_cms_breadcrumbs', ScopeInterface::SCOPE_STORE)
            ->willReturn(true);

        $layoutMock = $this->getMockBuilder(\Magento\Framework\View\LayoutInterface::class)
            ->getMockForAbstractClass();
        $layoutMock->expects($this->exactly(2))
            ->method('getBlock')
            ->willReturnMap([
                ['page.main.title', null],
                ['breadcrumbs', null],
            ]);

        $this->createAdditionalMockObjects();
        $this->assertEquals($this->block, $this->block->setLayout($layoutMock));
    }

    public function testAddBreadcrumbsWithPageIdentifierEqualCmsHomePage()
    {
        $pageIdentifier = 'custom-page-identifier';

        $this->scopeConfigMock->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnMap([
                ['web/default/show_cms_breadcrumbs', ScopeInterface::SCOPE_STORE, null, true],
                ['web/default/cms_home_page', ScopeInterface::SCOPE_STORE, null, $pageIdentifier],
            ]);

        $breadcrumbMock = $this->getMockBuilder(\Magento\Framework\View\Element\BlockInterface::class)
            ->getMockForAbstractClass();

        $layoutMock = $this->getMockBuilder(\Magento\Framework\View\LayoutInterface::class)
            ->getMockForAbstractClass();
        $layoutMock->expects($this->exactly(2))
            ->method('getBlock')
            ->willReturnMap([
                ['page.main.title', null],
                ['breadcrumbs', $breadcrumbMock],
            ]);

        $this->createAdditionalMockObjects($pageIdentifier);
        $this->assertEquals($this->block, $this->block->setLayout($layoutMock));
    }

    public function testAddBreadcrumbsWithPageIdentifiersNotEqualsCmsPages()
    {
        $customPageIdentifier = 'custom-page-identifier';

        $this->scopeConfigMock->expects($this->exactly(3))
            ->method('getValue')
            ->willReturnMap([
                ['web/default/show_cms_breadcrumbs', ScopeInterface::SCOPE_STORE, null, true],
                ['web/default/cms_home_page', ScopeInterface::SCOPE_STORE, null, ''],
                ['web/default/cms_no_route', ScopeInterface::SCOPE_STORE, null, $customPageIdentifier],
            ]);

        $breadcrumbMock = $this->getMockBuilder(\Magento\Framework\View\Element\BlockInterface::class)
            ->getMockForAbstractClass();

        $layoutMock = $this->getMockBuilder(\Magento\Framework\View\LayoutInterface::class)
            ->getMockForAbstractClass();
        $layoutMock->expects($this->exactly(2))
            ->method('getBlock')
            ->willReturnMap([
                ['page.main.title', null],
                ['breadcrumbs', $breadcrumbMock],
            ]);

        $this->createAdditionalMockObjects($customPageIdentifier);
        $this->assertEquals($this->block, $this->block->setLayout($layoutMock));
    }

    public function testAddBreadcrumbsWithNoCurrentNode()
    {
        $baseUrl = 'base-url';

        $customPageIdentifier = 'custom-page-identifier';
        $customPageTitle = 'custom-page-title';

        $this->scopeConfigMock->expects($this->exactly(3))
            ->method('getValue')
            ->willReturnMap([
                ['web/default/show_cms_breadcrumbs', ScopeInterface::SCOPE_STORE, null, true],
                ['web/default/cms_home_page', ScopeInterface::SCOPE_STORE, null, ''],
                ['web/default/cms_no_route', ScopeInterface::SCOPE_STORE, null, ''],
            ]);

        $this->storeMock->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn($baseUrl);

        $this->currentNodeResolverMock->expects($this->once())
            ->method('get')
            ->with($this->requestMock)
            ->willReturn(false);

        $breadcrumbMock = $this->getMockBuilder(\Magento\Framework\View\Element\BlockInterface::class)
            ->setMethods(['addCrumb'])
            ->getMockForAbstractClass();
        $breadcrumbMock->expects($this->exactly(2))
            ->method('addCrumb')
            ->willReturnMap([
                [
                    'home',
                    [
                        'label' => __('Home'),
                        'title' => __('Go to Home Page'),
                        'link' => $baseUrl,
                    ],
                    $breadcrumbMock,
                ],
                [
                    'cms_page',
                    [
                        'label' => $customPageTitle,
                        'title' => $customPageTitle,
                    ],
                    $breadcrumbMock,
                ],
            ]);

        $layoutMock = $this->getMockBuilder(\Magento\Framework\View\LayoutInterface::class)
            ->getMockForAbstractClass();
        $layoutMock->expects($this->exactly(2))
            ->method('getBlock')
            ->willReturnMap([
                ['page.main.title', null],
                ['breadcrumbs', $breadcrumbMock],
            ]);

        $this->createAdditionalMockObjects($customPageIdentifier, $customPageTitle);
        $this->assertEquals($this->block, $this->block->setLayout($layoutMock));
    }

    /**
     * Create additional mock objects
     *
     * Helper method, that provides unified logic of creation of CMS Page
     * and View Page Config mock objects, required to implement test iterations.
     *
     *
     * @param string|null $pageIdentifier
     * @param string|null $pageTitle
     * @return void
     */
    private function createAdditionalMockObjects($pageIdentifier = null, $pageTitle = null)
    {
        $pageIdentifier = $pageIdentifier ?: 'page-identifier';
        $pageTitle = $pageTitle ?: 'page-title';

        $pageMetaTitle = 'page-meta-title';
        $pageMetaKeywords = 'page-meta-keywords';
        $pageMetaDescription = 'page-meta-description';

        $this->pageMock->expects($this->any())
            ->method('getIdentifier')
            ->willReturn($pageIdentifier);
        $this->pageMock->expects($this->once())
            ->method('getMetaTitle')
            ->willReturn($pageMetaTitle);
        $this->pageMock->expects($this->once())
            ->method('getMetaKeywords')
            ->willReturn($pageMetaKeywords);
        $this->pageMock->expects($this->once())
            ->method('getMetaDescription')
            ->willReturn($pageMetaDescription);
        $this->pageMock->expects($this->any())
            ->method('getTitle')
            ->willReturn($pageTitle);

        $titleMock = $this->getMockBuilder(\Magento\Framework\View\Page\Title::class)
            ->disableOriginalConstructor()
            ->getMock();
        $titleMock->expects($this->once())
            ->method('set')
            ->with($pageMetaTitle)
            ->willReturnSelf();

        $this->pageConfigMock->expects($this->once())
            ->method('addBodyClass')
            ->with('cms-' . $pageIdentifier)
            ->willReturnSelf();
        $this->pageConfigMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($titleMock);
        $this->pageConfigMock->expects($this->once())
            ->method('setKeywords')
            ->willReturn($pageMetaKeywords);
        $this->pageConfigMock->expects($this->once())
            ->method('setDescription')
            ->willReturn($pageMetaDescription);
    }
}
