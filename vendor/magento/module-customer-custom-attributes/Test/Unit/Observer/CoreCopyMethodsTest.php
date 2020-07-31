<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Test\Unit\Observer;

class CoreCopyMethodsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomerCustomAttributes\Observer\AbstractObserver
     */
    protected $observer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteAddressFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $helper;

    protected function setUp()
    {
        $this->helper = $this->getMockBuilder(\Magento\CustomerCustomAttributes\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function coreCopyMethodsDataProvider()
    {
        return [
            'CoreCopyFieldsetSalesConvertQuoteToOrder' => [
                'CoreCopyFieldsetSalesConvertQuoteToOrder',
                'getCustomerUserDefinedAttributeCodes',
                'customer_',
                'customer_',
            ],
            'CoreCopyFieldsetSalesCopyOrderToEdit' => [
                'CoreCopyFieldsetSalesCopyOrderToEdit',
                'getCustomerUserDefinedAttributeCodes',
                'customer_',
                'customer_',
            ],
            'CoreCopyFieldsetCustomerAccountToQuote' => [
                'CoreCopyFieldsetCustomerAccountToQuote',
                'getCustomerUserDefinedAttributeCodes',
                '',
                'customer_',
            ],
            'CoreCopyFieldsetCheckoutOnepageQuoteToCustomer' => [
                'CoreCopyFieldsetCheckoutOnepageQuoteToCustomer',
                'getCustomerUserDefinedAttributeCodes',
                'customer_',
                '',
            ],
            'CoreCopyFieldsetSalesConvertQuoteAddressToOrderAddress' => [
                'CoreCopyFieldsetSalesConvertQuoteAddressToOrderAddress',
                'getCustomerAddressUserDefinedAttributeCodes',
                '',
                '',
            ],
            'CoreCopyFieldsetSalesCopyOrderBillingAddressToOrder' => [
                'CoreCopyFieldsetSalesCopyOrderBillingAddressToOrder',
                'getCustomerAddressUserDefinedAttributeCodes',
                '',
                '',
            ],
            'CoreCopyFieldsetSalesCopyOrderShippingAddressToOrder' => [
                'CoreCopyFieldsetSalesCopyOrderShippingAddressToOrder',
                'getCustomerAddressUserDefinedAttributeCodes',
                '',
                '',
            ],
            'CoreCopyFieldsetCustomerAddressToQuoteAddress' => [
                'CoreCopyFieldsetCustomerAddressToQuoteAddress',
                'getCustomerAddressUserDefinedAttributeCodes',
                '',
                '',
            ],
            'CoreCopyFieldsetQuoteAddressToCustomerAddress' => [
                'CoreCopyFieldsetQuoteAddressToCustomerAddress',
                'getCustomerAddressUserDefinedAttributeCodes',
                '',
                '',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider coreCopyMethodsDataProvider
     *
     * @param string $testableObserverClass
     * @param string $helperMethod
     * @param string $sourcePrefix
     * @param string $targetPrefix
     */
    public function testCoreCopyMethods($testableObserverClass, $helperMethod, $sourcePrefix, $targetPrefix)
    {
        $className = '\Magento\CustomerCustomAttributes\Observer\\' . $testableObserverClass;
        $this->observer = new $className(
            $this->helper
        );

        $attribute = 'testAttribute';
        $attributeData = 'data';
        $attributes = [$attribute];
        $sourceAttributeWithPrefix = $sourcePrefix . $attribute;
        $targetAttributeWithPrefix = $targetPrefix . $attribute;

        $observer = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder(\Magento\Framework\Event::class)
            ->setMethods(['getSource', 'getTarget'])
            ->disableOriginalConstructor()
            ->getMock();

        $sourceModel = $this->getMockBuilder(\Magento\Framework\Model\AbstractModel::class)
            ->setMethods(['__wakeup', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $targetModel = $this->getMockBuilder(\Magento\Framework\Model\AbstractModel::class)
            ->setMethods(['__wakeup', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper->expects($this->once())
            ->method($helperMethod)
            ->will($this->returnValue($attributes));
        $sourceModel->expects($this->once())
            ->method('getData')
            ->with($sourceAttributeWithPrefix)
            ->will($this->returnValue($attributeData));
        $targetModel->expects($this->once())
            ->method('setData')
            ->with($this->logicalOr($targetAttributeWithPrefix, $attributeData))
            ->will($this->returnSelf());
        $observer->expects($this->exactly(2))->method('getEvent')->will($this->returnValue($event));
        $event->expects($this->once())->method('getSource')->will($this->returnValue($sourceModel));
        $event->expects($this->once())->method('getTarget')->will($this->returnValue($targetModel));
        /** @var \Magento\Framework\Event\Observer $observer */

        $this->assertInstanceOf(
            $className,
            $this->observer->execute($observer)
        );
    }
}
