<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Reward\Model\SalesRule\RewardPointCounter;

class EarnForOrder implements ObserverInterface
{
    /**
     * Reward place order restriction interface
     *
     * @var \Magento\Reward\Observer\PlaceOrder\RestrictionInterface
     */
    protected $_restriction;

    /**
     * Reward model factory
     *
     * @var \Magento\Reward\Model\RewardFactory
     */
    protected $_modelFactory;

    /**
     * Reward resource model factory
     *
     * @var \Magento\Reward\Model\ResourceModel\RewardFactory
     * @deprecated 101.0.0
     */
    protected $_resourceFactory;

    /**
     * Core model store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Reward helper.
     *
     * @var \Magento\Reward\Helper\Data
     */
    protected $rewardHelper;

    /**
     * @var RewardPointCounter
     */
    private $rewardPointCounter;

    /**
     * @param \Magento\Reward\Observer\PlaceOrder\RestrictionInterface $restriction
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Reward\Model\RewardFactory $modelFactory
     * @param \Magento\Reward\Model\ResourceModel\RewardFactory $resourceFactory
     * @param \Magento\Reward\Helper\Data $rewardHelper
     * @param RewardPointCounter|null $rewardPointCounter
     */
    public function __construct(
        \Magento\Reward\Observer\PlaceOrder\RestrictionInterface $restriction,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Reward\Model\RewardFactory $modelFactory,
        \Magento\Reward\Model\ResourceModel\RewardFactory $resourceFactory,
        \Magento\Reward\Helper\Data $rewardHelper,
        RewardPointCounter $rewardPointCounter = null
    ) {
        $this->_restriction = $restriction;
        $this->_storeManager = $storeManager;
        $this->_modelFactory = $modelFactory;
        $this->_resourceFactory = $resourceFactory;
        $this->rewardHelper = $rewardHelper;
        $this->rewardPointCounter = $rewardPointCounter ?: ObjectManager::getInstance()->get(RewardPointCounter::class);
    }

    /**
     * Increase reward points balance for sales rules applied to order.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->_restriction->isAllowed() === false) {
            return;
        }

        /* @var $order \Magento\Sales\Model\Order */
        $order = $observer->getEvent()->getOrder();
        $appliedRuleIds = array_unique(explode(',', $order->getAppliedRuleIds()));
        $pointsDelta = $this->rewardPointCounter->getPointsForRules($appliedRuleIds);

        if ($pointsDelta && !$order->getCustomerIsGuest()) {
            $reward = $this->_modelFactory->create();
            $reward->setCustomerId(
                $order->getCustomerId()
            )->setWebsiteId(
                $this->_storeManager->getStore($order->getStoreId())->getWebsiteId()
            )->setPointsDelta(
                $pointsDelta
            )->setAction(
                \Magento\Reward\Model\Reward::REWARD_ACTION_SALESRULE
            )->setActionEntity(
                $order
            )->updateRewardPoints();

            $order->addStatusHistoryComment(
                __(
                    'Customer earned promotion extra %1.',
                    $this->rewardHelper->formatReward($pointsDelta)
                )
            );
        }
    }
}
