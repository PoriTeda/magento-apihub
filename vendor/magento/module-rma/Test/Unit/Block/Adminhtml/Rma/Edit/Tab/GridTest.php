<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Block\Adminhtml\Rma\Edit\Tab;

/**
 * Class GridTest
 */
class GridTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid
     */
    protected $grid;

    /**
     * @var \Magento\Rma\Model\Item|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $itemMock;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registryMock;

    /**
     * @var \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderMock;

    /**
     * @var \Magento\Sales\Model\Order\Item|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderItemMock;

    /**
     * Test setUp
     */
    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->itemMock = $this->createPartialMock(\Magento\Rma\Model\Item::class, ['getReturnableQty', '__wakeup']);
        $this->registryMock = $this->createMock(\Magento\Framework\Registry::class);
        $this->orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $this->orderItemMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Item::class,
            ['__wakeup', 'getId', 'getQtyShipped', 'getQtyReturned']
        );
        $this->registryMock->expects($this->exactly(2))
            ->method('registry')
            ->with($this->equalTo('current_order'))
            ->will($this->returnValue($this->orderMock));
        $this->orderMock->expects($this->once())
            ->method('getItemsCollection')
            ->will($this->returnValue([$this->orderItemMock]));
        $this->orderItemMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(15));
        $this->orderItemMock->expects($this->once())
            ->method('getQtyShipped')
            ->will($this->returnValue(1050));
        $this->orderItemMock->expects($this->once())
            ->method('getQtyReturned')
            ->will($this->returnValue(100500));
        $this->grid = $objectManager->getObject(
            \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid::class,
            [
                'coreRegistry' => $this->registryMock
            ]
        );
    }

    /**
     *  test method getRemainingQty
     */
    public function testGetRemainingQty()
    {
        $this->itemMock->expects($this->once())
            ->method('getReturnableQty')
            ->will($this->returnValue(100.50));
        $this->assertEquals(100.50, $this->grid->getRemainingQty($this->itemMock));
    }

    /**
     * test protected method _gatherOrderItemsData
     */
    public function testGatherOrderItemsData()
    {
        $expected = [15 => ['qty_shipped' => 1050, 'qty_returned' => 100500]];
        $this->assertEquals($expected, $this->grid->getOrderItemsData());
    }
}
