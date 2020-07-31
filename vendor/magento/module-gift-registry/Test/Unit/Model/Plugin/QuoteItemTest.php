<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Test\Unit\Model\Plugin;

use Magento\GiftRegistry\Model\Plugin\QuoteItem as QuoteItemPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Quote\Model\Quote\Item\ToOrderItem as QuoteToOrderItem;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem as AbstractQuoteItem;
use Magento\Quote\Model\Quote\Address\Item as QuoteAddressItem;

class QuoteItemTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var QuoteItemPlugin
     */
    private $plugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var QuoteToOrderItem|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectMock;

    /**
     * @var OrderItemInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultMock;

    /**
     * @var AbstractQuoteItem[]|\PHPUnit_Framework_MockObject_MockObject[]
     */
    private $quoteItemMocks;

    protected function setUp()
    {
        $this->subjectMock = $this->getMockBuilder(QuoteToOrderItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultMock = $this->getMockBuilder(OrderItemInterface::class)
            ->setMethods(['setGiftregistryItemId'])
            ->getMockForAbstractClass();
        $this->quoteItemMocks = [
            AbstractQuoteItem::class => $this->getMockBuilder(AbstractQuoteItem::class)
                ->disableOriginalConstructor()
                ->setMethods(['getGiftregistryItemId'])
                ->getMockForAbstractClass(),
            QuoteAddressItem::class => $this->getMockBuilder(QuoteAddressItem::class)
                ->disableOriginalConstructor()
                ->setMethods(['getQuoteItem'])
                ->getMock()
        ];

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->plugin = $this->objectManagerHelper->getObject(QuoteItemPlugin::class);
    }

    /**
     * @param string $quoteItemType
     * @param int $getQuoteItemCalls
     *
     * @dataProvider afterConvertDataProvider
     */
    public function testAfterConvert($quoteItemType, $getQuoteItemCalls)
    {
        $registryItemId = 1;

        $this->setQuoteItemExpectations($registryItemId, $getQuoteItemCalls);
        $this->resultMock->expects(static::once())
            ->method('setGiftregistryItemId')
            ->with($registryItemId)
            ->willReturnSelf();

        $this->assertSame(
            $this->resultMock,
            $this->plugin->afterConvert(
                $this->subjectMock,
                $this->resultMock,
                $this->quoteItemMocks[$quoteItemType]
            )
        );
    }

    /**
     * @param string $quoteItemType
     * @param int $getQuoteItemCalls
     *
     * @dataProvider afterConvertDataProvider
     */
    public function testAfterConvertNotGiftRegistry($quoteItemType, $getQuoteItemCalls)
    {
        $this->setQuoteItemExpectations(null, $getQuoteItemCalls);
        $this->resultMock->expects(static::never())
            ->method('setGiftregistryItemId');

        $this->assertSame(
            $this->resultMock,
            $this->plugin->afterConvert(
                $this->subjectMock,
                $this->resultMock,
                $this->quoteItemMocks[$quoteItemType]
            )
        );
    }

    /**
     * @return array
     */
    public function afterConvertDataProvider()
    {
        return [
            ['quoteItemType' => AbstractQuoteItem::class, 'getQuoteItemCalls' => 0],
            ['quoteItemType' => QuoteAddressItem::class, 'getQuoteItemCalls' => 1]
        ];
    }

    /**
     * Set quote item expectations
     *
     * @param int|null $registryItemId
     * @param int $getQuoteItemCalls
     * @return void
     */
    private function setQuoteItemExpectations($registryItemId, $getQuoteItemCalls)
    {
        $this->quoteItemMocks[QuoteAddressItem::class]->expects(static::exactly($getQuoteItemCalls))
            ->method('getQuoteItem')
            ->willReturn($this->quoteItemMocks[AbstractQuoteItem::class]);
        $this->quoteItemMocks[AbstractQuoteItem::class]->expects(static::atLeastOnce())
            ->method('getGiftregistryItemId')
            ->willReturn($registryItemId);
    }
}
