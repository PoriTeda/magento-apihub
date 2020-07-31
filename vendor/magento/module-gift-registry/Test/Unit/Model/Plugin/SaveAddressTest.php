<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Test\Unit\Model\Plugin;

class SaveAddressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftRegistry\Model\Plugin\SaveAddress
     */
    protected $model;

    /**
     * @var \Magento\GiftRegistry\Model\Entity|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityMock;

    /**
     * @var \Magento\Customer\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $session;

    /**
     * Prepare testable object
     */
    protected function setUp()
    {
        $this->session = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityMock = $this->getMockBuilder(\Magento\GiftRegistry\Model\Entity::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityFactoryMock = $this->getMockBuilder(\Magento\GiftRegistry\Model\EntityFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $entityFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->entityMock);

        /**
         * @var $entityFactoryMock \Magento\GiftRegistry\Model\EntityFactory
         */
        $this->model = new \Magento\GiftRegistry\Model\Plugin\SaveAddress(
            $entityFactoryMock,
            $this->session
        );
    }

    /**
     * @test
     */
    public function testBeforeSaveAddressInformation()
    {
        $giftRegistryId = 1;
        $customerId = 10;
        $exportAddressData = ['street' => 'Baker Street'];
        $cartId = 42;

        $subject = $this->createMock(\Magento\Checkout\Api\ShippingInformationManagementInterface::class);
        $addressInfoMock = $this->createMock(\Magento\Checkout\Api\Data\ShippingInformationInterface::class);
        $shippingAddressMock = $this->getMockForAbstractClass(
            \Magento\Quote\Api\Data\AddressInterface::class,
            [],
            '',
            false,
            false,
            false,
            ['importCustomerAddressData', 'setGiftregistryItemId']
        );
        $extensionAttributesMock = $this->getMockBuilder(\Magento\Quote\Api\Data\AddressExtensionInterface::class)
            ->setMethods(['getGiftRegistryId'])
            ->getMockForAbstractClass();

        $addressInfoMock->expects($this->once())->method('getShippingAddress')->willReturn($shippingAddressMock);
        $shippingAddressMock->expects($this->atLeastOnce())->method('getExtensionAttributes')
            ->willReturn($extensionAttributesMock);
        $extensionAttributesMock->expects($this->atLeastOnce())->method('getGiftRegistryId')
            ->willReturn($giftRegistryId);
        $this->entityMock->expects($this->once())->method('loadByEntityItem')->with($giftRegistryId)->willReturnSelf();
        $this->entityMock->expects($this->exactly(2))->method('getId')->willReturn($giftRegistryId);
        $this->session->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $shippingAddressMock->expects($this->once())->method('setCustomerAddressId')->with($customerId);
        $this->entityMock->expects($this->once())->method('exportAddressData')->willReturn($exportAddressData);
        $shippingAddressMock->expects($this->once())->method('importCustomerAddressData')->with($exportAddressData);
        $shippingAddressMock->expects($this->once())->method('setGiftregistryItemId')->with($giftRegistryId);

        $this->model->beforeSaveAddressInformation($subject, $cartId, $addressInfoMock);
    }
}
