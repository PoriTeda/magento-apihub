<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Test\Unit\Model;

use Magento\Reward\Model\Reward;
use Magento\Store\Model\ScopeInterface;

/**
 * Class PaymentDataImporterTest
 */
class PaymentDataImporterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rewardFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectConverterMock;

    /**
     * @var \Magento\Reward\Model\PaymentDataImporter
     */
    protected $model;

    protected function setUp()
    {
        $this->rewardFactoryMock = $this->getMockBuilder(\Magento\Reward\Model\RewardFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->scopeConfigMock = $this->createMock(\Magento\Framework\App\Config::class);
        $this->objectConverterMock = $this->createMock(\Magento\Framework\Api\ExtensibleDataObjectConverter::class);
        $this->model = new \Magento\Reward\Model\PaymentDataImporter(
            $this->rewardFactoryMock,
            $this->scopeConfigMock,
            $this->objectConverterMock
        );
    }

    public function testImport()
    {
        $baseGrandTotal = 42;
        $rewardCurrencyAmount = 74;
        $useRewardPoints = true;
        $customerId = 24;
        $websiteId = 18;
        $minPointsBalance = 100;
        $storeId = 94;
        $rewardId = 88;
        $pointsBalance = 128;
        $customerMock = $this->createMock(\Magento\Customer\Model\Data\Customer::class);
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $paymentMock = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getMethod', 'setMethod']);
        $rewardMock = $this->createPartialMock(
            \Magento\Reward\Model\Reward::class,
            ['getPointsBalance', 'getId', 'setCustomer', 'setWebsiteId', 'loadByCustomer']
        );
        $quoteMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            [
                'setUseRewardPoints',
                'getBaseRewardCurrencyAmount',
                'getUseRewardPoints',
                'getCustomerId',
                'getCustomer',
                'getBaseGrandTotal',
                'getStore',
                'getStoreId',
                'setRewardInstance'
            ]
        );

        $quoteMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $quoteMock->expects($this->once())->method('getBaseGrandTotal')->willReturn($baseGrandTotal);
        $quoteMock->expects($this->once())->method('getBaseRewardCurrencyAmount')->willReturn($rewardCurrencyAmount);
        $quoteMock->expects($this->once())->method('setUseRewardPoints')->with($useRewardPoints);
        $quoteMock->expects($this->once())->method('getUseRewardPoints')->willReturn($useRewardPoints);
        $quoteMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $this->rewardFactoryMock->expects($this->once())->method('create')->willReturn($rewardMock);
        $rewardMock->expects($this->once())->method('setCustomer')->with($customerMock)->willReturnSelf();
        $quoteMock->expects($this->once())->method('getStore')->willReturn($storeMock);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $rewardMock->expects($this->once())->method('setWebsiteId')->with($websiteId);
        $rewardMock->expects($this->once())->method('loadByCustomer');
        $quoteMock->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $this->scopeConfigMock->expects($this->once())->method('getValue')->with(
            Reward::XML_PATH_MIN_POINTS_BALANCE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        )->willReturn($minPointsBalance);
        $rewardMock->expects($this->once())->method('getId')->willReturn($rewardId);
        $rewardMock->expects($this->once())->method('getPointsBalance')->willReturn($pointsBalance);
        $quoteMock->expects($this->once())->method('setRewardInstance')->with($rewardMock);
        $paymentMock->expects($this->once())->method('getMethod')->willReturn(null);
        $paymentMock->expects($this->once())->method('setMethod')->with('free');

        $this->assertEquals($this->model, $this->model->import($quoteMock, $paymentMock, $useRewardPoints));
    }

    public function testImportNotUsingRewardPoints()
    {
        $baseGrandTotal = 42;
        $rewardCurrencyAmount = 74;
        $useRewardPoints = false;
        $customerId = 24;
        $paymentMock = $this->createMock(\Magento\Framework\DataObject::class);
        $quoteMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            [
                'setUseRewardPoints',
                'getBaseRewardCurrencyAmount',
                'getUseRewardPoints',
                'getCustomerId',
                'getBaseGrandTotal'
            ]
        );

        $quoteMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $quoteMock->expects($this->once())->method('getBaseGrandTotal')->willReturn($baseGrandTotal);
        $quoteMock->expects($this->once())->method('getBaseRewardCurrencyAmount')->willReturn($rewardCurrencyAmount);
        $quoteMock->expects($this->once())->method('setUseRewardPoints')->with($useRewardPoints);
        $quoteMock->expects($this->once())->method('getUseRewardPoints')->willReturn($useRewardPoints);
        $this->rewardFactoryMock->expects($this->never())->method('create');

        $this->assertEquals($this->model, $this->model->import($quoteMock, $paymentMock, $useRewardPoints));
    }

    public function testImportWithInvalidParameters()
    {
        $quoteMock = $this->createPartialMock(\Magento\Quote\Model\Quote::class, ['setUseRewardPoints']);
        $quoteMock->expects($this->never())->method('setUseRewardPoints');
        $this->assertEquals($this->model, $this->model->import(null, null, null));
    }
}
