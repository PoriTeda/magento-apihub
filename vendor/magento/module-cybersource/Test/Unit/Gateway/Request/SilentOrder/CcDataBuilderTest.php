<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Cybersource\Test\Unit\Gateway\Request\SilentOrder;

use Magento\Cybersource\Gateway\Request\SilentOrder\CcDataBuilder;

/**
 * Class CcDataBuilderTest
 */
class CcDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CcDataBuilder
     */
    protected $ccDataBuilder;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->ccDataBuilder = new CcDataBuilder();
    }

    /**
     * Run test build method
     *
     * @param array $buildSubject
     * @param string $expectedResult
     * @return void
     *
     * @dataProvider buildSuccessDataProvider
     */
    public function testBuildSuccess(array $buildSubject, $expectedResult)
    {
        $result = $this->ccDataBuilder->build($buildSubject);

        $this->assertArrayHasKey(CcDataBuilder::CARD_TYPE, $result);
        $this->assertEquals($expectedResult, $result[CcDataBuilder::CARD_TYPE]);
    }

    /**
     * Run test build method (Exception)
     *
     * @return void
     *
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage The credit card type field needs to be provided. Select the field and try again.
     */
    public function testBuildException()
    {
        $this->ccDataBuilder->build([]);
    }

    /**
     * Build data
     *
     * @return array
     */
    public function buildSuccessDataProvider()
    {
        return [
            [
                'buildSubject' => [
                    'cc_type' => 'AE'
                ],
                'expectedResult' => '003',
            ],
            [
                'buildSubject' => [
                    'cc_type' => 'VI'
                ],
                'expectedResult' => '001',
            ],
            [
                'buildSubject' => [
                    'cc_type' => 'MC'
                ],
                'expectedResult' => '002',
            ],
            [
                'buildSubject' => [
                    'cc_type' => 'DI'
                ],
                'expectedResult' => '004',
            ],
        ];
    }
}
