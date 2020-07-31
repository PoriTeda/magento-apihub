<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Test\Unit\Observer;

/**
 * Class AddPaymentCustomerBalanceItemObserverTest
 */
class AddPaymentCustomerBalanceItemObserverTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\CustomerBalance\Observer\AddPaymentCustomerBalanceItemObserver */
    protected $model;

    /**
     * @var \Magento\Framework\Event\Observer
     */
    protected $observer;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $event;

    protected function setUp()
    {
        $this->event = new \Magento\Framework\DataObject();
        $this->observer = new \Magento\Framework\Event\Observer(['event' => $this->event]);

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(
            \Magento\CustomerBalance\Observer\AddPaymentCustomerBalanceItemObserver::class
        );
    }

    /**
     * @param float $amount
     * @dataProvider addPaymentCustomerBalanceItemDataProvider
     */
    public function testAddPaymentCustomerBalanceItem($amount)
    {
        $salesModel = $this->getMockForAbstractClass(\Magento\Payment\Model\Cart\SalesModel\SalesModelInterface::class);
        $salesModel->expects($this->once())
            ->method('getDataUsingMethod')
            ->with('customer_balance_base_amount')
            ->will($this->returnValue($amount));

        $cart = $this->createMock(\Magento\Payment\Model\Cart::class);
        $cart->expects($this->once())->method('getSalesModel')->will($this->returnValue($salesModel));
        if (abs($amount) > 0.0001) {
            $cart->expects($this->once())->method('addDiscount')->with(abs($amount));
        } else {
            $cart->expects($this->never())->method('addDiscount');
        }
        $this->event->setCart($cart);
        $this->model->execute($this->observer);
    }

    public function addPaymentCustomerBalanceItemDataProvider()
    {
        return [[0.0], [0.1], [-0.1]];
    }
}
