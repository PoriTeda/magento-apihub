<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Test\Unit\Model;

class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomerBalance\Model\ConfigProvider
     */
    protected $model;

    /**
     * @var \Magento\Customer\Model\Session | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerSession;

    /**
     * @var \Magento\Store\Model\Store | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $store;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManager;

    /**
     * @var \Magento\Checkout\Model\Session | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutSession;

    /**
     * @var \Magento\CustomerBalance\Model\Balance | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $balance;

    /**
     * @var \Magento\CustomerBalance\Model\BalanceFactory | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $balanceFactory;

    /**
     * @var \Magento\Framework\UrlInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Quote\Model\Quote | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quote;

    protected function setUp()
    {
        $this->customerSession = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->store = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->getMock();
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->store);

        $this->checkoutSession = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->balance = $this->getMockBuilder(\Magento\CustomerBalance\Model\Balance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setCustomerId',
                'setWebsiteId',
                'loadByCustomer',
                'getAmount',
            ])
            ->getMock();

        $this->balanceFactory = $this->getMockBuilder(\Magento\CustomerBalance\Model\BalanceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->balanceFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->balance);

        $this->urlBuilder = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->getMock();

        $this->quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getUseCustomerBalance',
                'getBaseCustomerBalAmountUsed'
            ])
            ->getMock();

        $this->model = new \Magento\CustomerBalance\Model\ConfigProvider(
            $this->customerSession,
            $this->storeManager,
            $this->checkoutSession,
            $this->balanceFactory,
            $this->urlBuilder
        );
    }

    /**
     * @param int $customerId
     * @param int $websiteId
     * @param int $useCustomerBalance
     * @param float $baseCustomerBalAmountUsed
     * @param float $balanceAmount
     * @param bool $isAvailable
     * @param bool $amountSubstracted
     * @dataProvider providerGetConfig
     */
    public function testGetConfig(
        $customerId,
        $websiteId,
        $useCustomerBalance,
        $baseCustomerBalAmountUsed,
        $balanceAmount,
        $isAvailable,
        $amountSubstracted
    ) {
        $removeUrl = 'http://example.com/customerBalance/remove';
        $this->customerSession->expects($this->any())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->quote->expects($this->once())
            ->method('getUseCustomerBalance')
            ->willReturn($useCustomerBalance);
        $this->quote->expects($this->once())
            ->method('getBaseCustomerBalAmountUsed')
            ->willReturn($baseCustomerBalAmountUsed);

        $this->checkoutSession->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quote);

        $this->balance->expects($this->any())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $this->balance->expects($this->any())
            ->method('setWebsiteId')
            ->with($websiteId)
            ->willReturnSelf();
        $this->balance->expects($this->any())
            ->method('loadByCustomer')
            ->willReturnSelf();
        $this->balance->expects($this->any())
            ->method('getAmount')
            ->willReturn($balanceAmount);

        $this->store->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);
        $this->urlBuilder
            ->expects($this->once())
            ->method('getUrl')
            ->with('magento_customerbalance/cart/remove')
            ->willReturn($removeUrl);

        $expected = [
            'payment' => [
                'customerBalance' => [
                    'isAvailable' => $isAvailable,
                    'amountSubstracted' => $amountSubstracted,
                    'usedAmount' => $baseCustomerBalAmountUsed,
                    'balance' => $balanceAmount,
                    'balanceRemoveUrl' => $removeUrl
                ],
            ]
        ];

        $result = $this->model->getConfig();
        $this->assertEquals($expected, $result);
    }

    /**
     * 1. Customer ID
     * 2. Website ID
     * 3. Use Customer Balance flag
     * 4. Used Customer Balance Amount
     * 5. Customer Balance Amount
     * 6. Is Customer Balance Available (RESULT)
     * 7. Is Customer Balance Amount Substracted (RESULT)
     *
     * @return array
     */
    public function providerGetConfig()
    {
        return [
            [0, 0, 0, 0, 0, false, false],
            [1, 1, 0, 0, 0, false, false],
            [1, 1, 1, 5., 10., true, true],
        ];
    }
}
