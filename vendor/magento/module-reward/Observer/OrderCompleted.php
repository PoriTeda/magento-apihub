<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Observer;

use Magento\Framework\Event\ObserverInterface;

class OrderCompleted implements ObserverInterface
{
    /**
     * Reward factory
     *
     * @var \Magento\Reward\Model\RewardFactory
     */
    protected $_rewardFactory;

    /**
     * Reward helper
     *
     * @var \Magento\Reward\Helper\Data
     */
    protected $_rewardData;

    /**
     * @param \Magento\Reward\Helper\Data $rewardData
     * @param \Magento\Reward\Model\RewardFactory $rewardFactory
     */
    public function __construct(
        \Magento\Reward\Helper\Data $rewardData,
        \Magento\Reward\Model\RewardFactory $rewardFactory
    ) {
        $this->_rewardData = $rewardData;
        $this->_rewardFactory = $rewardFactory;
    }

    /**
     * Check if order is paid exactly now
     * If order was paid before Rewards were enabled, reward points should not be added
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    protected function _isOrderPaidNow($order)
    {
        $isOrderPaid = (double)$order->getBaseTotalPaid() > 0 &&
            $order->getBaseGrandTotal() - $order->getBaseSubtotalCanceled() - $order->getBaseTotalPaid() < 0.0001;

        if (!$order->getOrigData('base_grand_total')) {
            //New order with "Sale" payment action
            return $isOrderPaid;
        }

        return $isOrderPaid && $order->getOrigData(
            'base_grand_total'
        ) - $order->getOrigData(
            'base_subtotal_canceled'
        ) - $order->getOrigData(
            'base_total_paid'
        ) >= 0.0001;
    }

    /**
     * Update points balance after order becomes completed
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var $order \Magento\Sales\Model\Order */
        $order = $observer->getEvent()->getOrder();
        if ($order->getCustomerIsGuest() || !$this->_rewardData->isEnabledOnFront($order->getStore()->getWebsiteId())) {
            return $this;
        }

        if ($order->getCustomerId() && $this->_isOrderPaidNow($order)) {
            /* @var $reward \Magento\Reward\Model\Reward */
            $reward = $this->_rewardFactory->create()->setActionEntity(
                $order
            )->setCustomerId(
                $order->getCustomerId()
            )->setWebsiteId(
                $order->getStore()->getWebsiteId()
            )->setAction(
                \Magento\Reward\Model\Reward::REWARD_ACTION_ORDER_EXTRA
            )->updateRewardPoints();
            if ($reward->getRewardPointsUpdated() && $reward->getPointsDelta()) {
                $order->addStatusHistoryComment(
                    __(
                        'The customer earned %1 for this order.',
                        $this->_rewardData->formatReward($reward->getPointsDelta())
                    )
                )->save();
            }
        }

        return $this;
    }
}
