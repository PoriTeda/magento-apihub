<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Test\Unit\Model\Rule\Condition;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class CombineTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Combine model
     *
     * @var \Magento\TargetRule\Model\Rule\Condition\Combine
     */
    protected $_combine;

    /**
     * Return array
     *
     * @var array
     */
    protected $returnArray = [
        'value' => 'Test',
        'label' => 'Test Conditions',
    ];

    protected function setUp()
    {
        $attribute = $this->createPartialMock(
            \Magento\TargetRule\Model\Rule\Condition\Product\Attributes::class,
            ['getNewChildSelectOptions']
        );

        $attribute->expects($this->any())
            ->method('getNewChildSelectOptions')
            ->will($this->returnValue($this->returnArray));

        $attributesFactory = $this->createPartialMock(
            \Magento\TargetRule\Model\Rule\Condition\Product\AttributesFactory::class,
            ['create']
        );

        $attributesFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($attribute));

        $this->_combine = (new ObjectManager($this))->getObject(
            \Magento\TargetRule\Model\Rule\Condition\Combine::class,
            [
                'context' => $this->_getCleanMock(\Magento\Rule\Model\Condition\Context::class),
                'attributesFactory' => $attributesFactory,
            ]
        );
    }

    /**
     * Get clean mock by class name
     *
     * @param string $className
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getCleanMock($className)
    {
        return $this->createMock($className);
    }

    public function testGetNewChildSelectOptions()
    {
        $result = [
            '0' => [
                'value' => '',
                'label' => 'Please choose a condition to add.',
            ],
            '1' => [
                'value' => \Magento\TargetRule\Model\Rule\Condition\Combine::class,
                'label' => 'Conditions Combination',
            ],
            '2' => $this->returnArray,
        ];

        $this->assertEquals($result, $this->_combine->getNewChildSelectOptions());
    }

    public function testCollectValidatedAttributes()
    {
        $productCollection = $this->_getCleanMock(\Magento\Catalog\Model\ResourceModel\Product\Collection::class);
        $condition = $this->_getCleanMock(\Magento\TargetRule\Model\Rule\Condition\Combine::class);

        $condition->expects($this->once())
            ->method('collectValidatedAttributes')
            ->will($this->returnSelf());

        $this->_combine->setConditions([$condition]);

        $this->assertEquals($this->_combine, $this->_combine->collectValidatedAttributes($productCollection));
    }
}
