<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Request\SilentOrder;

use Magento\Cybersource\Gateway\Request\SilentOrder\MerchantSecureDataBuilder;
use Magento\Framework\Config\ScopeInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

/**
 * Class MerchantSecureDataBuilderTest
 *
 * Test for class \Magento\Cybersource\Gateway\Request\SilentOrder\MerchantSecureDataBuilder
 */
class MerchantSecureDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const STORE_ID = 10;
    const ORDER_ID = '1000001';
    const AREA = 'adminhtml';

    /**
     * @var MerchantSecureDataBuilder
     */
    protected $merchantSecureDataBuilder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | ScopeInterface
     */
    protected $scopeConfig;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->scopeConfig = $this->getMockBuilder(\Magento\Framework\Config\ScopeInterface::class)
            ->getMockForAbstractClass();
        $this->merchantSecureDataBuilder = new MerchantSecureDataBuilder($this->scopeConfig);
    }

    /**
     * Run test for build method
     *
     * @return void
     */
    public function testBuildSuccess()
    {
        $this->scopeConfig->expects(static::once())
            ->method('getCurrentScope')
            ->willReturn(self::AREA);

        $result = $this->merchantSecureDataBuilder->build(['payment' => $this->getPaymentMock()]);

        $this->assertArrayHasKey(MerchantSecureDataBuilder::MERCHANT_SECURE_DATA1, $result);
        $this->assertArrayHasKey(MerchantSecureDataBuilder::MERCHANT_SECURE_DATA2, $result);
        $this->assertArrayHasKey(MerchantSecureDataBuilder::MERCHANT_SECURE_DATA3, $result);

        $this->assertEquals(self::ORDER_ID, $result[MerchantSecureDataBuilder::MERCHANT_SECURE_DATA1]);
        $this->assertEquals(self::STORE_ID, $result[MerchantSecureDataBuilder::MERCHANT_SECURE_DATA2]);
        $this->assertEquals(self::AREA, $result[MerchantSecureDataBuilder::MERCHANT_SECURE_DATA3]);
    }

    /**
     * @return PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPaymentMock()
    {
        $paymentMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();

        $paymentMock->expects($this->exactly(2))
            ->method('getOrder')
            ->willReturn($this->getOrderMock());

        return $paymentMock;
    }

    /**
     * @return OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getOrderMock()
    {
        $orderMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class)
            ->getMockForAbstractClass();

        $orderMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn(self::STORE_ID);
        $orderMock->expects($this->once())
            ->method('getId')
            ->willReturn(self::ORDER_ID);

        return $orderMock;
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
        $this->merchantSecureDataBuilder->build(['payment' => null]);
    }
}
