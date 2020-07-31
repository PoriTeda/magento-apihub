<?php

namespace Riki\SalesRule\Observer;

class RevertRuleUsage implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $ruleFactory;
    /**
     * @var \Magento\SalesRule\Model\Rule\CustomerFactory
     */
    protected $ruleCustomerFactory;
    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory
     */
    protected $couponCollectionFactory;
    /**
     * @var \Riki\SalesRule\Model\ResourceModel\Coupon\Usage
     */
    protected $couponUsageResource;
    /**
     * @var \Riki\SalesRule\Logger\SalesRule
     */
    protected $logger;

    /**
     * RevertRuleUsage constructor.
     *
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     * @param \Magento\SalesRule\Model\Rule\CustomerFactory $ruleCustomerFactory
     * @param \Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory $couponCollectionFactory
     * @param \Riki\SalesRule\Model\ResourceModel\Coupon\Usage $couponUsageResource
     * @param \Riki\SalesRule\Logger\SalesRule $logger
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\SalesRule\Model\Rule\CustomerFactory $ruleCustomerFactory,
        \Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory $couponCollectionFactory,
        \Riki\SalesRule\Model\ResourceModel\Coupon\Usage $couponUsageResource,
        \Riki\SalesRule\Logger\SalesRule $logger
    ) {
        $this->timezone = $timezone;
        $this->ruleFactory = $ruleFactory;
        $this->ruleCustomerFactory = $ruleCustomerFactory;
        $this->couponCollectionFactory = $couponCollectionFactory;
        $this->couponUsageResource = $couponUsageResource;
        $this->logger = $logger;
        $this->logger->setTimezone(
            new \DateTimeZone($this->timezone->getConfigTimezone())
        );
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        /*Simulate doesn't need to update Sale Rule */
        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return;
        }

        /*list rule which order was applied*/
        $appliedRuleIds = $order->getAppliedRuleIds();

        if (empty($appliedRuleIds)) {
            return;
        }

        $ruleIds = explode(',', $appliedRuleIds);

        if (empty($ruleIds)) {
            return;
        }

        $this->revertRuleUsage($order, $ruleIds);
    }

    /**
     * revert rule usage for order
     *
     * @param \Magento\Sales\Model\Order $order
     * @param $ruleIds
     */
    public function revertRuleUsage(\Magento\Sales\Model\Order $order, $ruleIds)
    {
        if (empty($ruleIds) || empty($order->getAppliedRuleIds())) {
            return;
        }

        $this->logger->info('Revert Applied Rule for order #'. $order->getIncrementId());

        foreach ($ruleIds as $ruleId) {
            $this->revertRuleUsageByRuleId($order, $ruleId);
        }

        $this->logger->info('Revert Applied Rule for order #'. $order->getIncrementId().' success.');
    }

    /**
     * revert rule usage by rule id
     *
     * @param \Magento\Sales\Model\Order $order
     * @param $ruleId
     */
    public function revertRuleUsageByRuleId(\Magento\Sales\Model\Order $order, $ruleId)
    {
        /** @var \Magento\SalesRule\Model\Rule $rule */
        $rule = $this->getRuleById($ruleId);

        if (!$rule) {
            $this->logger->info('Rule Id: '. $ruleId. ' no longer exists.');
            return;
        }
        $rule->loadCouponCode();

        $this->logger->info('Revert Applied Rule Id: '. $ruleId);

        $this->revertRuleTimesUsed($rule);

        $this->revertRuleCustomerTimesUsed($ruleId, $order->getCustomerId());

        if ($rule->getCouponType() == \Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC) {

            $coupon = $this->getCouponByRuleId($ruleId);

            if (!$coupon) {
                $this->logger->info('Coupon code for rule Id: '. $ruleId. ' no longer exists.');
            } else {
                $this->revertCouponTimesUsed($coupon);
                $this->revertCouponCustomerTimesUsed($coupon->getId(), $order->getCustomerId());
            }

        } else {
            $this->logger->info('Rule Id: '. $ruleId. ' is not coupon type.');
        }

        $this->logger->info('Revert Applied Rule Id: '. $ruleId.' success.');

    }

    /**
     * revert coupon times used
     *
     * @param $coupon
     */
    public function revertCouponTimesUsed($coupon)
    {
        if (!$coupon) {
            return;
        }

        $timesUsed = $coupon->getTimesUsed();

        $this->logger->info('Coupon code Id: '. $coupon->getId(). ' -- Current Times Used:'. $timesUsed);

        if ($timesUsed > 0) {

            $coupon->setData('times_used', $timesUsed - 1);

            try {
                $coupon->save();
                $this->logger->info('Coupon code Id: '. $coupon->getId(). ' -- New Times Used:'. $coupon->getTimesUsed());
            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
            }
        }
    }

    /**
     * Revert coupon customer times used
     *
     * @param $couponId
     * @param $customerId
     */
    public function revertCouponCustomerTimesUsed($couponId, $customerId)
    {
        $customerTimesUsed = $this->couponUsageResource->getCustomerCouponTimesUsed($customerId, $couponId);

        if ($customerTimesUsed) {
            $timesUsed = $customerTimesUsed['times_used'];

            $this->logger->info('Coupon Id: '. $couponId. ' -- Customer Id: '.$customerId.' -- Current Times Used:'. $timesUsed);

            if ($timesUsed > 0) {
                $timesUsed--;
                $result = $this->couponUsageResource->updateCustomerCouponByTimesUsed($customerId, $couponId, $timesUsed);

                if ($result) {
                    $this->logger->info('Coupon Id: '. $couponId. ' -- Customer Id: '.$customerId.' -- New Times Used:'. $timesUsed);
                }
            }
        }
    }

    /**
     * Revert Rule times used
     *
     * @param $rule
     */
    public function revertRuleTimesUsed($rule)
    {
        $timesUsed = $rule->getTimesUsed();

        $this->logger->info('Rule Id: '. $rule->getId(). ' -- Current Times Used:'. $timesUsed);

        if ($timesUsed > 0) {

            $rule->setData('times_used', $timesUsed - 1);
            try {
                $rule->save();
                $this->logger->info('Rule Id: '. $rule->getId(). ' -- New Times Used:'. $rule->getTimesUsed());
            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
            }
        }
    }

    /**
     * Revert Rule customer times used
     *
     * @param $ruleId
     * @param $customerId
     */
    public function revertRuleCustomerTimesUsed($ruleId, $customerId)
    {
        /** @var \Magento\SalesRule\Model\Rule\Customer $ruleCustomer */
        $ruleCustomer = $this->ruleCustomerFactory->create();
        $ruleCustomer->loadByCustomerRule($customerId, $ruleId);

        if ($ruleCustomer->getId()) {

            $timesUsed = $ruleCustomer->getTimesUsed();

            $this->logger->info('Rule Id: '. $ruleId. ' -- Customer Id: '.$customerId.' -- Current Times Used:'. $timesUsed);

            if ($timesUsed > 0) {
                $ruleCustomer->setData('times_used', $timesUsed - 1);

                try {
                    $ruleCustomer->save();
                    $this->logger->info('Rule Id: '. $ruleId. ' -- Customer Id: '.$customerId.' -- New Times Used:'. $ruleCustomer->getTimesUsed());
                } catch (\Exception $e) {
                    $this->logger->info($e->getMessage());
                }
            }
        }
    }

    /**
     * Get rule by id
     *
     * @param $ruleId
     * @return bool|\Magento\SalesRule\Model\Rule
     */
    public function getRuleById($ruleId)
    {
        /** @var \Magento\SalesRule\Model\Rule $ruleModel */
        $ruleModel = $this->ruleFactory->create();
        $ruleModel->load($ruleId);

        if ($ruleModel->getId()) {
            return $ruleModel;
        }

        return false;
    }

    /**
     * Get coupon by rule Id
     *
     * @param $ruleId
     * @return bool|\Magento\SalesRule\Model\Coupon
     */
    public function getCouponByRuleId($ruleId)
    {
        /** @var \Magento\SalesRule\Model\ResourceModel\Coupon\Collection $couponCollection */
        $couponCollection = $this->couponCollectionFactory->create();

        $couponCollection->addRuleIdsToFilter([$ruleId]);

        if ($couponCollection->getSize()) {
            return $couponCollection->setPageSize(1)->getFirstItem();
        }

        return false;
    }
}