<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Test\Unit\Gateway\Response;

use Magento\Worldpay\Gateway\Response\WafMessageHandler;

class WafMessageHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testHandle()
    {
        $response = [
            'wafMerchMessage' => 'waf.caution'
        ];
        $additionalInfoExpectation = [
            ['waf_merch_message', 'waf.caution']
        ];

        $paymentDO = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class
        )
            ->getMockForAbstractClass();
        $paymentInfo = $this->getMockBuilder(
            \Magento\Payment\Model\InfoInterface::class
        )
            ->getMockForAbstractClass();

        $paymentDO->expects(static::any())
            ->method('getPayment')
            ->willReturn($paymentInfo);
        $paymentInfo->expects(static::once())
            ->method('setAdditionalInformation')
            ->willReturnMap($additionalInfoExpectation);

        $handler = new WafMessageHandler();
        $handler->handle(
            ['payment' => $paymentDO],
            $response
        );
    }

    public function testHandleFraud()
    {
        $response = [
            'wafMerchMessage' => 'waf.warning'
        ];
        $additionalInfoExpectation = [
            ['waf_merch_message', 'waf.warning']
        ];

        $paymentDO = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class
        )
            ->getMockForAbstractClass();
        $paymentInfo = $this->getMockBuilder(
            \Magento\Sales\Model\Order\Payment::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $paymentDO->expects(static::any())
            ->method('getPayment')
            ->willReturn($paymentInfo);
        $paymentInfo->expects(static::once())
            ->method('setAdditionalInformation')
            ->willReturnMap($additionalInfoExpectation);
        $paymentInfo->expects(static::once())
            ->method('setIsFraudDetected')
            ->with(true);

        $handler = new WafMessageHandler();
        $handler->handle(
            ['payment' => $paymentDO],
            $response
        );
    }
}
