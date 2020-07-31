<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\Subscription\Cron;

use Riki\DeliveryType\Model\Delitype as GroupDeliveryType;

class MergeProfile
{
    const CONFIG_MERGE_PROFILE_X_DAYS = 'subscriptioncourse/merge_profile/xdays';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    private $courseModel;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    private $profileFactory;

    /**
     * @var \Riki\Subscription\Model\ProductCart\ProductCartFactory
     */
    private $productCartFactory;

    /**
     * @var \Riki\Subscription\Logger\LoggerMergeProfile
     */
    private $loggerMergeProfile;

    /**
     * @var \Riki\SubscriptionProfileDisengagement\Model\ReasonFactory
     */
    private $reasonFactory;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    private $helperProfile;

    /**
     * @var bool
     */
    private $reasonCode;

    /**
     * @var \Riki\Subscription\Helper\Profile\AddSpotHelper
     */
    private $addSpotHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezone;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    private $productRepository;

    /**
     * @var \Riki\DeliveryType\Helper\Data
     */
    private $deliveryTypeHelper;

    /**
     * GenerateOrder constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Shell $shell
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Shell $shell,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Model\ProductCart\ProductCartFactory $productCartFactory,
        \Riki\Subscription\Logger\LoggerMergeProfile $loggerMergeProfile,
        \Riki\SubscriptionProfileDisengagement\Model\ReasonFactory $reasonFactory,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Riki\Subscription\Helper\Profile\AddSpotHelper $addSpotHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Riki\DeliveryType\Helper\Data $deliveryTypeHelper
    ) {
        $this->shell = $shell;
        $this->scopeConfig = $scopeConfig;
        $this->courseModel = $courseFactory;
        $this->profileFactory = $profileFactory;
        $this->productCartFactory = $productCartFactory;
        $this->loggerMergeProfile = $loggerMergeProfile;
        $this->reasonFactory = $reasonFactory;
        $this->helperProfile = $helperProfile;
        $this->addSpotHelper = $addSpotHelper;
        $this->timezone = $timezone;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
        $this->deliveryTypeHelper = $deliveryTypeHelper;
    }

    public function execute()
    {
        $customerWillDataMerge = [];
        $courseMerge = $this->getCourseMerge();
        if (empty($courseMerge)) {
            $this->loggerMergeProfile->addInfo('There is no data to merge');
            return false;
        }
        $customerCollection = $this->profileFactory->create()->getCollection();
        /**
         * If FROM profile is Stock Point profile Skip merging this FROM profile.
         */
        $customerCollection->join(
            ['spc' => 'subscription_profile_product_cart'],
            'main_table.profile_id = spc.profile_id',
            ['shipping_address_id' => 'GROUP_CONCAT(DISTINCT shipping_address_id)'],
            ['shipping_address_id']
        )
            ->addFieldToFilter('course_id', ['in' => array_keys($courseMerge)])
            ->addFieldToFilter('type', ['null' => true])
            ->addFieldToFilter('status', 1)
            ->addFieldToSelect('profile_id')
            ->addFieldToFilter('main_table.stock_point_profile_bucket_id', ['null' => true]);
        $customerCollection->getSelect()->group('profile_id');

        foreach ($customerCollection as $profileFrom) {
            $shippingAddressId = explode(',', $profileFrom->getData('shipping_address_id'));
            $profileFrom->setData('shipping_address_id', reset($shippingAddressId));
            $customerId = $profileFrom->getData('customer_id');
            $customerWillDataMerge[$customerId][$profileFrom->getData('course_id')][] = $profileFrom;
        }

