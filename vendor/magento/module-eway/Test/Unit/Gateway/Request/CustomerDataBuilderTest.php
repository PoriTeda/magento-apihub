<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Request;

use Magento\Eway\Gateway\Request\CustomerDataBuilder;

class CustomerDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CustomerDataBuilder
     */
    private $builder;

    protected function setUp()
    {
        $this->builder = new CustomerDataBuilder();
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

    /**
     * @param array $orderData
     * @param array $billingAddressData
     * @param array $expectedResult
     *
     * @dataProvider dataProviderBuild
     */
    public function testBuild($orderData, $billingAddressData, $expectedResult)
    {
        $paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $order = $this->getMockBuilder(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class)
            ->getMockForAbstractClass();
        $billingAddress = $this->getMockBuilder(\Magento\Payment\Gateway\Data\AddressAdapterInterface::class)
            ->getMockForAbstractClass();

        $paymentDO->expects($this->once())
            ->method('getOrder')
            ->willReturn($order);
        $order->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($billingAddress);

        $order->expects($this->once())
            ->method('getOrderIncrementId')
            ->willReturn($orderData['order_increment_id']);
        $billingAddress->expects($this->once())
            ->method('getPrefix')
            ->willReturn($billingAddressData['prefix']);
        $billingAddress->expects($this->once())
            ->method('getFirstname')
            ->willReturn($billingAddressData['first_name']);
        $billingAddress->expects($this->once())
            ->method('getLastname')
            ->willReturn($billingAddressData['last_name']);
        $billingAddress->expects($this->once())
            ->method('getCompany')
            ->willReturn($billingAddressData['company']);
        $billingAddress->expects($this->once())
            ->method('getStreetLine1')
            ->willReturn($billingAddressData['street_1']);
        $billingAddress->expects($this->once())
            ->method('getStreetLine2')
            ->willReturn($billingAddressData['street_2']);
        $billingAddress->expects($this->once())
            ->method('getCity')
            ->willReturn($billingAddressData['city']);
        $billingAddress->expects($this->once())
            ->method('getRegionCode')
            ->willReturn($billingAddressData['region_code']);
        $billingAddress->expects($this->once())
            ->method('getPostcode')
            ->willReturn($billingAddressData['post_code']);
        $billingAddress->expects($this->once())
            ->method('getCountryId')
            ->willReturn($billingAddressData['country_id']);
        $billingAddress->expects($this->once())
            ->method('getTelephone')
            ->willReturn($billingAddressData['telephone']);
        $billingAddress->expects($this->once())
            ->method('getEmail')
            ->willReturn($billingAddressData['email']);

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
                    'order_increment_id' => '1'
                ],
                [
                    'prefix' => 'Mr.',
                    'first_name' => 'John',
                    'last_name' => 'Smith',
                    'company' => 'Magento',
                    'street_1' => 'Street1',
                    'street_2' => 'Street2',
                    'city' => 'Chicago',
                    'region_code' => 'Illinois',
                    'post_code' => '00000',
                    'country_id' => 'US',
                    'telephone' => '123123',
                    'email' => 'user@example.com'
                ],
                [
                    'Customer' => [
                        'Reference' => '1',
                        'Title' => 'Mr.',
                        'FirstName' => 'John',
                        'LastName' => 'Smith',
                        'CompanyName' => 'Magento',
                        'Street1' => 'Street1',
                        'Street2' => 'Street2',
                        'City' => 'Chicago',
                        'State' => 'Illinois',
                        'PostalCode' => '00000',
                        'Country' => 'us',
                        'Phone' => '123123',
                        'Email' => 'user@example.com'
                    ]
                ]
            ]
        ];
    }
}
