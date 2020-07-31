<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedCheckout\Test\Unit\Block\Sku;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Class ProductsTest
 */
class ProductsTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\AdvancedCheckout\Block\Sku\Products */
    protected $products;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var \Magento\AdvancedCheckout\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    protected $checkoutHelperMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $stockItemMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $stockRegistry;

    protected function setUp()
    {
        $this->checkoutHelperMock = $this->createMock(\Magento\AdvancedCheckout\Helper\Data::class);
        $this->checkoutHelperMock->expects($this->once())
            ->method('getFailedItems')
            ->will($this->returnValue([]));

        $this->stockRegistry = $this->getMockBuilder(\Magento\CatalogInventory\Model\StockRegistry::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStockItem', '__wakeup'])
            ->getMock();

        $this->stockItemMock = $this->createPartialMock(
            \Magento\CatalogInventory\Model\Stock\Item::class,
            ['getIsInStock', '__wakeup']
        );

        $this->stockRegistry->expects($this->any())
            ->method('getStockItem')
            ->will($this->returnValue($this->stockItemMock));

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->products = $this->objectManagerHelper->getObject(
            \Magento\AdvancedCheckout\Block\Sku\Products::class,
            [
                'checkoutData' => $this->checkoutHelperMock,
                'stockRegistry' => $this->stockRegistry
            ]
        );
    }

    /**
     * @param array $config
     * @param bool $result
     * @dataProvider showItemLinkDataProvider
     */
    public function testShowItemLink($config, $result)
    {
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->expects($this->once())
            ->method('isComposite')
            ->will($this->returnValue($config['is_composite']));

        $quoteItem = $this->createMock(\Magento\Quote\Model\Quote\Item::class);
        $quoteItem->expects($this->once())
            ->method('getProduct')
            ->will($this->returnValue($product));

        if ($config['is_composite']) {
            $productsInGroup = [
                [$this->getChildProductMock($config['is_in_stock'])],
            ];

            $typeInstance = $this->createMock(\Magento\Catalog\Model\Product\Type\Simple::class);
            $typeInstance->expects($this->once())
                ->method('getProductsToPurchaseByReqGroups')
                ->with($this->equalTo($product))
                ->will($this->returnValue($productsInGroup));

            $product->expects($this->once())
                ->method('getTypeInstance')
                ->will($this->returnValue($typeInstance));

            $store = $this->createMock(\Magento\Store\Model\Store::class);
            $quoteItem->expects($this->once())
                ->method('getStore')
                ->will($this->returnValue($store));
        }

        $this->assertSame($result, $this->products->showItemLink($quoteItem));
    }

    /**
     * @param bool $isInStock
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getChildProductMock($isInStock)
    {
        $product = $this->createPartialMock(
            \Magento\Catalog\Model\Product::class,
            ['hasStockItem', 'isDisabled', 'getId', '__wakeup']
        );
        $product->expects($this->once())
            ->method('hasStockItem')
            ->will($this->returnValue(true));
        if ($isInStock) {
            $product->expects($this->once())
                ->method('isDisabled')
                ->will($this->returnValue(false));
        }
        $product->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(10));

        $this->stockItemMock->expects($this->once())
            ->method('getIsInStock')
            ->will($this->returnValue($isInStock));
        return $product;
    }

    /**
     * @return array
     */
    public function showItemLinkDataProvider()
    {
        return [
            [
                ['is_composite' => false], true,
            ],
            [
                ['is_composite' => true, 'is_in_stock' => true], true
            ],
            [
                ['is_composite' => true, 'is_in_stock' => false], false
            ],
        ];
    }
}
