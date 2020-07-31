<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Test\Unit\Model\Condition;

class AbstractConditionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AbstractCondition
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

    protected function setUp()
    {
        $this->context = $this->getMockBuilder(
            \Magento\Rule\Model\Condition\Context::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceSegment =
            $this->createMock(\Magento\CustomerSegment\Model\ResourceModel\Segment::class);

        $this->model = $this->getMockForAbstractClass(
            \Magento\CustomerSegment\Model\Condition\AbstractCondition::class,
            [
                $this->context,
                $this->resourceSegment,
            ]
        );
    }

    protected function tearDown()
    {
        unset(
            $this->model,
            $this->context,
            $this->resourceSegment
        );
    }

    public function testGetMatchedEvents()
    {
        $result = $this->model->getMatchedEvents();

        $this->assertTrue(is_array($result));
        $this->assertEmpty($result);
    }

    public function testGetResource()
    {
        $result = $this->model->getResource();

        $this->assertInstanceOf(\Magento\CustomerSegment\Model\ResourceModel\Segment::class, $result);
        $this->assertEquals($this->resourceSegment, $result);
    }

    public function testGetDefaultOperatorOptions()
    {
        $result = $this->model->getDefaultOperatorOptions();

        $this->assertTrue(is_array($result));

        $this->assertArrayHasKey('==', $result);
        $this->assertEquals('is', $result['==']);

        $this->assertArrayHasKey('!=', $result);
        $this->assertEquals('is not', $result['!=']);

        $this->assertArrayHasKey('>=', $result);
        $this->assertEquals('equals or greater than', $result['>=']);

        $this->assertArrayHasKey('<=', $result);
        $this->assertEquals('equals or less than', $result['<=']);

        $this->assertArrayHasKey('>', $result);
        $this->assertEquals('greater than', $result['>']);

        $this->assertArrayHasKey('<', $result);
        $this->assertEquals('less than', $result['<']);

        $this->assertArrayHasKey('{}', $result);
        $this->assertEquals('contains', $result['{}']);

        $this->assertArrayHasKey('!{}', $result);
        $this->assertEquals('does not contain', $result['!{}']);

        $this->assertArrayHasKey('()', $result);
        $this->assertEquals('is one of', $result['()']);

        $this->assertArrayHasKey('!()', $result);
        $this->assertEquals('is not one of', $result['!()']);

        $this->assertArrayHasKey('[]', $result);
        $this->assertEquals('contains', $result['[]']);

        $this->assertArrayHasKey('![]', $result);
        $this->assertEquals('does not contains', $result['![]']);
    }

    public function testGetDefaultOperatorInputByType()
    {
        $result = $this->model->getDefaultOperatorInputByType();

        $this->assertTrue(is_array($result));

        $this->assertArrayHasKey('string', $result);
        $this->assertTrue(is_array($result['string']));
        $this->assertEquals(['==', '!=', '{}', '!{}'], $result['string']);

        $this->assertArrayHasKey('numeric', $result);
        $this->assertTrue(is_array($result['numeric']));
        $this->assertEquals(['==', '!=', '>=', '>', '<=', '<'], $result['numeric']);

        $this->assertArrayHasKey('date', $result);
        $this->assertTrue(is_array($result['date']));
        $this->assertEquals(['==', '>=', '<='], $result['date']);

        $this->assertArrayHasKey('select', $result);
        $this->assertTrue(is_array($result['select']));
        $this->assertEquals(['==', '!='], $result['select']);

        $this->assertArrayHasKey('boolean', $result);
        $this->assertTrue(is_array($result['boolean']));
        $this->assertEquals(['==', '!='], $result['boolean']);

        $this->assertArrayHasKey('multiselect', $result);
        $this->assertTrue(is_array($result['multiselect']));
        $this->assertEquals(['==', '!=', '[]', '![]'], $result['multiselect']);

        $this->assertArrayHasKey('grid', $result);
        $this->assertTrue(is_array($result['grid']));
        $this->assertEquals(['()', '!()'], $result['grid']);
    }
}
