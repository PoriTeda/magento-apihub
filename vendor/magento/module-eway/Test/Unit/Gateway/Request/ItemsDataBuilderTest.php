<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Request;

use Magento\Eway\Gateway\Request\ItemsDataBuilder;

class ItemsDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ItemsDataBuilder
     */
    private $builder;

    protected function setUp()
    {
        $this->builder = new ItemsDataBuilder();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Payment data object should be provided
     */
    public function testBuildReadPaymentException()
    {
        $buildSubject = [
            'payment' => null,
        ];

        $this->builder->build($buildSubject);
    }

    /**
     * @param array $item1Data
     * @param array $item2Data
     * @param array $expectedResult
     *
     * @dataProvider dataProviderBuild
     */
    public function testBuild($item1Data, $item2Data, $expectedResult)
    {
        $paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $order = $this->getMockBuilder(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class)
            ->getMockForAbstractClass();
        $item1 = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $item2 = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $paymentDO->expects($this->once())
            ->method('getOrder')
            ->willReturn($order);
        $order->expects($this->once())
            ->method('getItems')
            ->willReturn([$item1, $item2]);

        $item1->expects($this->exactly(6))
            ->method('getData')
            ->willReturnMap(
                [
                    ['sku', null, $item1Data['sku']],
                    ['description', null, $item1Data['description']],
                    ['qty_ordered', null, $item1Data['qty_ordered']],
                    ['base_price', null, $item1Data['base_price']],
                    ['base_tax_amount', null, $item1Data['base_tax_amount']],
                    ['base_row_total_incl_tax', null, $item1Data['base_row_total_incl_tax']]
                ]
            );
        $item2->expects($this->exactly(6))
            ->method('getData')
            ->willReturnMap(
                [
                    ['sku', null, $item2Data['sku']],
                    ['description', null, $item2Data['description']],
                    ['qty_ordered', null, $item2Data['qty_ordered']],
                    ['base_price', null, $item2Data['base_price']],
                    ['base_tax_amount', null, $item2Data['base_tax_amount']],
                    ['base_row_total_incl_tax', null, $item2Data['base_row_total_incl_tax']]
                ]
            );

        $buildSubject = [
            'payment' => $paymentDO,
        ];

        $this->assertEquals($expectedResult, $this->builder->build($buildSubject));
    }

    public function dataProviderBuild()
    {
        return [
            [
                [
                    'sku' => 'item1',
                    'description' => 'brick',
                    'qty_ordered' => 1,
                    'base_price' => 100,
                    'base_tax_amount' => 10,
                    'base_row_total_incl_tax' => 110
                ],
                [
                    'sku' => 'item2',
                    'description' => 'monster',
                    'qty_ordered' => 1,
                    'base_price' => 100,
                    'base_tax_amount' => 10,
                    'base_row_total_incl_tax' => 110
                ],
                [
                    'Items' => [
                        [
                            'SKU' => 'item1',
                            'Description' => 'brick',
                            'Quantity' => 1,
                            'UnitCost' => 100,
                            'Tax' => 10,
                            'Total' => 110
                        ],
                        [
                            'SKU' => 'item2',
                            'Description' => 'monster',
                            'Quantity' => 1,
                            'UnitCost' => 100,
                            'Tax' => 10,
                            'Total' => 110
                        ]
                    ]
                ]
            ]
        ];
    }
}
