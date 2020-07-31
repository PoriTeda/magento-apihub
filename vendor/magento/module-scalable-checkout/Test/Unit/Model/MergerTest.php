<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScalableCheckout\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Unit test for Merger.
 */
class MergerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\MessageQueue\MergedMessageInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mergedMessageFactory;

    /**
     * @var \Magento\ScalableCheckout\Model\Merger
     */
    private $merger;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->mergedMessageFactory = $this
            ->getMockBuilder(\Magento\Framework\MessageQueue\MergedMessageInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->merger = $objectManagerHelper->getObject(
            \Magento\ScalableCheckout\Model\Merger::class,
            [
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
        $message = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $messages = [
            $topicName => [
                $messageId => $message
            ]
        ];
        $mergedMessage = $this->getMockBuilder(\Magento\Framework\MessageQueue\MergedMessageInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->mergedMessageFactory->expects($this->atLeastOnce())->method('create')->willReturn($mergedMessage);
        $result = [
            $topicName => [$mergedMessage]
        ];

        $this->assertEquals($result, $this->merger->merge($messages));
    }
}
