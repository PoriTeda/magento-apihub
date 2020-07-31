<?php

namespace Riki\Loyalty\Observer\Reward;

use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use Magento\Framework\Event\ObserverInterface;
use Bluecom\Paygent\Model\Paygent;
use Magento\Framework\Event\Observer as EventObserver;

use Riki\Loyalty\Model\Reward;

class QuoteSubmitSuccess implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\State
     */
    protected $_state;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory
     */
    protected $_saleRuleCollection;

    /**
     * @var \Riki\Loyalty\Helper\Data
     */
    protected $_loyaltyHelper;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $_orderRepository;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;

    /**
     * @var \Riki\Loyalty\Model\RewardFactory
     */
    protected $_rewardFactory;

    /**
     * @var \Riki\Loyalty\Helper\Email
     */
    protected $_email;

    /**
     * @var string
     */
    protected $_customerCode;

    /**
     * @var integer
     */
    protected $_pointStatus;

    /**
     * @var array
     */
    protected $_fixedPointApplied = [];

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * QuoteSubmitSuccess constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\State $state
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $salesRuleCollectionFactory
     * @param \Riki\Loyalty\Model\RewardFactory $reward
     * @param \Riki\Loyalty\Helper\Email $email
     * @param \Riki\Loyalty\Helper\Data $helper
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\State $state,
        \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $salesRuleCollectionFactory,
        \Riki\Loyalty\Model\RewardFactory $reward,
        \Riki\Loyalty\Helper\Email $email,
        \Riki\Loyalty\Helper\Data $helper,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\Registry $registry
    ) {
        $this->logger = $logger;
        $this->_state = $state;
        $this->_saleRuleCollection = $salesRuleCollectionFactory;
        $this->_loyaltyHelper = $helper;
        $this->_rewardFactory = $reward;
        $this->_email = $email;
        $this->_orderRepository = $orderRepository;
        $this->_registry = $registry;
    }

    /**
     * Add reward point when order purchase, and this points will be applied when delivery complete
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {

        // Reset array applied rule for one process
        if (!empty($this->_fixedPointApplied)) {
            unset($this->_fixedPointApplied);
            $this->_fixedPointApplied = [];
        }

        /**
         * Reset var for message queue
         */
        $this->_pointStatus = Reward::STATUS_TENTATIVE;
        /**
         * @var \Magento\Sales\Model\Order $order
         */
        $this->_order = $observer->getEvent()->getOrder();
        $this->_quote = $observer->getEvent()->getQuote();
        if ($this->_order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return $this;
        }
        if (!$this->_order instanceof \Magento\Sales\Model\AbstractModel) {
            return $this;
        }

        /**
         * Processed for import order form csv
         */
        if( $this->_quote->getData('original_unique_id') !='' || $this->_quote->getData('is_csv_import_order_flag'))
        {
            return $this;
        }

        if ($this->_state->getAreaCode() === 'adminhtml' || $this->_state->getAreaCode() == 'crontab') {
            if (!$this->_order->getData('allowed_earned_point')) {
                return $this;
            } elseif (
                !$this->_order->getData('is_generate') &&
                !$this->_quote->getData('is_simulator') &&
                !$this->_quote->getData('is_oos_order')
            ) {
                $this->_pointStatus = Reward::STATUS_PENDING_APPROVAL;
            }
        }
        $attribute = $this->_quote->getCustomer()->getCustomAttribute('consumer_db_id');
        if (!$attribute) {
            return $this; // do not process without consumerDb ID
        }
        $this->_customerCode = $attribute->getValue();

        try{
            $this->_pointEarnOnPurchase();
        }catch (\Exception $e){
            $this->logger->critical($e);
        }

        if ($this->_pointStatus == Reward::STATUS_PENDING_APPROVAL) {

            $payment = $this->_order->getPayment();

            /** @var \Riki\Loyalty\Model\Reward $rewardModel */
            $rewardModel = $this->_rewardFactory->create();
            $pendingPoint = $rewardModel->getResource()->pointOrderByStatus(
                $this->_order->getIncrementId(),
                Reward::STATUS_PENDING_APPROVAL
            );

            if ($pendingPoint && $payment && $payment->getMethod() !== Paygent::CODE) {
                $order = $this->_order;
                $order->setData('point_pending_status', $this->_order->getStatus());
                $order->setState(\Magento\Sales\Model\Order::STATE_HOLDED);
                $order->addStatusToHistory(
                    OrderStatus::STATUS_ORDER_PENDING_CRD_REVIEW,
                    __('Waiting shopping point approval')
                );

                try {
                    $this->_email->requestApproval($order);
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
        }
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->_quote;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Get rules was applied in this order
     *
     * @return bool| \Magento\Rule\Model\ResourceModel\Rule\Collection\AbstractCollection
     */
    protected function _promotionWasApplied()
    {
        $appliedRuleIds = array_unique(explode(',', $this->_order->getAppliedRuleIds()));
        if (!sizeof($appliedRuleIds)) {
            return false;
        }
        $ruleCollection = $this->_saleRuleCollection->create();
        $connection = $ruleCollection->getResource()->getConnection();
        $ruleCollection->addFieldToFilter('rule_id', ['in' => $appliedRuleIds]);
        $ruleCollection->addFieldToSelect(
            ['rule_id', 'wbs_shopping_point', 'account_code', 'name', 'to_date', 'point_expiration_period']
        );
        $ruleCollection->join(
            array('riki_rewards_salesrule' => $connection->getTableName('riki_rewards_salesrule')),
            'riki_rewards_salesrule.rule_id = main_table.rule_id',
            array('points_delta', 'type_by')
        );
        if (!$ruleCollection->getSize()) {
            return false;
        }
        return $ruleCollection;
    }
    
    /**
     * Earn point in purchase
     *
     * @return void
     */
    protected function _pointEarnOnPurchase()
    {
        /** @var \Magento\Sales\Model\Order\Item $item */
        $promotion = $this->_promotionWasApplied();
        foreach ($this->_order->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            $netSellingPrice = $this->_loyaltyHelper->netSellingPrice($item);
            if ($netSellingPrice <= 0.0001) {
                continue;
            }
            $item->setData('net_selling_price', $netSellingPrice);
            $product = $item->getProduct();
            $this->_pointInProductLevel($product, $item);
            if ($promotion) {
                $this->_pointInPromotionLevel($promotion, $item);
            }
        }
    }

    /**
     * Earn point in purchase: product level
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Sales\Model\Order\Item $cartItem
     * @return void
     */
    protected function _pointInProductLevel($product, $cartItem)
    {
        $additionalData = $cartItem->getData('additional_data');
        if ($additionalData) {
            try {
                $additionalData = \Zend_Json::decode($additionalData);
            } catch (\Zend_Json_Exception $e) {
                $this->logger->warning($cartItem->getId() . '_' . $cartItem->getSku() . '_' . (string)$cartItem->getAdditionalData());
                $this->logger->warning($e);
                $additionalData = [];
            }
        }
        if (isset($additionalData['applied_point_amount'])) {
            $amount = (int)($additionalData['applied_point_amount']/$cartItem->getQtyOrdered());
        } else {
            $pointsDelta = (int) $product->getData('point_currency');
            if (!$pointsDelta) {
                return;
            }
            $price = $cartItem->getData('net_selling_price');
            $amount = $price * (float) $pointsDelta/100;
            $amount = round($amount, 2);
            $amount = floor($amount);
        }

        if ($amount <= 0) {
            return;
        }
        /** @var \Riki\Loyalty\Model\Reward $tentativeModel */
        $tentativeModel = $this->_rewardFactory->create();
        $comment = __('Issued from %1', $this->_order->getIncrementId());
        $insertData = [
            'website_id' => $this->_order->getStore()->getWebsiteId(),
            'sku' => $cartItem->getSku(),
            'wbs_code' => $product->getData('booking_point_wbs'),
            'account_code' => $product->getData('booking_point_account'),
            'point' => $amount,
            'qty' => $cartItem->getQtyOrdered(),
            'description' => $comment,
            'point_type' => Reward::TYPE_PURCHASE,
            'order_no' => $this->_order->getIncrementId(),
            'order_item_id' => $cartItem->getId(),
            'customer_id' => $this->_order->getCustomerId(),
            'customer_code' => $this->_customerCode,
            'status' => $this->_pointStatus,
            'action_date' => $this->_loyaltyHelper->pointActionDate(),
            'expiry_period' => $this->_loyaltyHelper->getDefaultExpiryPeriod(),
            'level' => Reward::LEVEL_ITEM
        ];
        $tentativeModel->addData($insertData)->save();
    }

    /**
     * Earn point in purchase: promotion level
     *
     * @param \Magento\Rule\Model\ResourceModel\Rule\Collection\AbstractCollection $ruleCollection
     * @param \Magento\Sales\Model\Order\Item $cartItem
     * @return void
     */
    protected function _pointInPromotionLevel($ruleCollection, $cartItem)
    {
        $ruleIds = $cartItem->getAppliedRuleIds();
        if (!$ruleIds) {
            return;
        }
        $ruleIds = explode(',', $ruleIds);
        $skuOrder = '' ;
        $netSellingPrice = $cartItem->getData('net_selling_price');
        /** @var \Magento\SalesRule\Model\Rule $rule */
        foreach ($ruleCollection as $rule) {
            //check if this item does not have rule
            if (!in_array($rule->getId(), $ruleIds)) {
                continue;
            }
            // support 2 types of point
            if ($rule->getData('type_by') == 'riki_type_fixed') {
                if (in_array($rule->getId(), $this->_fixedPointApplied)) {
                    continue;
                } else {
                    $pointsDelta = (int) $rule->getData('points_delta');
                    $pointsDelta = floor($pointsDelta);
                    $this->_fixedPointApplied[] = $rule->getId();
                    $level = Reward::LEVEL_ORDER;
                }
            } else {
                $pointsDelta = (float) $rule->getData('points_delta') * $netSellingPrice / 100;
                $pointsDelta = floor($pointsDelta);
                $level = Reward::LEVEL_ITEM;
                $skuOrder = $cartItem->getSku();
            }
            if (!$pointsDelta) {
                continue;
            }
            $expiryPeriod = $rule->getData('point_expiration_period');
            if (!$expiryPeriod) {
                $expiryPeriod = $this->_loyaltyHelper->getDefaultExpiryPeriod();
            }
            /** @var \Riki\Loyalty\Model\Reward $tentativeModel */
            $tentativeModel = $this->_rewardFactory->create();
            $insertData = [
                'website_id' => $this->_order->getStore()->getWebsiteId(),
                'sku' => $skuOrder,
                'wbs_code' => $rule->getData('wbs_shopping_point'),
                'account_code' => $rule->getData('account_code'),
                'point' => $pointsDelta,
                'qty' => $level == Reward::LEVEL_ITEM ? $cartItem->getQtyOrdered(): 1,
                'description' => __('Issued from %1', $rule->getId()),
                'sales_rule_id' =>  $rule->getId(),
                'point_type' => Reward::TYPE_CAMPAIGN,
                'order_no' => $this->_order->getIncrementId(),
                'order_item_id' => $cartItem->getId(),
                'customer_id' => $this->_order->getCustomerId(),
                'customer_code' => $this->_customerCode,
                'status' => $this->_pointStatus,
                'action_date' => $this->_loyaltyHelper->pointActionDate(),
                'expiry_period' => $expiryPeriod,
                'level' => $level
            ];
            $tentativeModel->addData($insertData)->save();
        }
    }
}
