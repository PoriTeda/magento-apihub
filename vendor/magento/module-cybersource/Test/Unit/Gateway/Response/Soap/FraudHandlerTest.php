<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Response\Soap;

use Magento\Cybersource\Gateway\Response\Soap\FraudHandler;
use Magento\Cybersource\Gateway\Validator\DecisionValidator;

class FraudHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testHandle()
    {
        $paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $paymentInfo = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $handlingSubject = [
            'payment' => $paymentDO
        ];
        $response = [
            DecisionValidator::DECISION => 'REVIEW',
            'afsReply' => [
                'afsResult' => '20',
                'afsFactorCode' => 'Q^V'
            ]
        ];

        $paymentDO->expects(static::atLeastOnce())
            ->method('getPayment')
            ->willReturn($paymentInfo);
        $paymentInfo->expects(static::at(0))
            ->method('setAdditionalInformation')
            ->with(
                \Magento\Cybersource\Gateway\Response\FraudHandler::RISK_SCORE,
                '20'
            );
        $paymentInfo->expects(static::at(1))
            ->method('setAdditionalInformation')
            ->with(
                \Magento\Cybersource\Gateway\Response\FraudHandler::RISK_FACTORS,
                'Q^V'
            );

        $paymentInfo->expects(static::at(2))
            ->method('setIsTransactionPending')
            ->with(true);

        $paymentInfo->expects(static::at(3))
            ->method('setIsFraudDetected')
            ->with(true);

        $handler = new FraudHandler();
        $handler->handle($handlingSubject, $response);
    }
}
