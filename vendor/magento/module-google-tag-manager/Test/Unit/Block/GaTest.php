<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Test\Unit\Block;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GaTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\GoogleTagManager\Block\Ga */
    protected $ga;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $collectionFactory;

    /** @var \Magento\GoogleTagManager\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    protected $googleTagManagerHelper;

    /** @var \Magento\Cookie\Helper\Cookie|\PHPUnit_Framework_MockObject_MockObject */
    protected $cookieHelper;

    /** @var \Magento\Framework\Json\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    protected $jsonHelper;

    /** @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $storeManager;

    protected function setUp()
    {
        $this->collectionFactory = $this->createPartialMock(
            \Magento\Sales\Model\ResourceModel\Order\CollectionFactory::class,
            ['create']
        );
        $this->googleTagManagerHelper = $this->createMock(\Magento\GoogleTagManager\Helper\Data::class);
        $this->cookieHelper = $this->createMock(\Magento\Cookie\Helper\Cookie::class);
        $this->jsonHelper = $this->createMock(\Magento\Framework\Json\Helper\Data::class);
        $this->storeManager = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->ga = $this->objectManagerHelper->getObject(
            \Magento\GoogleTagManager\Block\Ga::class,
            [
                'salesOrderCollection' => $this->collectionFactory,
                'googleAnalyticsData' => $this->googleTagManagerHelper,
                'cookieHelper' => $this->cookieHelper,
                'jsonHelper' => $this->jsonHelper,
                'storeManager' => $this->storeManager
            ]
        );
    }

    public function testToHtml()
    {
        $this->googleTagManagerHelper->expects($this->atLeastOnce())->method('isGoogleAnalyticsAvailable')
            ->willReturn(true);
        $this->ga->toHtml();
    }

    public function testGetStoreCurrencyCode()
    {
        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $store->expects($this->atLeastOnce())->method('getBaseCurrencyCode')->willReturn('USD');
        $this->storeManager->expects($this->atLeastOnce())->method('getStore')->with(null)->willReturn($store);
        $this->assertEquals('USD', $this->ga->getStoreCurrencyCode());
    }

    public function testGetOrdersDataEmptyOrderIds()
    {
        $this->assertEmpty($this->ga->getOrdersData());
    }

    public function testGetOrdersData()
    {
        $result = $this->prepareOrderDataMocks();
        $this->jsonHelper->expects($this->once())->method('jsonEncode')->with($result)->willReturn('{encoded_string}');
        $this->assertEquals("dataLayer.push({encoded_string});\n", $this->ga->getOrdersData());
    }

    public function testGetOrdersDataArray()
    {
        $result = $this->prepareOrderDataMocks();
        $this->assertEquals([$result], $this->ga->getOrdersDataArray());
    }

    public function testIsUserNotAllowSaveCookie()
    {
        $this->cookieHelper->expects($this->atLeastOnce())->method('isUserNotAllowSaveCookie')->willReturn(true);
        $this->assertTrue($this->ga->isUserNotAllowSaveCookie());
    }

    /**
     * @return array
     */
    private function prepareOrderDataMocks(): array
    {
        $this->ga->setOrderIds([12, 13]);
        $item1 = $this->createMock(\Magento\Sales\Model\Order\Item::class);
        $item1->expects($this->atLeastOnce())->method('getSku')->willReturn('SKU-123');
        $item1->expects($this->atLeastOnce())->method('getName')->willReturn('Product Name');
        $item1->expects($this->atLeastOnce())->method('getBasePrice')->willReturn(85);
        $item1->expects($this->atLeastOnce())->method('getQtyOrdered')->willReturn(1);

        $item2 = $this->createMock(\Magento\Sales\Model\Order\Item::class);
        $item2->expects($this->atLeastOnce())->method('getSku')->willReturn('SKU-123');
        $item2->expects($this->atLeastOnce())->method('getName')->willReturn('Product Name');
        $item2->expects($this->atLeastOnce())->method('getBasePrice')->willReturn(85);
        $item2->expects($this->atLeastOnce())->method('getQtyOrdered')->willReturn(1);

        $order = $this->createMock(\Magento\Sales\Model\Order::class);
        $order->expects($this->atLeastOnce())->method('getIncrementId')->willReturn('10002323');
        $order->expects($this->atLeastOnce())->method('getBaseGrandTotal')->willReturn(120);
        $order->expects($this->atLeastOnce())->method('getBaseTaxAmount')->willReturn(15);
        $order->expects($this->atLeastOnce())->method('getBaseShippingAmount')->willReturn(20);
        $order->expects($this->atLeastOnce())->method('getCouponCode')->willReturn('ABC123123');
        $order->expects($this->atLeastOnce())->method('getAllVisibleItems')->willReturn([$item1, $item2]);

        $collection = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        $collection->expects($this->once())->method('addFieldToFilter')->with('entity_id', ['in' => [12, 13]]);
        $collection->expects($this->once())->method('getIterator')->willReturn(
            new \ArrayIterator([$order])
        );

        $this->collectionFactory->expects($this->once())->method('create')->willReturn($collection);

        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $store->expects($this->atLeastOnce())->method('getBaseCurrencyCode')->willReturn('USD');
        $this->storeManager->expects($this->atLeastOnce())->method('getStore')->with(null)->willReturn($store);

        $result = [
            'ecommerce' => [
                'purchase' => [
                    'actionField' => [
                        'id' => '10002323',
                        'revenue' => 85,
                        'tax' => 15,
                        'shipping' => 20,
                        'coupon' => 'ABC123123'
                    ],
                    'products' => [
                        0 => [
                            'id' => 'SKU-123',
                            'name' => 'Product Name',
                            'price' => 85,
                            'quantity' => 1
                        ],
                        1 => [
                            'id' => 'SKU-123',
                            'name' => 'Product Name',
                            'price' => 85,
                            'quantity' => 1
                        ],
                    ],
                ],
                'currencyCode' => 'USD'
            ],
            'event' => 'purchase'
        ];
        return $result;
    }
}
