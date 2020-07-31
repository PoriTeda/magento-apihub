<?php

namespace Riki\Loyalty\Observer\Adminhtml;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Riki\Loyalty\Model\RewardQuote;

class Redemption implements ObserverInterface
{
    /**
     * @var \Riki\Loyalty\Model\RewardQuoteFactory
     */
    protected $_rewardQuoteFactory;

    /**
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $_rewardManagement;

    /**
     * Redemption constructor.
     * @param \Riki\Loyalty\Model\RewardQuoteFactory $rewardQuoteFactory
     * @param \Riki\Loyalty\Model\RewardManagement $rewardManagement
     */
    public function __construct(
        \Riki\Loyalty\Model\RewardQuoteFactory $rewardQuoteFactory,
        \Riki\Loyalty\Model\RewardManagement $rewardManagement
    )
    {
        $this->_rewardQuoteFactory = $rewardQuoteFactory;
        $this->_rewardManagement = $rewardManagement;
    }

    /**
     * Init reward point redemption in admin page
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();
        if($quote instanceof \Riki\Subscription\Model\Emulator\Cart){
            return $this;
        }
        if (!$quote || !$quote->getId()) {
            return $this;
        }
        if (!$quote->getCustomer()->getId()) {
            return $this;
        }
        $rewardQuote = $this->_rewardQuoteFactory->create()->load($quote->getId(), RewardQuote::QUOTE_ID);
        if ($rewardQuote->getId()) {
            return $this;
        }
        $attribute = $quote->getCustomer()->getCustomAttribute('consumer_db_id');
        if (!$attribute) {
            return $this;
        }
        $customerCode = $attribute->getValue();
        $balance = $this->_rewardManagement->getPointBalance($customerCode);
        $setting = $this->_rewardManagement->getRewardUserSetting($customerCode);
        $usePointAmount = min($setting['use_point_amount'], $balance);
        $rewardQuote->setQuoteId($quote->getId())
                    ->setRewardUserRedeem($usePointAmount)
                    ->setRewardUserSetting($setting['use_point_type'])
                    ->save();
        return $this;
    }
}