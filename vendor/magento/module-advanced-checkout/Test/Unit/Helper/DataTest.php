<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Test\Unit\Helper;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * DataTest class
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedCheckout\Model\Cart|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cart;

    /**
     * @var \Magento\AdvancedCheckout\Model\ResourceModel\Product\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productCollection;

    /**
     * @var \Magento\Catalog\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $catalogConfig;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sessionManager;

    /**
     * @var \Magento\Checkout\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutSession;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $stockRegistry;

    /**
     * @var \Magento\CatalogInventory\Helper\Stock|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $stockHelper;

    /**
     * @var \Magento\Quote\Model\Quote\ItemFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteItemFactory;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Msrp\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $msrpData;

    /**
     * @var \Magento\AdvancedCheckout\Helper\Data
     */
    protected $dataHelper;

    protected function setUp()
    {
        $this->cart = $this->getMockBuilder(\Magento\AdvancedCheckout\Model\Cart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productCollection = $this->getMockBuilder(
            \Magento\AdvancedCheckout\Model\ResourceModel\Product\Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogConfig = $this->getMockBuilder(\Magento\Catalog\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sessionManager = $this->getMockBuilder(\Magento\Framework\Session\SessionManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->checkoutSession = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->stockRegistry = $this->getMockBuilder(\Magento\CatalogInventory\Api\StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->stockHelper = $this->getMockBuilder(\Magento\CatalogInventory\Helper\Stock::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItemFactory = $this->getMockBuilder(\Magento\Quote\Model\Quote\ItemFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceCurrency = $this->getMockBuilder(\Magento\Framework\Pricing\PriceCurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->msrpData = $this->getMockBuilder(\Magento\Msrp\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManagerHelper($this);

        $this->dataHelper = $objectManagerHelper->getObject(
            \Magento\AdvancedCheckout\Helper\Data::class,
            [
                'cart' => $this->cart,
                'products' => $this->productCollection,
                'catalogConfig' => $this->catalogConfig,
                'checkoutSession' => $this->checkoutSession,
                'stockRegistry' => $this->stockRegistry,
                'stockHelper' => $this->stockHelper,
                'quoteItemFactory' => $this->quoteItemFactory,
                'msrpData' => $this->msrpData
            ]
        );
    }

    /**
     * @dataProvider getFailedItemsDataProvider
     * @param $productOptions array|null
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetFailedItems($productOptions)
    {
        $code = 'failed_configure';
        $qty = 2;
        $item = [
            'sku' => 'product_sku',
            'qty' => $qty,
            'id' => '1',
            'is_qty_disabled' => false
        ];
        $failedItems = [
            'item' => $item,
            'code' => $code,
            'orig_qty' => $qty
        ];
        $productAttributesData = [
            'attribute_1',
            'attribute_2'
        ];
        $productData = [
            'entity_id' => '1',
            'sku' => 'product_sku'
        ];
        $productUrl = 'http://magetest.com/product1.html';
        $websiteId = '0';
        $customOption = ['custom_option'];

        $this->cart->expects($this->once())->method('getFailedItems')->willReturn([$failedItems]);
        $this->productCollection->expects($this->once())->method('addMinimalPrice')->willReturnSelf();
        $this->productCollection->expects($this->once())->method('addFinalPrice')->willReturnSelf();
        $this->productCollection->expects($this->once())->method('addTaxPercents')->willReturnSelf();
        $this->catalogConfig->expects($this->once())->method('getProductAttributes')
            ->willReturn([$productAttributesData]);
        $this->productCollection->expects($this->once())->method('addAttributeToSelect')->with([$productAttributesData])
            ->willReturnSelf();
        $this->productCollection->expects($this->once())->method('addUrlRewrite')->willReturnSelf();
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->setMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSession->expects($this->once())->method('getQuote')->willReturn($quote);
        $this->productCollection->expects($this->once())->method('addIdFilter')->willReturnSelf();
        $this->productCollection->expects($this->once())->method('setFlag')->with('has_stock_status_filter', true)
            ->willReturnSelf();

        $quoteItem = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->setMethods(
                [
                    'setRedirectUrl',
                    'addData',
                    'setQuote',
                    'setProduct',
                    'getOptions',
                    'setOptions',
                    'setCanApplyMsrp',
                    'setStockItem'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItemFactory->expects($this->once())->method('create')->willReturn($quoteItem);
        $productItem = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->setMethods(
                [
                    'getId',
                    'addData',
                    'getData',
                    'getUrlModel',
                    'getOptions',
                    'getOptionsByCode',
                    'setCustomOptions'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->productCollection->expects($this->once())->method('getItems')->willReturn([$productItem]);
        $productItem->expects($this->any())->method('getId')->willReturn($productData['entity_id']);
        $productItem->expects($this->once())->method('addData')->willReturnSelf();
        $productItem->expects($this->any())->method('getOptionsByCode')->willReturn($customOption);
        $productItem->expects($this->once())->method('getData')->willReturn($productData);
        $quoteItem->expects($this->once())->method('getOptions')->willReturn([]);
        $quoteItem->expects($this->once())->method('addData')->with($productData)->willReturnSelf();
        $quoteItem->expects($this->once())->method('setQuote')->with($quote)->willReturnSelf();
        $quoteItem->expects($this->once())->method('setProduct')->willReturnSelf();
        $productUrlModel = $this->getMockBuilder(\Magento\Catalog\Model\Product\Url::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productItem->expects($this->any())->method('getUrlModel')->willReturn($productUrlModel);
        $productUrlModel->expects($this->once())->method('getUrl')->with($productItem)
            ->willReturn($productUrl);
        $quoteItem->expects($this->once())->method('setRedirectUrl')->with($productUrl)->willReturnSelf();
        $productItem->expects($this->any())->method('getOptions')->willReturn($productOptions);
        $productItem->expects($this->once())->method('setCustomOptions')->with($customOption)->willReturnSelf();
        $this->msrpData->expects($this->once())->method('canApplyMsrp')->with($productItem)->willReturn(false);
        $quoteItem->expects($this->once())->method('setCanApplyMsrp')->with(false)->willReturnSelf();
        $quoteItem->expects($this->any())->method('setOptions')->with($productOptions)->willReturnSelf();
        $this->stockHelper->expects($this->once())->method('assignStatusToProduct')->willReturnSelf();
        $stockItem = $this->getMockBuilder(\Magento\CatalogInventory\Api\Data\StockItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $storeModel = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->once())->method('getStore')->willReturn($storeModel);
        $storeModel->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->stockRegistry->expects($this->once())->method('getStockItem')
            ->with($productData['entity_id'], $websiteId)
            ->willReturn($stockItem);
        $quoteItem->expects($this->once())->method('setStockItem')->with($stockItem)->willReturnSelf();
        $this->dataHelper->getFailedItems(false);
    }

    /**
     * @return array
     */
    public function getFailedItemsDataProvider()
    {
        return [
            [
                null
            ],
            [
                ['product_options']
            ]
        ];
    }
}
