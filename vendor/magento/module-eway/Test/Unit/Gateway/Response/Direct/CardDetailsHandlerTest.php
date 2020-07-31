<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Response\Direct;

use Magento\Sales\Model\Order\Payment;
use Magento\Eway\Model\Adminhtml\Source\Cctype;
use Magento\Eway\Gateway\Response\Direct\CardDetailsHandler;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

/**
 * Class CardDetailsHandlerTest
 *
 * Test for class \Magento\Eway\Gateway\Response\Direct\CardDetailsHandler
 */
class CardDetailsHandlerTest extends \PHPUnit\Framework\TestCase
{
    const CC_TYPE = 'test-type';

    const CC_TYPE_VALUE = 'test-type-value';

    const CARD_NUMBER = 'eeeee1234';

    const CARD_MONTH = '07';

    const CARD_YEAR = '21';

    /**
     * @var CardDetailsHandler
     */
    private $cardDetailsHandler;

    /**
     * Config
     *
     * @var Cctype|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sourceCCtypeMock;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->sourceCCtypeMock = $this->getMockBuilder(\Magento\Eway\Model\Adminhtml\Source\Cctype::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cardDetailsHandler = new CardDetailsHandler($this->sourceCCtypeMock);
    }

    /**
     * Run test for handle method
     *
     * @return void
     */
    public function testHandle()
    {
        $this->sourceCCtypeMock->expects($this->once())
            ->method('getCcTypes')
            ->willReturn($this->getCcTypesData());

        $this->cardDetailsHandler->handle($this->getHandlingSubjectMock(), $this->getResponseData());
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
            ->method('getAdditionalInformation')
            ->with('cc_type')
            ->willReturn(self::CC_TYPE);

        $paymentMock->expects($this->at(1))
            ->method('setAdditionalInformation')
            ->with('cc_type', self::CC_TYPE_VALUE);

        $paymentMock->expects($this->at(2))
            ->method('setAdditionalInformation')
            ->with('card_number', 'XXXX-' . substr(self::CARD_NUMBER, -4));

        $paymentMock->expects($this->at(3))
            ->method('setAdditionalInformation')
            ->with('card_expiry_date', self::CARD_MONTH . '/' . self::CARD_YEAR);

        return $paymentMock;
    }

    /**
     * @return array
     */
    private function getResponseData()
    {
        return [
            'Customer' => [
                'CardDetails' => [
                    'Number' => self::CARD_NUMBER,
                    'ExpiryMonth' => self::CARD_MONTH,
                    'ExpiryYear' => self::CARD_YEAR
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    private function getCcTypesData()
    {
        return [
            self::CC_TYPE => self::CC_TYPE_VALUE
        ];
    }
}
