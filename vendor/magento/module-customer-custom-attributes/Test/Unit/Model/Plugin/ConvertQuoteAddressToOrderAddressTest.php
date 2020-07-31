<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Plugin;

use Magento\CustomerCustomAttributes\Model\Plugin\ConvertQuoteAddressToOrderAddress;

class ConvertQuoteAddressToOrderAddressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConvertQuoteAddressToOrderAddress
     */
    private $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $customerDataMock;

    protected function setUp()
    {
        $this->customerDataMock = $this->createMock(\Magento\CustomerCustomAttributes\Helper\Data::class);
        $this->model = new ConvertQuoteAddressToOrderAddress($this->customerDataMock);
    }

    public function testAfterConvert()
    {
        $attribute = 'attribute';
        $attributeValue = 'attributeValue';
        $quoteAddressMock = $this->createMock(\Magento\Quote\Model\Quote\Address::class);
        $orderAddressMock = $this->createMock(\Magento\Sales\Model\Order\Address::class);

        $this->customerDataMock->expects($this->once())
            ->method('getCustomerAddressUserDefinedAttributeCodes')
            ->willReturn([$attribute]);

        $quoteAddressMock->expects($this->once())->method('getData')->with($attribute)->willReturn($attributeValue);
        $orderAddressMock->expects($this->once())
            ->method('setData')
            ->with($attribute, $attributeValue)
            ->willReturnSelf();

        $result = $this->model->afterConvert(
            $this->createMock(\Magento\Quote\Model\Quote\Address\ToOrderAddress::class),
            $orderAddressMock,
            $quoteAddressMock
        );

        $this->assertEquals($orderAddressMock, $result);
    }
}
