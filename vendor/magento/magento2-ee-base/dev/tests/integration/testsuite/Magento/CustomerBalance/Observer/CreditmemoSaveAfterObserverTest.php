<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Observer;

use Magento\CustomerBalance\Model\Balance;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDataFixture  Magento/CustomerBalance/_files/creditmemo_with_customer_balance.php
 */
class CreditmemoSaveAfterObserverTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CreditmemoSaveAfterObserver
     */
    private $observer;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->observer = $this->objectManager->create(CreditmemoSaveAfterObserver::class);
    }

    /**
     * Checks a case when entered balance is allowed to perform refund.
     *
     * @param float $maxAllowedBalance
     * @param float $customerBalance
     * @param int $rewardPoints
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @dataProvider totalsDataProvider
     */
    public function testExecute(float $maxAllowedBalance, float $customerBalance, int $rewardPoints): void
    {
        $creditMemo = $this->getCreditMemo('100000001');
        $creditMemo->setBaseCustomerBalanceReturnMax($maxAllowedBalance)
            ->setBsCustomerBalTotalRefunded($customerBalance)
            ->setRewardPointsBalanceRefund($rewardPoints)
            ->setCustomerBalanceRefundFlag(true);
        $observer = $this->getObserver($creditMemo);
        $this->observer->execute($observer);

        $balance = $this->getCustomerBalance((int)$creditMemo->getOrder()->getCustomerId());
        self::assertEquals($customerBalance, $balance->getAmount());
    }

    /**
     * Checks a case when the entered Customer Balance or Reward Points greater then allowed Balance.
     *
     * @return void
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage You can't use more store credit than the order amount.
     */
    public function testExecuteWithNotAllowedBalance(): void
    {
        $maxAllowedBalance = 66.48;
        $customerBalance = 28.53;
        $rewardPoints = 39;
        $creditMemo = $this->getCreditMemo('100000001');
        $creditMemo->setBaseCustomerBalanceReturnMax($maxAllowedBalance)
            ->setBsCustomerBalTotalRefunded($customerBalance)
            ->setRewardPointsBalanceRefund($rewardPoints);
        $observer = $this->getObserver($creditMemo);
        $this->observer->execute($observer);
    }

    /**
     * Gets list of totals variations.
     *
     * @return array
     */
    public function totalsDataProvider(): array
    {
        return [
            [
                'maxAllowedBalance' => 66.48,
                'customerBalance' => 28.53,
                'rewardPoints' => 38,
            ],
            [
                'maxAllowedBalance' => 66.02,
                'customerBalance' => 28.53,
                'rewardPoints' => 37,
            ]
        ];
    }

    /**
     * Creates stub for observer.
     *
     * @param CreditmemoInterface $creditMemo
     * @return Observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getObserver(CreditmemoInterface $creditMemo): Observer
    {
        /** @var DataObject $event */
        $event = $this->objectManager->create(DataObject::class);
        $event->setCreditmemo($creditMemo);

        /** @var Observer $observer */
        $observer = $this->objectManager->create(Observer::class);
        $observer->setEvent($event);

        return $observer;
    }

    /**
     * Gets Credit Memo by increment ID.
     *
     * @param string $incrementId
     * @return CreditmemoInterface
     */
    private function getCreditMemo(string $incrementId): CreditmemoInterface
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter(CreditmemoInterface::INCREMENT_ID, $incrementId)
            ->create();

        /** @var CreditmemoRepositoryInterface $creditMemoRepository */
        $creditMemoRepository = $this->objectManager->get(CreditmemoRepositoryInterface::class);
        $creditMemoList = $creditMemoRepository->getList($searchCriteria)
            ->getItems();

        return array_pop($creditMemoList);
    }

    /**
     * Gets Customer Balance entity by the customer.
     *
     * @param int $customerId
     * @return Balance
     */
    private function getCustomerBalance(int $customerId): Balance
    {
        /** @var Balance $customerBalance */
        $customerBalance = $this->objectManager->create(Balance::class);
        $customerBalance->setCustomerId($customerId);
        $customerBalance->loadByCustomer();

        return $customerBalance;
    }
}
