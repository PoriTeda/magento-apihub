<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Test\Unit\Model\Creditmemo\Tax;

/**
 * Test class for \Magento\GiftWrapping\Model\Creditmemo\Tax\Giftwrapping
 */
class GiftWrappingTest extends \PHPUnit\Framework\TestCase
{
    public function testCreditmemoItemTaxWrapping()
    {
        $objectHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $model = $objectHelper->getObject(\Magento\GiftWrapping\Model\Total\Creditmemo\Tax\Giftwrapping::class, []);

        $creditmemo = $this->getMockBuilder(
            \Magento\Sales\Model\Order\Creditmemo::class
        )->disableOriginalConstructor()->setMethods(
            ['getAllItems', 'getOrder', '__wakeup', 'setGwItemsBaseTaxAmount', 'setGwItemsTaxAmount']
        )->getMock();

        $item = new \Magento\Framework\DataObject();
        $orderItem = new \Magento\Framework\DataObject(
            ['gw_id' => 1, 'gw_base_tax_amount_invoiced' => 5, 'gw_tax_amount_invoiced' => 10]
        );

        $item->setQty(2)->setOrderItem($orderItem);
        $order = new \Magento\Framework\DataObject();

        $creditmemo->expects($this->any())->method('getAllItems')->will($this->returnValue([$item]));
        $creditmemo->expects($this->any())->method('getOrder')->will($this->returnValue($order));
        $creditmemo->expects($this->once())->method('setGwItemsBaseTaxAmount')->with(10);
        $creditmemo->expects($this->once())->method('setGwItemsTaxAmount')->with(20);

        $model->collect($creditmemo);
    }
}
