<?php

namespace Riki\Loyalty\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\PaymentException;

class RewardManagement
{
    const VALIDATE_CARD_AMOUNT = 1; //1$

    /**
     * @var ConsumerDb\ShoppingPoint
     */
    protected $_shoppingPoint;

    /**
     * @var ConsumerDb\CustomerSub
     */
    protected $_customerSub;

    /**
     * @var RewardFactory
     */
    protected $_rewardFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var ResourceModel\RewardFactory
     */
    protected $_rewardResourceFactory;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $_cartRepository;

    /**
     * @var \Magento\SalesRule\Api\RuleRepositoryInterface
     */
    protected $_ruleRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchBuilder;

    /**
     * @var \Riki\Loyalty\Helper\Data
     */
    protected $_loyaltyHelper;

    protected $_rewardSetting = [];
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    /**
     * @var \Riki\Loyalty\Helper\ConsumerLog
     */
    protected $_apiLogger;
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * RewardManagement constructor.
     *
     * @param ConsumerDb\ShoppingPoint $shoppingPoint
     * @param ConsumerDb\CustomerSub $customerSub
     * @param RewardFactory $rewardFactory
     */
    public function __construct(
        ConsumerDb\ShoppingPoint $shoppingPoint,
        ConsumerDb\CustomerSub $customerSub,
        RewardFactory $rewardFactory,
        ResourceModel\RewardFactory $rewardResourceFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepository,
        \Riki\Loyalty\Helper\Data $loyaltyHelper,
        \Magento\Framework\Registry $registry,
        \Riki\Loyalty\Helper\ConsumerLog $apiLogger,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
    )
    {
        $this->_shoppingPoint = $shoppingPoint;
        $this->_customerSub = $customerSub;
        $this->_rewardFactory = $rewardFactory;
        $this->_rewardResourceFactory = $rewardResourceFactory;
        $this->_customerRepository = $customerRepository;
        $this->_cartRepository = $cartRepositoryInterface;
        $this->_searchBuilder = $searchCriteriaBuilder;
        $this->_ruleRepository = $ruleRepository;
        $this->_loyaltyHelper = $loyaltyHelper;
        $this->_coreRegistry = $registry;
        $this->_apiLogger = $apiLogger;
        $this->courseFactory = $courseFactory;
    }

    /**
     * Get customer point balance
     *
     * @param string $customerCode
     * @param boolean $isSimulator
     * @return int
     */
    public function getPointBalance($customerCode, $isSimulator = false)
    {
        $result = $this->_shoppingPoint->getPoint($customerCode,\Riki\Loyalty\Model\ConsumerDb\ShoppingPoint::TYPE_POINT, $isSimulator);
        return $result['error'] ? 0 : $result['return']['REST_POINT'];
    }

    /**
     * Get reward user setting
     *
     * @param string $customerCode
     * @return array
     */
    public function getRewardUserSetting($customerCode)
    {
        $keyIds = [ConsumerDb\CustomerSub::USE_POINT_TYPE, ConsumerDb\CustomerSub::USE_POINT_AMOUNT];
        $rewardSetting = $this->_customerSub->getCustomerSub($customerCode, $keyIds);
        $result = [
            'use_point_type' => 0,
            'use_point_amount' => 0
        ];
        if (!$rewardSetting['error'] && isset($rewardSetting['value'][$customerCode][ConsumerDb\CustomerSub::USE_POINT_TYPE])) {
            $result['use_point_type'] = $rewardSetting['value'][$customerCode][ConsumerDb\CustomerSub::USE_POINT_TYPE]['value_name'];
        }
        if (!$rewardSetting['error'] && isset($rewardSetting['value'][$customerCode][ConsumerDb\CustomerSub::USE_POINT_AMOUNT])) {
            $result['use_point_amount'] = $rewardSetting['value'][$customerCode][ConsumerDb\CustomerSub::USE_POINT_AMOUNT]['value_name'];
        }
        $this->_rewardSetting[$customerCode] =  $result;
        return $result;
    }

