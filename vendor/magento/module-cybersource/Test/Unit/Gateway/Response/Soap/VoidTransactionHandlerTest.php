<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Response\Soap;

use Magento\Cybersource\Gateway\Response\Soap\VoidTransactionHandler;

/**
 * Class RequestIdHandlerTest
 */
class VoidTransactionHandlerTest extends \PHPUnit\Framework\TestCase
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
            VoidTransactionHandler::REQUEST_ID => '1'
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

        $handler = new VoidTransactionHandler();
        $handler->handle($handlingSubject, $response);
    }

    public function testHandleOrderPayment()
    {
        $paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $paymentInfo = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->setMethods(['setTransactionId', 'setShouldCloseParentTransaction', 'setIsTransactionClosed'])
            ->getMock();
        $handlingSubject = [
            'payment' => $paymentDO
        ];
        $response = [
            VoidTransactionHandler::REQUEST_ID => '1'
        ];

        $paymentDO->expects(static::exactly(2))
            ->method('getPayment')
            ->willReturn($paymentInfo);

        $paymentInfo->expects(static::once())
            ->method('setTransactionId')
            ->with($response[VoidTransactionHandler::REQUEST_ID]);
        $paymentInfo->expects(static::once())
            ->method('setIsTransactionClosed')
            ->with(true);
        $paymentInfo->expects(static::once())
            ->method('setShouldCloseParentTransaction')
            ->with(true);

        $handler = new VoidTransactionHandler();
        $handler->handle($handlingSubject, $response);
    }
}
