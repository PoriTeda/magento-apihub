<?php

namespace Riki\Loyalty\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class RevertRewardPoints implements ObserverInterface
{
    /**
     * Core model store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Reward balance validator
     *
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $_rewardManagement;

    /**
     * RedeemForOrder constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Riki\Loyalty\Model\RewardManagement $rewardManagement
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Riki\Loyalty\Model\RewardManagement $rewardManagement
    ) {
        $this->_storeManager = $storeManager;
        $this->_rewardManagement = $rewardManagement;
    }

    /**
     * Revert reward points if points was used during checkout
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /* @var $order \Magento\Sales\Model\Order */
        $order = $observer->getEvent()->getOrder();
        /** @var $quote \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();
        if ($quote->getUsedPoint() > 0) {
            $this->_rewardManagement->revertRedeemed($order);
        }
    }
}
