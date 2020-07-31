<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedCheckout\Test\Unit\Model\ResourceModel\Sku\Errors\Grid;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadData()
    {
        $productId = '3';
        $websiteId = '1';
        $sku = 'my sku';
        $typeId = 'giftcard';

        $cart = $this->getCartMock($productId, $websiteId, $sku);
        $product = $this->getProductMock($typeId);
        $priceCurrencyMock = $this->getPriceCurrencyMock();
        $entity = $this->getEntityFactoryMock();
        $stockStatusMock = $this->getMockBuilder(\Magento\CatalogInventory\Api\Data\StockStatusInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $registryMock = $this->getMockBuilder(\Magento\CatalogInventory\Api\StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $registryMock->expects($this->any())
            ->method('getStockStatus')
            ->withAnyParameters()
            ->willReturn($stockStatusMock);

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $collection = $objectManager->getObject(
            \Magento\AdvancedCheckout\Model\ResourceModel\Sku\Errors\Grid\Collection::class,
            [
                'entityFactory' => $entity,
                'cart' => $cart,
                'productModel' => $product,
                'priceCurrency' => $priceCurrencyMock,
                'stockRegistry' => $registryMock
            ]
        );
        $collection->loadData();

        foreach ($collection->getItems() as $item) {
            $product = $item->getProduct();
            if ($item->getCode() != 'failed_sku') {
                $this->assertEquals($typeId, $product->getTypeId());
                $this->assertEquals('10.00', $item->getPrice());
            }
        }
    }

    /**
     * Return cart mock instance
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\AdvancedCheckout\Model\Cart
     */
    protected function getCartMock($productId, $storeId, $sku)
    {
        $cartMock = $this->getMockBuilder(
            \Magento\AdvancedCheckout\Model\Cart::class
        )->disableOriginalConstructor()->setMethods(
            ['getFailedItems', 'getStore']
        )->getMock();
        $cartMock->expects(
            $this->any()
        )->method(
            'getFailedItems'
        )->will(
            $this->returnValue(
                [
                    [
                        "item" => ["id" => $productId, "is_qty_disabled" => "false", "sku" => $sku, "qty" => "1"],
                        "code" => "failed_configure",
                        "orig_qty" => "7",
                    ],
                    [
                        "item" => ["sku" => 'invalid', "qty" => "1"],
                        "code" => "failed_sku",
                        "orig_qty" => "1"
                    ],
                ]
            )
        );
        $storeMock = $this->getStoreMock($storeId);
        $cartMock->expects($this->any())->method('getStore')->will($this->returnValue($storeMock));

        return $cartMock;
    }

    /**
     * Return store mock instance
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Model\Store
     */
    protected function getStoreMock($websiteId)
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->expects($this->any())->method('getWebsiteId')->will($this->returnValue($websiteId));

        return $storeMock;
    }

    /**
     * Return product mock instance
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Product
     */
    protected function getProductMock($typeId)
    {
        $productMock = $this->createPartialMock(
            \Magento\Catalog\Model\Product::class,
            ['__wakeup', '_beforeLoad', '_afterLoad', '_getResource', 'load', 'getPriceModel', 'getPrice', 'getTypeId']
        );
        $productMock->expects($this->once())->method('getTypeId')->will($this->returnValue($typeId));
        $productMock->expects($this->once())->method('getPrice')->will($this->returnValue('10.00'));

        return $productMock;
    }

    /**
     * Return PriceCurrencyInterface mock instance
     *
     * @return \PHPUnit_Framework_MockObject_MockObject| \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected function getPriceCurrencyMock()
    {
        $priceCurrencyMock = $this->getMockBuilder(\Magento\Framework\Pricing\PriceCurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $priceCurrencyMock->expects($this->any())->method('format')->will($this->returnArgument(0));

        return $priceCurrencyMock;
    }

    /**
     * Return entityFactory mock instance
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Data\Collection\EntityFactory
     */
    protected function getEntityFactoryMock()
    {
        $entityFactoryMock = $this->createMock(\Magento\Framework\Data\Collection\EntityFactory::class);

        return $entityFactoryMock;
    }
}
