<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Request;

use Magento\Eway\Gateway\Request\CardDetailsDataBuilder;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;

class CardDetailsDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CardDetailsDataBuilder
     */
    private $builder;

    protected function setUp()
    {
        $this->builder = new CardDetailsDataBuilder();
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
     * @expectedException \LogicException
     * @expectedExceptionMessage Order payment should be provided.
     */
    public function testBuildAssertOrderPaymentException()
    {
        $paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $order = $this->getMockBuilder(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class)
            ->getMockForAbstractClass();
        $payment = $this->getMockBuilder(\Magento\Quote\Model\Quote\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentDO->expects($this->once())
            ->method('getOrder')
            ->willReturn($order);
        $paymentDO->expects($this->once())
            ->method('getPayment')
            ->willReturn($payment);

        $buildSubject = [
            'payment' => $paymentDO,
        ];

        $this->builder->build($buildSubject);
    }

    /**
     * @param array $billingAddressData
     * @param array $paymentData
     * @param array $expectedResult
     *
     * @dataProvider dataProviderBuild
     */
    public function testBuild($billingAddressData, $paymentData, $expectedResult)
    {
        $paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $order = $this->getMockBuilder(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class)
            ->getMockForAbstractClass();
        $billingAddress = $this->getMockBuilder(\Magento\Payment\Gateway\Data\AddressAdapterInterface::class)
            ->getMockForAbstractClass();
        $payment = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->setMethods(['getAdditionalInformation'])
            ->disableOriginalConstructor()
            ->getMock();

        $paymentDO->expects($this->once())
            ->method('getOrder')
            ->willReturn($order);
        $order->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($billingAddress);
        $paymentDO->expects($this->once())
            ->method('getPayment')
            ->willReturn($payment);

        $billingAddress->expects($this->once())
            ->method('getFirstname')
            ->willReturn($billingAddressData['first_name']);
        $billingAddress->expects($this->once())
            ->method('getLastname')
            ->willReturn($billingAddressData['last_name']);
        $payment->expects($this->once())
            ->method('getAdditionalInformation')
            ->willReturn([
                'cc_number' => $paymentData['cc_number'],
                'cc_exp_month' => $paymentData['cc_exp_month'],
                'cc_exp_year' => $paymentData['cc_exp_year'],
                'cc_cid' => $paymentData['cc_cid'],
            ]);

        $buildSubject = [
            'payment' => $paymentDO,
        ];

        $this->assertEquals($expectedResult, $this->builder->build($buildSubject));
    }

    /**
     * @return array
     */
    public function dataProviderBuild()
    {
        return [
            [
                [
                    'first_name' => 'John',
                    'last_name' => 'Smith'
                ],
                [
                    'cc_number' => '4444333322221111',
                    'cc_exp_month' => '1',
                    'cc_exp_year' => '2020',
                    'cc_cid' => '123'
                ],
                [
                    'Customer' => [
                        'CardDetails' => [
                            'Name' => 'John Smith',
                            'Number' => '4444333322221111',
                            'ExpiryMonth' => '01',
                            'ExpiryYear' => '20',
                            'CVN' => '123'
                        ]
                    ]
                ]
            ]
        ];
    }
}
