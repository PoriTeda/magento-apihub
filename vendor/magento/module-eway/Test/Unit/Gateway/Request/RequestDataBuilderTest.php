<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Request;

use Magento\Eway\Gateway\Request\RequestDataBuilder;
use Magento\Payment\Gateway\ConfigInterface;

/**
 * Class RequestDataBuilderTest
 */
class RequestDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RequestDataBuilder
     */
    private $builder;

    /**
     * @var ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    protected function setUp()
    {
        $this->configMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\ConfigInterface::class)
            ->getMockForAbstractClass();

        $this->builder = new RequestDataBuilder($this->configMock);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Payment data object should be provided
     */
    public function testBuildReadPaymentException()
    {
        $buildSubject = [
            'payment' => null
        ];

        $this->builder->build($buildSubject);
    }

    /**
     * @param array $configData
     * @param array $orderData
     * @param array $expectedResult
     *
     * @dataProvider dataProviderBuild
     */
    public function testBuild($configData, $orderData, $expectedResult)
    {
        $paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $order = $this->getMockBuilder(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class)
            ->getMockForAbstractClass();

        $paymentDO->expects($this->once())
            ->method('getOrder')
            ->willReturn($order);
        $this->configMock->expects($this->once())
            ->method('getValue')
            ->with('payment_action', $orderData['store_id'])
            ->willReturn($configData['payment_action']);
        $order->expects($this->once())
            ->method('getStoreId')
            ->willReturn($orderData['store_id']);
        $order->expects($this->once())
            ->method('getRemoteIp')
            ->willReturn($orderData['remote_ip']);

        $buildSubject = [
            'payment' => $paymentDO,
            'amount' => 1000
        ];

        $this->assertEquals($expectedResult, $this->builder->build($buildSubject));
    }

    /**
     * Case 1. Payment action "authorize"
     * Case 2. Payment action "authorize_capture"
     *
     * @return array
     */
    public function dataProviderBuild()
    {
        return [
            [
                [
                    'payment_action' => 'authorize'
                ],
                [
                    'store_id' => null,
                    'remote_ip' => '127.0.0.1'
                ],
                [
                    'Method' => 'Authorise',
                    'CustomerIP' => '127.0.0.1',
                    'TransactionType' => 'Purchase'
                ]
            ],
            [
                [
                    'payment_action' => 'authorize_capture'
                ],
                [
                    'store_id' => null,
                    'remote_ip' => '127.0.0.1'
                ],
                [
                    'Method' => 'ProcessPayment',
                    'CustomerIP' => '127.0.0.1',
                    'TransactionType' => 'Purchase'
                ]
            ]
        ];
    }
}
