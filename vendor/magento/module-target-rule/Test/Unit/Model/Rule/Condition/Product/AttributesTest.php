<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Test\Unit\Model\Rule\Condition\Product;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class AttributesTest
 * @package Magento\TargetRule\Model\Rule\Condition\Product
 *
 *
 */
class AttributesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Tested model
     *
     * @var \Magento\TargetRule\Model\Rule\Condition\Product\Attributes
     */
    protected $_attributes;

    protected function setUp()
    {
        $productResource = $this->createPartialMock(
            \Magento\Catalog\Model\ResourceModel\Product::class,
            ['loadAllAttributes', 'loadValueOptions']
        );

        $productResource->expects($this->any())
            ->method('loadAllAttributes')
            ->will($this->returnSelf());

        $productResource->expects($this->any())
            ->method('loadValueOptions')
            ->will($this->returnSelf());

        $this->_attributes = (new ObjectManager($this))->getObject(
            \Magento\TargetRule\Model\Rule\Condition\Product\Attributes::class,
            [
                'context' => $this->_getCleanMock(\Magento\Rule\Model\Condition\Context::class),
                'backendData' => $this->_getCleanMock(\Magento\Backend\Helper\Data::class),
                'config' => $this->_getCleanMock(\Magento\Eav\Model\Config::class),
                'productFactory' => $this->createPartialMock(\Magento\Catalog\Model\ProductFactory::class, ['create']),
                'productResource' => $productResource,
                'attrSetCollection' => $this->_getCleanMock(
                    \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection::class
                ),
                'localeFormat' => $this->_getCleanMock(\Magento\Framework\Locale\FormatInterface::class),
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
        $conditions = [
            [
                'value' => 'Magento\TargetRule\Model\Rule\Condition\Product\Attributes|attribute_set_id',
                'label' => __('Attribute Set'),
            ],
            [
                'value' => 'Magento\TargetRule\Model\Rule\Condition\Product\Attributes|category_ids',
                'label' => __('Category'),
            ],
        ];
        $result = ['value' => $conditions, 'label' => __('Product Attributes')];

        $this->assertEquals($result, $this->_attributes->getNewChildSelectOptions());
    }
}
