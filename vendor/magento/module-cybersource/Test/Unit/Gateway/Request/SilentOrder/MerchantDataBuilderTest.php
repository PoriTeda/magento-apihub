<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Request\SilentOrder;

use Magento\Cybersource\Gateway\Request\SilentOrder\MerchantDataBuilder;

/**
 * Class MerchantDataBuilderTest
 *
 * Test for class Magento\Cybersource\Gateway\Request\SilentOrder\MerchantDataBuilder
 */
class MerchantDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const STORE_ID = 10;

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
     * Run test for build method
     *
     * @return void
     */
    public function testBuildSuccess()
    {
        $accessKeyValue = MerchantDataBuilder::ACCESS_KEY. '-value';
        $profileIdValue = MerchantDataBuilder::PROFILE_ID. '-value';

        $this->configMock->expects($this->at(0))
            ->method('getValue')
            ->with(MerchantDataBuilder::ACCESS_KEY, self::STORE_ID)
            ->willReturn($accessKeyValue);
        $this->configMock->expects($this->at(1))
            ->method('getValue')
            ->with(MerchantDataBuilder::PROFILE_ID, self::STORE_ID)
            ->willReturn($profileIdValue);

        $result = $this->merchantDataBuilder->build(['payment' => $this->getPaymentMock()]);

        $this->assertArrayHasKey(MerchantDataBuilder::ACCESS_KEY, $result);
        $this->assertArrayHasKey(MerchantDataBuilder::PROFILE_ID, $result);

        $this->assertEquals($result[MerchantDataBuilder::ACCESS_KEY], $accessKeyValue);
        $this->assertEquals($result[MerchantDataBuilder::PROFILE_ID], $profileIdValue);
    }

    /**
     * @return \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
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
     * @return \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getOrderMock()
    {
        $orderMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class)
            ->getMockForAbstractClass();

        $orderMock->expects($this->exactly(2))
            ->method('getStoreId')
            ->willReturn(self::STORE_ID);

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
        $this->merchantDataBuilder->build(['payment' => null]);
    }
}