        foreach ($customerWillDataMerge as $customerId => $courseFrom) {
            $this->loggerMergeProfile->addInfo('Start for customer ' . $customerId);
            foreach ($courseFrom as $courseId => $profiles) {
                $courseTo = in_array($courseId, array_keys($courseMerge)) ? $courseMerge[$courseId] : [];
                if (empty($courseTo)) {
                    $this->loggerMergeProfile->addInfo('There is no data to merge');
                    return false;
                }
                // NED-6933 cannot reproduce the issue so we add
                // workaround solution to prevent the case profile self merging / self disengage
                if (in_array($courseId, $courseTo)) {
                    // check if $courseID exist in $courseTo then we remove that value
                    $key = array_search($courseId, $courseTo);
                    unset($courseTo[$key]);
                    $this->loggerMergeProfile->addInfo(
                        '[NED-6933] Same course_id detected in courseTo targets for merge. Process remove the value.'
                    );
                }
                $this->loggerMergeProfile->addInfo(
                    'Start merge course ' . $courseId . ' to ' . implode(', ', $courseTo)
                );
                $this->getProfileToByCustomer($customerId, $profiles, $courseTo);
                $this->loggerMergeProfile->addInfo(
                    'Finish merge course ' . $courseId . ' to ' . implode(', ', $courseTo)
                );
            }
            $this->loggerMergeProfile->addInfo('Finish for customer ' . $customerId);
        }
    }

    /**
     * Get profile TO by customer
     *
     * @param $customerId
     * @param $profileFrom
     * @param $courseTo
     * @return bool
     */
    public function getProfileToByCustomer($customerId, $profileFrom, $courseTo)
    {
        $currentTime = $this->timezone->date()->format(\Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT);
        $xDays = $this->scopeConfig->getValue(self::CONFIG_MERGE_PROFILE_X_DAYS);
        $tmpDeliveryDate = date(
            \Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT,
            strtotime($currentTime . ' + ' . $xDays . ' days')
        );
        $profileTo = [];
        /** @var \Riki\Subscription\Model\Profile\ResourceModel\Profile\Collection $profileToCollection */
        $profileToCollection = $this->profileFactory->create()->getCollection();
        $profileToCollection->join(
            ['spc' => 'subscription_profile_product_cart'],
            'main_table.profile_id = spc.profile_id',
            ['shipping_address_id' => 'GROUP_CONCAT(DISTINCT shipping_address_id)'],
            ['shipping_address_id']
        )
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('course_id', ['in' => $courseTo])
            ->addFieldToFilter('status', 1)
            ->addFieldToFilter('payment_method', ['notnull' => true])
            ->addFieldToFilter('type', ['null' => true])
            ->addFieldToFilter('next_delivery_date', ['gt' => $tmpDeliveryDate])
            ->addFieldToSelect(['payment_method', 'created_date'])
            ->setOrder('created_date', 'ASC')
            ->setOrder('profile_id', 'ASC');
        $profileToCollection->getSelect()->group('profile_id');
        foreach ($profileToCollection as $profile) {
            $shippingAddressId = explode(',', $profile->getData('shipping_address_id'));
            $profile->setData('shipping_address_id', reset($shippingAddressId));
            $profileTo[] = $profile->getData();
        }
        if (empty($profileTo)) {
            $this->loggerMergeProfile->addInfo('TO does not have any profile meet conditions');
            return false;
        }

        foreach ($profileFrom as $profile) {
            /*priority 1*/
            $profileNumber1 = array_keys(array_column($profileTo, 'payment_method'), 'paygent');
            if (!empty($profileNumber1)) {
                $profileNumber2 = array_keys(
                    array_column($profileTo, 'shipping_address_id'),
                    $profile->getData('shipping_address_id')
                );
                $result = array_intersect($profileNumber1, $profileNumber2);
                if (!empty($result)) {
                    $profileTo = $profileTo[reset($result)];
                } else {
                    $profileTo = $profileTo[$profileNumber1[0]];
                }
            } else {
                $fromShippingAddressId = $profile->getData('shipping_address_id');
                $profileNumber3 = array_keys(
                    array_column($profileTo, 'shipping_address_id'),
                    $fromShippingAddressId
                );
                if (!empty($profileNumber3)) {
                    $profileTo = $profileTo[$profileNumber3[0]];
                } else {
                    $profileTo = $profileTo[0];
                }
            }
            break;
        }

        foreach ($profileFrom as $profile) {
            $connection = $this->profileFactory->create()->getResource();
            $connection->beginTransaction();
            try {
                $result = $this->processMerge($profile, $profileTo);
                if ($result) {
                    if ($this->disengageProfileFrom($profile)) {
                        $connection->commit();
                    } else {
                        $toProfileId = $profileTo['profile_id'];
                        $this->loggerMergeProfile->addError(
                            'Rollback merge From profile ' . $profile->getId() . ' to profile ' . $toProfileId
                            . ' because cannot disengage FROM profile.'
                        );
                        $connection->rollBack();
                    }
                } else {
                    $this->loggerMergeProfile->addInfo(
                        'Cannot merge profile ' . $profile->getId() . ' to profile ' . $profileTo['profile_id']
                    );
                    $connection->rollBack();
                }
            } catch (\Exception $e) {
                $connection->rollBack();
            }
        }
    }

    /**
     * Get course merge
     *
     * @return array
     */
    public function getCourseMerge()
    {
        $courseWillMergeCollection = $this->courseModel->create()->getCollection();
        $courseWillMergeCollection->getSelect()->reset("columns")
            ->columns(['main_table.course_id'])->join(
                ['scmp' => 'subscription_course_merge_profile'],
                'main_table.course_id = scmp.course_id',
                ['merge_profile_to' => 'merge_profile_to']
            )
            ->where('main_table.is_enable = 1');
        $courseWillMerge = [];
        foreach ($courseWillMergeCollection->getData() as $course) {
            $courseWillMerge[$course['course_id']][] = $course['merge_profile_to'];
        }
        return $courseWillMerge;
    }

    /**
     * Process merge profile
     *
     * @param $profileFrom
     * @param $profileTo
     * @return bool
     */
    public function processMerge($profileFrom, $profileTo)
    {
        $profileToId = isset($profileTo['profile_id']) ? $profileTo['profile_id'] : false;
        if (!$profileToId) {
            $this->loggerMergeProfile->addError("TO profile invalid");
            return false;
        }
        $deliveryTypeFrom = $this->addSpotHelper->getDeliveryTypeOfProfile($profileFrom->getId());
        $deliveryTypeTo = $this->addSpotHelper->getDeliveryTypeOfProfile($profileToId);
        if (count(array_intersect($deliveryTypeFrom, $deliveryTypeTo)) == 0) {
            $this->loggerMergeProfile->addError("Can not merge because of they have a different delivery type");
            return false;
        }
        $this->loggerMergeProfile->addInfo("Start merge " . $profileFrom->getId() . ' to profile ' . $profileToId);
        $arrayProductIdTo = [];
        $currentTime = $this->timezone->date()->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        $productCartFromCollection = $this->productCartFactory->create()->getCollection()
            ->addFieldToFilter('profile_id', $profileFrom->getId())
            ->addFieldToFilter(
                ['parent_item_id', 'parent_item_id'],
                [
                    ['null' => true], 0
                ]
            );
        if (count($productCartFromCollection->getItems()) == 0) {
            $this->loggerMergeProfile->addInfo("FROM profile " . $profileFrom->getId() . " does not have any product");
            return false;
        }
        $checkProfileHaveTmp = $this->helperProfile->checkProfileHaveTmp($profileToId);
        if ($checkProfileHaveTmp) {
            $tmpProfile = $this->helperProfile->getTmpProfile($profileToId);
            if ($tmpProfile and $tmpProfile->getId()) {
                $this->loggerMergeProfile->addInfo(
                    'TO profile ' . $profileToId . ' has a tmp profile ' . $tmpProfile->getId()
                );
                $profileToId = $tmpProfile->getId();
            }
        }

        /**
         * If TO profile is stock point AND all products of FROM profile has allow_stock_point = YES
         * AND all products of FROM profile has NORMAL delivery type group
         */
        if (isset($profileTo['stock_point_profile_bucket_id']) && $profileTo['stock_point_profile_bucket_id']) {
            $productIds = $productCartFromCollection->getColumnValues('product_id');
            if (!$this->_validateStockPointProfileForMerging($productIds)) {
                $this->loggerMergeProfile->addInfo(
                    'FROM profile ' . $profileTo['profile_id'] . ' has product not match validate for stock point'
                );
                return false;
            }
        }

        $productCartToCollection = $this->productCartFactory->create()->getCollection()
            ->addFieldToFilter('profile_id', $profileToId);
        if (count($productCartToCollection->getItems()) == 0) {
            $this->loggerMergeProfile->addInfo("TO profile " . $profileToId . " does not have any product");
            return false;
        }
        foreach ($productCartToCollection as $productCartTo) {
            $arrayProductIdTo[$productCartTo['product_id']] = $productCartTo;
        }
        $productCartToBase = $productCartToCollection->getFirstItem();
        foreach ($productCartFromCollection as $productCartFrom) {
            $productIdFrom = $productCartFrom->getData('product_id');
            /*Update product exist in profileTo*/
            if (in_array($productIdFrom, array_keys($arrayProductIdTo))) {
                $productCartToUpdate = $arrayProductIdTo[$productIdFrom];
                $updateQty = $productCartToUpdate->getData('qty') + $productCartFrom->getData('qty');
                $productCartToUpdate->setData('qty', $updateQty);
                try {
                    $productCartToUpdate->save();
                    $this->loggerMergeProfile->addInfo(
                        'Product ' . $productIdFrom . ' merged success to profile ' . $profileToId
                    );
                } catch (\Exception $e) {
                    $this->loggerMergeProfile->addError(
                        'Product ' . $productIdFrom . ' cannot update product cart in profile ' . $profileToId
                    );
                    $this->loggerMergeProfile->critical($e);
                    return false;
                }
            } else {  /*Add new*/
                $productCartNew = $this->productCartFactory->create();
                $data = $productCartFrom->getData();
                unset($data['cart_id']);
                $data['profile_id'] = $profileToId;
                $data['updated_at'] = $currentTime;
                $data['created_at'] = $currentTime;
                $data['billing_address_id'] = $productCartToBase->getData('billing_address_id');
                $data['shipping_address_id'] = $productCartToBase->getData('shipping_address_id');
                $data['delivery_date'] = $productCartToBase->getData('delivery_date');
                $data['delivery_time_slot'] = $productCartToBase->getData('delivery_time_slot');
                $data['gw_used'] = null;
                $productCartNew->setData($data);
                try {
                    $productCartNew->save();
                    $this->loggerMergeProfile->addInfo(
                        'Product ' . $productIdFrom . ' added success to profile ' . $profileToId
                    );
                } catch (\Exception $e) {
                    $this->loggerMergeProfile->addError(
                        'Cannot merge product ' . $productIdFrom . ' into profile ' . $profileToId
                    );
                    $this->loggerMergeProfile->critical($e);
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param $profile
     * @return bool
     */
    public function disengageProfileFrom($profile)
    {
        if ($profile instanceof \Riki\Subscription\Model\Profile\Profile) {
            if (!$this->getDisengageReason()) {
                $this->loggerMergeProfile->addError(
                    'Cannot disengage profile ' . $profile->getId()
                    . ' because of does not have any reason code for this'
                );
                return false;
            }
            $currentTime = $this->timezone->date()->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
            $profile->setData('status', 0);
            $profile->setData('disengagement_date', $currentTime);
            $profile->setData('disengagement_reason', $this->getDisengageReason());
            $profile->setData('disengagement_user', 'RMM-377');
            try {
                $profile->save();
                $this->loggerMergeProfile->addInfo('Profile ' . $profile->getId() . ' disengaged success');
                if ($this->helperProfile->checkProfileHaveVersion($profile->getId())) {
                    $this->helperProfile->expiredVersion($profile->getId());
                }
                if ($this->helperProfile->checkProfileHaveTmp($profile->getId())) {
                    $profileTmp = $this->helperProfile->getTmpProfile($profile->getId());
                    if ($profileTmp and $profileTmp->getId()) {
                        $profileTmpModel = $this->profileFactory->create()
                            ->load($profileTmp->getData('linked_profile_id'));
                        if ($profileTmpModel->getId()) {
                            $profileTmpModel->setData('status', 0);
                            $profileTmpModel->setData('disengagement_date', $currentTime);
                            $profileTmpModel->setData('disengagement_reason', $this->getDisengageReason());
                            $profileTmpModel->setData('disengagement_user', 'RMM-377');
                            $profileTmpModel->save();
                            $this->loggerMergeProfile->addInfo(
                                'Temporary profile of profile ' . $profile->getId() . ' disengaged success'
                            );
                        } else {
                            $this->loggerMergeProfile->addInfo(
                                'There is no temporary profile with ID ' . $profileTmp->getId()
                            );
                            $this->loggerMergeProfile->addInfo('Some profile have not been disengage completely.');
                            return false;
                        }
                    }
                }
                return true;
            } catch (\Exception $e) {
                $this->loggerMergeProfile->addError('Cannot disengage profile ' . $profile->getId());
                $this->loggerMergeProfile->critical($e);
                return false;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function getDisengageReason()
    {
        if ($this->reasonCode != null) {
            return $this->reasonCode;
        }
        $reasonModel = $this->reasonFactory->create()->getCollection()
            ->addFieldToFilter('code', 27)
            ->setPageSize(1);
        foreach ($reasonModel as $reason) {
            $this->reasonCode = $reason->getData('code');
            return $this->reasonCode;
        }
        $this->reasonCode = false;
        return false;
    }

    /**
     * Validate all product for profile has stock point
     *
     * @param array $productIds
     * @return bool
     */
    private function _validateStockPointProfileForMerging(array $productIds)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $productIds, 'in')
            ->create();
        $productCollection = $this->productRepository->getList($searchCriteria);
        if ($productCollection->getTotalCount()) {
            $deliveryTypes = [];
            foreach ($productCollection->getItems() as $product) {
                if (!$product->getData('allow_stock_point') && !$product->getData('parent_item_id')) {
                    return false;
                }
                $deliveryTypes[$product->getId()] = $product->getDeliveryType();
            }

            /**
             * Validate group delivery type
             */
            if (!empty($deliveryTypes)) {
                if (array_diff($deliveryTypes, $this->deliveryTypeHelper->getCoolNormalDmTypes())) {
                    return false;
                }
                $groupDeliveryType = $this->deliveryTypeHelper->getDeliveryTypeOfCoolNormalDmGroup($deliveryTypes);
                if ($groupDeliveryType != GroupDeliveryType::NORMAl && $groupDeliveryType != GroupDeliveryType::DM) {
                    return false;
                }
            } else {
                return false;
            }
        }
        return true;
    }
}
