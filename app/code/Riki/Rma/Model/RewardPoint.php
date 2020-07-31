<?php
namespace Riki\Rma\Model;

use Magento\Framework\Exception\LocalizedException;
use Riki\Loyalty\Model\Reward;
use Riki\Rma\Api\Data\Rma\ReturnStatusInterface;
use Riki\Loyalty\Model\ConsumerDb\ShoppingPoint;

class RewardPoint
{
    /**
     * @var \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint
     */
    protected $shoppingPoint;

    /**
     * @var \Riki\Loyalty\Helper\Data
     */
    protected $loyaltyDataHelper;

    /**
     * @var \Riki\Rma\Model\Repository\RewardRepository
     */
    protected $rewardRepository;

    /**
     * @var \Riki\Rma\Model\Repository\RmaRepository
     */
    protected $rmaRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Riki\Rma\Logger\Point\Logger
     */
    protected $logger;

    /**
     * RewardPoint constructor.
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder
     * @param Repository\RmaRepository $rmaRepository
     * @param ShoppingPoint $shoppingPoint
     * @param \Riki\Loyalty\Helper\Data $loyaltyDataHelper
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Repository\RewardRepository $rewardRepository
     * @param \Riki\Rma\Helper\Data $dataHelper
     * @param \Riki\Rma\Logger\Point\Logger $logger
     */
    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder,
        \Riki\Rma\Model\Repository\RmaRepository $rmaRepository,
        \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint $shoppingPoint,
        \Riki\Loyalty\Helper\Data $loyaltyDataHelper,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Rma\Model\Repository\RewardRepository $rewardRepository,
        \Riki\Rma\Helper\Data $dataHelper,
        \Riki\Rma\Logger\Point\Logger $logger
    ) {
        $this->functionCache = $functionCache;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->rmaRepository = $rmaRepository;
        $this->shoppingPoint = $shoppingPoint;
        $this->loyaltyDataHelper = $loyaltyDataHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->rewardRepository = $rewardRepository;
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
    }

    /**
     * Get earned rewards by rma
     *
     * @param \Riki\Rma\Model\Rma $rma
     *
     * @return \Riki\Loyalty\Model\Reward[]
     * @throws \Exception
     */
    public function getCancelableReward(\Riki\Rma\Model\Rma $rma)
    {
        $allowedStatus = [
            Reward::STATUS_TENTATIVE,
            Reward::STATUS_SHOPPING_POINT,
            Reward::STATUS_REDEEMED
        ];
        $query = $this->searchCriteriaBuilder
            ->addFilter('status', $allowedStatus, 'in')
            ->addFilter('order_item_id', $rma->getOrderItemIds(['inclParent' => true]), 'in')
            ->addFilter('level', Reward::LEVEL_ITEM)
            ->setSortOrders([$this->sortOrderBuilder->setField('point')->setDescendingDirection()->create()])
            ->create();

        /** @var \Riki\Loyalty\Model\Reward[] $result */
        $result = $this->rewardRepository->getList($query)->getItems();

        if ($rma->isTriggerCancelPoint()) {
            // should improve repository to able use OR query
            $query = $this->searchCriteriaBuilder
                ->addFilter('status', $allowedStatus, 'in')
                ->addFilter('order_no', $rma->getOrderIncrementId())
                ->addFilter('level', Reward::LEVEL_ORDER)
                ->setSortOrders([$this->sortOrderBuilder->setField('point')->setDescendingDirection()->create()])
                ->create();
            $subResult = $this->rewardRepository->getList($query)->getItems();
            $result = array_merge($result, $subResult);
        }

        return $result;
    }

    /**
     * Get order level reward
     *
     * @param Rma $rma
     *
     * @return \Riki\Loyalty\Model\Reward|null
     * @throws \Exception
     */
    public function getOrderLevelReward(\Riki\Rma\Model\Rma $rma)
    {
        $cacheKey = [$rma->getOrderIncrementId()];
        if ($this->functionCache->has($cacheKey)) {
            return $this->functionCache->load($cacheKey);
        }

        $allowedStatus = [
            Reward::STATUS_TENTATIVE,
            Reward::STATUS_SHOPPING_POINT,
            Reward::STATUS_REDEEMED
        ];
        $query = $this->searchCriteriaBuilder
            ->addFilter('status', $allowedStatus, 'in')
            ->addFilter('order_no', $rma->getOrderIncrementId())
            ->addFilter('level', Reward::LEVEL_ORDER)
            ->setPageSize(1)
            ->create();
        /* @var \Riki\Loyalty\Model\Reward[] $items */
        $items = $this->rewardRepository->getList($query)->getItems();

        $result = $items ? end($items) : null;
        $this->functionCache->store($result, $cacheKey);

        return $result;
    }

    /**
     * Cancel earned point
     *
     * @param \Riki\Rma\Model\Rma $rma
     *
     * @return RewardPoint
     * @throws LocalizedException
     * @throws \Exception
     */
    public function cancelPoint(\Riki\Rma\Model\Rma $rma)
    {
        if ($rma->getData('substitution_order')) {
            return $this;
        }

        if ($rma->getData('return_status') != ReturnStatusInterface::COMPLETED) {
            return $this;
        }

        if ($rma->getOrigData('return_status') == ReturnStatusInterface::COMPLETED) {
            return $this;
        }

        $pointAdjusted = intval($rma->getData('total_cancel_point_adjusted')) ?: 0;
        if ($pointAdjusted <= 0) {
            return $this;
        }

        $result = $this->shoppingPoint->setPoint(
            ShoppingPoint::REQUEST_TYPE_USE,
            $rma->getConsumerDbId(),
            [
                'pointIssueType' => ShoppingPoint::ISSUE_TYPE_ADJUSTMENT,
                'description' => 'Point cancellation after return',
                'pointAmountId' => 'MAGENTO_SHOPPINGPOINT',
                'point' => $pointAdjusted,
                'orderNo' => $rma->getOrderId()
            ]
        );

        if (isset($result['error']) && $result['error'] === true) {
            $this->logger->error(__(
                'Return #%1 - Cancel Point API error:%2, Request: %3, Response: %4',
                $rma->getIncrementId(),
                $result['msg'],
                isset($result['request'])? $result['request'] : null,
                isset($result['response'])? $result['response'] : null
            ));
            throw new LocalizedException(__('Cancel Point API error: %1', $result['msg']));
        }

        $cancelRewards = [];
        $orderItemsQty = $rma->getOrderItemIds(['inclParent' => true, 'inclQty' => true]);
        foreach ($this->getCancelableReward($rma) as $reward) {
            $qty = isset($orderItemsQty[$reward->getData('order_item_id')])
                ? $orderItemsQty[$reward->getData('order_item_id')]
                : $reward->getData('qty');

            if ($reward->getData('level') == Reward::LEVEL_ORDER) {
                $qty = 1;
            }

            $cancelRewards[] = $this->rewardRepository->createFromArray([
                'website_id' => $reward->getData('website_id'),
                'customer_id' => $reward->getData('customer_id'),
                'customer_code' => $reward->getData('customer_code'),
                'action_date' => $this->loyaltyDataHelper->pointActionDate(),
                'expiry_period' => $reward->getData('expiry_period'),
                'status' => Reward::STATUS_CANCEL,
                'point' => $reward->getData('point'),
                'point_type' => $reward->getData('point_type'),
                'order_no' => $reward->getData('order_no'),
                'order_item_id' => $reward->getData('order_item_id'),
                'serial_code' => $reward->getData('serial_code'),
                'wbs_code' => $reward->getData('wbs_code'),
                'account_code' => $reward->getData('account_code'),
                'description' => 'Reverse',
                'sku' => $reward->getData('sku'),
                'qty' => $qty,
                'level' => $reward->getData('level')
            ]);
        }

        if (!$cancelRewards) {
            return $this;
        }

        $pointAdj = intval($rma->getData('total_cancel_point_adj'));
        if ($pointAdj) {
            $orderLevelReward = $this->getOrderLevelReward($rma);
            if ($orderLevelReward) { // have reward which is order level
                $cancelRewards[] = $this->rewardRepository->createFromArray([
                    'website_id' => $orderLevelReward->getData('website_id'),
                    'customer_id' => $orderLevelReward->getData('customer_id'),
                    'customer_code' => $orderLevelReward->getData('customer_code'),
                    'action_date' => $this->loyaltyDataHelper->pointActionDate(),
                    'expiry_period' => $orderLevelReward->getData('expiry_period'),
                    'status' => Reward::STATUS_CANCEL,
                    'point' => $pointAdj,
                    'point_type' => Reward::TYPE_ADJUSTMENT,
                    'order_no' => $orderLevelReward->getData('order_no'),
                    'order_item_id' => $orderLevelReward->getData('order_item_id'),
                    'serial_code' => $orderLevelReward->getData('serial_code'),
                    'wbs_code' => $orderLevelReward->getData('wbs_code'),
                    'account_code' => $orderLevelReward->getData('account_code'),
                    'description' => 'Reverse',
                    'sku' => $orderLevelReward->getData('sku'),
                    'qty' => $orderLevelReward->getData('qty'),
                    'level' => $orderLevelReward->getData('level')
                ]);
            } else {
                $adj = floor($pointAdj / count($cancelRewards));
                $add = $pointAdj - ($adj * count($cancelRewards));
                $cancelRewards[0]['point'] += $add; // add will into largest point item
                foreach ($cancelRewards as $cancelReward) {
                    $cancelReward['point'] += $adj;
                }
            }
        }

        /* @var \Riki\Loyalty\Model\Reward $cancelReward */
        foreach ($cancelRewards as $cancelReward) {
            $this->rewardRepository->save($cancelReward);
        }

        $this->rmaRepository->save($rma);

        return $this;
    }

    /**
     * Return used points to customer balance
     *
     * @param \Riki\Rma\Model\Rma $rma
     *
     * @return RewardPoint
     * @throws \Zend_Date_Exception
     * @throws LocalizedException
     */
    public function returnPoint(\Riki\Rma\Model\Rma $rma)
    {
        if ($rma->getData('substitution_order')) {
            return $this;
        }

        if ($rma->getData('return_status') != ReturnStatusInterface::COMPLETED) {
            return $this;
        }

        if ($rma->getOrigData('return_status') == ReturnStatusInterface::COMPLETED) {
            return $this;
        }

        $pointAdjusted = intval($rma->getData('total_return_point_adjusted'));

        if ($pointAdjusted < 0) {
            throw new LocalizedException(__('Return point cannot be a negative value.'));
        }

        if (!$pointAdjusted) {
            return $this;
        }

        // If order using "Shopping Point for trial", should not return used shopping point
        $status = Reward::STATUS_ERROR;
        $description = '';
        $pointForTrial = null;
        $order = $this->dataHelper->getRmaOrder($rma);
        if ($order->getData('point_for_trial')) {
            $status = Reward::STATUS_REDEEMED;
            $description = __('注文時特別付与ポイントの無効化');
            $pointForTrial = 1;
        } else {
            $result = $this->shoppingPoint->setPoint(
                ShoppingPoint::REQUEST_TYPE_ALLOCATION,
                $rma->getConsumerDbId(),
                [
                    'pointIssueType' => ShoppingPoint::ISSUE_TYPE_ADJUSTMENT,
                    'description' => 'Returned point after refund',
                    'pointAmountId' => 'MAGENTO_SHOPPINGPOINT',
                    'point' => $pointAdjusted,
                    'orderNo' => $rma->getOrderId(),
                    'scheduledExpiredDate' => $this->loyaltyDataHelper->scheduledExpiredDate()
                ]
            );

            if (isset($result['error']) && $result['error'] === false) {
                $status = Reward::STATUS_PENDING_APPROVAL;
                $description = __('Returned point after being approved the RMA #%1', $rma->getIncrementId());
            }
        }

        /* @var \Riki\Loyalty\Model\Reward $reward */
        $reward = $this->rewardRepository->createFromArray([
            'point' => $pointAdjusted,
            'description' => $description,
            'point_type' => ShoppingPoint::ISSUE_TYPE_ADJUSTMENT,
            'order_no' => $rma->getOrderIncrementId(),
            'expiry_period' => $this->loyaltyDataHelper->getDefaultExpiryPeriod(),
            'status' => $status,
            'customer_id' => $rma->getCustomerId(),
            'customer_code' => $rma->getConsumerDbId(),
            'action_date' => $this->loyaltyDataHelper->pointActionDate(),
            'level' => 0,
            'point_for_trial' => $pointForTrial
        ]);

        $this->rewardRepository->save($reward);

        return $this;
    }
}
