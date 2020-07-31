<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Request\SilentOrder;

use Magento\Cybersource\Gateway\Request\SilentOrder\ShippingAddressDataBuilder;

/**
 * Class ShippingAddressDataBuilderTest
 *
 * Test for class \Magento\Cybersource\Gateway\Request\SilentOrder\ShippingAddressDataBuilder
 */
class ShippingAddressDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ShippingAddressDataBuilder
     */
    protected $shippingAddressDataBuilder;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->shippingAddressDataBuilder = new ShippingAddressDataBuilder();
    }

    /**
     * Run test for build method
     *
     * @return void
     */
    public function testBuildSuccess()
    {
        $buildSubject = [
            'payment' => $this->getPaymentMock(),
        ];

        $result = $this->shippingAddressDataBuilder->build($buildSubject);

        $this->assertNotEmpty($result);

        foreach ($result as $key => $value) {
            $this->assertTrue(strpos($key, ShippingAddressDataBuilder::FIELD_SUFFIX) === 0);
            $this->assertContains($value, $this->getAddressData());
        }
    }

    public function testBuildSuccessNoBillingAddress()
    {
        $paymentMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $orderMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class)
            ->getMockForAbstractClass();

        $paymentMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);
        $orderMock->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn(null);

        $buildSubject = [
            'payment' => $paymentMock,
        ];

        $this->assertEquals([], $this->shippingAddressDataBuilder->build($buildSubject));
    }

    /**
     * Run test for build method (throw Exception)
     *
     * @expectedException \InvalidArgumentException
     */
    public function testBuildException()
    {
        $buildSubject = [
            'payment' => null,
        ];

        $this->shippingAddressDataBuilder->build($buildSubject);
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
            ->method('getShippingAddress')
            ->willReturn($this->getAddressMock());

        return $orderMock;
    }

    /**
     * @return \Magento\Payment\Gateway\Data\AddressAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAddressMock()
    {
        $addressMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\AddressAdapterInterface::class)
            ->getMockForAbstractClass();

        foreach ($this->getAddressData() as $method => $value) {
            $addressMock->expects($this->once())
                ->method($method)
                ->willReturn($value);
        }

        return $addressMock;
    }

    /**
     * @return array
     */
    protected function getAddressData()
    {
        return [
            'getCity' => 'getCity value',
            'getCountryId' => 'getCountryId value',
            'getStreetLine1' => 'getStreetLine1 value',
            'getPostcode' => 'getPostcode value',
            'getRegionCode' => 'getRegionCode value',
            'getEmail' => 'getEmail value',
            'getTelephone' => 'getTelephone value',
            'getFirstname' => 'getFirstname value',
            'getLastname' => 'getLastname value',
        ];
    }
}
