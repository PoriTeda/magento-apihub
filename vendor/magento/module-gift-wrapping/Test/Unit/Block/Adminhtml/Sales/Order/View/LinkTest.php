<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Test\Unit\Block\Adminhtml\Sales\Order\View;

class LinkTest extends \PHPUnit\Framework\TestCase
{
    public function testCanDisplayGiftWrappingForItem()
    {
        $giftWrappingData = $this->createPartialMock(
            \Magento\GiftWrapping\Helper\Data::class,
            ['isGiftWrappingAvailableForItems']
        );
        $giftWrappingData->expects($this->once())
            ->method('isGiftWrappingAvailableForItems')
            ->with($this->equalTo(1))
            ->will($this->returnValue(true));

        $typeInstance = $this->createMock(\Magento\Catalog\Model\Product\Type\Simple::class);

        $product = $this->createPartialMock(
            \Magento\Catalog\Model\Product::class,
            ['getTypeInstance', 'getGiftWrappingAvailable', '__wakeup']
        );
        $product->expects($this->once())->method('getTypeInstance')->will($this->returnValue($typeInstance));
        $product->expects($this->once())->method('getGiftWrappingAvailable')->will($this->returnValue(null));

        $orderItem = $this->createPartialMock(
            \Magento\Quote\Model\Quote\Item::class,
            ['getProduct', 'getStoreId', '__wakeup']
        );
        $orderItem->expects($this->once())->method('getProduct')->will($this->returnValue($product));
        $orderItem->expects($this->once())->method('getStoreId')->will($this->returnValue(1));

        $block1 = $this->createPartialMock(
            \Magento\GiftMessage\Block\Adminhtml\Sales\Order\Create\Giftoptions::class,
            ['getItem']
        );
        $block1->expects($this->any())->method('getItem')->will($this->returnValue($orderItem));

        $layout = $this->createPartialMock(
            \Magento\Framework\View\Layout::class,
            ['getParentName', 'getBlock']
        );
        $layout->expects($this->any())
            ->method('getParentName')
            ->with($this->equalTo('nameInLayout'))
            ->will($this->returnValue('parentName'));
        $layout->expects($this->any())
            ->method('getBlock')
            ->with($this->equalTo('parentName'))
            ->will($this->returnValue($block1));

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $context = $objectManager->getObject(\Magento\Backend\Block\Template\Context::class, ['layout' => $layout]);

        /** @var \Magento\GiftWrapping\Block\Adminhtml\Sales\Order\Create\Link $websiteModel */
        $block = $objectManager->getObject(
            \Magento\GiftWrapping\Block\Adminhtml\Sales\Order\Create\Link::class,
            ['context' => $context, 'giftWrappingData' => $giftWrappingData]
        );
        $block->setNameInLayout('nameInLayout');

        $this->assertTrue($block->canDisplayGiftWrappingForItem());
    }
}
