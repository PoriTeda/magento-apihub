<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Test\Unit\Model\Product;

class CatalogPriceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftCard\Model\Product\CatalogPrice
     */
    protected $catalogPrice;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $productMock;

    protected function setUp()
    {
        $this->productMock = $this->createMock(\Magento\Catalog\Model\Product::class);
        $this->catalogPrice = new \Magento\GiftCard\Model\Product\CatalogPrice();
    }

    public function testGetCatalogPrice()
    {
        $priceModelMock = $this->createPartialMock(\Magento\Catalog\Model\Product\Type\Price::class, ['getMinAmount']);
        $this->productMock->expects($this->once())->method('getPriceModel')->will($this->returnValue($priceModelMock));
        $priceModelMock->expects(
            $this->once()
        )->method(
            'getMinAmount'
        )->with(
            $this->productMock
        )->will(
            $this->returnValue(15)
        );
        $this->assertEquals(15, $this->catalogPrice->getCatalogPrice($this->productMock));
    }

    public function testGetCatalogRegularPrice()
    {
        $this->assertEquals(null, $this->catalogPrice->getCatalogRegularPrice($this->productMock));
    }
}
