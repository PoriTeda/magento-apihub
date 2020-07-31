<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftWrapping\Test\Unit\Observer;

class AddPaymentGiftWrappingItemTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\GiftWrapping\Observer\AddPaymentGiftWrappingItem */
    protected $_model;

    /**
     * @var \Magento\Framework\Event\Observer
     */
    protected $_observer;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $_event;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $helperDataMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $observerMock;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->_model = $objectManagerHelper->getObject(
            \Magento\GiftWrapping\Observer\AddPaymentGiftWrappingItem::class
        );
        $this->_event = new \Magento\Framework\DataObject();
        $this->_observer = new \Magento\Framework\Event\Observer(['event' => $this->_event]);
    }

    /**
     * @param float $amount
     * @dataProvider addPaymentGiftWrappingItemTotalCardDataProvider
     */
    public function testAddPaymentGiftWrappingItemTotalCard($amount)
    {
        $salesModel = $this->getMockForAbstractClass(\Magento\Payment\Model\Cart\SalesModel\SalesModelInterface::class);
        $salesModel->expects($this->once())->method('getAllItems')->will($this->returnValue([]));
        $salesModel->expects($this->any())->method('getDataUsingMethod')->will(
            $this->returnCallback(
                function ($key) use ($amount) {
                    if ($key == 'gw_card_base_price') {
                        return $amount;
                    } elseif ($key == 'gw_add_card' && is_float($amount)) {
                        return true;
                    } else {
                        return null;
                    }
                }
            )
        );
        $cart = $this->createMock(\Magento\Payment\Model\Cart::class);
        $cart->expects($this->once())->method('getSalesModel')->will($this->returnValue($salesModel));
        if ($amount) {
            $cart->expects($this->once())->method('addCustomItem')->with(__('Printed Card'), 1, $amount);
        } else {
            $cart->expects($this->never())->method('addCustomItem');
        }
        $this->_event->setCart($cart);
        $this->_model->execute($this->_observer);
    }

    public function addPaymentGiftWrappingItemTotalCardDataProvider()
    {
        return [[null], [0], [0.12]];
    }

    /**
     * @param array $items
     * @param float $amount
     * @param float $expected
     * @dataProvider addPaymentGiftWrappingItemTotalWrappingDataProvider
     */
    public function testAddPaymentGiftWrappingItemTotalWrapping(array $items, $amount, $expected)
    {
        $salesModel = $this->getMockForAbstractClass(\Magento\Payment\Model\Cart\SalesModel\SalesModelInterface::class);
        $salesModel->expects($this->once())->method('getAllItems')->will($this->returnValue($items));
        $salesModel->expects($this->any())->method('getDataUsingMethod')->will(
            $this->returnCallback(
                function ($key) use ($amount) {
                    if ($key == 'gw_base_price') {
                        return $amount;
                    } elseif ($key == 'gw_id' && is_float($amount)) {
                        return 1;
                    } else {
                        return null;
                    }
                }
            )
        );
        $cart = $this->createMock(\Magento\Payment\Model\Cart::class);
        $cart->expects($this->once())->method('getSalesModel')->will($this->returnValue($salesModel));
        if ($expected) {
            $cart->expects($this->once())->method('addCustomItem')->with(__('Gift Wrapping'), 1, $expected);
        } else {
            $cart->expects($this->never())->method('addCustomItem');
        }
        $this->_event->setCart($cart);
        $this->_model->execute($this->_observer);
    }

    public function addPaymentGiftWrappingItemTotalWrappingDataProvider()
    {
        $amounts = [null, 0, 0.12];
        $originalItems = [
            [],
            [
                new \Magento\Framework\DataObject(
                    ['parent_item' => 'something', 'gw_id' => 1, 'gw_base_price' => 0.3]
                ),
                new \Magento\Framework\DataObject(['gw_id' => null, 'gw_base_price' => 0.3]),
                new \Magento\Framework\DataObject(['gw_id' => 1, 'gw_base_price' => 0.0]),
                new \Magento\Framework\DataObject(['gw_id' => 2, 'gw_base_price' => null]),
                new \Magento\Framework\DataObject(['gw_id' => 3, 'gw_base_price' => 0.12]),
                new \Magento\Framework\DataObject(['gw_id' => 4, 'gw_base_price' => 2.1])
            ],
        ];
        $itemsPrice = [0, 0.12 + 2.1];
        $data = [];
        foreach ($amounts as $amount) {
            foreach ($originalItems as $i => $originalItemsSet) {
                $items = [];
                foreach ($originalItemsSet as $originalItem) {
                    $items[] = new \Magento\Framework\DataObject(['original_item' => $originalItem]);
                }
                $data[] = [$items, $amount, $itemsPrice[$i] + (double)$amount];
            }
        }
        return $data;
    }
}
