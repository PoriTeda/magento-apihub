<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCard\Test\Unit\Pricing\Render;

use Magento\Catalog\Model\Product\Pricing\Renderer\SalableResolverInterface;
use Magento\Catalog\Pricing\Price\MinimalPriceCalculatorInterface;

class FinalPriceBoxTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Catalog\Pricing\Price\SpecialPrice|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $saleableItemMock;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceCurrencyMock;

    /**
     * @var \Magento\Framework\View\Element\Template\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \Magento\Store\Model\Store|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeMock;

    /**
     * @var SalableResolverInterface
     */
    private $salableResolver;

    /**
     * @var MinimalPriceCalculatorInterface
     */
    private $minimalPriceCalculator;

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->saleableItemMock = $this->createPartialMock(
            \Magento\Catalog\Model\Product::class,
            [
                'getGiftcardAmounts',
                'getAllowOpenAmount',
                'getOpenAmountMin',
                'getOpenAmountMax',
                '__wakeup',
                'hasCustomOptions',
                'getCustomOption',
                'hasPreconfiguredValues',
                'getPreconfiguredValues',
            ],
            [],
            '',
            false
        );

        $this->priceCurrencyMock = $this->getMockBuilder(
            \Magento\Framework\Pricing\PriceCurrencyInterface::class
        )->disableOriginalConstructor()->setMethods(['convertAndFormat', 'convert'])->getMockForAbstractClass();

        $this->contextMock = $this->createPartialMock(
            \Magento\Framework\View\Element\Template\Context::class,
            ['getStoreManager']
        );

        $this->storeManagerMock = $this->getMockBuilder(
            \Magento\Store\Model\StoreManagerInterface::class
        )->disableOriginalConstructor()->setMethods(['getStore'])->getMockForAbstractClass();

        $this->storeMock = $this->createPartialMock(
            \Magento\Store\Model\Store::class,
            ['getCurrentCurrencyCode', '__wakeup']
        );

        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->will($this->returnValue($this->storeMock));

        $this->contextMock->expects($this->any())
            ->method('getStoreManager')
            ->will($this->returnValue($this->storeManagerMock));

        $this->priceCurrencyMock = $this->getMockBuilder(
            \Magento\Framework\Pricing\PriceCurrencyInterface::class
        )->disableOriginalConstructor()->setMethods(['convertAndFormat', 'convert'])->getMockForAbstractClass();

        $this->salableResolver = $this->getMockBuilder(SalableResolverInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->minimalPriceCalculator = $this->getMockBuilder(MinimalPriceCalculatorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    /**
     * @param array $amounts
     * @param bool $isOpenAmount
     * @param bool $expected
     *
     * @dataProvider isRegularPriceDataProvider
     */
    public function testIsRegularPrice($amounts, $isOpenAmount, $expected)
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->will($this->returnValue($amounts));

        $this->saleableItemMock->expects($this->any())
            ->method('getAllowOpenAmount')
            ->will($this->returnValue($isOpenAmount));

        $finalPriceBox = $this->getFinalPriceBox();

        $this->assertEquals($expected, $finalPriceBox->isRegularPrice());
    }

    /**
     * @return array
     */
    public function isRegularPriceDataProvider()
    {
        return [
            'one_amount' => [
                'amounts' => [
                    [
                        'website_value' => 10.,
                    ],
                ],
                'isOpenAmount' => false,
                'expected' => true,
            ],
            'two_amount' => [
                'amounts' => [
                    [
                        'website_value' => 10.,
                    ],
                    [
                        'website_value' => 20.
                    ],
                ],
                'isOpenAmount' => false,
                'expected' => false,
            ],
            'open_amount' => [
                'amounts' => [
                    [
                        'website_value' => 10.,
                    ],
                ],
                'isOpenAmount' => true,
                'expected' => false,
            ]

        ];
    }

    /**
     * @return \Magento\GiftCard\Pricing\Render\FinalPriceBox
     */
    protected function getFinalPriceBox()
    {
        return $this->objectManager->getObject(
            \Magento\GiftCard\Pricing\Render\FinalPriceBox::class,
            [
                'saleableItem' => $this->saleableItemMock,
                'priceCurrency' => $this->priceCurrencyMock,
                'context' => $this->contextMock,
                'salableResolver' => $this->salableResolver,
                'minimalPriceCalculator' => $this->minimalPriceCalculator
            ]
        );
    }

    /**
     * @param bool $isOpenAmount
     * @param bool $expected
     *
     * @dataProvider isOpenAmountDataProvider
     */
    public function testIsOpenAmountAvailable($isOpenAmount, $expected)
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->will($this->returnValue([]));

        $this->saleableItemMock->expects($this->any())
            ->method('getAllowOpenAmount')
            ->will($this->returnValue($isOpenAmount));

        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals($expected, $finalPriceBox->isOpenAmountAvailable());
    }

    /**
     * @return array
     */
    public function isOpenAmountDataProvider()
    {
        return [
            'major' => [
                'isOpenAmount' => true,
                'expected' => true,
            ],
            'minor' => [
                'isOpenAmount' => false,
                'expected' => false,
            ],
        ];
    }

    /**
     * @param array $amounts
     * @param float $expected
     *
     * @dataProvider getRegularPriceDataProvider
     */
    public function testGetRegularPrice($amounts, $expected)
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->will($this->returnValue($amounts));

        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals($expected, $finalPriceBox->getRegularPrice());
    }

    /**
     * @return array
     */
    public function getRegularPriceDataProvider()
    {
        return [
            'one_amount' => [
                'amounts' => [
                    [
                        'website_value' => 20.,
                    ],
                ],
                'expected' => 20.,
            ],
            'two_amount' => [
                'amounts' => [
                    [
                        'website_value' => 20.,
                    ],
                    [
                        'website_value' => 30.
                    ],
                ],
                'expected' => false,
            ],
        ];
    }

    /**
     * @param array $amounts
     * @param float $expected
     *
     * @dataProvider getAmountsDataProvider
     */
    public function testGetAmounts($amounts, $expected)
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->will($this->returnValue($amounts));

        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals($expected, $finalPriceBox->getAmounts());
    }

    /**
     * @return array
     */
    public function getAmountsDataProvider()
    {
        return [
            'zero_amount' => [
                'amounts' => [],
                'expected' => [],
            ],
            'one_amount' => [
                'amounts' => [
                    [
                        'website_value' => 50.,
                    ],
                ],
                'expected' => [50.],
            ],
            'two_amount' => [
                'amounts' => [
                    [
                        'website_value' => 60.,
                    ],
                    [
                        'website_value' => 70.
                    ],
                ],
                'expected' => [60., 70.],
            ],
        ];
    }

    public function testConvertAndFormatCurrency()
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->will($this->returnValue([]));

        $this->priceCurrencyMock->expects($this->any())
            ->method('convertAndFormat')
            ->with($this->equalTo(10), $this->equalTo(true))
            ->will($this->returnValue('$10.00'));

        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals('$10.00', $finalPriceBox->convertAndFormatCurrency(10, true));
    }

    public function testConvertCurrency()
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->will($this->returnValue([]));

        $this->priceCurrencyMock->expects($this->any())
            ->method('convert')
            ->with($this->equalTo(20))
            ->will($this->returnValue('50.00'));

        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals('50.00', $finalPriceBox->convertCurrency(20));
    }

    /**
     * @param array $amounts
     * @param bool $expected
     *
     * @dataProvider isAmountAvailableDataProvider
     */
    public function testIsAmountAvailable($amounts, $expected)
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->will($this->returnValue($amounts));

        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals($expected, $finalPriceBox->isAmountAvailable());
    }

    /**
     * @return array
     */
    public function isAmountAvailableDataProvider()
    {
        return [
            'zero_amount' => [
                'amounts' => [],
                'expected' => false,
            ],
            'one_amount' => [
                'amounts' => [
                    [
                        'website_value' => 50.,
                    ],
                ],
                'expected' => true,
            ]
        ];
    }

    public function testGetOpenAmountMin()
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->will($this->returnValue([]));

        $this->saleableItemMock->expects($this->any())
            ->method('getOpenAmountMin')
            ->will($this->returnValue(0.));

        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals(0., $finalPriceBox->getOpenAmountMin());
    }

    public function testGetOpenAmountMax()
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->will($this->returnValue([]));

        $this->saleableItemMock->expects($this->any())
            ->method('getOpenAmountMax')
            ->will($this->returnValue(20.));

        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals(20., $finalPriceBox->getOpenAmountMax());
    }

    public function testGetCurrentCurrency()
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->will($this->returnValue([]));

        $this->storeMock->expects($this->any())
            ->method('getCurrentCurrencyCode')
            ->will($this->returnValue('USD'));

        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals('USD', $finalPriceBox->getCurrentCurrency());
    }

    /**
     * @param array $amounts
     * @param float $openMinAmount
     * @param float $openMaxAmount
     * @param bool $isOpenAmount
     * @param float $expected
     *
     * @dataProvider getMinValueDataProvider
     */
    public function testGetMinValue($amounts, $openMinAmount, $openMaxAmount, $isOpenAmount, $expected)
    {
        $this->preparePriceCalculation($amounts, $openMinAmount, $openMaxAmount, $isOpenAmount);
        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals($expected, $finalPriceBox->getMinValue());
    }

    /**
     * @param array $amounts
     * @param float $openMinAmount
     * @param float $openMaxAmount
     * @param bool $isOpenAmount
     */
    protected function preparePriceCalculation($amounts, $openMinAmount, $openMaxAmount, $isOpenAmount)
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->will($this->returnValue($amounts));

        $this->saleableItemMock->expects($this->any())
            ->method('getOpenAmountMin')
            ->will($this->returnValue($openMinAmount));

        $this->saleableItemMock->expects($this->any())
            ->method('getOpenAmountMax')
            ->will($this->returnValue($openMaxAmount));

        $this->saleableItemMock->expects($this->any())
            ->method('getAllowOpenAmount')
            ->will($this->returnValue($isOpenAmount));
    }

    /**
     * @return array
     */
    public function getMinValueDataProvider()
    {
        return [
            'open_amount_minimal' => [
                'amounts' => [
                    [
                        'website_value' => 30.,
                    ],
                    [
                        'website_value' => 60.
                    ],
                ],
                'openMinAmount' => 20.,
                'openMaxAmount' => 90.,
                'isOpenAmount' => true,
                'expected' => 20.,
            ],
            'amounts_minimal' => [
                'amounts' => [
                    [
                        'website_value' => 100.,
                    ],
                    [
                        'website_value' => 90.
                    ],
                ],
                'openMinAmount' => 110.,
                'openMaxAmount' => 120.,
                'isOpenAmount' => true,
                'expected' => 90.,
            ],
            'open_amounts_disabled' => [
                'amounts' => [
                    [
                        'website_value' => 10.,
                    ],
                    [
                        'website_value' => 20.
                    ],
                ],
                'openMinAmount' => 1.,
                'openMaxAmount' => 2.,
                'isOpenAmount' => false,
                'expected' => 10.,
            ]
        ];
    }

    /**
     * @param array $amounts
     * @param float $openMinAmount
     * @param float $openMaxAmount
     * @param bool $isOpenAmount
     * @param float $expected
     *
     * @dataProvider isMinEqualToMaxDataProvider
     */
    public function testIsMinEqualToMax($amounts, $openMinAmount, $openMaxAmount, $isOpenAmount, $expected)
    {
        $this->preparePriceCalculation($amounts, $openMinAmount, $openMaxAmount, $isOpenAmount);
        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals($expected, $finalPriceBox->isMinEqualToMax());
    }

    /**
     * @return array
     */
    public function isMinEqualToMaxDataProvider()
    {
        return [
            'equal_open_and_amounts' => [
                'amounts' => [
                    [
                        'website_value' => 20.,
                    ],
                    [
                        'website_value' => 20.
                    ],
                ],
                'openMinAmount' => 20.,
                'openMaxAmount' => 20.,
                'isOpenAmount' => true,
                'expected' => true,
            ],
            'non_equal_open_and_amounts' => [
                'amounts' => [
                    [
                        'website_value' => 10.,
                    ],
                    [
                        'website_value' => 20.
                    ],
                ],
                'openMinAmount' => 10.,
                'openMaxAmount' => 20.,
                'isOpenAmount' => true,
                'expected' => false,
            ],
            'open_amounts_disabled' => [
                'amounts' => [
                    [
                        'website_value' => 10.,
                    ],
                ],
                'openMinAmount' => 20.,
                'openMaxAmount' => 30.,
                'isOpenAmount' => false,
                'expected' => true,
            ]
        ];
    }

    /**
     * @param $value
     * @param $result
     *
     * @dataProvider dataProviderOptionValue
     */
    public function testGetGiftcardAmount($value, $result)
    {
        $this->saleableItemMock->expects($this->any())
            ->method('getGiftcardAmounts')
            ->will($this->returnValue([]));

        $option = $this->getMockBuilder(\Magento\Wishlist\Model\Item\Option::class)
            ->disableOriginalConstructor()
            ->getMock();
        $option->expects($this->any())
            ->method('getData')
            ->with('giftcard_amount')
            ->willReturn($value);

        $this->saleableItemMock->expects($this->once())
            ->method('hasPreconfiguredValues')
            ->willReturn(true);
        $this->saleableItemMock->expects($this->once())
            ->method('getPreconfiguredValues')
            ->willReturn($option);

        $finalPriceBox = $this->getFinalPriceBox();
        $this->assertEquals($result, $finalPriceBox->getGiftcardAmount());
    }

    /**
     * @return array
     */
    public function dataProviderOptionValue()
    {
        return [
            [0., 0.],
            [1., 1.],
        ];
    }
}
