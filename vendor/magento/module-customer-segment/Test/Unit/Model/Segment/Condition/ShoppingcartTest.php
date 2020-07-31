<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition;

class ShoppingcartTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Shoppingcart
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
     * @var \Magento\CustomerSegment\Model\Segment\Condition\Shoppingcart\Amount
     */
    protected $cartAmount;

    /**
     * @var \Magento\CustomerSegment\Model\Segment\Condition\Shoppingcart\Itemsquantity
     */
    protected $cartItemsquantity;

    /**
     * @var \Magento\CustomerSegment\Model\Segment\Condition\Shoppingcart\Productsquantity
     */
    protected $cartProductsquantity;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder(\Magento\Rule\Model\Condition\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceSegment = $this->createMock(\Magento\CustomerSegment\Model\ResourceModel\Segment::class);
        $this->conditionFactory = $this->createMock(\Magento\CustomerSegment\Model\ConditionFactory::class);

        $this->cartAmount = $this->createMock(
            \Magento\CustomerSegment\Model\Segment\Condition\Shoppingcart\Amount::class
        );
        $this->cartItemsquantity = $this->createMock(
            \Magento\CustomerSegment\Model\Segment\Condition\Shoppingcart\Itemsquantity::class
        );
        $this->cartProductsquantity = $this->createMock(
            \Magento\CustomerSegment\Model\Segment\Condition\Shoppingcart\Productsquantity::class
        );

        $this->model = new \Magento\CustomerSegment\Model\Segment\Condition\Shoppingcart(
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
            $this->cartAmount,
            $this->cartItemsquantity,
            $this->cartProductsquantity
        );
    }

    public function testGetNewChildSelectOptions()
    {
        $amountOptions = ['test_amount_options'];
        $itemsquantityOptions = ['test_itemsquantity_options'];
        $productsquantityOptions = ['test_productsquantity_options'];

        $this->cartAmount
            ->expects($this->once())
            ->method('getNewChildSelectOptions')
            ->will($this->returnValue($amountOptions));

        $this->cartItemsquantity
            ->expects($this->once())
            ->method('getNewChildSelectOptions')
            ->will($this->returnValue($itemsquantityOptions));

        $this->cartProductsquantity
            ->expects($this->once())
            ->method('getNewChildSelectOptions')
            ->will($this->returnValue($productsquantityOptions));

        $this->conditionFactory
            ->expects($this->any())
            ->method('create')
            ->will($this->returnValueMap([
                ['Shoppingcart\Amount', [], $this->cartAmount],
                ['Shoppingcart\Itemsquantity', [], $this->cartItemsquantity],
                ['Shoppingcart\Productsquantity', [], $this->cartProductsquantity],
            ]));

        $result = $this->model->getNewChildSelectOptions();

        $this->assertTrue(is_array($result));
        $this->assertEquals(
            [
                'value' => [
                    $amountOptions,
                    $itemsquantityOptions,
                    $productsquantityOptions,
                ],
                'label' => __('Shopping Cart'),
                'available_in_guest_mode' => true,
            ],
            $result
        );
    }
}
