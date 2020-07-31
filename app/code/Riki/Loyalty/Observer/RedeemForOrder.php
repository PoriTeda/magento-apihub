<?php

namespace Riki\Loyalty\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class RedeemForOrder implements ObserverInterface
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
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    /**
     * @var ShoppingPoint
     */
    protected $_shoppingPoint;

    /**
     * RedeemForOrder constructor.
     * @param \Magento\Store\Model\StoreManagerInterface   $storeManager
     * @param \Riki\Loyalty\Model\RewardManagement         $rewardManagement
     * @param \Magento\Framework\Registry                  $registry
     * @param \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint $shoppingPoint
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Riki\Loyalty\Model\RewardManagement $rewardManagement,
        \Magento\Framework\Registry $registry,
        \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint $shoppingPoint
    ) {
        $this->_storeManager = $storeManager;
        $this->_rewardManagement = $rewardManagement;
        $this->_coreRegistry = $registry;
        $this->_shoppingPoint = $shoppingPoint;

    }

    /**
     * Reduce reward points if points was used during checkout
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
        $hasPointForTrial = $quote->getPointForTrial() > 0 ?true:false;
        if ($quote->getUsedPoint() > 0) {
            $this->_rewardManagement->validate($order);
            $order->setUsedPoint($quote->getUsedPoint());
            $order->setUsedPointAmount($quote->getUsedPointAmount());
            $order->setBaseUsedPointAmount($quote->getBaseUsedPointAmount());
            $order->setPointForTrial($hasPointForTrial);
            $order->setRikiCourseId($quote->getData('riki_course_id'));
            if(! $order instanceof \Riki\Subscription\Model\Emulator\Order) {
                $this->_rewardManagement->redeemForOrder($order);
            }
        }

        $order->setData('is_generate', $quote->getData('is_generate'));


        $customerCode = $this->getCustomerCode($quote);
        $registryKeyPoint = "customer_point_balance_{$customerCode}";

        // set point balance to order when customer have use point
        if($quote->getRewardPointsBalance() != null) {
            $order->setRewardPointsBalance($quote->getRewardPointsBalance());
        } else {
            // set point balance to order when customer not use point on order
            $pointBalance = $this->_coreRegistry->registry($registryKeyPoint);
            if ($pointBalance && !$pointBalance['error'] ) {
                $order->setRewardPointsBalance((int) $pointBalance['return']['REST_POINT']);
            } else {
                $statisticData = $this->_shoppingPoint->getPoint($customerCode);
                $currentPointBalance = 0;
                if(!$statisticData['error'])
                {
                    $currentPointBalance = isset($statisticData['return']['REST_POINT'])?$statisticData['return']['REST_POINT']:0;
                }
                $order->setRewardPointsBalance($currentPointBalance);
            }
        }


        //set earn point
        if ($earnPoint = $quote->getBonusPointAmount()) {
            $order->setBonusPointAmount($earnPoint);
        }
    }
    /**
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return string|boolean
     */
    private function getCustomerCode($quote)
    {
        $attribute = $quote->getCustomer()->getCustomAttribute('consumer_db_id');
        if ($attribute) {
            return $attribute->getValue();
        }
        return false;
    }
}
