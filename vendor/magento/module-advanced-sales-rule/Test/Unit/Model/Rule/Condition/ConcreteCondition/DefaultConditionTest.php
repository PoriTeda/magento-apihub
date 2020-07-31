<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition\ConcreteCondition;

use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\DefaultCondition;

/**
 * Class DefaultConditionTest
 */
class DefaultConditionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\DefaultCondition
     */
    protected $model;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * Setup the test
     */
    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->model = $this->objectManager->getObject(
            \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\DefaultCondition::class,
            []
        );
    }

    /**
     * test IsFilterable
     */
    public function testIsFilterable()
    {
        $this->assertFalse($this->model->isFilterable());
    }

    /**
     * test GetFilterGroups
     */
    public function testGetFilterGroups()
    {
        $this->assertTrue(is_array($this->model->getFilterGroups()));
        $this->assertTrue(empty($this->model->getFilterGroups()));
    }
}
