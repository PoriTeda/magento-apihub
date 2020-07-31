<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Test\Unit\Model\Cart\SalesModel;

class QuoteTest extends \Magento\Payment\Test\Unit\Model\Cart\SalesModel\QuoteTest
{
    /** @var \Magento\CustomerBalance\Model\Cart\SalesModel\Quote */
    protected $_model;

    /** @var \Magento\Quote\Model\Quote|\PHPUnit_Framework_MockObject_MockObject */
    protected $_quoteMock;

    protected function setUp()
    {
        $this->_quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->_model = new \Magento\CustomerBalance\Model\Cart\SalesModel\Quote($this->_quoteMock);
    }

    public function testGetDataUsingMethod()
    {
        $this->_quoteMock->expects(
            $this->exactly(2)
        )->method(
            'getDataUsingMethod'
        )->with(
            $this->anything(),
            'any args'
        )->will(
            $this->returnCallback(
                function ($key) {
                    return $key == 'base_customer_bal_amount_used' ? 'customer_balance_amount result' : 'some value';
                }
            )
        );
        $this->assertEquals('some value', $this->_model->getDataUsingMethod('any key', 'any args'));
        $this->assertEquals(
            'customer_balance_amount result',
            $this->_model->getDataUsingMethod('customer_balance_base_amount', 'any args')
        );
    }
}
