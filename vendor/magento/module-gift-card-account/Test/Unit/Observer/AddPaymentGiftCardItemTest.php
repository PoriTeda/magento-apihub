<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Test\Unit\Observer;

class AddPaymentGiftCardItemTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\GiftCardAccount\Observer\AddPaymentGiftCardItem */
    private $model;

    /**
     * @var \Magento\Framework\Event\Observer
     */
    private $observer;

    /**
     * @var \Magento\Framework\DataObject
     */
    private $event;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->model = $objectManagerHelper->getObject(
            \Magento\GiftCardAccount\Observer\AddPaymentGiftCardItem::class
        );

        $this->event = new \Magento\Framework\DataObject();

        $this->observer = new \Magento\Framework\Event\Observer(['event' => $this->event]);
    }

    /**
     * @param float $amount
     * @dataProvider addPaymentGiftCardItemDataProvider
     */
    public function testAddPaymentGiftCardItem($amount)
    {
        $salesModelMock = $this->getMockForAbstractClass(
            \Magento\Payment\Model\Cart\SalesModel\SalesModelInterface::class
        );
        $salesModelMock->expects(
            $this->once()
        )->method(
            'getDataUsingMethod'
        )->with(
            'base_gift_cards_amount'
        )->will(
            $this->returnValue($amount)
        );
        $cartMock = $this->createMock(\Magento\Payment\Model\Cart::class);
        $cartMock->expects($this->once())->method('getSalesModel')->will($this->returnValue($salesModelMock));
        if (abs($amount) > 0.0001) {
            $cartMock->expects($this->once())->method('addDiscount')->with(abs($amount));
        } else {
            $cartMock->expects($this->never())->method('addDiscount');
        }
        $this->event->setCart($cartMock);
        $this->model->execute($this->observer);
    }

    public function addPaymentGiftCardItemDataProvider()
    {
        return [[0.0], [0.1], [-0.1]];
    }
}
