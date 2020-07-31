<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Response;

use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Eway\Gateway\Response\ResponseMessagesHandler;

/**
 * Class ResponseMessagesHandlerTest
 *
 * Test for class \Magento\Eway\Gateway\Response\Direct\ResponseMessagesHandler
 */
class ResponseMessagesHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ResponseMessagesHandler
     */
    private $responseMessagesHandler;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->responseMessagesHandler = new ResponseMessagesHandler();
    }

    /**
     * Run test for handle method (fraud)
     *
     * @param array $responseData
     * @param array $fraudMessages
     * @param array $approveMessages
     *
     * @dataProvider dataProviderTestHandleFraud
     */
    public function testHandleFraud(array $responseData, $fraudMessages, $approveMessages)
    {
        $this->responseMessagesHandler->handle(
            $this->getHandlingSubjectMock(
                $this->getPaymentExpectFraudMock($fraudMessages, $approveMessages)
            ),
            $responseData
        );
    }

    /**
     * @return array
     */
    public function dataProviderTestHandleFraud()
    {
        return [
            [
                'responseData' => [
                    'ResponseMessage' => 'A0000,F7000,F9021'
                ],
                'fraud_messages' => [
                    0 => 'F7000',
                    21 => 'F9021'
                ],
                'approve_messages' => [
                    37 => 'A0000'
                ]
            ],
            [
                'responseData' => [
                    'ResponseMessage' => 'F7000,F9021,A0000'
                ],
                'fraud_messages' => [
                    0 => 'F7000',
                    21 => 'F9021'
                ],
                'approve_messages' => [
                    37 => 'A0000'
                ]
            ],
            [
                'responseData' => [
                    'ResponseMessage' => 'F7000, A0000,F9021 '
                ],
                'fraud_messages' => [
                    0 => 'F7000',
                    21 => 'F9021'
                ],
                'approve_messages' => [
                    37 => 'A0000'
                ]
            ]
        ];
    }

    /**
     * Run test for handle method
     *
     * @param array $responseData
     * @param array $approveMessages
     *
     * @dataProvider dataProviderTestHandle
     */
    public function testHandle(array $responseData, $approveMessages)
    {
        $this->responseMessagesHandler->handle(
            $this->getHandlingSubjectMock(
                $this->getPaymentExpectMock($approveMessages)
            ),
            $responseData
        );
    }

    /**
     * @return array
     */
    public function dataProviderTestHandle()
    {
        return [
            [
                'responseData' => [
                    'ResponseMessage' => 'A0000,A2011'
                ],
                'approve_messages' => [
                    37 => 'A0000',
                    41 => 'A2011'
                ]
            ],
        ];
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $paymentMock
     * @return array
     */
    private function getHandlingSubjectMock(\PHPUnit_Framework_MockObject_MockObject $paymentMock)
    {
        /** @var PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject $paymentDOMock */
        $paymentDOMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();

        $paymentDOMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        return [
            'payment' =>  $paymentDOMock
        ];
    }

    /**
     * @param array $fraudMessages
     * @param array $approveMessages
     * @return Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getPaymentExpectFraudMock($fraudMessages, $approveMessages)
    {
        $paymentMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->expects($this->once())
            ->method('setIsTransactionPending')
            ->with(false);
        $paymentMock->expects($this->once())
            ->method('setIsFraudDetected')
            ->with(true);
        $paymentMock->expects($this->exactly(2))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                ['fraud_messages', $fraudMessages],
                ['approve_messages', $approveMessages]
            );

        return $paymentMock;
    }

    /**
     * @param array $approveMessages
     * @return Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getPaymentExpectMock($approveMessages)
    {
        $paymentMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->expects($this->once())
            ->method('setAdditionalInformation')
            ->with('approve_messages', $approveMessages);

        return $paymentMock;
    }
}
