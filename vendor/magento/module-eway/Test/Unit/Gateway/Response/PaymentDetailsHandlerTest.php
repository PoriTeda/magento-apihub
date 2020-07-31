<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Response;

use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Eway\Gateway\Response\PaymentDetailsHandler;

/**
 * Class PaymentDetailsHandlerTest
 *
 * Test for class \Magento\Eway\Gateway\Response\Direct\PaymentDetailsHandler
 */
class PaymentDetailsHandlerTest extends \PHPUnit\Framework\TestCase
{
    const TRANSACTION_TYPE = 'test-type';

    const TRANSACTION_ID = 'test-id';

    const RESPONSE_CODE = 'test-code';

    /**
     * @var PaymentDetailsHandler
     */
    private $paymentDetailsHandler;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->paymentDetailsHandler = new PaymentDetailsHandler();
    }

    /**
     * Run test for handle method
     *
     * @return void
     */
    public function testHandle()
    {
        $this->paymentDetailsHandler->handle($this->getHandlingSubjectMock(), $this->getResponseData());
    }

    /**
     * @return array
     */
    private function getHandlingSubjectMock()
    {
        /** @var PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject $paymentDOMock */
        $paymentDOMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();

        $paymentDOMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($this->getPaymentMock());

        return [
            'payment' =>  $paymentDOMock
        ];
    }

    /**
     * @return Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getPaymentMock()
    {
        $paymentMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->expects($this->once())
            ->method('setTransactionId')
            ->with(self::TRANSACTION_ID);
        $paymentMock->expects($this->once())
            ->method('setLastTransId')
            ->with(self::TRANSACTION_ID);
        $paymentMock->expects($this->once())
            ->method('setIsTransactionClosed')
            ->with(false);

        $paymentMock->expects($this->at(3))
            ->method('setAdditionalInformation')
            ->with('transaction_type', self::TRANSACTION_TYPE);

        $paymentMock->expects($this->at(4))
            ->method('setAdditionalInformation')
            ->with('transaction_id', self::TRANSACTION_ID);

        $paymentMock->expects($this->at(5))
            ->method('setAdditionalInformation')
            ->with('response_code', self::RESPONSE_CODE);

        return $paymentMock;
    }

    /**
     * @return array
     */
    private function getResponseData()
    {
        return [
            'TransactionType' => self::TRANSACTION_TYPE,
            'TransactionID' => self::TRANSACTION_ID,
            'ResponseCode' => self::RESPONSE_CODE,
        ];
    }
}
