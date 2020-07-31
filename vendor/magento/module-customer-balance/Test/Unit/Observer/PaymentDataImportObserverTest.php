<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Test\Unit\Observer;

use Magento\CustomerBalance\Observer\PaymentDataImportObserver;

class PaymentDataImportObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomerBalance\Observer\PaymentDataImportObserver
     */
    protected $observer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerBalanceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $observerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMock;

    protected function setUp()
    {
        $custBalanceMethods = ['setCustomerId', 'setWebsiteId', 'loadByCustomer'];
        $this->customerBalanceMock = $this->createPartialMock(
            \Magento\CustomerBalance\Model\Balance::class,
            $custBalanceMethods
        );
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $quoteMethods = ['getCustomerId', 'getStoreId', 'getIsMultiShipping', 'setUseCustomerBalance',
            'setCustomerBalanceInstance'];
        $this->quoteMock = $this->createPartialMock(\Magento\Quote\Model\Quote::class, $quoteMethods);
        $this->observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $eventMethods = ['getPayment', 'getInput'];
        $this->eventMock = $this->createPartialMock(\Magento\Framework\Event::class, $eventMethods);
        $this->paymentMock = $this->createMock(\Magento\Quote\Model\Quote\Payment::class);
        $this->observer = new PaymentDataImportObserver(
            $this->customerBalanceMock,
            $this->storeManagerMock
        );
    }

    public function testExecuteForNotMultishippingQuote()
    {
        $this->observerMock->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->once())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->expects($this->once())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->once())->method('getIsMultiShipping')->willReturn(false);
        $this->eventMock->expects($this->never())->method('getInput');
        $this->observer->execute($this->observerMock);
    }

    public function testExecuteForMultishippingQuote()
    {
        $storeId = 1;
        $customerId = 2;
        $storeMock = $this->createMock(\Magento\Store\Api\Data\StoreInterface::class);
        $inputMock = $this->createPartialMock(
            \Magento\Framework\DataObject::class,
            ['getAdditionalData', 'getMethod', 'setMethod']
        );
        $this->observerMock->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->once())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->expects($this->once())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->once())->method('getIsMultiShipping')->willReturn(true);
        $this->eventMock->expects($this->once())->method('getInput')->willReturn($inputMock);
        $inputMock->expects($this->once())->method('getAdditionalData')->willReturn(['use_customer_balance' => true]);
        $this->quoteMock->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $this->storeManagerMock->expects($this->once())->method('getStore')->with($storeId)->willReturn($storeMock);
        $this->quoteMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->quoteMock->expects($this->once())->method('setUseCustomerBalance')->willReturn(true);
        $this->customerBalanceMock
            ->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn(2);
        $this->customerBalanceMock->expects($this->once())->method('setWebsiteId')->with(2)->willReturnSelf();
        $this->customerBalanceMock->expects($this->once())->method('loadByCustomer')->willReturnSelf();
        $this->quoteMock
            ->expects($this->once())
            ->method('setCustomerBalanceInstance')
            ->with($this->customerBalanceMock);
        $inputMock->expects($this->once())->method('getMethod')->willReturn(null);
        $inputMock->expects($this->once())->method('setMethod')->with('free');
        $this->observer->execute($this->observerMock);
    }
}
