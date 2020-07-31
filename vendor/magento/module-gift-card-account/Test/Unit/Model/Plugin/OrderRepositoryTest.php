<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Test\Unit\Model\Plugin;

use Magento\GiftCardAccount\Model\Plugin\OrderRepository;
use Magento\Sales\Api\Data\OrderExtension;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\GiftCardAccount\Model\GiftCardFactory;
use Magento\GiftCardAccount\Model\GiftCard;
use Magento\GiftCardAccount\Model\Giftcardaccount;

/**
 * Unit test for Order repository plugin.
 */
class OrderRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var OrderRepository
     */
    private $plugin;

    /**
     * @var OrderRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectMock;

    /**
     * @var OrderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    /**
     * @var GiftCardFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $giftCardFactoryMock;

    /**
     * @var GiftCard|\PHPUnit_Framework_MockObject_MockObject
     */
    private $giftCardMock;

    /**
     * @var array
     */
    private $giftCards = [
        [
        Giftcardaccount::ID => 1,
        Giftcardaccount::CODE => 'TESTCODE',
        Giftcardaccount::AMOUNT => 10,
        Giftcardaccount::BASE_AMOUNT => 10,
        ]
    ];

    /**
     * @var float
     */
    private $giftCardsAmount = 100;

    /**
     * @var float
     */
    private $giftCardsInvoiced = 95;

    /**
     * @var float
     */
    private $giftCardsRefunded = 90;

    /**
     * @var float
     */
    private $baseGiftCardsAmount = 85;

    /**
     * @var float
     */
    private $baseGiftCardsInvoiced = 80;

    /**
     * @var float
     */
    private $baseGiftCardsRefunded = 75;

    /**
     * @var OrderExtension|\PHPUnit_Framework_MockObject_MockObject
     */
    private $extensionAttributeMock;

    /**
     * @var OrderSearchResultInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderSearchResultMock;

    /**
     * @var OrderExtensionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderExtensionFactoryMock;

    protected function setUp()
    {
        $this->subjectMock = $this->getMockForAbstractClass(
            OrderRepositoryInterface::class
        );

        $this->orderMock = $this->getMockBuilder(OrderInterface::class)
            ->setMethods([
                'getExtensionAttributes',
                'setExtensionAttributes',
                'getGiftCards',
                'setGiftCards',
                'getBaseGiftCardsAmount',
                'setBaseGiftCardsAmount',
                'getGiftCardsAmount',
                'setGiftCardsAmount',
                'getBaseGiftCardsInvoiced',
                'setBaseGiftCardsInvoiced',
                'getGiftCardsInvoiced',
                'setGiftCardsInvoiced',
                'getBaseGiftCardsRefunded',
                'setBaseGiftCardsRefunded',
                'getGiftCardsRefunded',
                'setGiftCardsRefunded'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->extensionAttributeMock = $this->getMockBuilder(OrderExtension::class)
            ->setMethods([
                'getGiftCards',
                'setGiftCards',
                'getBaseGiftCardsAmount',
                'setBaseGiftCardsAmount',
                'getGiftCardsAmount',
                'setGiftCardsAmount',
                'getBaseGiftCardsInvoiced',
                'setBaseGiftCardsInvoiced',
                'getGiftCardsInvoiced',
                'setGiftCardsInvoiced',
                'getBaseGiftCardsRefunded',
                'setBaseGiftCardsRefunded',
                'getGiftCardsRefunded',
                'setGiftCardsRefunded'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderSearchResultMock = $this->getMockForAbstractClass(
            OrderSearchResultInterface::class
        );

        $this->orderExtensionFactoryMock = $this->getMockBuilder(OrderExtensionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->giftCardFactoryMock = $this->getMockBuilder(GiftCardFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->giftCardMock = $this->getMockBuilder(GiftCard::class)
            ->disableOriginalConstructor()
            ->getMock();

        $serializer = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Json::class)
            ->setMethods(['unserialize'])
            ->getMock();
        $serializer->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        $this->plugin = new OrderRepository(
            $this->orderExtensionFactoryMock,
            $this->giftCardFactoryMock,
            $serializer
        );
    }

    public function testAfterGet()
    {
        $this->orderMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);
        $this->orderMock->expects($this->atLeastOnce())
            ->method('getGiftCards')
            ->willReturn(json_encode($this->giftCards));
        $this->orderMock->expects($this->once())
            ->method('getGiftCardsAmount')
            ->willReturn($this->giftCardsAmount);
        $this->orderMock->expects($this->once())
            ->method('getBaseGiftCardsAmount')
            ->willReturn($this->baseGiftCardsAmount);
        $this->orderMock->expects($this->once())
            ->method('getGiftCardsInvoiced')
            ->willReturn($this->giftCardsInvoiced);
        $this->orderMock->expects($this->once())
            ->method('getBaseGiftCardsInvoiced')
            ->willReturn($this->baseGiftCardsInvoiced);
        $this->orderMock->expects($this->once())
            ->method('getGiftCardsRefunded')
            ->willReturn($this->giftCardsRefunded);
        $this->orderMock->expects($this->once())
            ->method('getBaseGiftCardsRefunded')
            ->willReturn($this->baseGiftCardsRefunded);

        $this->giftCardFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->giftCardMock);

        $this->giftCardMock->expects($this->once())
            ->method('setId')
            ->willReturnSelf();
        $this->giftCardMock->expects($this->once())
            ->method('setCode')
            ->willReturnSelf();
        $this->giftCardMock->expects($this->once())
            ->method('setAmount')
            ->willReturnSelf();
        $this->giftCardMock->expects($this->once())
            ->method('setBaseAmount')
            ->willReturnSelf();

        $this->extensionAttributeMock->expects($this->once())
            ->method('setGiftCards')
            ->with([$this->giftCardMock])
            ->willReturnSelf();
        $this->extensionAttributeMock->expects($this->once())
            ->method('setGiftCardsAmount')
            ->with($this->giftCardsAmount)
            ->willReturnSelf();
        $this->extensionAttributeMock->expects($this->once())
            ->method('setBaseGiftCardsAmount')
            ->with($this->baseGiftCardsAmount)
            ->willReturnSelf();
        $this->extensionAttributeMock->expects($this->once())
            ->method('setGiftCardsInvoiced')
            ->with($this->giftCardsInvoiced)
            ->willReturnSelf();
        $this->extensionAttributeMock->expects($this->once())
            ->method('setBaseGiftCardsInvoiced')
            ->with($this->baseGiftCardsInvoiced)
            ->willReturnSelf();
        $this->extensionAttributeMock->expects($this->once())
            ->method('setGiftCardsRefunded')
            ->with($this->giftCardsRefunded)
            ->willReturnSelf();
        $this->extensionAttributeMock->expects($this->once())
            ->method('setBaseGiftCardsRefunded')
            ->with($this->baseGiftCardsRefunded)
            ->willReturnSelf();

        $this->orderMock->expects($this->once())
            ->method('setExtensionAttributes')
            ->with($this->extensionAttributeMock)
            ->willReturnSelf();

        $this->plugin->afterGet($this->subjectMock, $this->orderMock);
    }
}
