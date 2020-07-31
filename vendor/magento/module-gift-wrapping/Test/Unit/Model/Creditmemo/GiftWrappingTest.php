<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Test\Unit\Model\Creditmemo;

/**
 * Test class for \Magento\GiftWrapping\Model\Creditmemo\Giftwrapping
 */
class GiftWrappingTest extends \PHPUnit\Framework\TestCase
{
    public function testCreditmemoItemWrapping()
    {
        $objectHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $model = $objectHelper->getObject(\Magento\GiftWrapping\Model\Total\Creditmemo\Giftwrapping::class, []);

        $creditmemo = $this->getMockBuilder(
            \Magento\Sales\Model\Order\Creditmemo::class
        )->disableOriginalConstructor()->setMethods(
            ['getAllItems', 'getOrder', '__wakeup', 'setGwItemsBasePrice', 'setGwItemsPrice']
        )->getMock();

        $item = new \Magento\Framework\DataObject();
        $orderItem = new \Magento\Framework\DataObject(
            ['gw_id' => 1, 'gw_base_price_invoiced' => 5, 'gw_price_invoiced' => 10]
        );

        $item->setQty(2)->setOrderItem($orderItem);
        $order = new \Magento\Framework\DataObject();

        $creditmemo->expects($this->any())->method('getAllItems')->will($this->returnValue([$item]));
        $creditmemo->expects($this->any())->method('getOrder')->will($this->returnValue($order));
        $creditmemo->expects($this->once())->method('setGwItemsBasePrice')->with(10);
        $creditmemo->expects($this->once())->method('setGwItemsPrice')->with(20);

        $model->collect($creditmemo);
    }
}
