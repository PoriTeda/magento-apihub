<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Test\Unit\Observer;

use Magento\CustomerBalance\Model\Balance\History;

/**
 * Class ProcessOrderPlaceObserverTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProcessOrderPlaceObserverTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\CustomerBalance\Observer\ProcessOrderPlaceObserver */
    protected $model;

    /**
     * @var \Magento\Framework\Event\Observer
     */
    protected $observer;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $event;

    /**
     * @var \Magento\CustomerBalance\Helper\Data | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerBalanceData;

    /**
     * @var \Magento\Store\Model\Store | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $store;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManager;

    /**
     * @var \Magento\CustomerBalance\Model\Balance | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $balance;

    /**
     * @var \Magento\CustomerBalance\Model\BalanceFactory | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $balanceFactory;

    /**
     * @var \Magento\CustomerBalance\Observer\CheckStoreCreditBalance|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkStoreCreditBalance;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->customerBalanceData = $this->getMockBuilder(\Magento\CustomerBalance\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->store = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkStoreCreditBalance = $this
            ->getMockBuilder(\Magento\CustomerBalance\Observer\CheckStoreCreditBalance::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->balance = $this->getMockBuilder(\Magento\CustomerBalance\Model\Balance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setCustomerId',
                'setWebsiteId',
                'setAmountDelta',
                'setHistoryAction',
                'setOrder',
                'save',
                'loadByCustomer',
                'getAmount',
            ])
            ->getMock();

        $this->balanceFactory = $this->getMockBuilder(\Magento\CustomerBalance\Model\BalanceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->balanceFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->balance);

        $this->model = $objectManagerHelper->getObject(
            \Magento\CustomerBalance\Observer\ProcessOrderPlaceObserver::class,
            [
                'balanceFactory' => $this->balanceFactory,
                'customerBalanceData' => $this->customerBalanceData,
                'storeManager' => $this->storeManager,
                'checkStoreCreditBalance' => $this->checkStoreCreditBalance,
            ]
        );

        $this->event = new \Magento\Framework\DataObject();
        $this->observer = new \Magento\Framework\Event\Observer(['event' => $this->event]);
    }

    public function testProcessOrderPlaceCustomerBalanceDisabled()
    {
        $this->customerBalanceData->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->assertEquals($this->model, $this->model->execute($this->observer));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessOrderPlaceCustomerBalanceEnabled()
    {
        $baseCustomerBalAmountUsed = 1.;
        $customerBalanceAmountUsed = 1.;
        $storeId = 1;
        $websiteId = 1;
        $customerId = 1;

        $this->customerBalanceData->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        /** @var \Magento\Sales\Model\Order | \PHPUnit_Framework_MockObject_MockObject $orderMock */
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setBaseCustomerBalanceAmount',
                'setCustomerBalanceAmount',
                'getBaseCustomerBalanceAmount',
                'getStoreId',
                'getCustomerId',
            ])
            ->getMock();
        $orderMock->expects($this->once())
            ->method('setBaseCustomerBalanceAmount')
            ->with($baseCustomerBalAmountUsed)
            ->willReturnSelf();
        $orderMock->expects($this->once())
            ->method('setCustomerBalanceAmount')
            ->with($customerBalanceAmountUsed)
            ->willReturnSelf();
        $orderMock->expects($this->exactly(2))
            ->method('getBaseCustomerBalanceAmount')
            ->willReturn($baseCustomerBalAmountUsed);
        $orderMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);
        $orderMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        /** @var \Magento\Quote\Model\Quote | \PHPUnit_Framework_MockObject_MockObject $quoteMock */
        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getUseCustomerBalance',
                'getBaseCustomerBalAmountUsed',
                'getCustomerBalanceAmountUsed',
            ])
            ->getMock();
        $quoteMock->expects($this->once())
            ->method('getUseCustomerBalance')
            ->willReturn(true);
        $quoteMock->expects($this->once())
            ->method('getBaseCustomerBalAmountUsed')
            ->willReturn($baseCustomerBalAmountUsed);
        $quoteMock->expects($this->once())
            ->method('getCustomerBalanceAmountUsed')
            ->willReturn($customerBalanceAmountUsed);

        $this->event->setOrder($orderMock);
        $this->event->setQuote($quoteMock);

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($this->store);
        $this->store->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->balance->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('setWebsiteId')
            ->with($websiteId)
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('setAmountDelta')
            ->with(-$baseCustomerBalAmountUsed)
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('setHistoryAction')
            ->with(History::ACTION_USED)
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('setOrder')
            ->with($orderMock)
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('save')
            ->willReturnSelf();

        $this->checkStoreCreditBalance
            ->expects($this->once())
            ->method('execute')
            ->with($orderMock);

        $this->assertEquals($this->model, $this->model->execute($this->observer));
    }
}
