<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Test\Unit\Model\Plugin\Quote;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GoogleTagManager\Helper\Data as DataHelper;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote as Quote;
use Magento\Framework\Registry;
use Magento\GoogleTagManager\Model\Plugin\Quote\SetGoogleAnalyticsOnCartRemove;

class SetGoogleAnalyticsOnCartRemoveTest extends \PHPUnit\Framework\TestCase
{
    /** @var SetGoogleAnalyticsOnCartRemove */
    private $model;

    /** @var QuoteItem|\PHPUnit_Framework_MockObject_MockObject */
    private $quoteItem;

    /** @var Quote|\PHPUnit_Framework_MockObject_MockObject */
    private $quote;

    /** @var ObjectManagerHelper */
    private $objectManagerHelper;

    /**
     * @var DataHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $helper;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    protected function setUp()
    {
        $this->helper = $this->getMockBuilder(DataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteItem = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQty'])
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItemById'])
            ->getMock();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->setMethods(['register', 'unregister'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->model = $this->objectManagerHelper->getObject(
            SetGoogleAnalyticsOnCartRemove::class,
            [
                'helper' => $this->helper,
                'registry' => $this->registry
            ]
        );
    }

    /**
     * @param int $callCount
     * @param int $getQtyCallCount
     * @param array $quoteItemQtys
     * @param bool $quoteHasItem
     *
     * @dataProvider updateItemDataProvider
     */
    public function testAroundUpdateItem(
        $callCount,
        $getQtyCallCount,
        $quoteItemQtys,
        $quoteHasItem = true
    ) {
        $params = null;
        $buyRequest = false;
        $itemId = 1;

        $proceed = function () use ($itemId, $buyRequest, $params) {
            return $this->quoteItem;
        };

        $this->quoteItem->expects($this->exactly($getQtyCallCount))
            ->method('getQty')
            ->will($this->onConsecutiveCalls($quoteItemQtys[0], $quoteItemQtys[1]));

        $this->quote->expects($this->once())
            ->method('getItemById')
            ->willReturn($quoteHasItem ? $this->quoteItem : false);

        $this->helper->expects($this->exactly($callCount))
            ->method('isTagManagerAvailable')
            ->willReturn(true);

        $this->registry->expects($this->exactly($callCount))
            ->method('unregister')
            ->with('GoogleTagManager_products_to_remove');

        $this->registry->expects($this->exactly($callCount))
            ->method('register')
            ->with('GoogleTagManager_products_to_remove');

        $this->model->aroundUpdateItem($this->quote, $proceed, $itemId, $buyRequest, $params);
    }

    /**
     * @return array
     */
    public function updateItemDataProvider()
    {
        return [
            'ItemWithQuoteItemQtyMoreThanQuoteQty' => [0, 2, ['2', '3']],
            'ItemWithQuoteItemQtyLessThanQuoteQty' => [1, 3, ['2', '1']],
            'QuoteHasNoItem' => [0, 1, ['2', '2'], false]
        ];
    }

    /**
     * @param $callCount
     * @param bool $quoteHasItem
     *
     * @dataProvider removeItemDataProvider
     */
    public function testAfterRemoveItemWithQuoteItemQtyMoreThanQuoteQty($callCount, $quoteHasItem = true)
    {
        $this->quoteItem->expects($this->exactly($callCount))
            ->method('getQty')
            ->willReturn(1);

        $this->quote->expects($this->once())
            ->method('getItemById')
            ->willReturn($quoteHasItem ? $this->quoteItem : false);

        $this->helper->expects($this->exactly($callCount))
            ->method('isTagManagerAvailable')
            ->willReturn(true);

        $this->registry->expects($this->exactly($callCount))
            ->method('unregister')
            ->with('GoogleTagManager_products_to_remove');

        $this->registry->expects($this->exactly($callCount))
            ->method('register')
            ->with('GoogleTagManager_products_to_remove');

        $this->model->afterRemoveItem($this->quote, $this->quoteItem, 1);
    }

    /**
     * @return array
     */
    public function removeItemDataProvider()
    {
        return [
            'QuoteHasItem' => [1],
            'QuoteHasNoItem' => [0, false]
        ];
    }
}
