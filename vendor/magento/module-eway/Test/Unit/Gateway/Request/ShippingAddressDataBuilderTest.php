<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Request;

use Magento\Eway\Gateway\Request\ShippingAddressDataBuilder;

class ShippingAddressDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ShippingAddressDataBuilder
     */
    private $builder;

    protected function setUp()
    {
        $this->builder = new ShippingAddressDataBuilder();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Payment data object should be provided
     */
    public function testBuildReadPaymentException()
    {
        $buildSubject = [
            'payment' => null,
        ];

        $this->builder->build($buildSubject);
    }

    public function testBuildNoShippingAddress()
    {
        $paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $order = $this->getMockBuilder(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class)
            ->getMockForAbstractClass();

        $paymentDO->expects($this->once())
            ->method('getOrder')
            ->willReturn($order);
        $order->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn(null);

        $buildSubject = [
            'payment' => $paymentDO,
        ];

        $this->assertEquals([], $this->builder->build($buildSubject));
    }

    /**
     * @param array $shippingAddressData
     * @param array $expectedResult
     *
     * @dataProvider dataProviderBuild
     */
    public function testBuild($shippingAddressData, $expectedResult)
    {
        $paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $order = $this->getMockBuilder(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class)
            ->getMockForAbstractClass();
        $shippingAddress = $this->getMockBuilder(\Magento\Payment\Gateway\Data\AddressAdapterInterface::class)
            ->getMockForAbstractClass();

        $paymentDO->expects($this->once())
            ->method('getOrder')
            ->willReturn($order);
        $order->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($shippingAddress);

        $shippingAddress->expects($this->once())
            ->method('getFirstname')
            ->willReturn($shippingAddressData['first_name']);
        $shippingAddress->expects($this->once())
            ->method('getLastname')
            ->willReturn($shippingAddressData['last_name']);
        $shippingAddress->expects($this->once())
            ->method('getStreetLine1')
            ->willReturn($shippingAddressData['street_1']);
        $shippingAddress->expects($this->once())
            ->method('getStreetLine2')
            ->willReturn($shippingAddressData['street_2']);
        $shippingAddress->expects($this->once())
            ->method('getCity')
            ->willReturn($shippingAddressData['city']);
        $shippingAddress->expects($this->once())
            ->method('getRegionCode')
            ->willReturn($shippingAddressData['region_code']);
        $shippingAddress->expects($this->once())
            ->method('getCountryId')
            ->willReturn($shippingAddressData['country_id']);
        $shippingAddress->expects($this->once())
            ->method('getPostcode')
            ->willReturn($shippingAddressData['post_code']);
        $shippingAddress->expects($this->once())
            ->method('getTelephone')
            ->willReturn($shippingAddressData['telephone']);

        $buildSubject = [
            'payment' => $paymentDO,
        ];

        $this->assertEquals($expectedResult, $this->builder->build($buildSubject));
    }

    /**
     * @return array
     */
    public function dataProviderBuild()
    {
        return [
            [
                [
                    'first_name' => 'John',
                    'last_name' => 'Smith',
                    'street_1' => 'street1',
                    'street_2' => 'street2',
                    'city' => 'Chicago',
                    'region_code' => 'IL',
                    'country_id' => 'US',
                    'post_code' => '00000',
                    'telephone' => '123123'
                ],
                [
                    'ShippingAddress' => [
                        'FirstName' => 'John',
                        'LastName' => 'Smith',
                        'Street1' => 'street1',
                        'Street2' => 'street2',
                        'City' => 'Chicago',
                        'State' => 'IL',
                        'Country' => 'us',
                        'PostalCode' => '00000',
                        'Phone' => '123123'
                    ]
                ]
            ]
        ];
    }
}
