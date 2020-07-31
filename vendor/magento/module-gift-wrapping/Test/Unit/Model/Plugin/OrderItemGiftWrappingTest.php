<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Test\Unit\Model\Plugin;

use Magento\Sales\Api\Data\OrderItemExtension;
use Magento\Sales\Api\Data\OrderItemExtensionFactory;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\GiftWrapping\Model\Plugin\OrderItemGiftWrapping;

class OrderItemGiftWrappingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var OrderItemGiftWrapping
     */
    private $plugin;

    /**
     * @var OrderRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectMock;

    /**
     * @var OrderItemInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderItemMock;

    /**
     * @var Order|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    /**
     * @var OrderItemExtension|\PHPUnit_Framework_MockObject_MockObject
     */
    private $extensionAttributeMock;

    /**
     * @var OrderItemExtensionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderItemExtensionFactoryMock;

    protected function setUp()
    {
        $this->subjectMock = $this->getMockForAbstractClass(OrderRepositoryInterface::class);
        $this->orderItemMock = $this->getMockBuilder(OrderItemInterface::class)
            ->setMethods([
                'getGwId',
                'getGwBasePrice',
                'getGwPrice',
                'getGwBaseTaxAmount',
                'getGwTaxAmount',
                'getGwBasePriceInvoiced',
                'getGwPriceInvoiced',
                'getGwBaseTaxAmountInvoiced',
                'getGwTaxAmountInvoiced',
                'getGwBasePriceRefunded',
                'getGwPriceRefunded',
                'getGwBaseTaxAmountRefunded',
                'getGwTaxAmountRefunded',
                'getExtensionAttributes',
                'setExtensionAttributes',
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->extensionAttributeMock = $this->getMockBuilder(OrderItemExtension::class)
            ->setMethods([
                'setGwId',
                'setGwBasePrice',
                'setGwPrice',
                'setGwBaseTaxAmount',
                'setGwTaxAmount',
                'setGwBasePriceInvoiced',
                'setGwPriceInvoiced',
                'setGwBaseTaxAmountInvoiced',
                'setGwTaxAmountInvoiced',
                'setGwBasePriceRefunded',
                'setGwPriceRefunded',
                'setGwBaseTaxAmountRefunded',
                'setGwTaxAmountRefunded',
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderItemExtensionFactoryMock = $this->getMockBuilder(OrderItemExtensionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new OrderItemGiftWrapping(
            $this->orderItemExtensionFactoryMock
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAfterGet()
    {
        $returnValue = 10;

        $this->orderItemExtensionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->extensionAttributeMock);

        $this->orderMock->expects(static::any())
            ->method('getItems')
            ->willReturn([$this->orderItemMock]);

        $this->orderItemMock->expects(static::once())
            ->method('getExtensionAttributes')
            ->willReturn(null);

        $this->orderItemMock->expects(static::once())
            ->method('getGwId')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwId')
            ->with($returnValue)
            ->willReturnSelf();
        $this->orderItemMock->expects(static::once())
            ->method('getGwBasePrice')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwBasePrice')
            ->with($returnValue)
            ->willReturnSelf();
        $this->orderItemMock->expects(static::once())
            ->method('getGwPrice')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwPrice')
            ->with($returnValue)
            ->willReturnSelf();
        $this->orderItemMock->expects(static::once())
            ->method('getGwBaseTaxAmount')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwBaseTaxAmount')
            ->with($returnValue)
            ->willReturnSelf();
        $this->orderItemMock->expects(static::once())
            ->method('getGwTaxAmount')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwTaxAmount')
            ->with($returnValue)
            ->willReturnSelf();
        $this->orderItemMock->expects(static::once())
            ->method('getGwBasePriceInvoiced')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwBasePriceInvoiced')
            ->with($returnValue)
            ->willReturnSelf();
        $this->orderItemMock->expects(static::once())
            ->method('getGwPriceInvoiced')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwPriceInvoiced')
            ->with($returnValue)
            ->willReturnSelf();
        $this->orderItemMock->expects(static::once())
            ->method('getGwBaseTaxAmountInvoiced')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwBaseTaxAmountInvoiced')
            ->with($returnValue)
            ->willReturnSelf();
        $this->orderItemMock->expects(static::once())
            ->method('getGwTaxAmountInvoiced')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwTaxAmountInvoiced')
            ->with($returnValue)
            ->willReturnSelf();
        $this->orderItemMock->expects(static::once())
            ->method('getGwBasePriceRefunded')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwBasePriceRefunded')
            ->with($returnValue)
            ->willReturnSelf();
        $this->orderItemMock->expects(static::once())
            ->method('getGwPriceRefunded')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwPriceRefunded')
            ->with($returnValue)
            ->willReturnSelf();
        $this->orderItemMock->expects(static::once())
            ->method('getGwBaseTaxAmountRefunded')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwBaseTaxAmountRefunded')
            ->with($returnValue)
            ->willReturnSelf();
        $this->orderItemMock->expects(static::once())
            ->method('getGwTaxAmountRefunded')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwTaxAmountRefunded')
            ->with($returnValue)
            ->willReturnSelf();

        $this->orderItemMock->expects(static::once())
            ->method('setExtensionAttributes')
            ->with($this->extensionAttributeMock)
            ->willReturnSelf();

        $this->plugin->afterGet($this->subjectMock, $this->orderMock);
    }
}
