<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Test\Unit\Model\Rule\Options;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class AppliesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Tested model
     *
     * @var \Magento\TargetRule\Model\Rule\Options\Applies
     */
    protected $_applies;

    protected function setUp()
    {
        $rule = $this->createMock(\Magento\TargetRule\Model\Rule::class);

        $rule->expects($this->once())
            ->method('getAppliesToOptions')
            ->will($this->returnValue([1, 2]));

        $this->_applies = (new ObjectManager($this))->getObject(
            \Magento\TargetRule\Model\Rule\Options\Applies::class,
            [
                'targetRuleModel' => $rule,
            ]
        );
    }

    public function testToOptionArray()
    {
        $this->assertEquals([1, 2], $this->_applies->toOptionArray());
    }
}
