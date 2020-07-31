<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Request\Soap;

use Magento\Cybersource\Gateway\Request\Soap\SubscriptionDataBuilder;
use Magento\Cybersource\Gateway\Response\SilentOrder\TransactionIdHandler;

/**
 * Class SubscriptionDataBuilderTest
 */
class SubscriptionDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const TRANSACTION_ID = '11111';

    /**
     * @var SubscriptionDataBuilder
     */
    protected $subscriptionDataBuilder;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->subscriptionDataBuilder = new SubscriptionDataBuilder();
    }

    /**
     * Run test build method
     *
     * @return void
     */
    public function testBuildSuccess()
    {
        $expected = [
            'paySubscriptionCreateService' => [
                'run' => 'true',
                'paymentRequestID' => self::TRANSACTION_ID
            ],
            'recurringSubscriptionInfo' => [
                'frequency' => 'on-demand'
            ]
        ];

        $result = $this->subscriptionDataBuilder->build(['payment' => $this->getPaymentMock()]);

        static::assertEquals($expected, $result);
    }

    /**
     * Run test for build method (throw Exception)
     *
     * @expectedException \InvalidArgumentException
     * @dataProvider buildExceptionDataProvider
     */
    public function testBuildException(array $buildSubject)
    {
        $this->subscriptionDataBuilder->build($buildSubject);
    }

    public function buildExceptionDataProvider()
    {
        return [
            [['payment' => $this->getMockBuilder('NotPaymentClass')->getMock()]],
            [[]],
        ];
    }

    /**
     * @return \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPaymentMock()
    {
        $paymentMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $paymentInstanceMock = $this->getMockBuilder(
            \Magento\Sales\Model\Order\Payment::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentInstanceMock);

        $paymentInstanceMock->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(TransactionIdHandler::TRANSACTION_ID)
            ->willReturn(self::TRANSACTION_ID);

        return $paymentMock;
    }
}
