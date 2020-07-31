<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Test\Unit\Helper;

use Magento\CustomerCustomAttributes\Helper\Data;

class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\App\Helper\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    private $context;

    /**
     * @var \Magento\Eav\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eavConfig;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $localeDate;

    /**
     * @var \Magento\Framework\Filter\FilterManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filterManager;

    /**
     * @var \Magento\CustomerCustomAttributes\Helper\Address|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerAddress;

    /**
     * @var \Magento\CustomerCustomAttributes\Helper\Customer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customer;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder(\Magento\Framework\App\Helper\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eavConfig = $this->getMockBuilder(\Magento\Eav\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeDate = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class)
            ->getMockForAbstractClass();

        $this->filterManager = $this->getMockBuilder(\Magento\Framework\Filter\FilterManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerAddress = $this->getMockBuilder(\Magento\CustomerCustomAttributes\Helper\Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customer = $this->getMockBuilder(\Magento\CustomerCustomAttributes\Helper\Customer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new Data(
            $this->context,
            $this->eavConfig,
            $this->localeDate,
            $this->filterManager,
            $this->customerAddress,
            $this->customer
        );
    }

    public function testGetAttributeValidateFilters()
    {
        $result = $this->helper->getAttributeValidateFilters();

        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('length', $result);
        $this->assertEquals(__('Length Only'), $result['length']);
    }

    public function testGetAttributeInputTypesWithInputTypeText()
    {
        $result = $this->helper->getAttributeInputTypes('text');

        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('validate_filters', $result);
        $this->assertTrue(is_array($result['validate_filters']));
        $this->assertContains('length', $result['validate_filters']);
    }

    public function testGetAttributeInputTypesWithInputTypeNull()
    {
        $result = $this->helper->getAttributeInputTypes();

        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('text', $result);
        $this->assertTrue(is_array($result['text']));
        $this->assertArrayHasKey('validate_filters', $result['text']);
        $this->assertTrue(is_array($result['text']['validate_filters']));
        $this->assertContains('length', $result['text']['validate_filters']);
    }
}
