<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedCheckout\Test\Unit\Model\Observer;

class CollectTotalsFailedItemsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CollectTotalsFailedItems
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $cartMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $itemProcessorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $observerMock;

    protected function setUp()
    {
        $this->cartMock = $this->createMock(\Magento\AdvancedCheckout\Model\Cart::class);
        $this->itemProcessorMock =
            $this->createMock(\Magento\AdvancedCheckout\Model\FailedItemProcessor::class);
        $this->observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);

        $this->model = new \Magento\AdvancedCheckout\Model\Observer\CollectTotalsFailedItems(
            $this->cartMock,
            $this->itemProcessorMock
        );
    }

    public function testExecuteWithEmptyAffectedItems()
    {
        $this->cartMock->expects($this->once())->method('getFailedItems')->will($this->returnValue([]));
        $this->itemProcessorMock->expects($this->never())->method('process');

        $this->model->execute($this->observerMock);
    }

    public function testExecuteWithNonEmptyAffectedItems()
    {
        $this->cartMock->expects($this->once())->method('getFailedItems')->will($this->returnValue(['not empty']));
        $this->itemProcessorMock->expects($this->once())->method('process');

        $this->model->execute($this->observerMock);
    }
}
