<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Test\Unit\Model\Observer;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Unit test of order attributes extension observer.
 */
class ExtendOrderAttributesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Subject of testing.
     *
     * @var \Magento\GiftWrapping\Model\Observer\ExtendOrderAttributes
     */
    protected $subject;

    /**
     * Event observer mock.
     *
     * @var \Magento\Framework\Event\Observer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $observerMock;

    /**
     * Event mock.
     *
     * @var \Magento\Framework\DataObject|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventMock;

    /**
     * Order model mock.
     *
     * @var \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderMock;

    /**
     * Quote address model mock.
     *
     * @var \Magento\Quote\Model\Address|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteAddressMock;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getEvent']);

        $this->eventMock = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getOrder', 'getQuote']);

        $this->observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->orderMock = $this->createMock(
            \Magento\Sales\Model\Order::class
        );

        $quoteMock = $this->createPartialMock(\Magento\Quote\Model\Quote::class, ['getShippingAddress']);

        $this->quoteAddressMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote\Address::class,
            ['hasData', 'getData']
        );
        $quoteMock->expects($this->once())->method('getShippingAddress')->willReturn($this->quoteAddressMock);

        $this->eventMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->eventMock->expects($this->any())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $this->subject = $objectManager->getObject(
            \Magento\GiftWrapping\Model\Observer\ExtendOrderAttributes::class
        );
    }

    public function testExecute()
    {
        $gwBasePriceInclTax = 25;

        $this->quoteAddressMock->expects($this->any())->method('hasData')->willReturnCallback(
            function ($attribute) {
                return in_array($attribute, ['gw_id', 'gw_allow_gift_receipt', 'gw_base_price_incl_tax']);
            }
        );

        $this->quoteAddressMock->expects($this->at(1))->method('getData')->with('gw_id')->willReturn(1);
        $this->orderMock->expects($this->at(0))->method('setData')->with('gw_id', 1);

        $this->quoteAddressMock->expects($this->at(3))->method('getData')->with('gw_allow_gift_receipt')
            ->willReturn(true);
        $this->orderMock->expects($this->at(1))->method('setData')->with('gw_allow_gift_receipt', true);

        $this->quoteAddressMock->expects($this->at(19))->method('getData')->with('gw_base_price_incl_tax')
            ->willReturn($gwBasePriceInclTax);
        $this->orderMock->expects($this->at(2))->method('setData')->with('gw_base_price_incl_tax', $gwBasePriceInclTax);

        $this->subject->execute($this->observerMock);
    }
}
