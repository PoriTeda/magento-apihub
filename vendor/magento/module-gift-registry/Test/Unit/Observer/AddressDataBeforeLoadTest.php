<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Test\Unit\Observer;

class AddressDataBeforeLoadTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftRegistry\Observer\AddressDataBeforeLoad
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $giftRegistryDataMock;

    protected function setUp()
    {
        $this->giftRegistryDataMock = $this->createMock(\Magento\GiftRegistry\Helper\Data::class);
        $this->model = new \Magento\GiftRegistry\Observer\AddressDataBeforeLoad($this->giftRegistryDataMock);
    }

    public function testexecute()
    {
        $addressId = 'prefixId';
        $prefix = 'prefix';
        $dataObject = $this->createPartialMock(
            \Magento\Framework\DataObject::class,
            ['setGiftregistryItemId', 'setCustomerAddressId']
        );
        $dataObject->expects($this->once())->method('setGiftregistryItemId')->with('Id')->willReturnSelf();
        $dataObject->expects($this->once())->method('setCustomerAddressId')->with($addressId)->willReturnSelf();

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getValue', 'getDataObject']);
        $eventMock->expects($this->once())->method('getValue')->willReturn($addressId);
        $eventMock->expects($this->once())->method('getDataObject')->willReturn($dataObject);

        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $this->giftRegistryDataMock->expects($this->once())->method('getAddressIdPrefix')->willReturn($prefix);

        $this->assertEquals($this->model, $this->model->execute($observerMock));
    }
}
