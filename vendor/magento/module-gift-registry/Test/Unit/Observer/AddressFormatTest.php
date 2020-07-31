<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Test\Unit\Observer;

class AddressFormatTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftRegistry\Observer\AddressFormat
     */
    protected $model;

    protected function setUp()
    {
        $this->model = new \Magento\GiftRegistry\Observer\AddressFormat();
    }

    public function testFormatIfGiftRegistryItemIdIsNull()
    {
        $format = 'format';
        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getType', 'getAddress']);
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $typeMock = $this->createPartialMock(
            \Magento\Framework\DataObject::class,
            ['getPrevFormat', 'setDefaultFormat']
        );
        $addressMock = $this->createPartialMock(
            \Magento\Customer\Model\Address\AbstractAddress::class,
            ['getGiftregistryItemId']
        );

        $eventMock->expects($this->once())->method('getType')->willReturn($typeMock);
        $eventMock->expects($this->once())->method('getAddress')->willReturn($addressMock);

        $addressMock->expects($this->once())->method('getGiftregistryItemId')->willReturn(null);
        $typeMock->expects($this->exactly(2))->method('getPrevFormat')->willReturn($format);
        $typeMock->expects($this->once())->method('setDefaultFormat')->with($format)->willReturn($format);

        $this->assertEquals($this->model, $this->model->format($observerMock));
    }

    public function testFormat()
    {
        $giftRegistryItemId = 100;
        $format = 'format';
        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getType', 'getAddress']);
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $typeMock = $this->createPartialMock(
            \Magento\Framework\DataObject::class,
            ['getPrevFormat', 'setDefaultFormat', 'getDefaultFormat', 'setPrevFormat']
        );
        $addressMock = $this->createPartialMock(
            \Magento\Customer\Model\Address\AbstractAddress::class,
            ['getGiftregistryItemId']
        );

        $eventMock->expects($this->once())->method('getType')->willReturn($typeMock);
        $eventMock->expects($this->once())->method('getAddress')->willReturn($addressMock);

        $addressMock->expects($this->once())->method('getGiftregistryItemId')->willReturn($giftRegistryItemId);

        $typeMock->expects($this->once())->method('getPrevFormat')->willReturn(null);
        $typeMock->expects($this->once())->method('getDefaultFormat')->willReturn($format);
        $typeMock->expects($this->once())->method('setPrevFormat')->with($format)->willReturnSelf();
        $typeMock->expects($this->once())
            ->method('setDefaultFormat')
            ->with(__("Ship to the recipient's address."))
            ->willReturnSelf();

        $this->assertEquals($this->model, $this->model->format($observerMock));
    }
}
