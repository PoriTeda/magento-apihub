<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Test\Unit\Observer;

use Magento\GiftCardAccount\Model\Giftcardaccount;

class PaymentDataImportTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftCardAccount\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $giftCardAccountHelper;

    /**
     * @var \Magento\GiftCardAccount\Model\GiftcardaccountFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $getCardAccountFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManager;

    /**
     * @var \Magento\GiftCardAccount\Observer\PaymentDataImport
     */
    private $paymentDataImport;

    protected function setUp()
    {
        $this->giftCardAccountHelper = $this->getMockBuilder(\Magento\GiftCardAccount\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->getCardAccountFactory = $this->getMockBuilder(
            \Magento\GiftCardAccount\Model\GiftcardaccountFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->paymentDataImport = new \Magento\GiftCardAccount\Observer\PaymentDataImport(
            $this->giftCardAccountHelper,
            $this->getCardAccountFactory,
            $this->storeManager
        );
    }

    /**
     * Test case when event object has no quote object
     */
    public function testExecuteWithNoQuote()
    {
        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getQuote')
            ->willReturn(null);

        $this->giftCardAccountHelper->expects($this->never())
            ->method('getCards')
            ->with($quoteMock);

        $observerMock = $this->getObserverMock($paymentMock);
        $this->paymentDataImport->execute($observerMock);
    }

    /**
     * Test case when quote object has no customer ID
     */
    public function testExecuteWithNoCustomerId()
    {
        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerId'])
            ->getMock();
        $quoteMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn(null);

        $paymentMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $this->giftCardAccountHelper->expects($this->never())
            ->method('getCards')
            ->with($quoteMock);

        $observerMock = $this->getObserverMock($paymentMock);
        $this->paymentDataImport->execute($observerMock);
    }

    /**
     * Test case when quote has no Gift Card applied
     */
    public function testExecuteWithNoGiftCards()
    {
        $customerId = 1;
        $storeId = 1;

        $baseGiftCardsAmountUsed = 0;

        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCustomerId',
                'getStoreId',
                'getBaseGiftCardsAmountUsed',
            ])
            ->getMock();
        $quoteMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);
        $quoteMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);
        $quoteMock->expects($this->once())
            ->method('getBaseGiftCardsAmountUsed')
            ->willReturn($baseGiftCardsAmountUsed);

        $paymentMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $websiteMock = $this->getMockBuilder(\Magento\Store\Model\Website::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($storeMock);

        $this->giftCardAccountHelper->expects($this->exactly(2))
            ->method('getCards')
            ->with($quoteMock)
            ->willReturn([]);

        $observerMock = $this->getObserverMock($paymentMock);
        $this->paymentDataImport->execute($observerMock);
    }

    /**
     * Test case with Gift Cards that have 'Available' state
     */
    public function testExecuteAvailableGiftCards()
    {
        $customerId = 1;
        $storeId = 1;

        $giftCardCode = 'gift_card_code';
        $baseGiftCardsAmountUsed = 0;

        $giftCards = [
            [
                Giftcardaccount::CODE => $giftCardCode,
            ],
        ];

        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCustomerId',
                'getStoreId',
                'getBaseGiftCardsAmountUsed',
            ])
            ->getMock();
        $quoteMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);
        $quoteMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);
        $quoteMock->expects($this->once())
            ->method('getBaseGiftCardsAmountUsed')
            ->willReturn($baseGiftCardsAmountUsed);

        $paymentMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $websiteMock = $this->getMockBuilder(\Magento\Store\Model\Website::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($storeMock);

        $this->giftCardAccountHelper->expects($this->exactly(2))
            ->method('getCards')
            ->with($quoteMock)
            ->willReturn($giftCards);

        $gitfCardAccountMock = $this->getMockBuilder(\Magento\GiftCardAccount\Model\Giftcardaccount::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'loadByCode',
                'isValid',
                'getState',
            ])
            ->getMock();
        $gitfCardAccountMock->expects($this->exactly(2))
            ->method('loadByCode')
            ->with($giftCardCode)
            ->willReturnSelf();
        $gitfCardAccountMock->expects($this->once())
            ->method('isValid')
            ->with(true, true, $websiteMock)
            ->willReturn(true);
        $gitfCardAccountMock->expects($this->once())
            ->method('getState')
            ->willReturn(Giftcardaccount::STATE_AVAILABLE);

        $this->getCardAccountFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($gitfCardAccountMock);

        $observerMock = $this->getObserverMock($paymentMock);
        $this->paymentDataImport->execute($observerMock);
    }

    /**
     * Test case with Gift Cards that have 'Used' state
     */
    public function testExecuteUsedGiftCards()
    {
        $customerId = 1;
        $storeId = 1;

        $giftCardCode = 'gift_card_code';
        $baseGiftCardsAmountUsed = 0;

        $giftCards = [
            [
                Giftcardaccount::CODE => $giftCardCode,
            ],
        ];

        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCustomerId',
                'getStoreId',
                'getBaseGiftCardsAmountUsed',
            ])
            ->getMock();
        $quoteMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);
        $quoteMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);
        $quoteMock->expects($this->once())
            ->method('getBaseGiftCardsAmountUsed')
            ->willReturn($baseGiftCardsAmountUsed);

        $paymentMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $websiteMock = $this->getMockBuilder(\Magento\Store\Model\Website::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($storeMock);

        $this->giftCardAccountHelper->expects($this->exactly(2))
            ->method('getCards')
            ->with($quoteMock)
            ->willReturn($giftCards);

        $gitfCardAccountMock = $this->getMockBuilder(\Magento\GiftCardAccount\Model\Giftcardaccount::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'loadByCode',
                'isValid',
                'getState',
                'removeFromCart',
            ])
            ->getMock();
        $gitfCardAccountMock->expects($this->exactly(2))
            ->method('loadByCode')
            ->with($giftCardCode)
            ->willReturnSelf();
        $gitfCardAccountMock->expects($this->once())
            ->method('isValid')
            ->with(true, true, $websiteMock)
            ->willReturn(true);
        $gitfCardAccountMock->expects($this->once())
            ->method('getState')
            ->willReturn(Giftcardaccount::STATE_USED);
        $gitfCardAccountMock->expects($this->once())
            ->method('removeFromCart')
            ->with(true, $quoteMock)
            ->willReturnSelf();

        $this->getCardAccountFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($gitfCardAccountMock);

        $observerMock = $this->getObserverMock($paymentMock);
        $this->paymentDataImport->execute($observerMock);
    }

    /**
     * Helper method to create Observer mock object
     *
     * @param \PHPUnit_Framework_MockObject_MockObject $paymentMock
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getObserverMock($paymentMock)
    {
        $eventMock = $this->getMockBuilder(\Magento\Framework\Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPayment'])
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $observerMock = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $observerMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);

        return $observerMock;
    }
}
