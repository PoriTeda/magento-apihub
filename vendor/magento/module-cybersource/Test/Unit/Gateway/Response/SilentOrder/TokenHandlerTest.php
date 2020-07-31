<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Response\SilentOrder;

use Magento\Cybersource\Gateway\Request\SilentOrder\PaymentTokenBuilder;
use Magento\Cybersource\Gateway\Response\SilentOrder\TokenHandler;

class TokenHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testHandle()
    {
        $paymentDO = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class
        )->getMockForAbstractClass();
        $paymentInfo = $this->getMockBuilder(
            \Magento\Payment\Model\InfoInterface::class
        )->getMockForAbstractClass();

        $handlingSubject = [
            'payment' => $paymentDO
        ];
        $response = [
            PaymentTokenBuilder::PAYMENT_TOKEN => '1'
        ];

        $paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentInfo);
        $paymentInfo->expects(static::once())
            ->method('setAdditionalInformation')
            ->with(PaymentTokenBuilder::PAYMENT_TOKEN, '1');

        $handler = new TokenHandler();
        $handler->handle($handlingSubject, $response);
    }
}
