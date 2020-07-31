<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Test\Unit\Observer;

class AddGiftRegistryQuoteFlagTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftRegistry\Observer\AddGiftRegistryQuoteFlag
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataMock;

    protected function setUp()
    {
        $this->dataMock = $this->createMock(\Magento\GiftRegistry\Helper\Data::class);
        $this->model = new \Magento\GiftRegistry\Observer\AddGiftRegistryQuoteFlag($this->dataMock);
    }

    public function testexecuteIfRegistryDisabled()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->dataMock->expects($this->once())->method('isEnabled')->willReturn(false);
        $this->assertEquals($this->model, $this->model->execute($observerMock));
    }

    public function testexecuteIfRegistryItemIdIsNull()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->dataMock->expects($this->once())->method('isEnabled')->willReturn(true);

        $productMock = $this->createPartialMock(\Magento\Catalog\Model\Product::class, ['getGiftregistryItemId']);
        $productMock->expects($this->once())->method('getGiftregistryItemId')->willReturn(null);

        $quoteItemMock = $this->createMock(\Magento\Quote\Model\Quote\Item::class);

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getProduct', 'getQuoteItem']);
        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $eventMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $eventMock->expects($this->once())->method('getQuoteItem')->willReturn($quoteItemMock);

        $this->assertEquals($this->model, $this->model->execute($observerMock));
    }

    public function testexecute()
    {
        $giftRegistryItemId = 100;
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->dataMock->expects($this->once())->method('isEnabled')->willReturn(true);

        $productMock = $this->createPartialMock(\Magento\Catalog\Model\Product::class, ['getGiftregistryItemId']);
        $productMock->expects($this->once())->method('getGiftregistryItemId')->willReturn($giftRegistryItemId);

        $quoteItemMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote\Item::class,
            ['setGiftregistryItemId', 'getParentItem']
        );

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getProduct', 'getQuoteItem']);
        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $eventMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $eventMock->expects($this->once())->method('getQuoteItem')->willReturn($quoteItemMock);

        $quoteItemMock->expects($this->once())
            ->method('setGiftregistryItemId')
            ->with($giftRegistryItemId)
            ->willReturnSelf();

        $parentItemMock = $this->createPartialMock(\Magento\Quote\Model\Quote\Item::class, ['setGiftregistryItemId']);
        $parentItemMock->expects($this->once())
            ->method('setGiftregistryItemId')
            ->with($giftRegistryItemId)
            ->willReturnSelf();

        $quoteItemMock->expects($this->once())->method('getParentItem')->willReturn($parentItemMock);

        $this->assertEquals($this->model, $this->model->execute($observerMock));
    }
}
