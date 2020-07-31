<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Test\Unit\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Handler;

use Magento\PricePermissions\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Handler\CustomOptions;

class CustomOptionsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CustomOptions
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $productMock;

    protected function setUp()
    {
        $this->productMock = $this->createMock(\Magento\Catalog\Model\Product::class);
        $this->model = new CustomOptions();
    }

    public function testHandleProductWithoutOptions()
    {
        $this->productMock->expects(
            $this->once()
        )->method(
            'getData'
        )->with(
            'product_options'
        )->will(
            $this->returnValue(null)
        );

        $this->productMock->expects($this->never())->method('setData');

        $this->model->handle($this->productMock);
    }

    public function testHandleProductWithoutOriginalOptions()
    {
        $this->productMock->expects($this->once())->method('getOptions')->will($this->returnValue([]));
        $options = [
            'one' => ['price' => '10', 'price_type' => '20'],
            'two' => ['values' => 123],
            'three' => [
                'values' => [['price' => 30, 'price_type' => 40], ['price' => 50, 'price_type' => 60]],
            ],
        ];

        $expectedData = [
            'one' => ['price' => '0', 'price_type' => '0'],
            'two' => ['values' => 123],
            'three' => [
                'values' => [['price' => 0, 'price_type' => 0], ['price' => 0, 'price_type' => 0]],
            ],
        ];

        $this->productMock->expects(
            $this->once()
        )->method(
            'getData'
        )->with(
            'product_options'
        )->will(
            $this->returnValue($options)
        );

        $this->productMock->expects($this->once())->method('setData')->with('product_options', $expectedData);

        $this->model->handle($this->productMock);
    }

    public function testHandleProductWithOriginalOptions()
    {
        $mockedMethodList = [
            'getOptionId',
            '__wakeup',
            'getType',
            'getPriceType',
            'getGroupByType',
            'getPrice',
            'getValues',
        ];

        $optionOne = $this->createPartialMock(\Magento\Catalog\Model\Product\Option::class, $mockedMethodList);
        $optionTwo = $this->createPartialMock(\Magento\Catalog\Model\Product\Option::class, $mockedMethodList);
        $optionTwoValue = $this->createPartialMock(
            \Magento\Catalog\Model\Product\Option\Value::class,
            ['getOptionTypeId', 'getPriceType', 'getPrice', '__wakeup']
        );

        $optionOne->expects($this->any())->method('getOptionId')->will($this->returnValue('one'));
        $optionOne->expects($this->any())->method('getType')->will($this->returnValue(2));
        $optionOne->expects(
            $this->any()
        )->method(
            'getGroupByType'
        )->will(
            $this->returnValue(\Magento\Catalog\Api\Data\ProductCustomOptionInterface::OPTION_GROUP_DATE)
        );
        $optionOne->expects($this->any())->method('getPrice')->will($this->returnValue(10));
        $optionOne->expects($this->any())->method('getPriceType')->will($this->returnValue(2));

        $optionTwo->expects($this->any())->method('getOptionId')->will($this->returnValue('three'));
        $optionTwo->expects($this->any())->method('getType')->will($this->returnValue(3));
        $optionTwo->expects(
            $this->any()
        )->method(
            'getGroupByType'
        )->will(
            $this->returnValue(\Magento\Catalog\Api\Data\ProductCustomOptionInterface::OPTION_GROUP_SELECT)
        );
        $optionTwo->expects($this->any())->method('getValues')->will($this->returnValue([$optionTwoValue]));

        $optionTwoValue->expects($this->any())->method('getOptionTypeId')->will($this->returnValue(1));
        $optionTwoValue->expects($this->any())->method('getPrice')->will($this->returnValue(100));
        $optionTwoValue->expects($this->any())->method('getPriceType')->will($this->returnValue(2));

        $this->productMock->expects(
            $this->once()
        )->method(
            'getOptions'
        )->will(
            $this->returnValue([$optionOne, $optionTwo])
        );

        $options = [
            'one' => ['price' => '10', 'price_type' => '20', 'type' => 2],
            'two' => ['values' => 123, 'type' => 10],
            'three' => [
                'type' => 3,
                'values' => [['price' => 30, 'price_type' => 40, 'option_type_id' => '1']],
            ],
        ];

        $expectedData = [
            'one' => ['price' => 10, 'price_type' => 2, 'type' => 2],
            'two' => ['values' => 123, 'type' => 10],
            'three' => [
                'type' => 3,
                'values' => [['price' => 100, 'price_type' => 2, 'option_type_id' => 1]],
            ],
        ];

        $this->productMock->expects(
            $this->once()
        )->method(
            'getData'
        )->with(
            'product_options'
        )->will(
            $this->returnValue($options)
        );

        $this->productMock->expects($this->once())->method('setData')->with('product_options', $expectedData);

        $this->model->handle($this->productMock);
    }
}
