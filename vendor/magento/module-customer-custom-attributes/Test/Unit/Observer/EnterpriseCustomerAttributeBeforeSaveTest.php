<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Test\Unit\Observer;

class EnterpriseCustomerAttributeBeforeSaveTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomerCustomAttributes\Observer\EnterpriseCustomerAttributeBeforeSave
     */
    protected $observer;

    protected function setUp()
    {
        $this->observer = new \Magento\CustomerCustomAttributes\Observer\EnterpriseCustomerAttributeBeforeSave;
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testEnterpriseCustomerAttributeBeforeSaveNegative()
    {
        $attributeData = str_pad('attribute_code_256_symbols_', 61, 'x', STR_PAD_RIGHT);

        $observer = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder(\Magento\Framework\Event::class)
            ->setMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel = $this->getMockBuilder(\Magento\Customer\Model\Attribute::class)
            ->setMethods(['__wakeup', 'isObjectNew', 'getAttributeCode'])
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel->expects($this->once())
            ->method('isObjectNew')
            ->will($this->returnValue(true));

        $dataModel->expects($this->once())
            ->method('getAttributeCode')
            ->will($this->returnValue($attributeData));

        $observer->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $event->expects($this->once())->method('getAttribute')->will($this->returnValue($dataModel));
        /** @var \Magento\Framework\Event\Observer $observer */

        $this->observer->execute($observer);
    }

    public function testEnterpriseCustomerAttributeBeforeSavePositive()
    {
        $attributeData = 'normal_attribute_code';
        $observer = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder(\Magento\Framework\Event::class)
            ->setMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel = $this->getMockBuilder(\Magento\Customer\Model\Attribute::class)
            ->setMethods(['__wakeup', 'isObjectNew', 'getAttributeCode'])
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel->expects($this->once())
            ->method('isObjectNew')
            ->will($this->returnValue(true));

        $dataModel->expects($this->once())
            ->method('getAttributeCode')
            ->will($this->returnValue($attributeData));

        $observer->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $event->expects($this->once())->method('getAttribute')->will($this->returnValue($dataModel));
        /** @var \Magento\Framework\Event\Observer $observer */

        $this->assertInstanceOf(
            \Magento\CustomerCustomAttributes\Observer\EnterpriseCustomerAttributeBeforeSave::class,
            $this->observer->execute($observer)
        );
    }
}
