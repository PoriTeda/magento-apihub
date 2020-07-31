<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Controller\Adminhtml\Rma;

class GetShippingItemsGridTest extends \Magento\Rma\Test\Unit\Controller\Adminhtml\RmaTest
{
    protected $name = 'GetShippingItemsGrid';

    public function testAction()
    {
        $response = 'testResponse';

        $block = $this->createMock(\Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shipping\Grid::class);
        $block->expects($this->once())
            ->method('toHtml')
            ->willReturn($response);

        $layoutMock = $this->createPartialMock(\Magento\Framework\View\Layout::class, ['renderLayout', 'getBlock']);
        $layoutMock->expects($this->once())
            ->method('getBlock')
            ->with('magento_rma_getshippingitemsgrid')
            ->will($this->returnValue($block));

        $this->viewMock->expects($this->once())
            ->method('getLayout')
            ->will($this->returnValue($layoutMock));

        $this->responseMock->expects($this->once())
            ->method('setBody')
            ->with($response);

        $this->action->execute();
    }
}
