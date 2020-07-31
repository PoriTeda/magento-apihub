<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Request;

use Magento\Eway\Gateway\Request\RefundDataBuilder;

class RefundDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RefundDataBuilder
     */
    private $builder;

    protected function setUp()
    {
        $this->builder = new RefundDataBuilder();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Amount should be provided
     */
    public function testBuildReadAmountException()
    {
        $buildSubject = [
            'amount' => null
        ];

        $this->builder->build($buildSubject);
    }

    /**
     * @param array $buildSubject
     * @param array $expectedResult
     * @dataProvider dataProviderBuild
     */
    public function testBuild($buildSubject, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->builder->build($buildSubject));
    }

    /**
     * Case 1. Integer amount
     * Case 2. Float amount
     * Case 3. Float amount was rounded by sprintf function
     *
     * @return array
     */
    public function dataProviderBuild()
    {
        return [
            [
                [
                    'amount' => 10
                ],
                [
                    'Refund' => [
                        'TotalAmount' => 1000
                    ]
                ]
            ],
            [
                [
                    'amount' => 10.01
                ],
                [
                    'Refund' => [
                        'TotalAmount' => 1001
                    ]
                ]
            ],
            [
                [
                    'amount' => 10.015
                ],
                [
                    'Refund' => [
                        'TotalAmount' => 1002
                    ]
                ]
            ]
        ];
    }
}
