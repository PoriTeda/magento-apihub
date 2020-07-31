<?php
namespace Riki\Rma\Helper;

use Riki\Loyalty\Model\ConsumerDb\ShoppingPoint;

class Point extends \Magento\Framework\App\Helper\AbstractHelper
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
     * @var \Riki\Rma\Api\ItemRepositoryInterface
     */
    protected $rmaItemRepository;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Riki\Framework\Helper\Datetime
     */
    protected $datetimeHelper;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Riki\Rma\Model\ItemFactory
     */
    protected $rmaItemFactory;

    /**
     * @var \Riki\Rma\Model\RmaFactory
     */
    protected $rmaFactory;

    /**
     * Point constructor.
     *
     * @param \Riki\Rma\Model\RmaFactory $rmaFactory
     * @param \Riki\Rma\Model\ItemFactory $rmaItemFactory
     * @param Data $dataHelper
     * @param \Riki\Framework\Helper\Datetime $datetimeHelper
     * @param \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Riki\Rma\Model\Repository\RewardRepository $rewardRepository
     * @param \Riki\Loyalty\Helper\Data $loyaltyDataHelper
     * @param ShoppingPoint $shoppingPoint
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Riki\Rma\Model\RmaFactory $rmaFactory,
        \Riki\Rma\Model\ItemFactory $rmaItemFactory,
        \Riki\Rma\Helper\Data $dataHelper,
        \Riki\Framework\Helper\Datetime $datetimeHelper,
        \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Riki\Rma\Model\Repository\RewardRepository $rewardRepository,
        \Riki\Loyalty\Helper\Data $loyaltyDataHelper,
        \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint $shoppingPoint,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->rmaFactory = $rmaFactory;
        $this->rmaItemFactory = $rmaItemFactory;
        $this->dataHelper = $dataHelper;
        $this->datetimeHelper = $datetimeHelper;
        $this->rmaItemRepository = $rmaItemRepository;
        $this->searchHelper = $searchHelper;
        $this->functionCache = $functionCache;
        $this->rewardRepository = $rewardRepository;
        $this->loyaltyDataHelper = $loyaltyDataHelper;
        $this->shoppingPoint = $shoppingPoint;
        parent::__construct($context);
    }

    /**
     * Call api to ConsumerDB for cancel point
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool
     */
    public function cancelPointOnConsumerDb(\Magento\Rma\Model\Rma $rma)
    {
        /** @var \Riki\Rma\Model\Rma $newRma */
        $newRma = $this->rmaFactory->create(['data' => $rma->getData()]);

        $pointAdjusted = intval($rma->getData('total_cancel_point_adjusted')) ?: 0;
        if ($pointAdjusted <= 0) {
            return true;
        }

        $pointCancel = intval($rma->getData('total_cancel_point'))?: 1;
        $prorata = $pointAdjusted / $pointCancel;
        $consumerId = $this->dataHelper->getRmaCustomerConsumerDbId($rma);
        try {
            $pointFinal = 0;
            $newRewardList = [];
            $skuList = [];
            $orderItemIds = [];
            $items = $newRma->getRmaItems();
            foreach ($items as $item) {
                $parentOrderItem = $item->getParentOrderItem();
                $orderItem = $item->getOrderItem();
                $qty = $parentOrderItem
                    ? (floatval($item->getData('qty_requested')) * ($parentOrderItem->getQtyOrdered()/$orderItem->getQtyOrdered()))
                    : floatval($item->getData('qty_requested'));
                $orderItem = $parentOrderItem ?: $orderItem;
                if (isset($orderItemIds[$orderItem->getItemId()])) {
                    continue;
                }
                $skuList[] = $orderItem->getSku();
                $orderItemIds[$orderItem->getItemId()] = floor($qty);
            }

            $rewards = $this->searchHelper
                ->getByOrderNo($rma->getOrderIncrementId())
                ->getBySku($skuList)
                ->getByOrderItemId(array_keys($orderItemIds))
                ->getByStatus(4, 'neq')
                ->sortByPoint(\Magento\Framework\Api\SortOrder::SORT_DESC)
                ->getAll()
                ->execute($this->rewardRepository);
            foreach ($rewards as $reward) {
                if ($reward->getData('level') == 1) {
                    if ($rma->getData('full_partial') == \Riki\Rma\Api\Data\Rma\TypeInterface::PARTIAL
                        && $this->dataHelper->getRmaReasonDueTo($rma) == \Riki\Rma\Api\Data\Reason\DuetoInterface::NESTLE
                    ) {
                        continue;
                    }
                    $qty = 1;
                } else {
                    $qty = isset($orderItemIds[$reward->getData('order_item_id')])
                        ? $orderItemIds[$reward->getData('order_item_id')]
                        : 1;
                }

                if (!$newRewardList) {
                    $point = ceil(floatval($reward->getData('point')) * $prorata);
                } else {
                    $point = floor(floatval($reward->getData('point')) * $prorata);
                }

                $newReward = $this->rewardRepository->createFromArray([
                    'website_id' => $reward->getData('website_id'),
                    'customer_id' => $reward->getData('customer_id'),
                    'customer_code' => $reward->getData('customer_code'),
                    'action_date' => $this->loyaltyDataHelper->pointActionDate(),
                    'description' => 'Reverse',
                    'point_type' => $reward->getData('point_type'),
                    'order_no' => $rma->getOrderIncrementId(),
                    'order_item_id' => $reward->getData('order_item_id'),
                    'serial_code' => $reward->getData('serial_code'),
                    'sku' => $reward->getData('sku'),
                    'wbs_code' => $reward->getData('wbs_code'),
                    'account_code' => $reward->getData('account_code'),
                    'sales_rule_id' => $reward->getData('sales_rule_id'),
                    'level' => $reward->getData('level'),
                    'status' => 4, //@todo verify status cancel constant from Riki_Loyalty
                    'point' => $point,
                    'qty' => $qty
                ]);
                $newRewardList[] = $newReward;
                $pointFinal += $point * $qty;
            }
            if (!$pointFinal) {
                return true;
            }
            try {
                $result = $this->shoppingPoint->setPoint(ShoppingPoint::REQUEST_TYPE_USE, $consumerId, [
                    'pointIssueType' => ShoppingPoint::ISSUE_TYPE_ADJUSTMENT,
                    'description' => 'Point cancellation after return',
                    'pointAmountId' => 'MAGENTO_SHOPPINGPOINT',
                    'point' => $pointFinal,
                    'orderNo' => $rma->getOrderId()
                ]);
                if (isset($result['error']) && $result['error'] === true) {
                    return false;
                }

                $newRewardIds = [];
                foreach ($newRewardList as $newReward) {
                    $this->rewardRepository->save($newReward);
                    $newRewardIds[] = $newReward->getId();
                }

                $extensionData = \Zend_Json::decode($rma->getData('extension_data') ?: '{}');
                $extensionData['point']['cancel'] = [
                    'point' => $pointFinal,
                    'rewardIds' => $newRewardIds
                ];
                $rma->setData('extension_data', \Zend_Json::encode($extensionData));
            } catch (\Exception $e) {
                $this->_logger->critical($e);
                return false;
            }

        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return false;
        }

        return true;
    }

    /**
     * Call api to ConsumerDB for return point
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool
     */
    public function returnPointOnConsumerDb(\Magento\Rma\Model\Rma $rma)
    {
        $returnPoint = $rma->getData('total_return_point_adjusted') ?: 0;
        if ($returnPoint <= 0) {
            return true;
        }
        $consumerId = $this->dataHelper->getRmaCustomerConsumerDbId($rma);
        try {
            $result = $this->shoppingPoint->setPoint(ShoppingPoint::REQUEST_TYPE_ALLOCATION, $consumerId, [
                'pointIssueType' => ShoppingPoint::ISSUE_TYPE_ADJUSTMENT,
                'description' => 'Returned point after refund',
                'pointAmountId' => 'MAGENTO_SHOPPINGPOINT',
                'point' => $returnPoint,
                'orderNo' => $rma->getOrderId(),
                'scheduledExpiredDate' => $this->loyaltyDataHelper->scheduledExpiredDate()
            ]);
            $status = \Riki\Loyalty\Model\Reward::STATUS_ERROR;
            if (isset($result['error']) && $result['error'] === false) {
                $status = \Riki\Loyalty\Model\Reward::STATUS_PENDING_APPROVAL;
            }
            /** @var \Riki\Loyalty\Model\Reward $reward */
            $reward = $this->rewardRepository->createFromArray([
                'date' => $this->datetimeHelper->toDb(),
                'point' => $returnPoint,
                'description' => 'Returned point after refund',
                'point_type' => ShoppingPoint::ISSUE_TYPE_ADJUSTMENT,
                'order_no' => $rma->getOrderIncrementId(),
                'expiry_period' => $this->loyaltyDataHelper->getDefaultExpiryPeriod(),
                'status' => $status,
                'customer_id' => $rma->getData('customer_id'),
                'customer_code' => $consumerId,
                'action_date' => $this->loyaltyDataHelper->pointActionDate(),
                'level' => 0
            ]);
            $this->rewardRepository->save($reward);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return false;
        }

        return isset($result['error']) ? !$result['error'] : false;
    }

    /**
     * Call api to ConsumerDB for revert cancel point
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool
     */
    public function revertCancelPointOnConsumerDb(\Magento\Rma\Model\Rma $rma)
    {
        try {
            $extensionData = \Zend_Json::decode($rma->getData('extension_data') ?: '{}');
            if (!isset($extensionData['point']['cancel'])) {
                return true;
            }
            $point = isset($extensionData['point']['cancel']['point'])
                ? $extensionData['point']['cancel']['point'] :
                0;
            $rewardIds = isset($extensionData['point']['cancel']['rewardIds'])
                ? $extensionData['point']['cancel']['rewardIds'] :
                [];
            if (!$point) {
                return true;
            }

            $consumerId = $this->dataHelper->getRmaCustomerConsumerDbId($rma);
            $result = $this->shoppingPoint->setPoint(ShoppingPoint::REQUEST_TYPE_ALLOCATION, $consumerId, [
                'pointIssueType' => ShoppingPoint::ISSUE_TYPE_ADJUSTMENT,
                'description' => 'Returned point after reject by SC',
                'pointAmountId' => 'MAGENTO_SHOPPINGPOINT',
                'point' => $point,
                'orderNo' => $rma->getOrderId(),
                'scheduledExpiredDate' => $this->loyaltyDataHelper->scheduledExpiredDate()
            ]);
            if (isset($result['error']) && $result['error'] === false) {
                foreach ($rewardIds as $rewardId) {
                    $this->rewardRepository->deleteById($rewardId);
                }
            }
            unset($extensionData['point']['cancel']);
            $rma->setData('extension_data', \Zend_Json::encode($extensionData));
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return false;
        }

        return isset($result['error']) ? !$result['error'] : false;
    }
}
