<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Test\Unit\Observer;

class AddressDataBeforeSaveTest extends \PHPUnit\Framework\TestCase
{
    /**
     * GiftRegistry observer
     *
     * @var \Magento\GiftRegistry\Observer\AddressDataBeforeSave
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $helperMock;

    protected function setUp()
    {
        $this->helperMock = $this->createMock(\Magento\GiftRegistry\Helper\Data::class);

        $this->model = new \Magento\GiftRegistry\Observer\AddressDataBeforeSave($this->helperMock);
    }

    /**
     *
     * @dataProvider addressDataBeforeSaveDataProvider
     * @param string $addressId
     * @param int $expectedCalls
     * @param int $expectedResult
     */
    public function testAddressDataBeforeSave($addressId, $expectedCalls, $expectedResult)
    {
        $addressMockMethods = ['getCustomerAddressId', 'setGiftregistryItemId', '__wakeup'];
        $addressMock = $this->createPartialMock(\Magento\Quote\Model\Quote\Address::class, $addressMockMethods);
        $addressMock->expects($this->once())->method('getCustomerAddressId')->will($this->returnValue($addressId));
        $addressMock->expects($this->exactly($expectedCalls))->method('setGiftregistryItemId')->with($expectedResult);

        $event = new \Magento\Framework\DataObject();
        $event->setDataObject($addressMock);

        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($event));

        $this->helperMock->expects($this->any())->method('getAddressIdPrefix')->will($this->returnValue('gr_address_'));

        $this->model->execute($observerMock);
    }

    /**
     * @return array
     */
    public function addressDataBeforeSaveDataProvider()
    {
        return [
            [
                'addressId' => 'gr_address_2',
                'expectedCalls' => 1,
                'expectedResult' => 2,
            ],
            [
                'addressId' => 'gr_address_',
                'expectedCalls' => 0,
                'expectedResult' => ''
            ],
            [
                'addressId' => '2',
                'expectedCalls' => 0,
                'expectedResult' => ''
            ],
        ];
    }
}