    /**
     * Convert point to amount
     *
     * @param integer $rewardPoints
     * @return float
     */
    public function convertPointToAmount($rewardPoints)
    {
        //currently, rate is 1:1
        $amount = (float)sprintf('%.4F', $rewardPoints);
        return $amount;
    }

    /**
     * Check reward points balance
     *
     * @param Order $order
     * @return void
     * @throws \Magento\Framework\Exception\PaymentException
     */
    public function validate(Order $order)
    {
        return true;
    }

    /**
     * @param Order $order
     * @return $this
     * @throws PaymentException
     */
    public function redeemForOrder(Order $order)
    {
        try {
            $customer = $this->_customerRepository->getById($order->getCustomerId());
        } catch (NoSuchEntityException $e) {
            return $this;
        }

        $timesRetry = $this->_loyaltyHelper->getDefaultRetryPoint();
        $customAttribute = $customer->getCustomAttribute('consumer_db_id');

        if (!$customAttribute) {
            return $this;
        }
        $customerCode = $customAttribute->getValue();
        $registryKeyPoint = "customer_point_balance_{$customerCode}";
        /** @var Reward $rewardPoint */
        $rewardPoint = $this->_rewardFactory->create();
        $hasPointForTrial = ($order->getPointForTrial()>0)?true:false;
        $wbs = null;
        $accountCode = null;
        if ($hasPointForTrial) {
            $courseId = $order->getRikiCourseId();
            $wbs = null;
            $accountCode = null;
            if($courseId != null) {
                $courseModel = $this->courseFactory->create()->load($courseId);
                if ($courseModel->getId()) {
                    $wbs = $courseModel->getData('point_for_trial_wbs');
                    $accountCode = $courseModel->getData('point_for_trial_account_code');
                }
            }
        }
        $data = [
            'website_id' => $order->getStore()->getWebsiteId(),
            'point' => $order->getUsedPoint(),
            'description' => __('Redemption'),
            'customer_id' => $customer->getId(),
            'customer_code' => $customerCode,
            'point_type' => \Riki\Loyalty\Model\Reward::TYPE_ORDER_DISCOUNT,
            'status' => Reward::STATUS_REDEEMED,
            'order_no' => $order->getIncrementId(),
            'action_date' => $this->_loyaltyHelper->pointActionDate(),
            'expiry_period' => null,
            'wbs_code' => $wbs,
            'account_code' => $accountCode,
            'point_for_trial' => $hasPointForTrial
        ];
        $rewardPoint->setData($data);
        if (!$hasPointForTrial) {
            $parameters = [
                'pointIssueType' => ConsumerDb\ShoppingPoint::ISSUE_TYPE_DISCOUNT,
                'description' => sprintf('購入時使用ポイント(受注番号:%s)', $order->getIncrementId()),
                'pointAmountId' => ConsumerDb\ShoppingPoint::POINT_AMOUNT_ID,
                'point' => $order->getUsedPoint(),
                'orderNo' => $order->getIncrementId()
            ];
            $response = $this->_shoppingPoint->setPoint(
                ConsumerDb\ShoppingPoint::REQUEST_TYPE_USE, $customerCode, $parameters
            );
            if ($response['error']) {
                // Count times to retry create order with without using point
                $countRetry = 0;
                if ($this->_coreRegistry->registry('order_retry-' . $customerCode)) {
                    $countRetry = $this->_coreRegistry->registry('order_retry-' . $customerCode);
                    $this->_coreRegistry->unregister('order_retry-' . $customerCode);
                    if ($countRetry <= $timesRetry) {
                        $this->_coreRegistry->register('order_retry-' . $customerCode, $countRetry + 1);
                    }

                } else {
                    $this->_coreRegistry->register('order_retry-' . $customerCode, $countRetry + 1);
                }
                throw new PaymentException(__($response['msg']));
            }
            $pointBalance = $this->_coreRegistry->registry($registryKeyPoint);
            if (!$pointBalance['error']) {
                $totalBalance = (int)$pointBalance['return']['REST_POINT'];
                $pointRemain = $totalBalance - $order->getUsedPoint();
                $pointBalance['return']['REST_POINT'] = $pointRemain;
                $this->_coreRegistry->unregister($registryKeyPoint);
                $this->_coreRegistry->register($registryKeyPoint, $pointBalance);
            }
        }
        return $rewardPoint->save();
    }

