<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Test\Unit\Model\Total\Quote;

use Magento\CustomerBalance\Model\Total\Quote\Customerbalance;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerbalanceTest extends \PHPUnit\Framework\TestCase
{
    /** @var Customerbalance */
    protected $customerBalance;

    /** @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $storeManager;

    /** @var \Magento\CustomerBalance\Model\BalanceFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $balanceFactory;

    /** @var \Magento\Framework\Pricing\PriceCurrencyInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $priceCurrency;

    /** @var \Magento\CustomerBalance\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    protected $customerBalanceData;

    /** @var \Magento\Quote\Model\Quote|\PHPUnit_Framework_MockObject_MockObject */
    protected $quote;

    /** @var \Magento\Quote\Api\Data\ShippingAssignmentInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingAssignment;

    /** @var \Magento\Quote\Model\Quote\Address\Total|\PHPUnit_Framework_MockObject_MockObject */
    protected $total;

    /** @var \Magento\Quote\Api\Data\ShippingInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $shipping;

    /** @var \Magento\Quote\Api\Data\AddressInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $address;

    /** @var \Magento\Customer\Api\Data\CustomerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $customer;

    /** @var \Magento\Store\Api\Data\StoreInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $store;

    /** @var \Magento\CustomerBalance\Model\Balance|\PHPUnit_Framework_MockObject_MockObject */
    protected $balance;

    public function setUpCustomerBalance()
    {
        $this->storeManager = $this->getMockForAbstractClass(
            \Magento\Store\Model\StoreManagerInterface::class,
            [],
            '',
            false
        );
        $this->balanceFactory = $this->createPartialMock(
            \Magento\CustomerBalance\Model\BalanceFactory::class,
            ['create']
        );
        $this->priceCurrency = $this->getMockForAbstractClass(
            \Magento\Framework\Pricing\PriceCurrencyInterface::class,
            [],
            '',
            false
        );
        $this->customerBalanceData = $this->createMock(\Magento\CustomerBalance\Helper\Data::class);

        $this->customerBalance = new Customerbalance(
            $this->storeManager,
            $this->balanceFactory,
            $this->customerBalanceData,
            $this->priceCurrency
        );
    }
    
    protected function setUp()
    {
        $this->setUpCustomerBalance();

        $this->quote = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            [
                'setBaseCustomerBalAmountUsed',
                'setCustomerBalanceAmountUsed',
                'getBaseCustomerBalAmountUsed',
                'getCustomerBalanceAmountUsed',
                'getUseCustomerBalance',
                'getCustomer',
                'getStoreId',
                'isVirtual',
                'getStore'
            ]
        );

        $this->total = $this->createPartialMock(
            \Magento\Quote\Model\Quote\Address\Total::class,
            [
                'getCustomerBalanceAmount',
                'setBaseCustomerBalanceAmount',
                'setCustomerBalanceAmount',
                'getBaseGrandTotal',
                'getGrandTotal',
                'setBaseGrandTotal',
                'setGrandTotal'
            ]
        );
        $this->shippingAssignment = $this->getMockForAbstractClass(
            \Magento\Quote\Api\Data\ShippingAssignmentInterface::class,
            [],
            '',
            false
        );
        $this->shipping = $this->getMockForAbstractClass(
            \Magento\Quote\Api\Data\ShippingInterface::class,
            [],
            '',
            false
        );
        $this->address = $this->getMockForAbstractClass(
            \Magento\Quote\Api\Data\AddressInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getAddressType']
        );
        $this->customer = $this->getMockForAbstractClass(
            \Magento\Customer\Api\Data\CustomerInterface::class,
            [],
            '',
            false
        );
        $this->store = $this->getMockForAbstractClass(\Magento\Store\Api\Data\StoreInterface::class, [], '', false);
        $this->balance = $this->createPartialMock(
            \Magento\CustomerBalance\Model\Balance::class,
            [
                'setCustomer',
                'setCustomerId',
                'setWebsiteId',
                'loadByCustomer',
                'getAmount'
            ]
        );
    }

    public function testCollectWithDisabledCustomerBalance()
    {
        $this->customerBalanceData->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->assertSame(
            $this->customerBalance,
            $this->customerBalance->collect(
                $this->quote,
                $this->shippingAssignment,
                $this->total
            )
        );
    }

    public function testCollectIfQuoteIsVirtual()
    {
        $this->customerBalanceData->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->shippingAssignment->expects($this->once())
            ->method('getShipping')
            ->willReturn($this->shipping);
        $this->shipping->expects($this->once())
            ->method('getAddress')
            ->willReturn($this->address);
        $this->address->expects($this->once())
            ->method('getAddressType')
            ->willReturn(\Magento\Customer\Model\Address\AbstractAddress::TYPE_SHIPPING);
        $this->quote->expects($this->once())
            ->method('isVirtual')
            ->willReturn(true);

        $this->assertSame(
            $this->customerBalance,
            $this->customerBalance->collect(
                $this->quote,
                $this->shippingAssignment,
                $this->total
            )
        );
    }

    protected function loadCustomerBalanceAmount()
    {
        $customerId = 4;
        $storeId = 1;
        $websiteId = 2;

        $this->customerBalanceData->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->shippingAssignment->expects($this->once())
            ->method('getShipping')
            ->willReturn($this->shipping);
        $this->shipping->expects($this->once())
            ->method('getAddress')
            ->willReturn($this->address);
        $this->address->expects($this->once())
            ->method('getAddressType')
            ->willReturn(\Magento\Customer\Model\Address\AbstractAddress::TYPE_BILLING);
        $this->quote->expects($this->never())
            ->method('isVirtual')
            ->willReturn(true);
        $this->quote->expects($this->atLeastOnce())
            ->method('setBaseCustomerBalAmountUsed');
        $this->quote->expects($this->atLeastOnce())
            ->method('setCustomerBalanceAmountUsed');
        $this->quote->expects($this->exactly(3))
            ->method('getCustomer')
            ->willReturn($this->customer);
        $this->quote->expects($this->once())
            ->method('getUseCustomerBalance')
            ->willReturn(true);
        $this->quote->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);
        $this->quote->expects($this->once())
            ->method('getStore')
            ->willReturn($this->store);
        $this->customer->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($customerId);
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($this->store);

        $this->balanceFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->balance);

        $this->balance->expects($this->once())
            ->method('setCustomer')
            ->with($this->customer)
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();

        $this->balance->expects($this->once())
            ->method('setWebsiteId')
            ->with($websiteId)
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('loadByCustomer')
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('getAmount')
            ->willReturn(100);
        $this->store->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn($websiteId);
        $this->priceCurrency->expects($this->once())
            ->method('convert')
            ->with(100, $this->store)
            ->willReturnArgument(0);
    }

    public function testCollect()
    {
        $this->loadCustomerBalanceAmount();

        $this->quote->expects($this->exactly(2))
            ->method('getBaseCustomerBalAmountUsed')
            ->willReturn(50);
        $this->quote->expects($this->exactly(2))
            ->method('getCustomerBalanceAmountUsed')
            ->willReturn(50);

        $this->total->expects($this->exactly(2))
            ->method('getBaseGrandTotal')
            ->willReturn(50);
        $this->total->expects($this->once())
            ->method('getGrandTotal')
            ->willReturn(50);
        $this->total->expects($this->once())
            ->method('setBaseGrandTotal')
            ->with(0);
        $this->total->expects($this->once())
            ->method('setGrandTotal')
            ->with(0);
        $this->total->expects($this->once())
            ->method('setBaseCustomerBalanceAmount')
            ->with(50);
        $this->total->expects($this->once())
            ->method('setCustomerBalanceAmount')
            ->with(50);

        $this->customerBalance->collect(
            $this->quote,
            $this->shippingAssignment,
            $this->total
        );
    }

    public function testCollectWithInsufficientlyOfStoreCredits()
    {
        $this->loadCustomerBalanceAmount();

        $this->quote->expects($this->exactly(2))
            ->method('getBaseCustomerBalAmountUsed')
            ->willReturn(50);
        $this->quote->expects($this->exactly(2))
            ->method('getCustomerBalanceAmountUsed')
            ->willReturn(50);

        $this->total->expects($this->exactly(2))
            ->method('getBaseGrandTotal')
            ->willReturn(100);
        $this->total->expects($this->once())
            ->method('getGrandTotal')
            ->willReturn(100);
        $this->total->expects($this->once())
            ->method('setBaseGrandTotal')
            ->with(50);
        $this->total->expects($this->once())
            ->method('setGrandTotal')
            ->with(50);
        $this->total->expects($this->once())
            ->method('setBaseCustomerBalanceAmount')
            ->with(50);
        $this->total->expects($this->once())
            ->method('setCustomerBalanceAmount')
            ->with(50);

        $this->customerBalance->collect(
            $this->quote,
            $this->shippingAssignment,
            $this->total
        );
    }

    public function testFetch()
    {
        $code = 'customerbalance';
        $this->customerBalance->setCode($code);
        $this->customerBalanceData->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->total->expects($this->exactly(2))
            ->method('getCustomerBalanceAmount')
            ->willReturn(50);
        $this->assertEquals(
            [
                'code' => $code,
                'title' => __('Store Credit'),
                'value' => -50
            ],
            $this->customerBalance->fetch($this->quote, $this->total)
        );
    }

    public function testFetchWithDisabledCustomerBalance()
    {
        $this->customerBalanceData->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->assertNull($this->customerBalance->fetch($this->quote, $this->total));
    }
}
