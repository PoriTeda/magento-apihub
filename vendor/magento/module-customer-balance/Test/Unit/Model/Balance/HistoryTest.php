<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerBalance\Test\Unit\Model\Balance;

use Magento\CustomerBalance\Model\Balance\History;
use Magento\Framework\App\Area;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HistoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var History
     */
    protected $model;

    /**
     * @var \PHPUnit_FrameWork_MockObject_MockObject
     */
    protected $balanceModelMock;

    /**
     * @var \PHPUnit_FrameWork_MockObject_MockObject
     */
    protected $customerRegistryMock;

    /**
     * @var \PHPUnit_FrameWork_MockObject_MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var \PHPUnit_FrameWork_MockObject_MockObject
     */
    protected $transportBuilderMock;

    /**
     * @var \PHPUnit_FrameWork_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \PHPUnit_FrameWork_MockObject_MockObject
     */
    protected $designMock;

    /**
     * @var \PHPUnit_FrameWork_MockObject_MockObject
     */
    protected $resourceMock;

    /**
     * @var \Magento\Customer\Helper\View|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerHelperViewMock;

    /**
     * @var \Magento\Sales\Model\Order | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $order;

    /**
     * @var \Magento\Framework\Event\ManagerInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventManager;

    /**
     * @var \Magento\Sales\Model\Order\Creditmemo | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $creditMemo;

    protected function setUp()
    {
        $this->balanceModelMock = $this->getMockBuilder(\Magento\CustomerBalance\Model\Balance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getNotifyByEmail',
                'getStoreId',
                'getCustomer',
                'getWebsiteId',
                'getAmount',
                'getAmountDelta',
                'getId',
                'getHistoryAction',
                'getOrder',
                'getUpdatedActionAdditionalInfo',
                'getCreditMemo',
            ])
            ->getMock();

        $this->scopeConfigMock = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $this->transportBuilderMock = $this->createMock(\Magento\Framework\Mail\Template\TransportBuilder::class);
        $this->resourceMock = $this->getMockForAbstractClass(
            \Magento\Framework\Model\ResourceModel\AbstractResource::class,
            [],
            '',
            false,
            false,
            true,
            ['getIdFieldName', 'markAsSent']
        );
        $this->customerRegistryMock = $this->createMock(\Magento\Customer\Model\CustomerRegistry::class);
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->designMock = $this->createMock(\Magento\Framework\View\DesignInterface::class);

        $this->customerHelperViewMock = $this->getMockBuilder(\Magento\Customer\Helper\View::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerName'])
            ->getMock();

        $this->order = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventManager = $this->getMockBuilder(\Magento\Framework\Event\ManagerInterface::class)
            ->getMock();

        $this->creditMemo = $this->getMockBuilder(\Magento\Sales\Model\Order\Creditmemo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectHelper->getObject(
            \Magento\CustomerBalance\Model\Balance\History::class,
            [
                'customerRegistry' => $this->customerRegistryMock,
                'transportBuilder' => $this->transportBuilderMock,
                'scopeConfig' => $this->scopeConfigMock,
                'design' => $this->designMock,
                'storeManager' => $this->storeManagerMock,
                'resource' => $this->resourceMock,
                'customerHelperView' => $this->customerHelperViewMock
            ]
        );
    }

    public function testAfterSave()
    {
        $this->model->setBalanceModel($this->balanceModelMock);
        $customerId = 1;
        $storeId = 2;
        $websiteId = 3;
        $templateIdentifier = 'tpl';
        $format = 'format';
        $amount = 10;
        $customerFullName = 'Mr. John Doe';
        $customerEmail = 'johndoe@example.com';

        $customerDataMock = $this->createPartialMock(\Magento\Customer\Model\Data\Customer::class, ['getId']);
        $customerMock = $this->createPartialMock(\Magento\Customer\Model\Customer::class, ['getEmail', 'getName']);
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $transportMock = $this->createMock(\Magento\Framework\Mail\TransportInterface::class);
        $websiteMock = $this->createMock(\Magento\Store\Model\Website::class);
        $currencyMock = $this->createMock(\Magento\Directory\Model\Currency::class);
        $this->balanceModelMock->expects($this->once())->method('getNotifyByEmail')->willReturn(true);
        $this->balanceModelMock->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $this->balanceModelMock->expects($this->once())->method('getCustomer')->willReturn($customerDataMock);
        $customerDataMock->expects($this->once())->method('getId')->willReturn($customerId);

        $this->customerHelperViewMock
            ->expects($this->atLeastOnce())
            ->method('getCustomerName')
            ->willReturn($customerFullName);

        $this->customerRegistryMock->expects($this->once())->method('retrieve')->with($customerId)
            ->willReturn($customerMock);
        $this->scopeConfigMock->expects($this->exactly(2))->method('getValue')->withConsecutive(
            [
                'customer/magento_customerbalance/email_template',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            ],
            [
                'customer/magento_customerbalance/email_identity',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            ]
        )->willReturn($templateIdentifier);
        $this->transportBuilderMock->expects($this->once())->method('setTemplateIdentifier')->with($templateIdentifier)
            ->willReturnSelf();
        $this->transportBuilderMock->expects($this->once())->method('setTemplateOptions')->with(
            ['area' => Area::AREA_FRONTEND, 'store' => $storeId]
        )->willReturnSelf();
        $this->balanceModelMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->storeManagerMock->expects($this->once())->method('getWebsite')->with($websiteId)
            ->willReturn($websiteMock);
        $websiteMock->expects($this->once())->method('getBaseCurrency')->willReturn($currencyMock);
        $this->balanceModelMock->expects($this->once())->method('getAmount')->willReturn($amount);
        $currencyMock->expects($this->once())->method('format')->with($amount, [], false)->willReturn($format);
        $customerMock->expects($this->never())->method('getName');
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($storeMock);
        $this->transportBuilderMock->expects($this->once())->method('setTemplateVars')->with(
            ['balance' => $format, 'name' => $customerFullName, 'store' => $storeMock]
        )->willReturnSelf();
        $this->transportBuilderMock->expects($this->once())->method('setFrom')->with($templateIdentifier)
            ->willReturnSelf();
        $customerMock->expects($this->once())->method('getEmail')->willReturn($customerEmail);
        $this->transportBuilderMock->expects($this->once())->method('addTo')->with($customerEmail, $customerFullName)
            ->willReturnSelf();
        $this->transportBuilderMock->expects($this->once())->method('getTransport')->willReturn($transportMock);
        $transportMock->expects($this->once())->method('sendMessage');
        $this->assertEquals($this->model, $this->model->afterSave());
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage A balance is needed to save a balance history.
     */
    public function testBeforeSaveNoBalanceModel()
    {
        $this->model->beforeSave();
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage There is no order set to balance model.
     */
    public function testBeforeSaveNoOrder()
    {
        $balanceId = 1;
        $amount = 1.;
        $amountDelta = 0.1;
        $historyAction = History::ACTION_USED;

        $this->balanceModelMock->expects($this->any())
            ->method('getId')
            ->willReturn($balanceId);
        $this->balanceModelMock->expects($this->once())
            ->method('getAmount')
            ->willReturn($amount);
        $this->balanceModelMock->expects($this->once())
            ->method('getAmountDelta')
            ->willReturn($amountDelta);
        $this->balanceModelMock->expects($this->once())
            ->method('getHistoryAction')
            ->willReturn($historyAction);
        $this->balanceModelMock->expects($this->once())
            ->method('getOrder')
            ->willReturn(null);

        $this->model->setBalanceModel($this->balanceModelMock);

        $this->model->beforeSave();
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage There is no credit memo set to balance model.
     */
    public function testBeforeSaveNoCreditMemo()
    {
        $modelId = 1;
        $balanceId = 1;
        $amount = 1.;
        $amountDelta = 0.1;
        $historyAction = History::ACTION_REFUNDED;

        $this->balanceModelMock->expects($this->any())
            ->method('getId')
            ->willReturn($balanceId);
        $this->balanceModelMock->expects($this->once())
            ->method('getAmount')
            ->willReturn($amount);
        $this->balanceModelMock->expects($this->once())
            ->method('getAmountDelta')
            ->willReturn($amountDelta);
        $this->balanceModelMock->expects($this->once())
            ->method('getHistoryAction')
            ->willReturn($historyAction);
        $this->balanceModelMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->order);
        $this->balanceModelMock->expects($this->any())
            ->method('getCreditMemo')
            ->willReturn(null);

        $this->model->setId($modelId);
        $this->model->setBalanceModel($this->balanceModelMock);

        $this->model->beforeSave();
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage The balance history action code is unknown. Verify the code and try again.
     */
    public function testBeforeSaveNoAction()
    {
        $modelId = 1;
        $balanceId = 1;
        $amount = 1.;
        $amountDelta = 0.1;
        $historyAction = null;

        $this->balanceModelMock->expects($this->any())
            ->method('getId')
            ->willReturn($balanceId);
        $this->balanceModelMock->expects($this->once())
            ->method('getAmount')
            ->willReturn($amount);
        $this->balanceModelMock->expects($this->once())
            ->method('getAmountDelta')
            ->willReturn($amountDelta);
        $this->balanceModelMock->expects($this->once())
            ->method('getHistoryAction')
            ->willReturn($historyAction);

        $this->model->setId($modelId);
        $this->model->setBalanceModel($this->balanceModelMock);

        $this->model->beforeSave();
    }

    /**
     * @param int $modelId
     * @param int $balanceId
     * @param float $amount
     * @param float $amountDelta
     * @param int $historyAction
     * @param string $incrementId
     * @param string $result
     * @dataProvider providerBeforeSave
     */
    public function testBeforeSave(
        $modelId,
        $balanceId,
        $amount,
        $amountDelta,
        $historyAction,
        $incrementId,
        $result
    ) {
        $this->order->expects($this->any())
            ->method('getIncrementId')
            ->willReturn($incrementId);

        $this->creditMemo->expects($this->any())
            ->method('getIncrementId')
            ->willReturn($incrementId);

        $this->balanceModelMock->expects($this->any())
            ->method('getId')
            ->willReturn($balanceId);
        $this->balanceModelMock->expects($this->once())
            ->method('getAmount')
            ->willReturn($amount);
        $this->balanceModelMock->expects($this->once())
            ->method('getAmountDelta')
            ->willReturn($amountDelta);
        $this->balanceModelMock->expects($this->exactly(2))
            ->method('getHistoryAction')
            ->willReturn($historyAction);
        $this->balanceModelMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->order);
        $this->balanceModelMock->expects($this->any())
            ->method('getUpdatedActionAdditionalInfo')
            ->willReturn($result);
        $this->balanceModelMock->expects($this->any())
            ->method('getCreditMemo')
            ->willReturn($this->creditMemo);

        $this->eventManager->expects($this->any())
            ->method('dispatch')
            ->willReturnSelf();

        $this->model->setId($modelId);
        $this->model->setBalanceModel($this->balanceModelMock);

        $this->model->beforeSave();
        $this->assertEquals($result, $this->model->getAdditionalInfo());
    }

    public function providerBeforeSave()
    {
        return [
            [1, 1, 1., 0.1, History::ACTION_CREATED, 'increment-1', __('Order #%1', 'increment-1')],
            [1, 1, 1., 0.1, History::ACTION_UPDATED, 'increment-1', __('Order #%1', 'increment-1')],
            [1, 1, 1., 0.1, History::ACTION_USED, 'increment-1', __('Order #%1', 'increment-1')],
            [1, 1, 1., 0.1, History::ACTION_REFUNDED, 'increment-1',
                __('Order #%1, creditmemo #%2', 'increment-1', 'increment-1')],
            [1, 1, 1., 0.1, History::ACTION_REVERTED, 'increment-1', __('Order #%1', 'increment-1')],
        ];
    }
}
