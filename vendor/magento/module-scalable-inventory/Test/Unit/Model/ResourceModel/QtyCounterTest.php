<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScalableInventory\Test\Unit\Model\ResourceModel;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\ScalableInventory\Model\ResourceModel\QtyCounter;

/**
 * Class QtyCounterTest
 */
class QtyCounterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\ScalableInventory\Model\Counter\ItemsBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $itemsBuilder;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $publisher;

    /**
     * @var \Magento\ScalableInventory\Model\ResourceModel\QtyCounter
     */
    private $resource;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->itemsBuilder = $this->getMockBuilder(\Magento\ScalableInventory\Model\Counter\ItemsBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->publisher = $this->getMockBuilder(\Magento\Framework\MessageQueue\PublisherPool::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resource = $objectManager->getObject(
            \Magento\ScalableInventory\Model\ResourceModel\QtyCounter::class,
            ['itemsBuilder' => $this->itemsBuilder, 'publisher' => $this->publisher]
        );
    }

    public function testCorrectItemsQty()
    {
        $items = [4 => 2, 23 => 12];
        $websiteId = 1;
        $operator = '-';

        $itemsObject = $this->getMockBuilder(\Magento\ScalableInventory\Api\Counter\ItemsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->itemsBuilder->expects($this->once())
            ->method('build')
            ->with($items, $websiteId, $operator)
            ->willReturn($itemsObject);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(QtyCounter::TOPIC_NAME, $itemsObject);

        $this->resource->correctItemsQty($items, $websiteId, $operator);
    }
}
