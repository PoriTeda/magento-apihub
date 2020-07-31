<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Test\Unit\Model\Invoice;

/**
 * Test class for \Magento\GiftWrapping\Model\Invoice\Giftwrapping
 */
class GiftWrappingTest extends \PHPUnit\Framework\TestCase
{
    public function testInvoiceItemWrapping()
    {
        $objectHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $model = $objectHelper->getObject(\Magento\GiftWrapping\Model\Total\Invoice\Giftwrapping::class, []);

        $invoice = $this->getMockBuilder(
            \Magento\Sales\Model\Order\Invoice::class
        )->disableOriginalConstructor()->setMethods(
            ['getAllItems', 'getOrder', '__wakeup', 'setGwItemsPrice', 'setGwItemsBasePrice']
        )->getMock();

        $item = new \Magento\Framework\DataObject();
        $orderItem = new \Magento\Framework\DataObject(['gw_id' => 1, 'gw_base_price' => 5, 'gw_price' => 10]);

        $item->setQty(2)->setOrderItem($orderItem);
        $order = new \Magento\Framework\DataObject();

        $invoice->expects($this->any())->method('getAllItems')->will($this->returnValue([$item]));
        $invoice->expects($this->any())->method('getOrder')->will($this->returnValue($order));
        $invoice->expects($this->once())->method('setGwItemsBasePrice')->with(10);
        $invoice->expects($this->once())->method('setGwItemsPrice')->with(20);

        $model->collect($invoice);
    }
}
