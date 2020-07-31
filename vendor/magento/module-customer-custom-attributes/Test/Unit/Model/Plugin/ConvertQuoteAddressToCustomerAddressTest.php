<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Plugin;

use Magento\CustomerCustomAttributes\Model\Plugin\ConvertQuoteAddressToCustomerAddress;

class ConvertQuoteAddressToCustomerAddressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConvertQuoteAddressToCustomerAddress
     */
    private $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $customerDataMock;

    protected function setUp()
    {
        $this->customerDataMock = $this->createMock(\Magento\CustomerCustomAttributes\Helper\Data::class);
        $this->model = new ConvertQuoteAddressToCustomerAddress($this->customerDataMock);
    }

    public function testAfterExportCustomerAddress()
    {
        $attribute = 'attribute';
        $attributeValue = 'attributeValue';
        $quoteAddressMock = $this->createMock(\Magento\Quote\Model\Quote\Address::class);
        $customerAddressMock = $this->createMock(\Magento\Customer\Api\Data\AddressInterface::class);

        $this->customerDataMock->expects($this->once())
            ->method('getCustomerAddressUserDefinedAttributeCodes')
            ->willReturn([$attribute]);

        $quoteAddressMock->expects($this->once())->method('getData')->with($attribute)->willReturn($attributeValue);
        $customerAddressMock->expects($this->once())
            ->method('setCustomAttribute')
            ->with($attribute, $attributeValue)
            ->willReturnSelf();

        $this->assertEquals(
            $customerAddressMock,
            $this->model->afterExportCustomerAddress($quoteAddressMock, $customerAddressMock)
        );
    }
}
