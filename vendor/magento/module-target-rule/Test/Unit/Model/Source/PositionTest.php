<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Test\Unit\Model\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class PositionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Rotation
     */
    protected $_rotation;

    protected function setUp()
    {
        $this->_rotation = (new ObjectManager($this))->getObject(\Magento\TargetRule\Model\Source\Position::class, []);
    }

    public function testSetType()
    {
        $result = [
            \Magento\TargetRule\Model\Rule::BOTH_SELECTED_AND_RULE_BASED => __('Both Selected and Rule-Based'),
            \Magento\TargetRule\Model\Rule::SELECTED_ONLY => __('Selected Only'),
            \Magento\TargetRule\Model\Rule::RULE_BASED_ONLY => __('Rule-Based Only'),
        ];
        $this->assertEquals($result, $this->_rotation->toOptionArray());
    }
}
