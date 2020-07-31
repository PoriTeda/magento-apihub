<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Request\Soap;

use Magento\Cybersource\Gateway\Request\Soap\CaptureDataBuilder;

/**
 * Class CaptureDataBuilderTest
 */
class CaptureDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const PARENT_TRANSACTION_ID = '123456';
    const AMOUNT = 2.1111;
    const CURRENCY_CODE = 'USD';

    /**
     * @var CaptureDataBuilder
     */
    protected $captureDataBuilder;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->captureDataBuilder = new CaptureDataBuilder();
    }

    /**
     * Run test build method
     *
     * @return void
     */
    public function testBuildSuccess()
    {
        $expected = [
            'ccCaptureService' => [
                'run' => 'true',
                'authRequestID' => self::PARENT_TRANSACTION_ID
            ],
            'purchaseTotals' => [
                'currency' => self::CURRENCY_CODE,
                'grandTotalAmount' => self::AMOUNT
            ]
        ];
        $result = $this->captureDataBuilder->build(
            [
                'payment' => $this->getPaymentMock(),
                'amount' => self::AMOUNT
            ]
        );
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
        $this->captureDataBuilder->build($buildSubject);
    }

    public function buildExceptionDataProvider()
    {
        return [
            [['amount' => self::AMOUNT]],
            [['payment' => $this->getPaymentMock()]],
            [[]]
        ];
    }

    /**
     * @return \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPaymentMock()
    {
        $paymentMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $paymentInstanceMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
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

        $paymentMock->expects(static::once())
            ->method('getOrder')
            ->willReturn($orderInstanceMock);

        $orderInstanceMock->expects(static::once())
            ->method('getCurrencyCode')
            ->willReturn(self::CURRENCY_CODE);

        return $paymentMock;
    }
}
