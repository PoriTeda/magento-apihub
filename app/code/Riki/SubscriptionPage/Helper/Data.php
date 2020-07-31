<?php

namespace Riki\SubscriptionPage\Helper;

use Magento\Framework\Exception\LocalizedException;
use Riki\Subscription\Model\Profile\Profile;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const APPLICATION_LIMIT_CHECK_CODE_OVER_LIMIT = 'over_limit';

    const APPLICATION_LIMIT_CHECK_CODE_UNDER_LIMIT_HAS_ACTIVE_PROFILE = 'under_limit_has_active_profile';

    const APPLICATION_LIMIT_CHECK_CODE_UNDER_LIMIT_HAS_INACTIVE_PROFILE = 'under_limit_has_inactive_profile';

    /**
     * @var array
     */
    protected $applicationLimitRejectCodes = [
        self::APPLICATION_LIMIT_CHECK_CODE_UNDER_LIMIT_HAS_ACTIVE_PROFILE,
        self::APPLICATION_LIMIT_CHECK_CODE_UNDER_LIMIT_HAS_INACTIVE_PROFILE,
        self::APPLICATION_LIMIT_CHECK_CODE_OVER_LIMIT
    ];

    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $subscriptionCourseModel;

    /**
     * @var \Riki\SubscriptionCourse\Model\ResourceModel\Course
     */
    protected $subscriptionCourseResourceModel;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $stdTimezone;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Profile
     */
    protected $profileModel;

    /**
     * @var \Riki\SubscriptionCourse\Helper\Data
     */
    protected $subCourseHelperData;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Framework\Api\Search\FilterGroupBuilder
     */
    protected $filterGroupBuilder;

    /**
     * @var \Riki\Subscription\Model\Profile\ResourceModel\Profile\CollectionFactory
     */
    protected $profileCollectionFactory;

    /**
     * Data constructor.
     * @param \Riki\Subscription\Model\Profile\Profile\Proxy $profileModel
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone
     * @param \Riki\SubscriptionCourse\Model\ResourceModel\Course $courseResourceModel
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Riki\SubscriptionCourse\Model\Course $model
     * @param \Riki\SubscriptionCourse\Helper\Data $helperCourse
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
     */
    public function __construct(
        \Riki\Subscription\Model\Profile\Profile\Proxy $profileModel,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course $courseResourceModel,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\SubscriptionCourse\Model\Course $model,
        \Riki\SubscriptionCourse\Helper\Data $helperCourse,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder,
        \Riki\Subscription\Model\Profile\ResourceModel\Profile\CollectionFactory $profileCollectionFactory
    ) {
        $this->profileModel = $profileModel;
        $this->subscriptionCourseModel = $model;
        $this->subscriptionCourseResourceModel = $courseResourceModel;
        $this->dateTime = $dateTime;
        $this->stdTimezone = $stdTimezone;
        $this->subCourseHelperData = $helperCourse;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->profileRepository = $profileRepository;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->profileCollectionFactory = $profileCollectionFactory;
        parent::__construct($context);
    }

    /**
     * GetSubscriptionCourseModelFromCourseId
     *
     * @param $courseId
     * @return \Riki\SubscriptionCourse\Model\Course
     */
    public function getSubscriptionCourseModelFromCourseId($courseId)
    {
        return $this->subscriptionCourseModel->load($courseId);
    }

    /**
     * GetSubscriptionType
     *
     * @param $courseId
     *
     * @return mixed
     */
    public function getSubscriptionType($courseId)
    {
        return $this->getSubscriptionCourseModelFromCourseId($courseId)->getData('subscription_type');

    }

    /**
     *GetHanpukaiType
     *
     * @param $courseId
     *
     * @return mixed
     */
    public function getHanpukaiType($courseId)
    {
        return $this->getSubscriptionCourseModelFromCourseId($courseId)->getData('hanpukai_type');
    }

    /**
     *
     * GetHanpukaiProductId
     *
     * @param $hanpukaiType
     * @param $courseId
     * @param null $nDelivery
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getHanpukaiProductId($hanpukaiType, $courseId, $nDelivery = null)
    {
        $result = [];
        if ($hanpukaiType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI_FIXED) {
            $arrProduct = $this->subscriptionCourseResourceModel->getHanpukaiFixedProductsData(
                $this->getSubscriptionCourseModelFromCourseId($courseId)
            );
            foreach ($arrProduct as $key => $value) {
                $result[] = $key;
                try{
                    $oParentProduct = $this->productRepository->getById($key);
                    if ('bundle' == $oParentProduct->getTypeId()) {
                        $childIds = $oParentProduct->getTypeInstance(true)->getChildrenIds(
                            $oParentProduct->getId(),
                            false
                        );
                        if($childIds){
                            foreach($childIds as $childId){
                                $productChildId = reset($childId);
                                $result[] = $productChildId;
                            }
                        }
                    }
                }
                catch(\Magento\Framework\Exception\NoSuchEntityException $e){
                    $this->_logger->critical($e->getMessage());
                }
            }
        }

        if ($hanpukaiType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI_SEQUENCE) {
            $arrProduct = $this->subscriptionCourseResourceModel->getHanpukaiSequenceProductsData(
                $this->getSubscriptionCourseModelFromCourseId($courseId)
            );
            $firstDelivery = $this->getFirstDeliveryNumber($arrProduct);

            if ($nDelivery) {
                $firstDelivery = $nDelivery + 1;
            }

            foreach ($arrProduct as $key => $value) {
                if ($value['delivery_number'] == $firstDelivery) {
                    $result[] = $key;
                    try {
                        $oParentProduct = $this->productRepository->getById($key);
                        if ('bundle' == $oParentProduct->getTypeId()) {
                            $childIds = $oParentProduct->getTypeInstance(true)->getChildrenIds(
                                $oParentProduct->getId(),
                                false
                            );
                            if ($childIds) {
                                foreach ($childIds as $childId) {
                                    $productChildId = reset($childId);
                                    $result[] = $productChildId;
                                }
                            }
                        }
                    }
                    catch(\Magento\Framework\Exception\NoSuchEntityException $e){
                        $this->_logger->critical($e->getMessage());
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get Product Fixed Hanpukai
     *
     * @param $courseId
     * @return array
     */
    public function getArrProductFixedHanpukai($courseId)
    {
        $result = [];
        $arrProduct = $this->subscriptionCourseResourceModel->getHanpukaiFixedProductsData(
            $this->getSubscriptionCourseModelFromCourseId($courseId)
        );
        foreach ($arrProduct as $productId => $qty) {
            $result[$productId]['product_id'] = $productId;
            $result[$productId]['qty'] = $qty;
        }
        return $result;
    }

    /**
     * Get Arr product for first delivery hanpukai sequence
     *
     * @param $courseId
     *
     * @return array
     */
    public function getArrProductForFirstDeliveryHanpukaiSequence($courseId)
    {
        $result = [];
        $arrProduct = $this->subscriptionCourseResourceModel->getHanpukaiSequenceProductsData(
            $this->getSubscriptionCourseModelFromCourseId($courseId)
        );
        $firstDelivery = $this->getFirstDeliveryNumber($arrProduct);
        if ($firstDelivery === false) {
            $firstDelivery = 0;
        }
        foreach ($arrProduct as $key => $value)
        {
            if ($value['delivery_number'] == $firstDelivery) {
                $result[$key]['product_id'] = $key;
                $result[$key]['qty'] = $value['qty'];
            }
        }
        return $result;

    }


    /**
     * GetTotalNumberDeliveryForHanpukaiSequency
     *
     * @param $courseId
     *
     * @return array
     */
    public function getTotalNumberDeliveryForHanpukaiSequency($courseId)
    {
        $arrProduct = $this->subscriptionCourseResourceModel->getHanpukaiSequenceProductsData(
            $this->getSubscriptionCourseModelFromCourseId($courseId)
        );
        $deliveryNumberArr = [];
        foreach ($arrProduct as $key => $value) {
            if(isset($value['delivery_number']) && !in_array($value['delivery_number'], $deliveryNumberArr) ) {
                $deliveryNumberArr[] = $value['delivery_number'];
            }
}
        return $deliveryNumberArr;
    }

    /**
     * GetFirstDeliveryNumber
     *
     * @param $arrProduct
     *
     * @return bool
     */
    public function getFirstDeliveryNumber($arrProduct)
    {
        $deliveryNumberArr = array();
        foreach ($arrProduct as $key => $value) {
            if(isset($value['delivery_number'])) {
                $deliveryNumberArr[] = $value['delivery_number'];
            }
        }
        $deliveryNumberArr = $this->sort($deliveryNumberArr, count($deliveryNumberArr));
        return isset($deliveryNumberArr[0])?$deliveryNumberArr[0]:false;
    }

    /**
     * GetCurrentDate
     *
     * @return string
     */
    public function getCurrentDate()
    {
        $dateTimeNow = $this->stdTimezone->date();
        $coverDate = $dateTimeNow->format('Y/m');
        return $coverDate;
    }

    /**
     * Sort
     *
     * @param $arr
     * @param $length
     * @return mixed
     */
    public function sort($arr, $length)
    {
        for($i=0; $i < $length - 1; $i++) {
            for($j = $i+1 ; $j < $length; $j++) {
                if((int)$arr[$j] < (int)$arr[$i]) {
                    $tmp = $arr[$i];
                    $arr[$i] = $arr[$j];
                    $arr[$j] = $tmp;
                }
            }
        }
        return $arr;
    }

    /**
     * CheckExistSubscriptionHanpukaiInCart
     *
     * @param $quote
     *
     * @return bool
     */
    public function checkExistSubscriptionHanpukaiInCart($quote)
    {
        if ($quote->getData('riki_course_id') != null) {
            if ($this->getSubscriptionType($quote->getData('riki_course_id')) == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
                return true;
            }
        }
        return false;
    }

    /**
     * IsProductInCartBeforeAddHanpukai
     *
     * @param $allProductInCart
     * @param $subscriptionId
     * @return bool
     */
    public function isProductInCartBeforeAddHanpukai($allProductInCart, $subscriptionId)
    {
        if($subscriptionId != null && $this->getSubscriptionType($subscriptionId) == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
            if (count($allProductInCart) > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $customerId
     * @param string $excludedCourses
     * @return bool
     */
    protected function hasActiveProfile($customerId, $excludedCourses)
    {
        /** @var \Riki\Subscription\Model\Profile\ResourceModel\Profile\Collection $collection */
        $collection = $this->profileModel->getCustomerSubscriptionProfile($customerId);
        $collection->setCurPage(1)->setPageSize(1);

        if ($excludedCourses) {
            $this->excludeCourseFilterApply($collection, $excludedCourses);
        }

        if ($collection->getSize() > 0) {
            return true;
        }
        return false;
    }

    /**
     * @param string $customerId
     * @param string $excludedCourses
     * @return bool
     */
    protected function hasInactiveProfile($customerId, $excludedCourses)
    {
        /** @var \Riki\Subscription\Model\Profile\ResourceModel\Profile\Collection $profileCollection */
        $profileCollection = $this->profileCollectionFactory->create();
        $profileCollection->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('type', [
                ['neq' => Profile::SUBSCRIPTION_TYPE_TMP],
                ['null' => true]
            ])->addFieldToFilter('status', Profile::STATUS_DISABLED)
            ->setCurPage(1)->setPageSize(1)
        ;

        $profileCollection->getSelect()->joinInner(
            ['course' => 'subscription_course'],
            "main_table.course_id = course.course_id",
            []
        );

        if ($excludedCourses) {
            $this->excludeCourseFilterApply($profileCollection, $excludedCourses);
        }

        if ($profileCollection->getSize() > 0) {
            return true;
        }
        return false;
    }

    /**
     * @param \Riki\Subscription\Model\Profile\ResourceModel\Profile\Collection $collection
     * @param string $excludedCourses
     */
    private function excludeCourseFilterApply($collection, $excludedCourses)
    {
        $excludedCourses = explode(',', $excludedCourses);
        $excludedCourses = array_map(function ($value) {
            return trim($value);
        }, $excludedCourses);

        $wildCardCourses = [];
        $specificCourse = [];

        foreach ($excludedCourses as $courseCode) {
            if (strpos($courseCode, '*') !== false) {
                $wildCardCourses[] = $courseCode;
            } elseif ($courseCode != "") {
                $specificCourse[] = $courseCode;
            }
        }

        if (count($specificCourse) > 0) {
            $collection->addFieldToFilter('course_code', ['nin' => $specificCourse]);
        }

        if (count($wildCardCourses) > 0) {
            foreach ($wildCardCourses as $wildcard) {
                $collection->addFieldToFilter('course_code', ['nlike' => str_replace('*', '%', $wildcard)]);
            }
        }
    }

    /**
     * @param string $customerId
     * @param \Riki\SubscriptionCourse\Model\Course $courseModel
     * @param array $arrResult
     * @return string
     */
    protected function getApplicationLimitCode($customerId, $courseModel)
    {
        if (!$courseModel->getData('application_limit') || $this->isUnderApplicationLimit($courseModel, $customerId)) {
            $excludedCourses = $courseModel->getData('restrict_exclude_course');
            if ($courseModel->getRestrictActiveCourse() && $this->hasActiveProfile($customerId, $excludedCourses)) {
                return self::APPLICATION_LIMIT_CHECK_CODE_UNDER_LIMIT_HAS_ACTIVE_PROFILE;
            }
            if ($courseModel->getRestrictInactiveCourse() && $this->hasInactiveProfile($customerId, $excludedCourses)) {
                return self::APPLICATION_LIMIT_CHECK_CODE_UNDER_LIMIT_HAS_INACTIVE_PROFILE;
            }
        } else {
            return self::APPLICATION_LIMIT_CHECK_CODE_OVER_LIMIT;
        }
    }

    /**
     * @param \Riki\SubscriptionCourse\Model\Course $courseModel
     * @param string $customerId
     * @return bool
     */
    protected function isUnderApplicationLimit($courseModel, $customerId)
    {
        $profileCollection = $this->profileModel->getCollectionProfileByCustomerIdAndCourseId(
            $customerId,
            $courseModel->getId()
        );
        if ($profileCollection->getSize() >= $courseModel->getData('application_limit')) {
            return false;
        }
        return true;
    }

    /**
     * @param $customerId
     * @param $courseId
     *
     * @return array (0 no error, 1 has error)
     */
    public function checkApplicationLimit($customerId, $courseId)
    {
        $arrResult = ['has_error' => 0, 'application_limit' => 0, 'course_name' => ''];
        $courseModel = $this->getSubscriptionCourseModelFromCourseId($courseId);
        $applicationLimitCode = $this->getApplicationLimitCode($customerId, $courseModel);
        if (in_array($applicationLimitCode, $this->applicationLimitRejectCodes)) {
            $arrResult['application_limit'] = $courseModel->getData('application_limit');
            $arrResult['course_name'] = $courseModel->getData('course_name');
            $arrResult['has_error'] = 1;
            $arrResult['error_code'] = $applicationLimitCode;
            return $arrResult;
        }
        return $arrResult;
    }

    /**
     * Validate subscription rule when edit product
     *
     * @param $quote
     * @return int
     */
    public function validateSubscriptionRule($quote)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $objCourse = $this->subscriptionCourseModel->load($quote->getRikiCourseId());
        $arrProductId = [];
        $arrProductIdQty = [];
        $totalQtyShow = 0;
        foreach ($quote->getAllVisibleItems() as $item) {
            $buyRequest = $item->getBuyRequest();
            if (isset($buyRequest['options']['ampromo_rule_id'])) {
                continue;
            }
            if ($item->getData('is_riki_machine')) {
                continue;
            }

            if (strtoupper($item->getUnitCase()) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                $unitQty = ((int)$item->getUnitQty() != 0)?(int)$item->getUnitQty():1;
                $arrProductIdQty[$item->getProduct()->getId()] = $item->getQty()/$unitQty;
            }
            else{
                $arrProductIdQty[$item->getProduct()->getId()] = $item->getQty();
            }

            $arrProductId[] = $item->getProduct()->getId();

            if (strtoupper($item->getUnitCase()) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_PIECE) {
                $totalQtyShow = $totalQtyShow + $item->getQty();
            }

            if (strtoupper($item->getUnitCase()) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                $totalQtyShow = $totalQtyShow + ($item->getQty() / $item->getUnitQty());
            }
        }

        $mustHaveCatId = $objCourse->getData('must_select_sku');
        $isValid = $this->subCourseHelperData->isValidMakeHaveInCart($arrProductId, $mustHaveCatId);
        if (!$isValid) {
            return 3;
        }

        $minimumOrderQty = $objCourse->getData('minimum_order_qty');
        if( $totalQtyShow < $minimumOrderQty ) {
            return 4; // Maximum limit
        }

        if (!$this->subCourseHelperData->isValidMustHaveQtyInCategory($arrProductIdQty, $mustHaveCatId)) {
            return 5; // Minimum product qty in category
        }

        return 0;
    }

    /**
     * Get category name when course setting must select SKU
     *
     * @param $courseModel \Riki\SubscriptionCourse\Model\Course
     * @return string
     */
    public function getCategoryNameMustSkuInSubCourse($courseModel){
        $categoryName = '';
        $arrCategoryIdQtyConfig = [];

        $mustHaveCatId = $courseModel->getData("must_select_sku");
        if($mustHaveCatId){
            $arrCategoryIdQtyConfig =  explode(':', $mustHaveCatId);
        }
        if (count($arrCategoryIdQtyConfig) > 1) {
            $categoryId = $arrCategoryIdQtyConfig[0];
            if ($categoryObj = $this->categoryRepository->get($categoryId)) {
                $categoryName = $categoryObj->getName();
            } else {
                $categoryName = '';
            }
        }
        return $categoryName;
    }

    /**
     * @param array $applicationLimitData
     * @return string
     */
    public function getApplicationLimitErrorMessage($applicationLimitData)
    {
        if (!isset($applicationLimitData['course_name']) || !isset($applicationLimitData['error_code'])) {
            throw new LocalizedException(__('Missing required parameters'));
        }
        $courseName = $applicationLimitData['course_name'];
        $messagePart = 'Sorry, but you have already subscribed to %1.';
        $messagePart .= ' If you would like to change the products or quantity,';
        $messagePart .= ' please access the subscription edit profile on the MyPage.';
        $messagePart .= ' If you have questions, please contact the call center.';
        $message = __($messagePart, $courseName);
        $defaultMessageCode = self::APPLICATION_LIMIT_CHECK_CODE_OVER_LIMIT;
        if (isset($applicationLimitData['error_code']) && $applicationLimitData['error_code'] != $defaultMessageCode) {
            $messagePart = 'Sorry, but you cannot subscribe %1,';
            $messagePart .= ' because you already subscribed other subscription course in the past.';
            $message = __($messagePart, $courseName);
        }
        return $message;
    }
}
