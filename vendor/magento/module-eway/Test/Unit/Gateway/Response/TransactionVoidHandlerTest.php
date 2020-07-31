<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Response;

use Magento\Sales\Model\Order\Payment;
use Magento\Eway\Gateway\Response\TransactionVoidHandler;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

/**
 * Class TransactionVoidHandlerTest
 *
 * @see \Magento\Eway\Gateway\Response\TransactionVoidHandler
 */
class TransactionVoidHandlerTest extends \PHPUnit\Framework\TestCase
{
    const TRANSACTION_ID = 'test-id';

    /**
     * @var TransactionVoidHandler
     */
    private $transactionVoidHandler;

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

        $this->transactionVoidHandler = new TransactionVoidHandler();
    }

    /**
     * Run test for handle method
     *
     * @return void
     */
    public function testHandle()
    {
        $handlingSubject = ['payment' => $this->getPaymentDOMock()];
        $response = ['TransactionID' => self::TRANSACTION_ID];

        $this->transactionVoidHandler->handle($handlingSubject, $response);
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
            ->method('setTransactionId')
            ->with(self::TRANSACTION_ID);
        $this->paymentMock->expects($this->once())
            ->method('setIsTransactionClosed')
            ->with(true);
        $this->paymentMock->expects($this->once())
            ->method('setShouldCloseParentTransaction')
            ->with(true);

        return $this->paymentMock;
    }
}
