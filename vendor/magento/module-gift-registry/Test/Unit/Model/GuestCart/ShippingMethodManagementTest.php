<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Test\Unit\Model\GuestCart;

use Magento\GiftRegistry\Model\GuestCart\ShippingMethodManagement;

class ShippingMethodManagementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftRegistry\Model\GuestCart\ShippingMethodManagement
     */
    private $model;

    /**
     * Shipping method management
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $methodManagementMock;

    /**
     * Quote ID mask factory
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $idMaskFactoryMock;

    protected function setUp()
    {
        $this->idMaskFactoryMock = $this->createPartialMock(
            \Magento\Quote\Model\QuoteIdMaskFactory::class,
            ['create', '__wakeup']
        );
        $this->addressFactoryMock = $this->createMock(\Magento\Quote\Api\Data\EstimateAddressInterfaceFactory::class);
        $this->methodManagementMock = $this->createMock(
            \Magento\GiftRegistry\Api\ShippingMethodManagementInterface::class
        );
        $this->model = new ShippingMethodManagement(
            $this->methodManagementMock,
            $this->idMaskFactoryMock
        );
    }

    /**
     * @covers \Magento\GiftRegistry\Model\GuestCart\ShippingMethodManagement::estimateByRegistryId
     */
    public function testEstimateByRegistryId()
    {
        $cartId = 1;
        $maskedCartId = '8909fa89ced';
        $giftRegistryId = 1;

        $quoteIdMask = $this->createPartialMock(
            \Magento\Quote\Model\QuoteIdMask::class,
            ['load', 'getQuoteId', '__wakeup']
        );
        $quoteIdMask->expects($this->any())->method('getQuoteId')->willReturn($cartId);
        $quoteIdMask->expects($this->any())->method('load')->with($maskedCartId, 'masked_id')->willReturnSelf();

        $this->idMaskFactoryMock->expects($this->once())->method('create')->willReturn($quoteIdMask);

        $this->methodManagementMock->expects($this->once())
            ->method('estimateByRegistryId')
            ->with($cartId, $giftRegistryId);

        $this->model->estimateByRegistryId($maskedCartId, $giftRegistryId);
    }
}
