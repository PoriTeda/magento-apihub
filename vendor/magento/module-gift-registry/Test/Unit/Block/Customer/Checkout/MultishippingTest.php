<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Test\Unit\Block\Customer\Checkout;

class MultishippingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftRegistry\Block\Customer\Checkout\Multishipping
     */
    protected $block;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerSessionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $itemMock;

    protected function setUp()
    {
        $this->contextMock = $this->createMock(\Magento\Framework\View\Element\Template\Context::class);
        $this->customerSessionMock = $this->createMock(\Magento\Checkout\Model\Session::class);
        $this->quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->entityMock = $this->createPartialMock(
            \Magento\GiftRegistry\Model\Entity::class,
            ['loadByEntityItem', 'getId', 'getShippingAddress']
        );
        $this->itemMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote\Item::class,
            ['getGiftregistryItemId', 'getId', 'getQuoteItem', 'getCustomerAddressId']
        );
        $this->entityFactoryMock = $this->createPartialMock(
            \Magento\GiftRegistry\Model\EntityFactory::class,
            ['create']
        );
        $this->block = new \Magento\GiftRegistry\Block\Customer\Checkout\Multishipping(
            $this->contextMock,
            $this->createMock(\Magento\GiftRegistry\Helper\Data::class),
            $this->customerSessionMock,
            $this->entityFactoryMock
        );
    }

    public function testGetGiftregistrySelectedAddressesIndexes()
    {
        $item = [
            'entity_id' => 1,
            'item_id' => 'registryId',
            'is_address' => 1
        ];
        $this->customerSessionMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->customerSessionMock->expects($this->any())->method('getQuoteId')->willReturn(1);
        $this->entityFactoryMock->expects($this->once())->method('create')->willReturn($this->entityMock);
        $this->quoteMock->expects($this->once())->method('getItemsCollection')->willReturn([$this->itemMock]);
        $this->itemMock->expects($this->once())->method('getGiftregistryItemId')->willReturn('registryId');
        $this->entityMock->expects($this->once())->method('loadByEntityItem')->with('registryId')->willReturnSelf();
        $this->entityMock->expects($this->once())->method('getId')->willReturn($item['entity_id']);
        $this->entityMock->expects($this->once())->method('getShippingAddress')->willReturn(1);
        $this->itemMock->expects($this->once())->method('getId')->willReturn('itemId');
        $this->quoteMock
            ->expects($this->once())
            ->method('getShippingAddressesItems')
            ->willReturn([ 'index' => $this->itemMock]);
        $this->itemMock->expects($this->once())->method('getQuoteItem')->willReturn($this->quoteMock);
        $this->itemMock->expects($this->once())->method('getCustomerAddressId')->willReturn(null);
        $this->quoteMock->expects($this->once())->method('getId')->willReturn('itemId');
        $this->assertEquals(['index'], $this->block->getGiftregistrySelectedAddressesIndexes());
    }
}
