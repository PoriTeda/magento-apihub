<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Request\SilentOrder;

use Magento\Framework\Math\Random;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Cybersource\Gateway\Request\SilentOrder\TransactionDataBuilder;

/**
 * Class TransactionDataBuilderTest
 *
 * Test for class \Magento\Cybersource\Gateway\Request\SilentOrder\TransactionDataBuilder
 */
class TransactionDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const TRANSACTION_TYPE = 'test-transaction';
    const AMOUNT = 2.1111;
    const CURRENCY = '$';
    const REFERENCE_NUMBER_RESULT = 123456789;

    /**
     * @var TransactionDataBuilder
     */
    protected $transactionDataBuilder;

    /**
     * @var Random|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $randomMock;

    /**
     * @var ResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeResolverMock;

    /**
     * @var InfoInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentInstanceMock;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->randomMock = $this->getMockBuilder(\Magento\Framework\Math\Random::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeResolverMock = $this->getMockBuilder(\Magento\Framework\Locale\ResolverInterface::class)
            ->getMockForAbstractClass();
        $this->paymentInstanceMock = $this->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)
            ->setMethods(['getAdditionalInformation'])
            ->getMockForAbstractClass();

        $this->transactionDataBuilder = new TransactionDataBuilder(
            $this->randomMock,
            $this->localeResolverMock,
            self::TRANSACTION_TYPE
        );
    }

    /**
     * Run test build method
     *
     * @param string $referenceNumber
     * @return void
     *
     * @dataProvider buildSuccessDataProvider
     */
    public function testBuildSuccess($referenceNumber)
    {
        $this->randomMock->expects($this->at(0))
            ->method('getRandomString')
            ->with(TransactionDataBuilder::RANDOM_LENGTH, Random::CHARS_DIGITS)
            ->willReturn(self::REFERENCE_NUMBER_RESULT);

        $this->localeResolverMock->expects($this->once())
            ->method('getLocale')
            ->willReturn('us_US');

        $this->{$referenceNumber}();

        $result = $this->transactionDataBuilder->build(
            [
                'payment' => $this->getPaymentMock(),
                'amount' => self::AMOUNT
            ]
        );

        $this->assertArrayHasKey(TransactionDataBuilder::REFERENCE_NUMBER, $result);
        $this->assertArrayHasKey(TransactionDataBuilder::TRANSACTION_TYPE, $result);
        $this->assertArrayHasKey(TransactionDataBuilder::AMOUNT, $result);
        $this->assertArrayHasKey(TransactionDataBuilder::CURRENCY, $result);
        $this->assertArrayHasKey(TransactionDataBuilder::LOCALE, $result);

        $this->assertEquals(self::REFERENCE_NUMBER_RESULT, $result[TransactionDataBuilder::REFERENCE_NUMBER]);
        $this->assertEquals(self::TRANSACTION_TYPE, $result[TransactionDataBuilder::TRANSACTION_TYPE]);
        $this->assertEquals(sprintf('%.2F', self::AMOUNT), $result[TransactionDataBuilder::AMOUNT]);
        $this->assertEquals(self::CURRENCY, $result[TransactionDataBuilder::CURRENCY]);
        $this->assertEquals('us', $result[TransactionDataBuilder::LOCALE]);
    }

    /**
     * @return \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPaymentMock()
    {
        $paymentMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();

        $paymentMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->getOrderMock());
        $paymentMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($this->paymentInstanceMock);

        return $paymentMock;
    }

    /**
     * @return \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getOrderMock()
    {
        $orderMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class)
            ->getMockForAbstractClass();

        $orderMock->expects($this->once())
            ->method('getCurrencyCode')
            ->willReturn(self::CURRENCY);

        return $orderMock;
    }

    /**
     * @return void
     */
    protected function additionalInformation()
    {

        $this->paymentInstanceMock->expects($this->exactly(2))
            ->method('getAdditionalInformation')
            ->with(TransactionDataBuilder::REFERENCE_NUMBER)
            ->willReturn(self::REFERENCE_NUMBER_RESULT);
    }

    /**
     * @return void
     */
    protected function randomString()
    {
        $this->paymentInstanceMock->expects($this->once())
            ->method('getAdditionalInformation')
            ->with(TransactionDataBuilder::REFERENCE_NUMBER)
            ->willReturn(null);

        $this->randomMock->expects($this->at(1))
            ->method('getRandomString')
            ->with(TransactionDataBuilder::RANDOM_LENGTH, Random::CHARS_DIGITS)
            ->willReturn(self::REFERENCE_NUMBER_RESULT);
    }

    /**
     * @return array
     */
    public function buildSuccessDataProvider()
    {
        return [
            [
                'referenceNumber' => 'additionalInformation'
            ],
            [
                'referenceNumber' => 'randomString'
            ]
        ];
    }

    /**
     * Run test build method (Exception)
     *
     * @param array $buildSubject
     * @return void
     *
     * @expectedException \InvalidArgumentException
     *
     * @dataProvider buildExceptionDataProvider
     */
    public function testBuildException(array $buildSubject)
    {
        $this->transactionDataBuilder->build($buildSubject);
    }

    /**
     * @return array
     */
    public function buildExceptionDataProvider()
    {
        return [
            [
                'buildSubject' => []
            ],
            [
                'buildSubject' => [
                    'payment' => $this->getMockBuilder(
                        \Magento\Payment\Model\InfoInterface::class
                    )->getMockForAbstractClass()
                ]
            ],
            [
                'buildSubject' => ['payment' => 'test', 'amount' => self::AMOUNT]
            ]
        ];
    }
}
