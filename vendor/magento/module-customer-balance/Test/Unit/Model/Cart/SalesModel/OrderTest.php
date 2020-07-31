<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Test\Unit\Model\Cart\SalesModel;

class OrderTest extends \Magento\Payment\Test\Unit\Model\Cart\SalesModel\OrderTest
{
    /** @var \Magento\CustomerBalance\Model\Cart\SalesModel\Order */
    protected $_model;

    /** @var \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject */
    protected $_orderMock;

    protected function setUp()
    {
        $this->_orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $this->_model = new \Magento\CustomerBalance\Model\Cart\SalesModel\Order($this->_orderMock);
    }

    public function testGetDataUsingMethod()
    {
        $this->_orderMock->expects(
            $this->exactly(2)
        )->method(
            'getDataUsingMethod'
        )->with(
            $this->anything(),
            'any args'
        )->will(
            $this->returnCallback(
                function ($key) {
                    return $key == 'base_customer_balance_amount' ? 'customer_balance result' : 'some value';
                }
            )
        );
        $this->assertEquals('some value', $this->_model->getDataUsingMethod('any key', 'any args'));
        $this->assertEquals(
            'customer_balance result',
            $this->_model->getDataUsingMethod('customer_balance_base_amount', 'any args')
        );
    }
}
