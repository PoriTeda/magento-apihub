<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Request;

use Magento\Sales\Model\Order\Payment;
use Magento\Eway\Gateway\Request\TransactionIdDataBuilder;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

/**
 * Class VoidDataBuilderTest
 *
 * @see \Magento\Eway\Gateway\Request\VoidDataBuilder
 */
class TransferIdDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const TRANSACTION_ID = 'test-transaction-id';

    /**
     * @var TransactionIdDataBuilder
     */
    private $dataBuilder;

    /**
     * @var PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /**
     * @var Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->paymentDOMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $this->paymentMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataBuilder = new TransactionIdDataBuilder();
    }

    /**
     * Run test for build method
     *
     * @return void
     */
    public function testBuild()
    {
        $buildSubject = ['payment' => $this->getPaymentDOMock()];

        $result = $this->dataBuilder->build($buildSubject);

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('TransactionId', $result);
        $this->assertEquals($result['TransactionId'], self::TRANSACTION_ID);
    }

    /**
     * @return PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getPaymentDOMock()
    {
        $this->paymentDOMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($this->getPaymentMock());

        return $this->paymentDOMock;
    }

    /**
     * @return Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getPaymentMock()
    {
        $this->paymentMock->expects($this->once())
            ->method('getParentTransactionId')
            ->willReturn(self::TRANSACTION_ID);

        return $this->paymentMock;
    }
}
