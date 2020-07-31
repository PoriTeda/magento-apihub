<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Test\Unit\Model\Plugin;

use Magento\GiftCardAccount\Model\Plugin\CreditmemoRepository;
use Magento\Sales\Api\Data\CreditmemoExtension;
use Magento\Sales\Api\Data\CreditmemoExtensionFactory;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoSearchResultInterface;

/**
 * Unit test for Creditmemo repository plugin.
 */
class CreditmemoRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CreditmemoRepository
     */
    private $plugin;

    /**
     * @var CreditmemoRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectMock;

    /**
     * @var CreditmemoInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $creditmemoMock;

    /**
     * @var float
     */
    private $giftCardsAmount = 10;

    /**
     * @var float
     */
    private $baseGiftCardsAmount = 15;

    /**
     * @var CreditmemoExtension|\PHPUnit_Framework_MockObject_MockObject
     */
    private $extensionAttributeMock;

    /**
     * @var CreditmemoSearchResultInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $creditmemoSearchResultMock;

    /**
     * @var CreditmemoExtensionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $creditmemoExtensionFactoryMock;

    protected function setUp()
    {
        $this->subjectMock = $this->getMockForAbstractClass(
            CreditmemoRepositoryInterface::class
        );

        $this->creditmemoMock = $this->getMockBuilder(CreditmemoInterface::class)
            ->setMethods([
                'getExtensionAttributes',
                'setExtensionAttributes',
                'setGiftCardsAmount',
                'setBaseGiftCardsAmount',
                'getBaseGiftCardsAmount',
                'getGiftCardsAmount'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->extensionAttributeMock = $this->getMockBuilder(CreditmemoExtension::class)
            ->setMethods([
                'getGiftCardsAmount',
                'getBaseGiftCardsAmount',
                'setGiftCardsAmount',
                'setBaseGiftCardsAmount'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->creditmemoSearchResultMock = $this->getMockForAbstractClass(
            CreditmemoSearchResultInterface::class
        );

        $this->creditmemoExtensionFactoryMock = $this->getMockBuilder(CreditmemoExtensionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new CreditmemoRepository(
            $this->creditmemoExtensionFactoryMock
        );
    }

    public function testAfterGet()
    {
        $this->creditmemoMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);
        $this->creditmemoMock->expects($this->once())
            ->method('getGiftCardsAmount')
            ->willReturn($this->giftCardsAmount);
        $this->creditmemoMock->expects($this->once())
            ->method('getBaseGiftCardsAmount')
            ->willReturn($this->baseGiftCardsAmount);

        $this->extensionAttributeMock->expects($this->once())
            ->method('setGiftCardsAmount')
            ->with($this->giftCardsAmount)
            ->willReturnSelf();
        $this->extensionAttributeMock->expects($this->once())
            ->method('setBaseGiftCardsAmount')
            ->with($this->baseGiftCardsAmount)
            ->willReturnSelf();

        $this->creditmemoMock->expects($this->once())
            ->method('setExtensionAttributes')
            ->with($this->extensionAttributeMock)
            ->willReturnSelf();

        $this->plugin->afterGet($this->subjectMock, $this->creditmemoMock);
    }

    public function testAfterGetList()
    {
        $this->creditmemoSearchResultMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->creditmemoMock]);

        $this->creditmemoMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);
        $this->creditmemoMock->expects($this->once())
            ->method('getGiftCardsAmount')
            ->willReturn($this->giftCardsAmount);
        $this->creditmemoMock->expects($this->once())
            ->method('getBaseGiftCardsAmount')
            ->willReturn($this->baseGiftCardsAmount);

        $this->extensionAttributeMock->expects($this->once())
            ->method('setGiftCardsAmount')
            ->with($this->giftCardsAmount)
            ->willReturnSelf();
        $this->extensionAttributeMock->expects($this->once())
            ->method('setBaseGiftCardsAmount')
            ->with($this->baseGiftCardsAmount)
            ->willReturnSelf();

        $this->creditmemoMock->expects($this->once())
            ->method('setExtensionAttributes')
            ->with($this->extensionAttributeMock)
            ->willReturnSelf();

        $this->plugin->afterGetList($this->subjectMock, $this->creditmemoSearchResultMock);
    }
}
