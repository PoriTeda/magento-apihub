<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Block\Direct;

use Magento\Eway\Block\Direct\Info;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class InfoTest
 *
 * @see Magento\Eway\Block\Direct\Info
 */
class InfoTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Info
     */
    private $info;

    /**
     * @var InfoInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $infoModelMock;

    /**
     * @var ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contextMock;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->configMock = $this->getMockBuilder(\Magento\Payment\Gateway\ConfigInterface::class)
            ->getMockForAbstractClass();
        $this->contextMock = $this->getMockBuilder(\Magento\Framework\View\Element\Template\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->infoModelMock = $this->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)
            ->getMockForAbstractClass();

        $this->info = new Info(
            $this->contextMock,
            $this->configMock,
            [
                'info' => $this->infoModelMock,
                'is_secure_mode' => false
            ]
        );
    }

    /**
     * Run test for getValueView method
     *
     * @param string $field
     * @param array|string $value
     * @param string $expected
     * @return void
     *
     * @dataProvider dataProviderTestGetValueView
     */
    public function testGetValueView($field, $value, $expected)
    {
        $this->configMock->expects($this->once())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['paymentInfoKeys', null, $field]
                ]
            );

        $this->infoModelMock->expects($this->exactly(2))
            ->method('getAdditionalInformation')
            ->with($field)
            ->willReturn($value);

        $this->assertEquals($expected, $this->info->getSpecificInformation());
    }

    /**
     * @return array
     */
    public function dataProviderTestGetValueView()
    {
        return [
            [
                'field' => 'test-field',
                'value' => 'test-value',
                'expected' => ['test-field' => 'test-value'],
            ],
            [
                'field' => 'transaction_id',
                'value' => 'transaction_id',
                'expected' => ['Transaction ID' => 'transaction_id'],
            ],
            [
                'field' => 'fraud_messages',
                'value' => ['F7000', 'F7001', 'F7002'],
                'expected' => [
                    'Fraud Message' => 'F7000 - Undefined Fraud Error,'
                        . ' F7001 - Challenged Fraud, F7002 - Country Match Fraud'
                ],
            ],
            [
                'field' => 'approve_messages',
                'value' => ['A0000', 'A2000', 'A2008'],
                'expected' => [
                    'Approve Message' => 'A0000 - Undefined Approved,'
                        . ' A2000 - Transaction Approved, A2008 - Honour With Identification'
                ],
            ]
        ];
    }
}
