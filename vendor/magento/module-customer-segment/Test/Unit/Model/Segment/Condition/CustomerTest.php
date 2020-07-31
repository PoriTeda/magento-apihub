<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition;

class CustomerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Customer
     */
    protected $model;

    /**
     * @var \Magento\Rule\Model\Condition\Context
     */
    protected $context;

    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Segment
     */
    protected $resourceSegment;

    /**
     * @var \Magento\CustomerSegment\Model\ConditionFactory
     */
    protected $conditionFactory;

    /**
     * @var \Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes
     */
    protected $customerAttributes;

    /**
     * @var \Magento\CustomerSegment\Model\Segment\Condition\Customer\Newsletter
     */
    protected $customerNewsletter;

    /**
     * @var \Magento\CustomerSegment\Model\Segment\Condition\Customer\Storecredit
     */
    protected $customerStorecredit;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder(\Magento\Rule\Model\Condition\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceSegment = $this->createMock(\Magento\CustomerSegment\Model\ResourceModel\Segment::class);
        $this->conditionFactory = $this->createMock(\Magento\CustomerSegment\Model\ConditionFactory::class);

        $this->customerAttributes = $this->createMock(
            \Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes::class
        );
        $this->customerNewsletter = $this->createMock(
            \Magento\CustomerSegment\Model\Segment\Condition\Customer\Newsletter::class
        );
        $this->customerStorecredit = $this->createMock(
            \Magento\CustomerSegment\Model\Segment\Condition\Customer\Storecredit::class
        );

        $this->model = new \Magento\CustomerSegment\Model\Segment\Condition\Customer(
            $this->context,
            $this->resourceSegment,
            $this->conditionFactory
        );
    }

    protected function tearDown()
    {
        unset(
            $this->model,
            $this->context,
            $this->resourceSegment,
            $this->conditionFactory,
            $this->customerAttributes,
            $this->customerNewsletter,
            $this->customerStorecredit
        );
    }

    public function testGetNewChildSelectOptions()
    {
        $attributesOptions = ['test_attributes_options'];
        $newsletterOptions = ['test_newsletter_options'];
        $storecreditOptions = ['test_storecredit_options'];

        $this->customerAttributes
            ->expects($this->once())
            ->method('getNewChildSelectOptions')
            ->will($this->returnValue($attributesOptions));

        $this->customerNewsletter
            ->expects($this->once())
            ->method('getNewChildSelectOptions')
            ->will($this->returnValue($newsletterOptions));

        $this->customerStorecredit
            ->expects($this->once())
            ->method('getNewChildSelectOptions')
            ->will($this->returnValue($storecreditOptions));

        $this->conditionFactory
            ->expects($this->any())
            ->method('create')
            ->will($this->returnValueMap([
                ['Customer\Attributes', [], $this->customerAttributes],
                ['Customer\Newsletter', [], $this->customerNewsletter],
                ['Customer\Storecredit', [], $this->customerStorecredit],
            ]));

        $result = $this->model->getNewChildSelectOptions();

        $this->assertTrue(is_array($result));
        $this->assertEquals(
            [
                'value' => array_merge($attributesOptions, $newsletterOptions, $storecreditOptions),
                'label' => __('Customer'),
            ],
            $result
        );
    }
}
