<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition\Combine;

use Magento\CustomerSegment\Model\Segment\Condition\Combine\Root;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rule\Model\Condition\Context;
use Magento\CustomerSegment\Model\ConditionFactory;
use Magento\CustomerSegment\Model\ResourceModel\Segment;
use Magento\Customer\Model\Config\Share;
use Magento\CustomerSegment\Model\Condition\Combine\AbstractCombine;

/**
 * Test for Magento\CustomerSegment\Model\Segment\Condition\Combine\Root
 */
class RootTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    private $context;

    /**
     * @var ConditionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $conditionFactory;

    /**
     * @var Segment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $segment;

    /**
     * @var Share|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerConfig;

    /**
     * @var Root
     */
    private $root;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        $objectManager = new ObjectManager($this);
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->conditionFactory = $this->getMockBuilder(ConditionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->segment = $this->getMockBuilder(Segment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerConfig = $this->getMockBuilder(Share::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->root = $objectManager->getObject(
            Root::class,
            [
                'context'  => $this->context,
                'conditionFactory' => $this->conditionFactory,
                'resourceSegment' => $this->segment,
                'configShare' => $this->customerConfig,
                'data' => ['aggregator' => 'all'],
            ]
        );
    }

    /**
     * @param int $value
     * @param bool $result
     * @dataProvider testDataProvider
     *
     * @return void
     */
    public function testIsSatisfiedBy(int $value, bool $result): void
    {
        $customer = 1;
        $websiteId = 1;
        $params = ['some' => 'params'];
        /** @var \PHPUnit_Framework_MockObject_MockObject $condition1Mock */
        $condition1Mock = $this->getMockBuilder(AbstractCombine::class)
            ->disableOriginalConstructor()
            ->getMock();
        $condition1Mock->expects($this->any())
            ->method('isSatisfiedBy')
            ->with($customer, $websiteId, $params)
            ->willReturn(true);
        /** @var \PHPUnit_Framework_MockObject_MockObject $condition2Mock */
        $condition2Mock = $this->getMockBuilder(AbstractCombine::class)
            ->disableOriginalConstructor()
            ->getMock();
        $condition2Mock->expects($this->any())
            ->method('isSatisfiedBy')
            ->with($customer, $websiteId, $params)
            ->willReturn(true);
        $this->root['conditions'] = [$condition1Mock, $condition2Mock];

        $this->root['value'] = $value;
        $this->assertEquals($result, $this->root->isSatisfiedBy($customer, $websiteId, $params));
    }

    /**
     * @return array
     */
    public function testDataProvider(): array
    {
        return [
            [1, true],
            [0, false],
        ];
    }
}
