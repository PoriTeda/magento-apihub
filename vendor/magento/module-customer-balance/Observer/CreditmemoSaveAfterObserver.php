<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerBalance\Observer;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Math\FloatComparator;
use Magento\Sales\Model\Order\Creditmemo;

/**
 * Customer balance observer
 */
class CreditmemoSaveAfterObserver implements ObserverInterface
{
    /**
     * Customer balance data
     *
     * @var \Magento\CustomerBalance\Helper\Data
     */
    protected $_customerBalanceData;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\CustomerBalance\Model\BalanceFactory
     */
    protected $_balanceFactory;

    /**
     * @var FloatComparator
     */
    private $comparator;

    /**
     * Constructor
     *
     * @param \Magento\CustomerBalance\Model\BalanceFactory $balanceFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\CustomerBalance\Helper\Data $customerBalanceData
     * @param FloatComparator $comparator
     */
    public function __construct(
        \Magento\CustomerBalance\Model\BalanceFactory $balanceFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CustomerBalance\Helper\Data $customerBalanceData,
        FloatComparator $comparator
    ) {
        $this->_balanceFactory = $balanceFactory;
        $this->_storeManager = $storeManager;
        $this->_customerBalanceData = $customerBalanceData;
        $this->comparator = $comparator;
    }

    /**
     * Refund process.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var Creditmemo $creditmemo */
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $order = $creditmemo->getOrder();

        if ($creditmemo->getAutomaticallyCreated()) {
            if ($this->_customerBalanceData->isAutoRefundEnabled()) {
                $creditmemo->setCustomerBalanceRefundFlag(true)
                    ->setCustomerBalTotalRefunded($creditmemo->getCustomerBalanceAmount())
                    ->setBsCustomerBalTotalRefunded($creditmemo->getBaseCustomerBalanceAmount());
            } else {
                return $this;
            }
        }

        if (!$this->isBalanceAllowed($creditmemo)) {
            throw new LocalizedException(__('You can\'t use more store credit than the order amount.'));
        }
        //doing actual refund to customer balance if user has submitted refund
        if ($creditmemo->getCustomerBalanceRefundFlag() && $creditmemo->getBsCustomerBalTotalRefunded()) {
            $order->setBsCustomerBalTotalRefunded(
                $order->getBsCustomerBalTotalRefunded() + $creditmemo->getBsCustomerBalTotalRefunded()
            );
            $order->setCustomerBalTotalRefunded(
                $order->getCustomerBalTotalRefunded() + $creditmemo->getCustomerBalTotalRefunded()
            );

            $websiteId = $this->_storeManager->getStore($order->getStoreId())->getWebsiteId();

            $this->_balanceFactory->create()
                ->setCustomerId($order->getCustomerId())
                ->setWebsiteId($websiteId)
                ->setAmountDelta($creditmemo->getBsCustomerBalTotalRefunded())
                ->setHistoryAction(\Magento\CustomerBalance\Model\Balance\History::ACTION_REFUNDED)
                ->setOrder($order)
                ->setCreditMemo($creditmemo)
                ->save();
        }

        return $this;
    }

    /**
     * Checks if balance is allowed for refund.
     *
     * @param Creditmemo $creditmemo
     * @return bool
     */
    private function isBalanceAllowed(Creditmemo $creditmemo): bool
    {
        // as Reward Points is rounded value but max allowed Customer Balance calculated with raw totals,
        // we need to used some delta to validate allowed balance
        $maxDelta = 0.5; // `0.5` because Reward uses `ceil()` to round value
        $totalRefunded = $creditmemo->getBsCustomerBalTotalRefunded() + $creditmemo->getRewardPointsBalanceRefund();
        $maxBalance = $creditmemo->getBaseCustomerBalanceReturnMax() + $maxDelta;
        return !$this->comparator->greaterThan($totalRefunded, $maxBalance);
    }
}
