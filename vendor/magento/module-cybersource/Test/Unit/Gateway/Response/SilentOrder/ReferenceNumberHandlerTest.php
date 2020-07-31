<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Response\SilentOrder;

use Magento\Cybersource\Gateway\Request\SilentOrder\TransactionDataBuilder;
use Magento\Cybersource\Gateway\Response\SilentOrder\ReferenceNumberHandler;

class ReferenceNumberHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testHandle()
    {
        $paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $paymentInfo = $this->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)
            ->getMockForAbstractClass();
        $handlingSubject = [
            'payment' => $paymentDO
        ];
        $response = [
            'req_' . TransactionDataBuilder::REFERENCE_NUMBER => '1'
        ];

        $paymentDO->expects(static::atLeastOnce())
            ->method('getPayment')
            ->willReturn($paymentInfo);
        $paymentInfo->expects(static::once())
            ->method('setAdditionalInformation')
            ->with(
                TransactionDataBuilder::REFERENCE_NUMBER,
                '1'
            );

        $handler = new ReferenceNumberHandler();
        $handler->handle($handlingSubject, $response);
    }
}