    /**
     * Revert point redeemed when order failure
     *
     * @param Order $order
     * @return boolean
     * @throws PaymentException
     */
    public function revertRedeemed(Order $order)
    {
        $apiRequest = $apiResponse = '';
        $logMsg = __("Revert point for customer : %1", $order->getCustomerId());
        $status = \Riki\Customer\Model\ConsumerLog::STATUS_ERROR;

        try {
            $customer = $this->_customerRepository->getById($order->getCustomerId());
        } catch (NoSuchEntityException $e) {
            $this->_apiLogger->saveAPILog('setShoppingPoint', $logMsg, $status, $apiRequest, $apiResponse,false,true);
            return false;
        }

        $customAttribute = $customer->getCustomAttribute('consumer_db_id');
        if (!$customAttribute) {
            $logMsg = __("Revert point has error consumer for customer : %1", $order->getCustomerId());
            $this->_apiLogger->saveAPILog('setShoppingPoint', $logMsg, $status, $apiRequest, $apiResponse,false,true);
            return false;
        }

        $customerCode = $customAttribute->getValue();
        $parameters = [
            'pointIssueType' => ConsumerDb\ShoppingPoint::ISSUE_TYPE_PURCHASE,
            'description' => '受注キャンセルのため無効化', //Point redemption cancellation
            'pointAmountId' => ConsumerDb\ShoppingPoint::POINT_AMOUNT_ID,
            'point' => $order->getUsedPoint(),
            'orderNo' => $order->getIncrementId()
        ];

        $response = $this->_shoppingPoint->setPoint(
            ConsumerDb\ShoppingPoint::REQUEST_TYPE_CANCEL, $customerCode, $parameters
        );

        if ($response['error']) {
            throw new PaymentException(__($response['msg']));
        }

        /** @var \Riki\Loyalty\Model\ResourceModel\Reward $resourceModel */
        $resourceModel = $this->_rewardResourceFactory->create();
        $resourceModel->revertRedeem($order->getIncrementId());

        return true;
    }

    /**
     * Special case means this cart is subscription order with use all point to pay
     *
     * @param int|\Magento\Quote\Model\Quote $quote
     * @param int|null $storeId
     * @return boolean
     */
    public function isSpecialCase($quote, $storeId = null)
    {
        if (!$quote instanceof \Magento\Quote\Api\Data\CartInterface) {
            $sharedStoreIds = [];
            if ($storeId) {
                $sharedStoreIds[] = $storeId;
            }
            $quote = $this->_cartRepository->get((int) $quote, $sharedStoreIds);
        }
        if (!$quote->getData('riki_course_id')) {
            return false;
        }
        if ($quote->getBaseGrandTotal() < 0.0001 && (float) $quote->getUsedPointAmount()) {
            return true;
        }
        return false;
    }

    /**
     * Check order point
     *
     * @param Order $order
     * @return boolean|array
     */
    public function checkPointExpiration(Order $order)
    {
        try {
            $customer = $this->_customerRepository->getById($order->getCustomerId());
        } catch (NoSuchEntityException $e) {
            return false;
        }

        $customAttribute = $customer->getCustomAttribute('consumer_db_id');
        if (!$customAttribute) {
            return false;
        }
        $customerCode = $customAttribute->getValue();
        $parameters = [
            'pointIssueType' => ConsumerDb\ShoppingPoint::ISSUE_TYPE_PURCHASE,
            'description' => '受注キャンセルのため無効化', //Point redemption cancellation
            'pointAmountId' => ConsumerDb\ShoppingPoint::POINT_AMOUNT_ID,
            'point' => $order->getUsedPoint(),
            'orderNo' => $order->getIncrementId()
        ];

        return $this->_shoppingPoint->checkPoint(
            ConsumerDb\ShoppingPoint::REQUEST_TYPE_EXPIRATION, $customerCode, $parameters
        );
    }
}