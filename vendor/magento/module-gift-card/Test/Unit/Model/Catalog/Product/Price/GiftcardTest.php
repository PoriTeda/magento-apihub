<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Test\Unit\Model\Catalog\Product\Price;

class GiftcardTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftCard\Model\Catalog\Product\Price\Giftcard
     */
    protected $model;

    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(\Magento\GiftCard\Model\Catalog\Product\Price\Giftcard::class);
    }

    /**
     * @param array $amounts
     * @param bool $withCustomOptions
     * @param float $expectedPrice
     * @dataProvider getPriceDataProvider
     */
    public function testGetPrice($amounts, $withCustomOptions, $expectedPrice)
    {
        $product = $this->createPartialMock(
            \Magento\Catalog\Model\Product::class,
            ['getData', 'getAllowOpenAmount', 'hasCustomOptions', '__wakeup']
        );
        $product->expects($this->once())->method('getAllowOpenAmount')->will($this->returnValue(false));
        $product->expects($this->any())->method('hasCustomOptions')->will($this->returnValue($withCustomOptions));
        $product->expects($this->atLeastOnce())->method('getData')->will(
            $this->returnValueMap([['price', null, null], ['giftcard_amounts', null, $amounts]])
        );

        $this->assertEquals($expectedPrice, $this->model->getPrice($product));
    }

    /**
     * @return array
     */
    public function getPriceDataProvider()
    {
        return [
            [[['website_id' => 0, 'value' => '10.0000', 'website_value' => 10]], false, 10],
            [[['website_id' => 0, 'value' => '10.0000', 'website_value' => 10]], true, 0],
            [
                [
                    ['website_id' => 0, 'value' => '10.0000', 'website_value' => 10],
                    ['website_id' => 0, 'value' => '100.0000', 'website_value' => 100],
                ],
                false,
                0
            ],
        ];
    }

    public function testGetPriceWithFixedAmount()
    {
        $price = 3;

        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $product->expects($this->exactly(2))->method('getData')->with('price')->will($this->returnValue($price));

        $this->assertEquals($price, $this->model->getPrice($product));
    }

    public function testGetFinalPrice()
    {
        $productPrice = 5;
        $optionPrice = 3;

        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customOption = $this->getMockBuilder(\Magento\Catalog\Model\Product\Configuration\Item\Option::class)
            ->disableOriginalConstructor()
            ->getMock();

        $product->expects($this->once())->method('getPrice')->will($this->returnValue($productPrice));
        $product->expects($this->once())->method('hasCustomOptions')->will($this->returnValue(true));
        $product->expects($this->at(2))
            ->method('getCustomOption')
            ->with('giftcard_amount')
            ->will($this->returnValue($customOption));
        $customOption->expects($this->once())->method('getValue')->will($this->returnValue($optionPrice));
        $product->expects($this->at(3))
            ->method('getCustomOption')
            ->with('option_ids')
            ->will($this->returnValue(null));
        $product->expects($this->once())
            ->method('setData')
            ->with('final_price', $productPrice + $optionPrice)
            ->will($this->returnSelf());
        $product->expects($this->once())
            ->method('getData')
            ->with('final_price')
            ->will($this->returnValue($productPrice + $optionPrice));

        $this->assertEquals($productPrice + $optionPrice, $this->model->getFinalPrice(5, $product));
    }
}
