<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Test\Unit\Gateway\Response;

use Magento\Worldpay\Gateway\Response\CvvHandler;

class CvvHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testHandle()
    {
        $response = [
            'AVS' => '1124'
        ];
        $additionalInfoExpectation = [
            ['cvv_result', '1']
        ];
        $fraudCases = '';

        $paymentDO = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class
        )
            ->getMockForAbstractClass();
        $paymentInfo = $this->getMockBuilder(
            \Magento\Payment\Model\InfoInterface::class
        )
            ->getMockForAbstractClass();
        $configMock = $this->getMockBuilder(
            \Magento\Payment\Gateway\ConfigInterface::class
        )
            ->getMockForAbstractClass();

        $paymentDO->expects(static::any())
            ->method('getPayment')
            ->willReturn($paymentInfo);
        $paymentInfo->expects(static::exactly(1))
            ->method('setAdditionalInformation')
            ->willReturnMap($additionalInfoExpectation);
        $configMock->expects(static::once())
            ->method('getValue')
            ->with(CvvHandler::FRAUD_CASE)
            ->willReturn($fraudCases);

        $handler = new CvvHandler($configMock);
        $handler->handle(
            ['payment' => $paymentDO],
            $response
        );
    }

    public function testHandleFraud()
    {
        $response = [
            'AVS' => '2224'
        ];
        $additionalInfoExpectation = [
            ['cvv_result', '2']
        ];
        $fraudCases = '2,4';

        $paymentDO = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class
        )
            ->getMockForAbstractClass();
        $paymentInfo = $this->getMockBuilder(
            \Magento\Sales\Model\Order\Payment::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $configMock = $this->getMockBuilder(
            \Magento\Payment\Gateway\ConfigInterface::class
        )
            ->getMockForAbstractClass();

        $paymentDO->expects(static::any())
            ->method('getPayment')
            ->willReturn($paymentInfo);
        $paymentInfo->expects(static::exactly(1))
            ->method('setAdditionalInformation')
            ->willReturnMap($additionalInfoExpectation);
        $configMock->expects(static::once())
            ->method('getValue')
            ->with(CvvHandler::FRAUD_CASE)
            ->willReturn($fraudCases);
        $paymentInfo->expects(static::once())
            ->method('setIsFraudDetected')
            ->with(true);

        $handler = new CvvHandler($configMock);
        $handler->handle(
            ['payment' => $paymentDO],
            $response
        );
    }
}
