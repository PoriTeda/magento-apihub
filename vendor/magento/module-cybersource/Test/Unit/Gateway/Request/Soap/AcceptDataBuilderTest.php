<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Request\Soap;

use Magento\Cybersource\Gateway\Request\Soap\AcceptDataBuilder;

/**
 * Class AcceptDataBuilderTest
 */
class AcceptDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const LAST_TRANSACTION_ID = '123456';

    /**
     * @var AcceptDataBuilder
     */
    protected $acceptDataBuilder;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->acceptDataBuilder = new AcceptDataBuilder();
    }

    /**
     * Run test build method
     *
     * @return void
     */
    public function testBuildSuccess()
    {
        $expected = [
            'caseManagementActionService' => [
                'run' => 'true',
                'requestID' => self::LAST_TRANSACTION_ID,
                'actionCode' => 'ACCEPT'
            ]
        ];
        $result = $this->acceptDataBuilder->build(['payment' => $this->getPaymentMock()]);
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
        $this->acceptDataBuilder->build($buildSubject);
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
        $paymentInstanceMock->expects(static::once())
            ->method('getLastTransId')
            ->willReturn(self::LAST_TRANSACTION_ID);

        $paymentMock->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentInstanceMock);

        return $paymentMock;
    }
}
