<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCard\Test\Unit\Pricing\Price;

class FinalPriceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftCard\Pricing\Price\FinalPrice
     */
    protected $model;

    /**
     * @var \Magento\Catalog\Pricing\Price\BasePrice|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $basePriceMock;

    /**
     * @var \Magento\Framework\Pricing\SaleableInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $saleableMock;

    /**
     * @var \Magento\Framework\Pricing\Adjustment\Calculator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $calculatorMock;

    /**
     * @var \Magento\Catalog\Pricing\Price\SpecialPrice|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $saleableItemMock;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceCurrencyMock;

    /**
     * Set up function
     */
    protected function setUp()
    {
        $this->saleableMock = $this->createPartialMock(
            \Magento\Catalog\Model\Product::class,
            [
                'getPriceInfo',
                'getGiftcardAmounts',
                '__wakeup'
            ]
        );

        $this->basePriceMock = $this->createMock(\Magento\Catalog\Pricing\Price\BasePrice::class);

        $this->calculatorMock = $this->createMock(\Magento\Framework\Pricing\Adjustment\Calculator::class);

        $this->priceCurrencyMock = $this->createMock(\Magento\Framework\Pricing\PriceCurrencyInterface::class);
        $this->priceCurrencyMock->expects($this->any())
            ->method('convertAndRound')
            ->will(
                $this->returnCallback(
                    function ($arg) {
                        return round(0.5 * $arg, 2);
                    }
                )
            );

        $this->model = new \Magento\GiftCard\Pricing\Price\FinalPrice(
            $this->saleableMock,
            1,
            $this->calculatorMock,
            $this->priceCurrencyMock
        );
    }

    /**
     * @param array $amounts
     * @param bool $expected
     *
     * @dataProvider getAmountsDataProvider
     */
    public function testGetAmounts($amounts, $expected)
    {
        $this->saleableMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->will($this->returnValue($amounts));

        $this->assertEquals($expected, $this->model->getAmounts());
    }

    /**
     * @return array
     */
    public function getAmountsDataProvider()
    {
        return [
            'one_amount' => [
                'amounts' => [
                    ['website_value' => 10.],
                ],
                'expected' => [5.],
            ],
            'two_amount' => [
                'amounts' => [
                    ['website_value' => 10.],
                    ['website_value' => 20.],
                ],
                'expected' => [5., 10.],
            ],
            'zero_amount' => [
                'amounts' => [],
                'expected' => [],
            ]

        ];
    }

    public function testGetAmountsCached()
    {
        $amount = [['website_value' => 5]];

        $this->saleableMock->expects($this->once())
            ->method('getGiftcardAmounts')
            ->will($this->returnValue($amount));

        $this->model->getAmounts();

        $this->assertEquals([2.5], $this->model->getAmounts());
    }

    /**
     * @param array $amounts
     * @param bool $expected
     *
     * @dataProvider getValueDataProvider
     */
    public function testGetValue($amounts, $expected)
    {
        $this->saleableMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->will($this->returnValue($amounts));

        $this->assertEquals($expected, $this->model->getValue());
    }

    /**
     * @return array
     */
    public function getValueDataProvider()
    {
        return [
            'one_amount' => [
                'amounts' => [
                    ['website_value' => 10.],
                ],
                'expected' => 5.,
            ],
            'two_amount' => [
                'amounts' => [
                    ['website_value' => 10.],
                    ['website_value' => 20.],
                ],
                'expected' => 5.,
            ],
            'zero_amount' => [
                'amounts' => [],
                'expected' => false,
            ]

        ];
    }
}
