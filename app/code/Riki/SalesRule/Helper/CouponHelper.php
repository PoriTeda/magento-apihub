<?php

namespace Riki\SalesRule\Helper;

use Magento\Setup\Exception;

class CouponHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\SalesRule\Model\CouponFactory
     */
    protected $couponFactory;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManagerInterface;

    /**
     * @var \Magento\SalesRule\Api\RuleRepositoryInterface
     */
    protected $_ruleRepository;

    /**
     * @var \Magento\Framework\Api\Filter
     */
    protected $_filter;

    /**
     * @var \Magento\Framework\Api\Search\FilterGroup
     */
    protected $_filterGroup;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaInterface
     */
    protected $_searchCriteriaInterface;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManagerInterface;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Coupon\UsageFactory
     */
    protected $usageFactory;

    /**
     * @var \Magento\SalesRule\Model\Rule\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $objectFactory;

    /**
     * CouponHelper constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\SalesRule\Model\CouponFactory $couponFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\SalesRule\Model\CouponFactory $couponFactory,
        \Magento\Framework\Message\ManagerInterface $messageManagerInterface,
        \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepository,
        \Magento\Framework\Api\Filter $filter,
        \Magento\Framework\Api\Search\FilterGroup $filerGroup,
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteriaInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\SalesRule\Model\ResourceModel\Coupon\UsageFactory $usageFactory,
        \Magento\SalesRule\Model\Rule\CustomerFactory $customerFactory,
        \Magento\Framework\DataObjectFactory $objectFactory
    ) {
        parent::__construct($context);
        $this->couponFactory = $couponFactory;
        $this->_messageManagerInterface = $messageManagerInterface;
        $this->_ruleRepository = $ruleRepository;
        $this->_filter = $filter;
        $this->_filterGroup = $filerGroup;
        $this->_searchCriteriaInterface = $searchCriteriaInterface;
        $this->_storeManagerInterface = $storeManagerInterface;
        $this->usageFactory = $usageFactory;
        $this->customerFactory = $customerFactory;
        $this->objectFactory = $objectFactory;
    }

    /**
     * Get coupon data by coupon code
     *
     * @param string $couponCode
     * @return bool|\Magento\SalesRule\Model\Coupon
     */
    public function getCouponDataByCode($couponCode)
    {
        /** @var \Magento\SalesRule\Model\Coupon $couponModel */
        $couponData = $this->couponFactory->create();

        $couponData->loadByCode($couponCode);

        if ($couponData->getId()) {
            return $couponData;
        }

        return false;
    }

    /**
     * Check coupon with rule id ,coupon code simulator
     *
     * @param $ruleId
     * @param $listCouponCode
     * @return array|bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkCouponRealIdsWhenProcessSimulator($ruleId,$listCouponCode)
    {

        if ($ruleId != null && $listCouponCode != null) {
            $arrCoupon  = explode(',', $listCouponCode);

            if(is_array($arrCoupon)&&count($arrCoupon)>0)
            {
                $listData  = [];
                $ruleIds   = [];
                foreach($arrCoupon as $code)
                {
                    $rule =$this->getCouponDataByCode(trim($code));
                    if($rule&& $rule->getRuleId() !=null)
                    {
                        $ruleIds[$rule->getRuleId()] = $rule->getRuleId();
                        $listData[$rule->getRuleId()] = [
                            'code'=>$code,
                            'name'=>null
                        ];
                    }
                }

                if(count($ruleIds)>0)
                {
                    $filters[] = $this->_filter
                        ->setField('rule_id')
                        ->setConditionType('in')
                        ->setValue($ruleIds);

                    $filterGroup[]  = $this->_filterGroup->setFilters($filters);
                    $searchCriteria = $this->_searchCriteriaInterface->setFilterGroups($filterGroup);
                    $searchResults  = $this->_ruleRepository->getList($searchCriteria);
                    if ($searchResults->getTotalCount()) {
                        foreach($searchResults->getItems() as $rule)
                        {
                            if(isset($listData[$rule->getRuleId()])){
                                $listData[$rule->getRuleId()]['name']= $this->getLabelCartPriceRules($rule);
                            }
                        }
                    }
                }

                if(count($listData)>0)
                {
                    return $listData;
                }
            }
        }
        return false;
    }

    /**
     * @param $simulatorOrder
     * @return array|bool
     */
    public function getRealAppliedCoupon($simulatorOrder)
    {

        if (!$simulatorOrder) {
            return [];
        }

        if (empty($simulatorOrder->getCouponCode()) || empty($simulatorOrder->getAppliedRuleIds())) {
            return [];
        }

        $couponCode = explode(',', $simulatorOrder->getCouponCode());

        $appliedRules = explode(',', $simulatorOrder->getAppliedRuleIds());

        $rs = [];

        foreach ($couponCode as $key => $coupon) {

            /*coupon data*/
            $rule = $this->getCouponDataByCode($coupon);

            if ($rule && in_array($rule->getRuleId(), $appliedRules)) {

                if (!in_array($coupon, $rs)) {
                    array_push($rs, $coupon);
                }
            }
        }

        return $rs;
    }

    /**
     * Check remove coupon when simulator order
     *
     * @param $simulatorOrder
     * @param $couponCodeInput
     * @return bool
     */
    public function checkRemoveCouponFromSessionProfileWithOrderSimulator($simulatorOrder,$couponCodeInput=null)
    {
        $couponOrder = [];
        if (!empty($couponCodeInput)) {
            $couponOrder = explode(',', $simulatorOrder->getCouponCode());
        }

        $couponRealApplied = $this->getRealAppliedCoupon($simulatorOrder);

        $result = array_diff($couponOrder,$couponRealApplied);
        if(count($result)>0)
        {
            return true;
        }

        return false;
    }

    /**
     * Add message
     *
     * @param $orderSimulator
     * @param $couponCodeSession
     */
    public function addMessageRemoveCouponCode($orderSimulator,$couponCodeSession=null)
    {
        /**
         * Add message when auto remove coupon code
         */
        $isRemoveCouponCode = $this->checkRemoveCouponFromSessionProfileWithOrderSimulator($orderSimulator,$couponCodeSession);
        if($isRemoveCouponCode)
        {
            $this->_messageManagerInterface->addError(__('Coupon code is not valid'));
        }
    }

    /**
     *
     *
     * @param $rule
     * @return null
     */
    public function getLabelCartPriceRules($rule)
    {
        $storeCurrentId = $this->_storeManagerInterface->getStore()->getId();
        $label = null;
        if ($rule->getStoreLabels()) {
            $storeLabelDefault = null;
            foreach ($rule->getStoreLabels() as $ruleLabel )
            {
                if ($ruleLabel->getStoreId()==0)
                {
                    $storeLabelDefault = $ruleLabel->getStoreLabel();
                }

                if ($ruleLabel->getStoreId() == $storeCurrentId)
                {
                    $label = $ruleLabel->getStoreLabel();
                }
            }

            if ($label==null) {
                $label = $storeLabelDefault;
            }
        }
        return $label;
    }

    /**
     * @param $id
     * @return bool|\Magento\SalesRule\Api\Data\RuleInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRuleById($id)
    {
        try {
            $rule = $this->_ruleRepository->getById($id);
            if ($rule->getRuleId()) {
                return $rule;
            }
        } catch (Exception $e) {
            return false;
        }
        return false;
    }


    public function getRulesUsedLabel($ruleIds)
    {
        $listData = false;
        $filters[] = $this->_filter
            ->setField('rule_id')
            ->setConditionType('in')
            ->setValue($ruleIds);

        $filterGroup[] = $this->_filterGroup->setFilters($filters);
        $searchCriteria = $this->_searchCriteriaInterface->setFilterGroups($filterGroup);
        $searchResults = $this->_ruleRepository->getList($searchCriteria);
        if ($searchResults->getTotalCount()) {
            foreach ($searchResults->getItems() as $rule) {
                if ($this->getLabelCartPriceRules($rule) != null) {
                    $listData[$rule->getRuleId()]['name'] = $this->getLabelCartPriceRules($rule);
                }
            }
        }

        return $listData;
    }

    /**
     * Check if rule can be applied for specific address/quote/customer
     *
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param \Magento\Quote\Model\Quote\Address $address
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function canProcessRuleForAddressAndCustomer($rule, $address)
    {
        if ($rule->hasIsValidForAddress($address) && !$address->isObjectNew()) {
            return $rule->getIsValidForAddress($address);
        }

        /**
         * check per coupon usage limit
         */
        if ($rule->getCouponType() != \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON) {
            $couponCode = $address->getQuote()->getCouponCode();
            if (strlen($couponCode)) {
                /** @var \Magento\SalesRule\Model\Coupon $coupon */
                $coupon = $this->couponFactory->create();
                $coupon->load($couponCode, 'code');
                if ($coupon->getId()) {
                    // check entire usage limit
                    if ($coupon->getUsageLimit() && $coupon->getTimesUsed() >= $coupon->getUsageLimit()) {
                        return false;
                    }
                    // check per customer usage limit
                    $customerId = $address->getQuote()->getCustomerId();
                    if ($customerId && $coupon->getUsagePerCustomer()) {
                        $couponUsage = $this->objectFactory->create();
                        $this->usageFactory->create()->loadByCustomerCoupon(
                            $couponUsage,
                            $customerId,
                            $coupon->getId()
                        );
                        if ($couponUsage->getCouponId() &&
                            $couponUsage->getTimesUsed() >= $coupon->getUsagePerCustomer()
                        ) {
                            return false;
                        }
                    }
                }
            }
        }

        /**
         * check per rule usage limit
         */
        $ruleId = $rule->getId();
        if ($ruleId && $rule->getUsesPerCustomer()) {
            $customerId = $address->getQuote()->getCustomerId();
            /** @var \Magento\SalesRule\Model\Rule\Customer $ruleCustomer */
            $ruleCustomer = $this->customerFactory->create();
            $ruleCustomer->loadByCustomerRule($customerId, $ruleId);
            if ($ruleCustomer->getId()) {
                if ($ruleCustomer->getTimesUsed() >= $rule->getUsesPerCustomer()) {
                    return false;
                }
            }
        }

        return true;
    }
}
