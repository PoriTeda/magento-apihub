<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Request\Soap;

use Magento\Cybersource\Gateway\Request\Soap\VoidDataBuilder;

/**
 * Class VoidDataBuilderTest
 */
class VoidDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const PARENT_TRANSACTION_ID = '123456';
    const AMOUNT = 100.00;
    const CURRENCY_CODE = 'USD';

    /**
     * @var VoidDataBuilder
     */
    protected $voidDataBuilder;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->voidDataBuilder = new VoidDataBuilder();
    }

    /**
     * Run test build method
     *
     * @return void
     */
    public function testBuildSuccess()
    {
        $expected = [
            'ccAuthReversalService' => [
                'run' => 'true',
                'authRequestID' => self::PARENT_TRANSACTION_ID
            ],
            'purchaseTotals' => [
                'currency' => self::CURRENCY_CODE,
                'grandTotalAmount' => self::AMOUNT
            ]
        ];

        $result = $this->voidDataBuilder->build(['payment' => $this->getPaymentMock()]);
        static::assertEquals($expected, $result);
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
        $orderInstanceMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class)
            ->getMockForAbstractClass();

        $paymentMock->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentInstanceMock);

        $paymentInstanceMock->expects(static::once())
            ->method('getParentTransactionId')
            ->willReturn(self::PARENT_TRANSACTION_ID);

        $paymentMock->expects(static::exactly(2))
            ->method('getOrder')
            ->willReturn($orderInstanceMock);

        $orderInstanceMock->expects(static::once())
            ->method('getCurrencyCode')
            ->willReturn(self::CURRENCY_CODE);

        $orderInstanceMock->expects(static::once())
            ->method('getGrandTotalAmount')
            ->willReturn(self::AMOUNT);

        return $paymentMock;
    }

    /**
     * Run test for build method (throw Exception)
     *
     * @expectedException \InvalidArgumentException
     */
    public function testBuildException()
    {
        $this->voidDataBuilder->build(['payment' => null]);
    }
}
