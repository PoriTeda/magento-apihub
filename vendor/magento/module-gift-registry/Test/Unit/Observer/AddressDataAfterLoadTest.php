<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Test\Unit\Observer;

class AddressDataAfterLoadTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftRegistry\Observer\AddressDataAfterLoad
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $giftRegistryDataMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerSessionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityFactoryMock;

    protected function setUp()
    {
        $this->giftRegistryDataMock = $this->createMock(\Magento\GiftRegistry\Helper\Data::class);
        $this->customerSessionMock = $this->createMock(\Magento\Customer\Model\Session::class);
        $this->entityFactoryMock = $this->createPartialMock(
            \Magento\GiftRegistry\Model\EntityFactory::class,
            ['create']
        );

        $this->model = new \Magento\GiftRegistry\Observer\AddressDataAfterLoad(
            $this->giftRegistryDataMock,
            $this->customerSessionMock,
            $this->entityFactoryMock
        );
    }

    public function testexecuteIfGiftRegistryEntityIdIsNull()
    {
        $registryItemId = 100;
        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getDataObject']);
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $dataObjectMock = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getGiftregistryItemId']);
        $eventMock->expects($this->once())->method('getDataObject')->willReturn($dataObjectMock);

        $dataObjectMock->expects($this->once())->method('getGiftregistryItemId')->willReturn($registryItemId);

        $entityMock = $this->createPartialMock(
            \Magento\GiftRegistry\Model\Entity::class,
            ['loadByEntityItem', 'getId']
        );
        $this->entityFactoryMock->expects($this->once())->method('create')->willReturn($entityMock);

        $entityMock->expects($this->once())->method('loadByEntityItem')->with($registryItemId)->willReturnSelf();
        $entityMock->expects($this->once())->method('getId')->willReturn(null);

        $this->assertEquals($this->model, $this->model->execute($observerMock));
    }

    public function testexecute()
    {
        $prefix = 'prefix';
        $registryItemId = 100;
        $entityId = 200;
        $customerId = 300;
        $addressData = ['data' => 'value'];

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getDataObject']);
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $dataObjectMock = $this->createPartialMock(
            \Magento\Framework\DataObject::class,
            ['getGiftregistryItemId', 'setId', 'setCustomerId', 'addData']
        );
        $eventMock->expects($this->once())->method('getDataObject')->willReturn($dataObjectMock);

        $dataObjectMock->expects($this->once())->method('getGiftregistryItemId')->willReturn($registryItemId);

        $entityMock = $this->createPartialMock(
            \Magento\GiftRegistry\Model\Entity::class,
            ['loadByEntityItem', 'getId', 'exportAddress']
        );
        $this->entityFactoryMock->expects($this->once())->method('create')->willReturn($entityMock);

        $entityMock->expects($this->once())->method('loadByEntityItem')->with($registryItemId)->willReturnSelf();
        $entityMock->expects($this->once())->method('getId')->willReturn($entityId);

        $customerMock = $this->createMock(\Magento\Customer\Model\Customer::class);
        $customerMock->expects($this->once())->method('getId')->willReturn($customerId);
        $this->giftRegistryDataMock->expects($this->once())->method('getAddressIdPrefix')->willReturn($prefix);
        $this->customerSessionMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);

        $exportedAddressMock = $this->createMock(\Magento\Customer\Model\Address::class);
        $exportedAddressMock->expects($this->once())->method('getData')->willReturn($addressData);
        $entityMock->expects($this->once())->method('exportAddress')->willReturn($exportedAddressMock);

        $dataObjectMock->expects($this->once())->method('setId')->with($prefix . $registryItemId)->willReturnSelf();
        $dataObjectMock->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();
        $dataObjectMock->expects($this->once())->method('addData')->with($addressData)->willReturnSelf();

        $this->assertEquals($this->model, $this->model->execute($observerMock));
    }
}
