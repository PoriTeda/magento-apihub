<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Test\Unit\Model\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class RotationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Rotation
     */
    protected $_rotation;

    protected function setUp()
    {
        $this->_rotation = (new ObjectManager($this))->getObject(\Magento\TargetRule\Model\Source\Rotation::class, []);
    }

    public function testToOptionArray()
    {
        $result = [
            \Magento\TargetRule\Model\Rule::ROTATION_NONE => __('Do not rotate'),
            \Magento\TargetRule\Model\Rule::ROTATION_SHUFFLE => __('Shuffle'),
        ];
        $this->assertEquals($result, $this->_rotation->toOptionArray());
    }
}
