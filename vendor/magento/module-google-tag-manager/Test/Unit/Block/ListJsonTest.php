<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Test\Unit\Block;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Class ListJsonTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ListJsonTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\GoogleTagManager\Block\ListJson */
    protected $listJson;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var \Magento\GoogleTagManager\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    protected $googleTagManagerHelper;

    /** @var \Magento\Framework\Json\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    protected $jsonHelper;

    /** @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var \Magento\Checkout\Model\Session|\PHPUnit_Framework_MockObject_MockObject */
    protected $checkoutSession;

    /** @var \Magento\Customer\Model\Session|\PHPUnit_Framework_MockObject_MockObject */
    protected $customerSession;

    /** @var \Magento\Checkout\Helper\Cart|\PHPUnit_Framework_MockObject_MockObject */
    protected $checkoutCartHelper;

    /** @var \Magento\Catalog\Model\Layer|\PHPUnit_Framework_MockObject_MockObject */
    protected $layer;

    /** @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject */
    protected $http;

    /** @var \Magento\Framework\View\LayoutInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $layout;

    /** @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $storeManager;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $scopeConfig;

    protected function setUp()
    {
        $this->googleTagManagerHelper = $this->createMock(\Magento\GoogleTagManager\Helper\Data::class);
        $this->jsonHelper = $this->createMock(\Magento\Framework\Json\Helper\Data::class);
        $this->registry = $this->createMock(\Magento\Framework\Registry::class);
        $this->checkoutSession = $this->createMock(\Magento\Checkout\Model\Session::class);
        $this->customerSession = $this->createMock(\Magento\Customer\Model\Session::class);
        $this->checkoutCartHelper = $this->createMock(\Magento\Checkout\Helper\Cart::class);
        $this->http = $this->createMock(\Magento\Framework\App\Request\Http::class);
        $this->layout = $this->createMock(\Magento\Framework\View\LayoutInterface::class);
        $this->storeManager = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->scopeConfig = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $this->createListJson(false);
    }

    protected function createListJson($initLayer = true)
    {
        $this->layer = $initLayer ?
            $this->createPartialMock(\Magento\Catalog\Model\Layer::class, ['getCurrentCategory']) :
            null;
        $layerResolver = $this->createPartialMock(\Magento\Catalog\Model\Layer\Resolver::class, ['get']);
        $layerResolver->expects($this->once())->method('get')->willReturn($this->layer);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->listJson = $this->objectManagerHelper->getObject(
            \Magento\GoogleTagManager\Block\ListJson::class,
            [
                'helper' => $this->googleTagManagerHelper,
                'jsonHelper' => $this->jsonHelper,
                'registry' => $this->registry,
                'checkoutSession' => $this->checkoutSession,
                'customerSession' => $this->customerSession,
                'checkoutCart' => $this->checkoutCartHelper,
                'layerResolver' => $layerResolver,
                'request' => $this->http,
                'layout' => $this->layout,
                'storeManager' => $this->storeManager,
                'scopeConfig' => $this->scopeConfig
            ]
        );
    }

    public function testToHtml()
    {
        $this->googleTagManagerHelper->expects($this->atLeastOnce())->method('isTagManagerAvailable')
            ->willReturn(true);
        $this->listJson->toHtml();
    }

    public function testGetListBlock()
    {
        $this->listJson->setBlockName('catalog.product.related');
        $block = $this->createMock(\Magento\Framework\View\Element\BlockInterface::class);
        $this->layout->expects($this->atLeastOnce())->method('getBlock')->with('catalog.product.related')
            ->willReturn($block);

        $this->assertSame($block, $this->listJson->getListBlock());
    }

    public function testCheckCartItems()
    {
        $this->checkoutCartHelper->expects($this->atLeastOnce())->method('getItemsCount')->willReturn(0);
        $this->listJson->checkCartItems();
    }

    public function testGetLoadedProductCollectionForCatalogList()
    {
        $collection = $this->createMock(\Magento\Catalog\Model\ResourceModel\Product\Collection::class);
        $category = $this->createPartialMock(\Magento\Catalog\Model\Category::class, ['getDisplayMode']);
        $category->expects($this->atLeastOnce())->method('getDisplayMode')
            ->willReturn(\Magento\Catalog\Model\Category::DM_PRODUCT);
        $this->registry->expects($this->atLeastOnce())->method('registry')->with('current_category')
            ->willReturn($category);

        $this->listJson->setBlockName('catalog.product.related');
        $block = $this->createPartialMock(
            \Magento\Framework\View\Element\BlockInterface::class,
            [
                'getLoadedProductCollection',
                'toHtml'
            ]
        );
        $block->expects($this->atLeastOnce())->method('getLoadedProductCollection')->willReturn($collection);

        $this->layout->expects($this->atLeastOnce())->method('getBlock')->with('catalog.product.related')
            ->willReturn($block);

        $this->assertSame($collection, $this->listJson->getLoadedProductCollection());
    }

    public function testGetLoadedProductCollectionForCrossSell()
    {
        $collection = $this->createMock(\Magento\Catalog\Model\ResourceModel\Product\Collection::class);
        $category = $this->createMock(\Magento\Catalog\Model\Category::class);
        $category->expects($this->atLeastOnce())->method('getDisplayMode')
            ->willReturn(\Magento\Catalog\Model\Category::DM_PRODUCT);
        $this->registry->expects($this->atLeastOnce())->method('registry')->with('current_category')
            ->willReturn($category);

        $this->listJson->setBlockName('catalog.product.related');
        $block = $this->createPartialMock(
            \Magento\Framework\View\Element\BlockInterface::class,
            [
                'getLoadedProductCollection',
                'getItemCollection',
                'toHtml'
            ]
        );
        $block->expects($this->atLeastOnce())->method('getItemCollection')->willReturn($collection);

        $this->layout->expects($this->atLeastOnce())->method('getBlock')->with('catalog.product.related')
            ->willReturn($block);

        $this->assertSame($collection, $this->listJson->getLoadedProductCollection());
    }

    public function testGetLoadedProductCollectionForRelated()
    {
        $collection = $this->createMock(\Magento\Catalog\Model\ResourceModel\Product\Collection::class);
        $category = $this->createMock(\Magento\Catalog\Model\Category::class);
        $category->expects($this->atLeastOnce())->method('getDisplayMode')
            ->willReturn(\Magento\Catalog\Model\Category::DM_PRODUCT);
        $this->registry->expects($this->atLeastOnce())->method('registry')->with('current_category')
            ->willReturn($category);

        $this->listJson->setBlockName('catalog.product.related');
        $block = $this->createPartialMock(
            \Magento\Framework\View\Element\BlockInterface::class,
            [
                'getLoadedProductCollection',
                'getItemCollection',
                'getItems',
                'toHtml'
            ]
        );
        $block->expects($this->atLeastOnce())->method('getItems')->willReturn($collection);

        $this->layout->expects($this->atLeastOnce())->method('getBlock')->with('catalog.product.related')
            ->willReturn($block);

        $this->assertSame($collection, $this->listJson->getLoadedProductCollection());
        $this->assertSame($collection, $this->listJson->getLoadedProductCollection());
    }

    /**
     * @covers \Magento\GoogleTagManager\Block\ListJson::getCurrentCategory
     */
    public function testGetCurrentCategoryFromLayer()
    {
        $this->createListJson(true);
        $category = $this->createMock(\Magento\Catalog\Model\Category::class);
        $this->layer->expects($this->atLeastOnce())->method('getCurrentCategory')->willReturn($category);
        $this->assertSame($category, $this->listJson->getCurrentCategory());
    }

    /**
     * @covers \Magento\GoogleTagManager\Block\ListJson::getCurrentCategory
     */
    public function testGetCurrentCategoryFromRegistry()
    {
        $category = $this->createMock(\Magento\Catalog\Model\Category::class);
        $this->registry->expects($this->atLeastOnce())->method('registry')->with('current_category')
            ->willReturn($category);
        $this->assertSame($category, $this->listJson->getCurrentCategory());
    }

    public function testGetCurrentProduct()
    {
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $this->registry->expects($this->atLeastOnce())->method('registry')->with('product')->willReturn($product);
        $this->assertSame($product, $this->listJson->getCurrentProduct());
    }

    /**
     * @param bool $showCategory
     * @param int $categoryId
     * @param string $expected
     *
     * @dataProvider getCurrentCategoryNameDataProvider
     */
    public function testGetCurrentCategoryName($showCategory, $categoryId, $expected)
    {
        $this->listJson->setShowCategory($showCategory);
        $category = $this->createMock(\Magento\Catalog\Model\Category::class);
        $category->expects($this->any())->method('getId')->willReturn($categoryId);
        $category->expects($this->any())->method('getName')->willReturn('Category Name');
        $this->registry->expects($this->any())->method('registry')->with('current_category')
            ->willReturn($category);

        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $store->expects($this->any())->method('getRootCategoryId')->willReturn(2);
        $this->storeManager->expects($this->any())->method('getStore')->with(null)->willReturn($store);

        $this->assertEquals($expected, $this->listJson->getCurrentCategoryName());
    }

    public function getCurrentCategoryNameDataProvider()
    {
        return [
            [false, 0, ''],
            [true, 2, ''],
            [true, 5, 'Category Name']
        ];
    }

    /**
     * @param string $type
     * @param string $listPath
     * @param string $expected
     *
     * @dataProvider getCurrentListNameDataProvider
     */
    public function testGetCurrentListName($type, $listPath, $expected)
    {
        $this->listJson->setListType($type);
        $this->scopeConfig->expects($this->any())->method('getValue')->with($listPath)->willReturn($expected);
        $this->assertEquals($expected, $this->listJson->getCurrentListName());
    }

    public function getCurrentListNameDataProvider()
    {
        return [
            ['catalog', \Magento\GoogleTagManager\Helper\Data::XML_PATH_LIST_CATALOG_PAGE, 'catalog'],
            ['search', \Magento\GoogleTagManager\Helper\Data::XML_PATH_LIST_SEARCH_PAGE, 'search'],
            ['related', \Magento\GoogleTagManager\Helper\Data::XML_PATH_LIST_RELATED_BLOCK, 'related'],
            ['upsell', \Magento\GoogleTagManager\Helper\Data::XML_PATH_LIST_UPSELL_BLOCK, 'upsell'],
            ['crosssell', \Magento\GoogleTagManager\Helper\Data::XML_PATH_LIST_CROSSSELL_BLOCK, 'crosssell'],
            ['other', 'other', ''],
            ['', 'n/a', ''],
        ];
    }

    public function testGetBannerPosition()
    {
        $this->http->expects($this->atLeastOnce())->method('getFullActionName')->willReturn('actionName');
        $this->assertEquals('actionName', $this->listJson->getBannerPosition());
    }

    /**
     * @param bool $logged
     * @param string $expected
     *
     * @dataProvider detectStepNameDataProvider
     */
    public function testDetectStepName($logged, $expected)
    {
        $this->customerSession->expects($this->atLeastOnce())->method('isLoggedIn')->willReturn($logged);
        $this->listJson->detectStepName();
        $this->assertEquals($expected, $this->listJson->getStepName());
    }

    public function detectStepNameDataProvider()
    {
        return [
            [true, 'billing'],
            [false, 'login'],
        ];
    }

    public function testIsCustomerLoggedIn()
    {
        $this->customerSession->expects($this->atLeastOnce())->method('isLoggedIn')->willReturn(true);
        $this->assertTrue($this->listJson->isCustomerLoggedIn());
    }

    public function testGetCartContent()
    {
        $item = $this->createMock(\Magento\Quote\Model\Quote\Item::class);
        $item->expects($this->atLeastOnce())->method('getSku')->willReturn('SKU12323');
        $item->expects($this->atLeastOnce())->method('getName')->willReturn('Product Name');
        $item->expects($this->atLeastOnce())->method('getPrice')->willReturn(116);
        $item->expects($this->atLeastOnce())->method('getQty')->willReturn(2);

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->expects($this->atLeastOnce())->method('getAllVisibleItems')->willReturn([$item]);
        $this->checkoutSession->expects($this->atLeastOnce())->method('getQuote')->willReturn($quote);
        $this->checkoutSession->expects($this->once())->method('start')->willReturnSelf();

        $json = [
            [
                'id' => 'SKU12323',
                'name' => 'Product Name',
                'price' => 116,
                'qty' => 2
            ]
        ];

        $this->jsonHelper->expects($this->once())->method('jsonEncode')->with($json)->willReturn('{encoded_string}');
        $this->assertEquals('{encoded_string}', $this->listJson->getCartContent());
    }

    public function testGetCartContentForUpdate()
    {
        $item = $this->createMock(\Magento\Quote\Model\Quote\Item::class);
        $item->expects($this->atLeastOnce())->method('getSku')->willReturn('SKU12323');
        $item->expects($this->atLeastOnce())->method('getName')->willReturn('Product Name');
        $item->expects($this->atLeastOnce())->method('getPrice')->willReturn(116);
        $item->expects($this->atLeastOnce())->method('getQty')->willReturn(2);

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->expects($this->atLeastOnce())->method('getAllVisibleItems')->willReturn([$item]);
        $this->checkoutSession->expects($this->atLeastOnce())->method('getQuote')->willReturn($quote);
        $this->checkoutSession->expects($this->once())->method('start')->willReturnSelf();

        $json = [
            'SKU12323' => [
                'id' => 'SKU12323',
                'name' => 'Product Name',
                'price' => 116,
                'qty' => 2
            ]
        ];

        $this->jsonHelper->expects($this->once())->method('jsonEncode')->with($json)->willReturn('{encoded_string}');
        $this->assertEquals('{encoded_string}', $this->listJson->getCartContentForUpdate());
    }
}
