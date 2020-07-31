<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Test\Unit\Model\Plugin;

use Magento\GiftWrapping\Model\Plugin\MessageItemRepository as MessageItemRepositoryPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\GiftWrapping\Model\WrappingFactory;
use Magento\GiftWrapping\Helper\Data as DataHelper;
use Magento\GiftMessage\Api\ItemRepositoryInterface;
use Magento\GiftMessage\Api\Data\MessageInterface;
use Magento\GiftMessage\Api\Data\MessageExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\GiftWrapping\Model\Wrapping;

class MessageItemRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MessageItemRepositoryPlugin
     */
    private $plugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var CartRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteRepositoryMock;

    /**
     * @var WrappingFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $wrappingFactoryMock;

    /**
     * @var DataHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataHelperMock;

    /**
     * @var ItemRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectMock;

    /**
     * @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $giftMessageMock;

    /**
     * @var MessageExtensionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $extensionAttributesMock;

    /**
     * @var CartInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteMock;

    /**
     * @var CartItemInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteItemMock;

    /**
     * @var Wrapping|\PHPUnit_Framework_MockObject_MockObject
     */
    private $wrappingMock;

    protected function setUp()
    {
        $this->quoteRepositoryMock = $this->getMockBuilder(CartRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->wrappingFactoryMock = $this->getMockBuilder(WrappingFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->dataHelperMock = $this->getMockBuilder(DataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subjectMock = $this->getMockBuilder(ItemRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->giftMessageMock = $this->getMockBuilder(MessageInterface::class)
            ->getMockForAbstractClass();
        $this->extensionAttributesMock = $this->getMockBuilder(MessageExtensionInterface::class)
            ->setMethods(['getWrappingId'])
            ->getMockForAbstractClass();
        $this->quoteMock = $this->getMockBuilder(CartInterface::class)
            ->setMethods(['getItemById'])
            ->getMockForAbstractClass();
        $this->quoteItemMock = $this->getMockBuilder(CartItemInterface::class)
            ->setMethods(['setGwId', 'save'])
            ->getMockForAbstractClass();
        $this->wrappingMock = $this->getMockBuilder(Wrapping::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->plugin = $this->objectManagerHelper->getObject(
            MessageItemRepositoryPlugin::class,
            [
                'quoteRepository' => $this->quoteRepositoryMock,
                'wrappingFactory' => $this->wrappingFactoryMock,
                'dataHelper' => $this->dataHelperMock
            ]
        );
    }

    public function testAfterSave()
    {
        $cartId = 7;
        $itemId = 11;
        $wrappingId = 23;
        $loadedWrappingId = 37;

        $this->giftMessageMock->expects(static::atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);
        $this->dataHelperMock->expects(static::atLeastOnce())
            ->method('isGiftWrappingAvailableForItems')
            ->willReturn(true);
        $this->quoteRepositoryMock->expects(static::atLeastOnce())
            ->method('getActive')
            ->with($cartId, [])
            ->willReturn($this->quoteMock);
        $this->quoteMock->expects(static::atLeastOnce())
            ->method('getItemById')
            ->with($itemId)
            ->willReturn($this->quoteItemMock);
        $this->extensionAttributesMock->expects(static::atLeastOnce())
            ->method('getWrappingId')
            ->willReturn($wrappingId);
        $this->wrappingFactoryMock->expects(static::atLeastOnce())
            ->method('create')
            ->willReturn($this->wrappingMock);
        $this->wrappingMock->expects(static::atLeastOnce())
            ->method('load')
            ->with($wrappingId, null)
            ->willReturnSelf();
        $this->wrappingMock->expects(static::atLeastOnce())
            ->method('getId')
            ->willReturn($loadedWrappingId);
        $this->quoteItemMock->expects(static::atLeastOnce())
            ->method('setGwId')
            ->with($loadedWrappingId)
            ->willReturnSelf();
        $this->quoteItemMock->expects(static::once())
            ->method('save')
            ->willReturnSelf();

        $this->assertTrue($this->plugin->afterSave($this->subjectMock, true, $cartId, $this->giftMessageMock, $itemId));
    }

    public function testAfterSaveNoExtensionAttributes()
    {
        $this->giftMessageMock->expects(static::atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturn(null);
        $this->dataHelperMock->expects(static::any())
            ->method('isGiftWrappingAvailableForItems')
            ->willReturn(true);
        $this->quoteItemMock->expects(static::never())
            ->method('save');

        $this->assertTrue($this->plugin->afterSave($this->subjectMock, true, 7, $this->giftMessageMock, 11));
    }

    public function testAfterSaveGiftWrappingNotAvailable()
    {
        $this->giftMessageMock->expects(static::atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);
        $this->dataHelperMock->expects(static::atLeastOnce())
            ->method('isGiftWrappingAvailableForItems')
            ->willReturn(false);
        $this->quoteItemMock->expects(static::never())
            ->method('save');

        $this->assertTrue($this->plugin->afterSave($this->subjectMock, true, 7, $this->giftMessageMock, 11));
    }
}
