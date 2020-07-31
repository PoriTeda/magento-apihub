<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScalableInventory\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Unit test for Merger.
 */
class MergerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\ScalableInventory\Model\Counter\ItemsBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $itemsBuilder;

    /**
     * @var \Magento\Framework\MessageQueue\MergedMessageInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mergedMessageFactory;

    /**
     * @var \Magento\ScalableInventory\Model\Merger|\PHPUnit_Framework_MockObject_MockObject
     */
    private $merger;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->itemsBuilder = $this->getMockBuilder(\Magento\ScalableInventory\Model\Counter\ItemsBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mergedMessageFactory = $this
            ->getMockBuilder(\Magento\Framework\MessageQueue\MergedMessageInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->merger = $objectManagerHelper->getObject(
            \Magento\ScalableInventory\Model\Merger::class,
            [
                'itemsBuilder' => $this->itemsBuilder,
                'mergedMessageFactory' => $this->mergedMessageFactory
            ]
        );
    }

    /**
     * Test for merge().
     *
     * @return void
     */
    public function testMerge()
    {
        $topicName = 'topic';
        $messageId = 1;
        $operator = '-';
        $websiteId = 2;
        $productId = 3;
        $qty = 4;
        $messageItem = $this->getMockBuilder(\Magento\ScalableInventory\Api\Counter\ItemsInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQty', 'getProductId'])
            ->getMockForAbstractClass();
        $messageItem->expects($this->atLeastOnce())->method('getQty')->willReturn($qty);
        $messageItem->expects($this->atLeastOnce())->method('getProductId')->willReturn($productId);
        $message = $this->getMockBuilder(\Magento\ScalableInventory\Api\Counter\ItemsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $message->expects($this->atLeastOnce())->method('getItems')->willReturn([$messageItem]);
        $message->expects($this->atLeastOnce())->method('getOperator')->willReturn($operator);
        $message->expects($this->atLeastOnce())->method('getWebsiteId')->willReturn($websiteId);
        $mergedMessage = $this->getMockBuilder(\Magento\Framework\MessageQueue\MergedMessageInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->mergedMessageFactory->expects($this->atLeastOnce())->method('create')->willReturn($mergedMessage);
        $messages = [
            $topicName => [
                $messageId => $message
            ]
        ];
        $result = [
            $topicName => [$mergedMessage]
        ];

        $this->assertEquals($result, $this->merger->merge($messages));
    }
}
