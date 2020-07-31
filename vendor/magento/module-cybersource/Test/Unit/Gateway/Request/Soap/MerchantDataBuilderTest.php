<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Request\Soap;

use Magento\Cybersource\Gateway\Request\Soap\MerchantDataBuilder;
use Magento\Cybersource\Gateway\Request\SilentOrder\TransactionDataBuilder;

/**
 * Class MerchantDataBuilderTest
 */
class MerchantDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const MERCHANT_ID = 'MERCHANT_ID';
    const REFERENCE_NUMBER = 'REFERENCE_NUMBER';

    /**
     * @var MerchantDataBuilder
     */
    protected $merchantDataBuilder;

    /**
     * @var \Magento\Payment\Gateway\ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->configMock = $this->getMockBuilder(\Magento\Payment\Gateway\ConfigInterface::class)
            ->getMockForAbstractClass();

        $this->merchantDataBuilder = new MerchantDataBuilder($this->configMock);
    }

    /**
     * Run test build method
     *
     * @return void
     */
    public function testBuildSuccess()
    {
        $expected = [
            'merchantID' => self::MERCHANT_ID,
            'merchantReferenceCode' => self::REFERENCE_NUMBER
        ];

        $this->configMock->expects(static::once())
            ->method('getValue')
            ->with(MerchantDataBuilder::MERCHANT_ID)
            ->willReturn(self::MERCHANT_ID);

        $result = $this->merchantDataBuilder->build(['payment' => $this->getPaymentMock()]);
        static::assertEquals($expected, $result);
    }

    /**
     * @return \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPaymentMock()
    {
        $paymentMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $paymentInstanceMock = $this->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)
            ->getMockForAbstractClass();

        $paymentMock->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentInstanceMock);

        $paymentInstanceMock->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(TransactionDataBuilder::REFERENCE_NUMBER)
            ->willReturn(self::REFERENCE_NUMBER);

        return $paymentMock;
    }

    /**
     * Run test build method (Exception)
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     */
    public function testBuildException()
    {
        $this->merchantDataBuilder->build(['payment' => null]);
    }
}
