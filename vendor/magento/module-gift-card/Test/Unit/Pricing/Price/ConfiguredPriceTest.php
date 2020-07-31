<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Test\Unit\Pricing\Price;

use Magento\Catalog\Pricing\Price\BasePrice;

class ConfiguredPriceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Catalog\Model\Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $saleableItem;

    /**
     * @var \Magento\Framework\Pricing\Adjustment\CalculatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $calculator;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Catalog\Model\Product\Configuration\Item\ItemInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $item;

    /**
     * @var \Magento\GiftCard\Pricing\Price\ConfiguredPrice|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $model;

    /**
     * @var \Magento\Framework\Pricing\PriceInfoInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceInfo;

    protected function setUp()
    {
        $this->priceInfo = $this->getMockBuilder(\Magento\Framework\Pricing\PriceInfoInterface::class)
            ->getMock();

        $this->saleableItem = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getPrice',
                'hasCustomOptions',
                'getCustomOption',
                'getPriceInfo',
            ])
            ->getMock();
        $this->saleableItem->expects($this->once())
            ->method('getPriceInfo')
            ->willReturn($this->priceInfo);

        $this->calculator = $this->getMockBuilder(\Magento\Framework\Pricing\Adjustment\CalculatorInterface::class)
            ->getMock();

        $this->priceCurrency = $this->getMockBuilder(\Magento\Framework\Pricing\PriceCurrencyInterface::class)
            ->getMock();

        $this->item = $this->getMockBuilder(\Magento\Catalog\Model\Product\Configuration\Item\ItemInterface::class)
            ->getMock();

        $this->model = new \Magento\GiftCard\Pricing\Price\ConfiguredPrice(
            $this->saleableItem,
            null,
            $this->calculator,
            $this->priceCurrency,
            $this->item
        );
    }

    /**
     * @param $productPriceValue
     * @param $optionValue
     * @param $basePriceValue
     * @param $result
     *
     * @dataProvider dataProviderForGetValue
     */
    public function testGetValue($productPriceValue, $optionValue, $basePriceValue, $result)
    {
        $option = $this->getMockBuilder(\Magento\Wishlist\Model\Item\Option::class)
            ->disableOriginalConstructor()
            ->getMock();
        $option->expects($this->any())
            ->method('getValue')
            ->willReturn($optionValue);

        $price = $this->getMockBuilder(\Magento\Framework\Pricing\Price\PriceInterface::class)
            ->getMock();
        $price->expects($this->once())
            ->method('getValue')
            ->willReturn($basePriceValue);

        $this->priceInfo->expects($this->once())
            ->method('getPrice')
            ->with(BasePrice::PRICE_CODE)
            ->willReturn($price);

        $this->saleableItem->expects($this->once())
            ->method('getPrice')
            ->willReturn($productPriceValue);
        $this->saleableItem->expects($this->once())
            ->method('hasCustomOptions')
            ->willReturn(true);
        $this->saleableItem->expects($this->once())
            ->method('getCustomOption')
            ->with('giftcard_amount')
            ->willReturn($option);

        $this->item->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->saleableItem);
        $this->item->expects($this->once())
            ->method('getOptionByCode')
            ->with('option_ids')
            ->willReturn([]);

        $this->assertEquals($result, $this->model->getValue());
    }

    /**
     * @return array
     */
    public function dataProviderForGetValue()
    {
        return [
            [0., 0., 0., 0.],
            [1., 2., 3., 2.],
            [2., 3., 4., 3.],
        ];
    }
}
