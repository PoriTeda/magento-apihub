<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Test\Unit\Model\Plugin;

use Magento\GiftRegistry\Model\Plugin\ConvertQuoteAddressToOrderAddress as ConvertQuoteAddressToOrderAddressPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Quote\Model\Quote\Address\ToOrderAddress as QuoteToOrderAddress;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Quote\Model\Quote\Address as QuoteAddress;

class ConvertQuoteAddressToOrderAddressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConvertQuoteAddressToOrderAddressPlugin
     */
    private $plugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var QuoteToOrderAddress|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectMock;

    /**
     * @var OrderAddressInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultMock;

    /**
     * @var QuoteAddress|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteAddressMock;

    protected function setUp()
    {
        $this->subjectMock = $this->getMockBuilder(QuoteToOrderAddress::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultMock = $this->getMockBuilder(OrderAddressInterface::class)
            ->setMethods(['setGiftregistryItemId'])
            ->getMockForAbstractClass();
        $this->quoteAddressMock = $this->getMockBuilder(QuoteAddress::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGiftregistryItemId'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->plugin = $this->objectManagerHelper->getObject(
            ConvertQuoteAddressToOrderAddressPlugin::class,
            [
                'subject' => $this->subjectMock,
                'result' => $this->resultMock,
                'quoteAddress' => $this->quoteAddressMock
            ]
        );
    }

    public function testAfterConvert()
    {
        $giftRegistryId = 100;

        $this->quoteAddressMock->expects(static::atLeastOnce())
            ->method('getGiftregistryItemId')
            ->willReturn($giftRegistryId);
        $this->resultMock->expects(static::once())
            ->method('setGiftregistryItemId')
            ->with($giftRegistryId)
            ->willReturnSelf();

        $this->assertSame(
            $this->resultMock,
            $this->plugin->afterConvert($this->subjectMock, $this->resultMock, $this->quoteAddressMock)
        );
    }

    public function testAfterConvertNotGiftRegistry()
    {
        $this->quoteAddressMock->expects(static::once())
            ->method('getGiftregistryItemId')
            ->willReturn(null);
        $this->resultMock->expects(static::never())
            ->method('setGiftregistryItemId');

        $this->assertSame(
            $this->resultMock,
            $this->plugin->afterConvert($this->subjectMock, $this->resultMock, $this->quoteAddressMock)
        );
    }
}
