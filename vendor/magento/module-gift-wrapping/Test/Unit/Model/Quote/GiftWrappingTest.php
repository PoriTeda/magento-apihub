<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Test\Unit\Model\Quote;

use Magento\Quote\Model\Quote\Address;
use Magento\GiftWrapping\Model\Total\Quote\Giftwrapping;

/**
 * Test class for \Magento\GiftWrapping\Model\Quote\Giftwrapping
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GiftWrappingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\GiftWrapping\Model\Wrapping
     */
    protected $wrappingMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Address
     */
    protected $addressMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * Test for collect method
     *
     * @param bool $withProduct
     * @dataProvider collectQuoteDataProvider
     */
    public function testCollectQuote($withProduct)
    {
        $shippingAssignmentMock = $this->_prepareData();
        $helperMock = $this->createMock(\Magento\GiftWrapping\Helper\Data::class);
        $factoryMock = $this->createPartialMock(\Magento\GiftWrapping\Model\WrappingFactory::class, ['create']);
        $factoryMock->expects($this->any())->method('create')->will($this->returnValue($this->wrappingMock));

        $model = new Giftwrapping($helperMock, $factoryMock, $this->priceCurrency);
        $item = new \Magento\Framework\DataObject();

        $product = $this->createPartialMock(\Magento\Catalog\Model\Product::class, ['isVirtual', '__wakeup']);
        $product->expects($this->any())->method('isVirtual')->willReturn(false);
        if ($withProduct) {
            $product->setGiftWrappingPrice(10);
        } else {
            $product->setGiftWrappingPrice(0);
            $item->setWrapping($this->wrappingMock);
        }
        $item->setProduct($product)->setQty(2)->setGwId(1);

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $shippingAssignmentMock->expects($this->any())->method('getItems')->willReturn([$item]);

        $quoteMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            [
                'setGwItemsBasePrice',
                'getStore',
                'setGwItemsPrice',
                'setGwBasePrice',
                'setGwPrice',
                'setGwCardBasePrice',
                'setGwCardPrice',
                'getGwItemsBasePrice',
                'getGwItemsPrice',
                'getGwBasePrice',
                'getGwPrice',
                'getGwCardBasePrice',
                'getGwCardPrice'
            ]
        );
        $quoteMock->expects($this->atLeastOnce())->method('setGwItemsBasePrice')->willReturnSelf();
        $quoteMock->expects($this->atLeastOnce())->method('setGwItemsPrice')->willReturnSelf();
        $quoteMock->expects($this->atLeastOnce())->method('setGwBasePrice')->willReturnSelf();
        $quoteMock->expects($this->atLeastOnce())->method('setGwPrice')->willReturnSelf();
        $quoteMock->expects($this->atLeastOnce())->method('setGwCardBasePrice')->willReturnSelf();
        $quoteMock->expects($this->atLeastOnce())->method('setGwCardPrice')->willReturnSelf();
        $quoteMock->expects($this->atLeastOnce())->method('getGwItemsBasePrice');
        $quoteMock->expects($this->atLeastOnce())->method('getGwItemsPrice');
        $quoteMock->expects($this->atLeastOnce())->method('getGwBasePrice');
        $quoteMock->expects($this->atLeastOnce())->method('getGwPrice');
        $quoteMock->expects($this->atLeastOnce())->method('getGwCardBasePrice');
        $quoteMock->expects($this->atLeastOnce())->method('getGwCardPrice');
        $quoteMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $totalMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote\Address\Total::class,
            [
                'setBaseGrandTotal',
                'getBaseGrandTotal',
                'getGwItemsBasePrice',
                'getGwBasePrice',
                'getGwCardBasePrice',
                'getGrandTotal',
                'getGwItemsPrice',
                'getGwPrice',
                'getGwCardPrice',
                'setGwItemsBasePrice',
                'setGwItemsPrice',
                'setGwItemIds',
                'setGwCardBasePrice',
                'setGwCardPrice',
                'setGwAddCard'
            ]
        );
        $totalMock->expects($this->atLeastOnce())->method('setBaseGrandTotal');
        $totalMock->expects($this->atLeastOnce())->method('getBaseGrandTotal');
        $totalMock->expects($this->atLeastOnce())->method('getGwItemsBasePrice');
        $totalMock->expects($this->atLeastOnce())->method('getGwBasePrice');
        $totalMock->expects($this->atLeastOnce())->method('getGwCardBasePrice');
        $totalMock->expects($this->atLeastOnce())->method('getGrandTotal');
        $totalMock->expects($this->atLeastOnce())->method('getGwItemsPrice');
        $totalMock->expects($this->atLeastOnce())->method('getGwPrice');
        $totalMock->expects($this->atLeastOnce())->method('getGwCardPrice');
        $totalMock->expects($this->atLeastOnce())->method('setGwItemsBasePrice');
        $totalMock->expects($this->atLeastOnce())->method('setGwItemsPrice');
        $totalMock->expects($this->atLeastOnce())->method('setGwItemIds');
        $totalMock->expects($this->atLeastOnce())->method('setGwCardBasePrice');
        $totalMock->expects($this->atLeastOnce())->method('setGwCardPrice');
        $totalMock->expects($this->atLeastOnce())->method('setGwAddCard');

        $model->collect($quoteMock, $shippingAssignmentMock, $totalMock);
    }

    /**
     * Prepare mocks for test
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function _prepareData()
    {

        $this->wrappingMock = $this->createPartialMock(
            \Magento\GiftWrapping\Model\Wrapping::class,
            ['load', 'setStoreId', 'getBasePrice', '__wakeup']
        );
        $this->addressMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote\Address::class,
            [
                'getAddressType',
                'getQuote',
                'getAllItems',
                '__wakeup'
            ]
        );

        $this->priceCurrency = $this->getMockBuilder(
            \Magento\Framework\Pricing\PriceCurrencyInterface::class
        )->getMock();
        $this->priceCurrency->expects($this->any())->method('convert')->will($this->returnValue(10));

        $this->wrappingMock->expects($this->any())->method('load')->will($this->returnSelf());
        $this->wrappingMock->expects($this->any())->method('getBasePrice')->will($this->returnValue(6));
        $this->addressMock->expects($this->any())->method('getAddressType')->willReturn(Address::TYPE_SHIPPING);

        $shippingAssignmentMock = $this->createMock(\Magento\Quote\Api\Data\ShippingAssignmentInterface::class);

        $shippingMock = $this->createMock(\Magento\Quote\Api\Data\ShippingInterface::class);
        $shippingAssignmentMock->expects($this->once())->method('getShipping')->willReturn($shippingMock);
        $shippingMock->expects($this->once())->method('getAddress')->willReturn($this->addressMock);

        return $shippingAssignmentMock;
    }

    /**
     * Data provider for testCollectQuote
     *
     * @return array
     */
    public function collectQuoteDataProvider()
    {
        return [
            'withProduct' => [true],
            'withoutProduct' => [false]
        ];
    }
}
