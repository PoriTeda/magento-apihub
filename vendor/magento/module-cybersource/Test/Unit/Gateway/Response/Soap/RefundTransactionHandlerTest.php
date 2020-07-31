<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Response\Soap;

use Magento\Cybersource\Gateway\Response\Soap\RefundTransactionHandler;

/**
 * Class RequestIdHandlerTest
 */
class RefundTransactionHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testHandle()
    {
        $paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $paymentInfo = $this->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)
            ->setMethods(['setTransactionId', 'setIsTransactionClosed', 'setShouldCloseParentTransaction'])
            ->getMockForAbstractClass();
        $handlingSubject = [
            'payment' => $paymentDO
        ];
        $response = [
            RefundTransactionHandler::REQUEST_ID => '1'
        ];

        $paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentInfo);

        $paymentInfo->expects(static::never())
            ->method('setTransactionId');
        $paymentInfo->expects(static::never())
            ->method('setIsTransactionClosed');
        $paymentInfo->expects(static::never())
            ->method('setShouldCloseParentTransaction');

        $handler = new RefundTransactionHandler();
        $handler->handle($handlingSubject, $response);
    }

    /**
     * @param bool $canRefund
     * @param bool $shouldCloseParentTransaction
     * @dataProvider handleOrderPaymentDataProvider
     */
    public function testHandleOrderPayment($canRefund, $shouldCloseParentTransaction)
    {
        $paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $paymentInfo = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setTransactionId',
                    'setShouldCloseParentTransaction',
                    'setIsTransactionClosed',
                    'getCreditmemo'
                ]
            )
            ->getMock();
        $handlingSubject = [
            'payment' => $paymentDO
        ];
        $response = [
            RefundTransactionHandler::REQUEST_ID => '1'
        ];

        $paymentDO->expects(static::exactly(2))
            ->method('getPayment')
            ->willReturn($paymentInfo);

        $paymentInfo->expects(static::once())
            ->method('setTransactionId')
            ->with($response[RefundTransactionHandler::REQUEST_ID]);

        /** @var \PHPUnit_Framework_MockObject_MockObject | \Magento\Sales\Model\Order\Invoice $invoiceMock */
        $invoiceMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Invoice::class)
            ->disableOriginalConstructor()
            ->getMock();
        $invoiceMock->expects(static::once())
            ->method('canRefund')
            ->willReturn($canRefund);

        /** @var \PHPUnit_Framework_MockObject_MockObject | \Magento\Sales\Model\Order\Creditmemo $creditmemoMock */
        $creditmemoMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Creditmemo::class)
            ->setMethods(['getInvoice'])
            ->disableOriginalConstructor()
            ->getMock();
        $creditmemoMock->expects(static::once())
            ->method('getInvoice')
            ->willReturn($invoiceMock);

        $paymentInfo->expects(static::once())
            ->method('getCreditmemo')
            ->willReturn($creditmemoMock);

        $paymentInfo->expects(static::once())
            ->method('setIsTransactionClosed')
            ->with(true);
        $paymentInfo->expects(static::once())
            ->method('setShouldCloseParentTransaction')
            ->with($shouldCloseParentTransaction);

        $handler = new RefundTransactionHandler();
        $handler->handle($handlingSubject, $response);
    }

    public function handleOrderPaymentDataProvider()
    {
        return [
            [true, false], //$canRefund, $shouldCloseParentTransaction
            [false, true]
        ];
    }
}
