<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Response;

use Magento\Eway\Gateway\Response\TransactionRefundHandler;

class TransactionRefundHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TransactionRefundHandler
     */
    private $handler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDO;

    protected function setUp()
    {
        $this->paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();

        $this->handler = new TransactionRefundHandler();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Payment data object should be provided
     */
    public function testHandleReadPaymentException()
    {
        $handlingSubject = [];
        $response = [];

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * @param $canRefund
     * @dataProvider dataProviderTestHandle
     */
    public function testHandle($canRefund)
    {
        $handlingSubject = [
            'payment' => $this->paymentDO
        ];
        $response = [
            'TransactionID' => 12345678
        ];

        $orderPayment = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $creditMemo = $this->getMockBuilder(\Magento\Sales\Model\Order\Creditmemo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $invoice = $this->getMockBuilder(\Magento\Sales\Model\Order\Invoice::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentDO->expects($this->once())
            ->method('getPayment')
            ->willReturn($orderPayment);
        $orderPayment->expects($this->once())
            ->method('getCreditMemo')
            ->willReturn($creditMemo);
        $creditMemo->expects($this->once())
            ->method('getInvoice')
            ->willReturn($invoice);
        $invoice->expects($this->once())
            ->method('canRefund')
            ->willReturn($canRefund);
        $orderPayment->expects($this->once())
            ->method('setTransactionId')
            ->with($response['TransactionID']);
        $orderPayment->expects($this->once())
            ->method('setIsTransactionClosed')
            ->with();
        $orderPayment->expects($this->once())
            ->method('setShouldCloseParentTransaction')
            ->with(!$canRefund);

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * @return array
     */
    public function dataProviderTestHandle()
    {
        return [
            [true],
            [false]
        ];
    }
}
