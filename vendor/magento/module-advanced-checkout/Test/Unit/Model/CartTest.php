<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Test\Unit\Model;

use Magento\AdvancedCheckout\Helper\Data;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CartTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedCheckout\Model\Cart
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeFormatMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $helperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $itemServiceMock;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $stockRegistry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $stockItemMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $stockState;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $stockHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $quoteMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $quoteRepositoryMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $quoteFactoryMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $serializer;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $cartMock = $this->createMock(\Magento\Checkout\Model\Cart::class);
        $messageFactoryMock = $this->createMock(\Magento\Framework\Message\Factory::class);
        $eventManagerMock = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);
        $this->helperMock = $this->createMock(\Magento\AdvancedCheckout\Helper\Data::class);
        $this->serializer = $this->createMock(\Magento\Framework\Serialize\Serializer\Json::class);
        $wishListFactoryMock = $this->createPartialMock(\Magento\Wishlist\Model\WishlistFactory::class, ['create']);
        $this->quoteMock = $this->createPartialMock(\Magento\Quote\Model\Quote::class, ['getStore', '__wakeup']);
        $this->quoteRepositoryMock = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->storeManagerMock = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();
        $this->localeFormatMock = $this->createMock(\Magento\Framework\Locale\FormatInterface::class);
        $messageManagerMock = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);
        $customerSessionMock = $this->createMock(\Magento\Customer\Model\Session::class);

        $this->productRepository = $this->createMock(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $optionFactoryMock = $this->createPartialMock(\Magento\Catalog\Model\Product\OptionFactory::class, ['create']);
        $prodTypesConfigMock = $this->createMock(\Magento\Catalog\Model\ProductTypes\ConfigInterface::class);
        $cartConfigMock = $this->createMock(\Magento\Catalog\Model\Product\CartConfiguration::class);

        $this->stockRegistry = $this->getMockBuilder(\Magento\CatalogInventory\Model\StockRegistry::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStockItem', '__wakeup'])
            ->getMock();

        $this->stockItemMock = $this->createPartialMock(
            \Magento\CatalogInventory\Model\Stock\Item::class,
            ['getQtyIncrements', 'getIsInStock', '__wakeup', 'getMaxSaleQty', 'getMinSaleQty']
        );

        $this->stockRegistry->expects($this->any())
            ->method('getStockItem')
            ->will($this->returnValue($this->stockItemMock));

        $this->stockState = $this->createMock(\Magento\CatalogInventory\Model\StockState::class);

        $this->stockHelper = $this->createMock(\Magento\CatalogInventory\Helper\Stock::class);
        $this->quoteFactoryMock = $this->createPartialMock(\Magento\Quote\Model\QuoteFactory::class, ['create']);
        $this->searchCriteriaBuilder = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new \Magento\AdvancedCheckout\Model\Cart(
            $cartMock,
            $messageFactoryMock,
            $eventManagerMock,
            $this->helperMock,
            $optionFactoryMock,
            $wishListFactoryMock,
            $this->quoteRepositoryMock,
            $this->storeManagerMock,
            $this->localeFormatMock,
            $messageManagerMock,
            $prodTypesConfigMock,
            $cartConfigMock,
            $customerSessionMock,
            $this->stockRegistry,
            $this->stockState,
            $this->stockHelper,
            $this->productRepository,
            $this->quoteFactoryMock,
            \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_SKU,
            [],
            $this->serializer,
            $this->searchCriteriaBuilder
        );
    }

    /**
     * @param string $sku
     * @param array $config
     * @param array $expectedResult
     *
     * @covers \Magento\AdvancedCheckout\Model\Cart::__construct
     * @covers \Magento\AdvancedCheckout\Model\Cart::setAffectedItemConfig
     * @covers \Magento\AdvancedCheckout\Model\Cart::getAffectedItemConfig
     * @dataProvider setAffectedItemConfigDataProvider
     */
    public function testSetAffectedItemConfig($sku, $config, $expectedResult)
    {
        $this->model->setAffectedItemConfig($sku, $config);
        $this->assertEquals($expectedResult, $this->model->getAffectedItemConfig($sku));
    }

    /**
     * @return array
     */
    public function setAffectedItemConfigDataProvider()
    {
        return [
            [
                'sku' => 123,
                'config' => ['1'],
                'expectedResult' => [1]
            ],
            [
                'sku' => 0,
                'config' => ['1'],
                'expectedResult' => [1]
            ],
            [
                'sku' => 'aaa',
                'config' => ['1'],
                'expectedResult' => [1]
            ],
            [
                'sku' => '',
                'config' => ['1'],
                'expectedResult' => []
            ],
            [
                'sku' => false,
                'config' => ['1'],
                'expectedResult' => [1]
            ],
            [
                'sku' => null,
                'config' => ['1'],
                'expectedResult' => [1]
            ],
            [
                'sku' => 'aaa',
                'config' => [],
                'expectedResult' => []
            ],
            [
                'sku' => 'aaa',
                'config' => null,
                'expectedResult' => []
            ],
            [
                'sku' => 'aaa',
                'config' => false,
                'expectedResult' => []
            ],
            [
                'sku' => 'aaa',
                'config' => 0,
                'expectedResult' => []
            ],
            [
                'sku' => 'aaa',
                'config' => '',
                'expectedResult' => []
            ]
        ];
    }

    /**
     * @param string $sku
     * @param integer $qty
     * @param string $expectedCode
     *
     * @dataProvider prepareAddProductsBySkuDataProvider
     * @covers \Magento\AdvancedCheckout\Model\Cart::_getValidatedItem
     * @covers \Magento\AdvancedCheckout\Model\Cart::_loadProductBySku
     * @covers \Magento\AdvancedCheckout\Model\Cart::checkItem
     */
    public function testGetValidatedItem($sku, $qty, $expectedCode)
    {
        $storeMock = $this->createPartialMock(\Magento\Store\Model\Store::class, ['getId', 'getWebsiteId', 'getStore']);
        $storeMock->expects($this->any())->method('getStore')->will($this->returnValue(1));
        $storeMock->method('getId')->willReturn(1);
        $storeMock->expects($this->any())->method('getWebsiteId')->will($this->returnValue(1));

        $sessionMock = $this->createPartialMock(
            \Magento\Framework\Session\SessionManager::class,
            ['getAffectedItems', 'setAffectedItems']
        );
        $sessionMock->expects($this->any())->method('getAffectedItems')->will($this->returnValue([]));

        $productMock = $this->createPartialMock(
            \Magento\Catalog\Model\Product::class,
            ['getId', 'getWebsiteIds', 'isComposite', 'getSku', '__wakeup', '__sleep']
        );
        $productMock->expects($this->any())->method('getId')->will($this->returnValue(1));
        $productMock->expects($this->any())->method('getWebsiteIds')->will($this->returnValue([1]));
        $productMock->method('getSku')->willReturn('testSKU');
        $productMock->expects($this->any())->method('isComposite')->will($this->returnValue(false));

        $this->productRepository->expects($this->any())->method('get')->with($sku)
            ->will($this->returnValue($productMock));
        $this->helperMock->expects($this->any())->method('getSession')->will($this->returnValue($sessionMock));
        $this->localeFormatMock->expects($this->any())->method('getNumber')->will($this->returnArgument(0));
        $this->storeManagerMock->expects($this->any())->method('getStore')->will($this->returnValue($storeMock));
        $item = $this->model->checkItem($sku, $qty);

        $this->assertTrue($item['code'] == $expectedCode);
    }

    /**
     * Test checkItem for item with config.
     */
    public function testGetValidatedItemWithConfig()
    {
        $config = ['options' => [1 => 2]];
        $jsonConfig = json_encode($config);
        $this->serializer->expects($this->once())->method('serialize')->willReturn($jsonConfig);
        $storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getWebsiteId'])
            ->getMock();
        $storeMock->expects($this->atLeastOnce())->method('getWebsiteId')->willReturn(1);
        $storeMock->expects($this->atLeastOnce())->method('getId')->willReturn(1);

        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getWebsiteIds', 'isComposite'])
            ->getMock();
        $productMock->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $productMock->expects($this->atLeastOnce())->method('getWebsiteIds')->willReturn([1]);
        $productMock->expects($this->atLeastOnce())->method('isComposite')->willReturn(false);

        $this->productRepository->expects($this->atLeastOnce())->method('get')->with('test', false, 1)
            ->willReturn($productMock);
        $this->localeFormatMock->expects($this->atLeastOnce())->method('getNumber')->willReturnArgument(0);
        $this->storeManagerMock->expects($this->atLeastOnce())->method('getStore')->willReturn($storeMock);
        $item = $this->model->checkItem('test', 2, $config);

        $this->assertEquals(\Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_SUCCESS, $item['code']);
    }

    /**
     * @return array
     */
    public function prepareAddProductsBySkuDataProvider()
    {
        return [
            [
                'sku' => 'aaa',
                'qty' => 2,
                'expectedCode' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_SUCCESS,
            ],
            [
                'sku' => 'aaa',
                'qty' => 'aaa',
                'expectedCode' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_QTY_INVALID_NUMBER,
            ],
            [
                'sku' => 'aaa',
                'qty' => -1,
                'expectedCode' =>
                    \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_QTY_INVALID_NON_POSITIVE,
            ],
            [
                'sku' => 'aaa',
                'qty' => 0.00001,
                'expectedCode' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_QTY_INVALID_RANGE,
            ],
            [
                'sku' => 'aaa',
                'qty' => 100000000.0,
                'expectedCode' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_QTY_INVALID_RANGE,
            ],
            [
                'sku' => 'a',
                'qty' => 2,
                'expectedCode' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_SUCCESS,
            ],
            [
                'sku' => 123,
                'qty' => 2,
                'expectedCode' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_SUCCESS,
            ],
            [
                'sku' => 0,
                'qty' => 2,
                'expectedCode' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_SUCCESS,
            ],
            [
                'sku' => '',
                'qty' => 2,
                'expectedCode' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_EMPTY,
            ]
        ];
    }

    /**
     * @param array $config
     * @param array $result
     * @dataProvider getQtyStatusDataProvider
     * @TODO refactor me
     */
    public function testGetQtyStatus($config, $result)
    {
        $websiteId = 10;
        $productId = $config['product_id'];
        $requestQty = $config['request_qty'];

        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $store->expects($this->any())
            ->method('getWebsiteId')
            ->will($this->returnValue($websiteId));
        $this->quoteMock->expects($this->any())
            ->method('getStore')
            ->will($this->returnValue($store));

        $this->quoteMock->expects($this->any())
            ->method('getStore')
            ->will($this->returnValue($store));

        $this->quoteFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($this->quoteMock));

        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($productId));

        $resultObject = new \Magento\Framework\DataObject($config['result']);
        $this->stockState->expects($this->once())
            ->method('checkQuoteItemQty')
            ->with(
                $this->equalTo($productId),
                $this->equalTo($requestQty),
                $this->equalTo($requestQty),
                $this->equalTo($requestQty),
                $this->equalTo($websiteId)
            )
            ->will($this->returnValue($resultObject));

        if ($config['result']['has_error']) {
            switch ($resultObject->getErrorCode()) {
                case 'qty_increments':
                    $this->stockItemMock->expects($this->once())
                        ->method('getQtyIncrements')
                        ->will($this->returnValue($config['result']['qty_increments']));
                    break;
                case 'qty_min':
                    $this->stockItemMock->expects($this->once())
                        ->method('getMinSaleQty')
                        ->will($this->returnValue($config['result']['qty_min_allowed']));
                    break;
                case 'qty_max':
                    $this->stockItemMock->expects($this->once())
                        ->method('getMaxSaleQty')
                        ->will($this->returnValue($config['result']['qty_max_allowed']));
                    break;
                default:
                    $this->stockState->expects($this->once())
                        ->method('getStockQty')
                        ->with($this->equalTo($productId))
                        ->will($this->returnValue($config['result']['qty_max_allowed']));
                    break;
            }
        }
        $this->assertSame($result, $this->model->getQtyStatus($product, $requestQty));
    }

    /**
     * @return array
     */
    public function getQtyStatusDataProvider()
    {
        return [
            'error qty_increments' => [
                [
                    'product_id' => 11,
                    'request_qty' => 6,
                    'result' => [
                        'has_error' => true,
                        'error_code' => 'qty_increments',
                        'qty_increments' => 1,
                        'message' => 'hello qty_increments'
                    ]
                ],
                [
                    'qty_increments' => 1,
                    'status' => Data::ADD_ITEM_STATUS_FAILED_QTY_INCREMENTS,
                    'error' => 'hello qty_increments'
                ]
            ],
            'error qty_min' => [
                [
                    'product_id' => 14,
                    'request_qty' => 5,
                    'result' => [
                        'has_error' => true,
                        'error_code' => 'qty_min',
                        'qty_min_allowed' => 2,
                        'message' => 'hello qty_min_allowed'
                    ]
                ],
                [
                    'qty_min_allowed' => 2,
                    'status' => Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED_IN_CART,
                    'error' => 'hello qty_min_allowed'
                ]
            ],
            'error qty_max' => [
                [
                    'product_id' => 13,
                    'request_qty' => 4,
                    'result' => [
                        'has_error' => true,
                        'error_code' => 'qty_max',
                        'qty_max_allowed' => 3,
                        'message' => 'hello qty_max_allowed'
                    ]
                ],
                [
                    'qty_max_allowed' => 3,
                    'status' => Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED_IN_CART,
                    'error' => 'hello qty_max_allowed'
                ]
            ],
            'error default' => [
                [
                    'product_id' => 12,
                    'request_qty' => 3,
                    'result' => [
                        'has_error' => true,
                        'error_code' => 'default',
                        'qty_max_allowed' => 4,
                        'message' => 'hello default'
                    ]
                ],
                [
                    'qty_max_allowed' => 4,
                    'status' => Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED,
                    'error' => 'hello default'
                ]
            ],
            'no error' => [
                [
                    'product_id' => 18,
                    'request_qty' => 22,
                    'result' => ['has_error' => false]
                ],
                true
            ],
        ];
    }

    public function testAdditionalOptionsInReorderItem()
    {
        $additionalOptionResult = ['additional_option' => 1];

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $productMock = $this->createMock(\Magento\Catalog\Model\Product::class);
        $quoteItemMock = $this->createMock(\Magento\Quote\Model\Quote\Item::class);
        $quoteMock = $this->createPartialMock(\Magento\Quote\Model\Quote::class, ['getStore', 'addProduct']);
        $orderItemMock = $this->createMock(\Magento\Sales\Model\Order\Item::class);

        $orderItemMock->expects($this->any())->method('getProductOptionByCode')->will($this->returnValueMap(
            [
                ['info_buyRequest', []],
                ['additional_options', $additionalOptionResult]
            ]
        ));

        $storeMock->expects($this->any())->method('getId')->will($this->returnValue(1));
        $orderItemMock->expects($this->once())->method('getId')->willReturn(1);
        $this->quoteFactoryMock->expects($this->once())->method('create')->willReturn($quoteMock);
        $quoteMock->expects($this->any())->method('getStore')->willReturn($storeMock);
        $quoteMock->expects($this->any())->method('addProduct')->willReturn($quoteItemMock);
        $this->productRepository->expects($this->any())->method('getById')->willReturn($productMock);
        $this->serializer->expects($this->once())->method('serialize')->with($additionalOptionResult);

        $this->model->reorderItem($orderItemMock, 1);
    }
}
