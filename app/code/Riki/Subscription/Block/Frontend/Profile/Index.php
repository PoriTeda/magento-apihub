<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category Riki_Shipment
 * @package  Riki\Subscription\Block\Frontend\Profile
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */

namespace Riki\Subscription\Block\Frontend\Profile;

use Magento\Framework\Exception\NoSuchEntityException;
use \Riki\Subscription\Helper\Order\Simulator as HelperOrderSimulator;
use \Magento\Framework\App\ResourceConnection;
use \Riki\Subscription\Block\Html\Pager as BlockHtmlPager;
use \Riki\Sales\Model\ResourceModel\Sales\Grid\ShipmentStatus;
use \Riki\Sales\Model\ResourceModel\Order\OrderStatus;

/**
 * Class Index
 *
 * @category Riki_Subscription
 * @package  Riki\Subscription\Block\Frontend\Profile
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */
class Index extends \Magento\Framework\View\Element\Template
{
    const PROFILE_STATUS_PLANED = 'waiting_for_shipment';
    const PROFILE_STATUS_EDITABLE = 'editable';
    const PROFILE_STATUS_FOR_REFERENCE = 'for_reference';
    const CSS_CLASS_PREPARE_SHIP = 'prepare-ship';
    const CSS_CLASS_NEXT_SHIP = 'next-ship';
    /**
     * @var string
     */
    protected $template = 'subscription-profile.phtml';

    protected $profile;

    protected $modelProfile;

    /**
     * @var \Riki\Subscription\Model\Profile\Profile
     */
    protected $profileModel;
    /**
     * @var \Riki\Subscription\Model\Profile\ResourceModel\Profile
     */
    protected $profileResourceModel;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $helperPrice;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var \Magento\Sales\Model\Order\Address
     */
    protected $addressModel;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfile;

    protected $deliveryType;
    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $subscriptionPageHelper;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var HelperOrderSimulator
     */
    protected $helperSimulator;

    /**
     * @var ResourceConnection
     */
    protected $resource;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileRepository
     */
    protected $profileRepository;
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * @var \Riki\TimeSlots\Model\TimeSlotsFactory
     */
    protected $timeSlotFactory;
    /**
     * @var \Riki\SubscriptionFrequency\Helper\Data
     */
    protected $frequencyHelper;

    /**
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $rewardManagement;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var \Riki\Subscription\Helper\Indexer\Data
     */
    protected $profileIndexerHelper;

    /**
     * @var \Riki\SubscriptionProfileDisengagement\Helper\Data
     */
    protected $disengageHelper;

    /**
     * Construct
     *
     * @param ResourceConnection $resourceConnection
     * @param HelperOrderSimulator $helperOrderSimulator
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @param \Riki\Subscription\Model\Profile\ResourceModel\Profile $profileResource
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Pricing\Helper\Data $helperPrice
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Sales\Model\Order\Address $address
     * @param \Riki\DeliveryType\Model\Product\Deliverytype $deliverytype
     * @param \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Loyalty\Model\RewardManagement $rewardManagement,
        \Riki\TimeSlots\Model\TimeSlotsFactory $timeSlotsFactory,
        ResourceConnection $resourceConnection,
        HelperOrderSimulator $helperOrderSimulator,
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\Subscription\Model\Profile\Profile $profile,
        \Riki\Subscription\Model\Profile\ResourceModel\Profile $profileResource,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Pricing\Helper\Data $helperPrice,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Sales\Model\Order\Address $address,
        \Riki\DeliveryType\Model\Product\Deliverytype $deliverytype,
        \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\SubscriptionFrequency\Helper\Data $frequencyHelper,
        \Riki\Subscription\Helper\Indexer\Data $profileIndexerHelper,
        \Riki\SubscriptionProfileDisengagement\Helper\Data $disengageHelper,
        array $data = []
    ) {
        $this->customerRepository = $customerRepository;
        $this->rewardManagement = $rewardManagement;
        $this->timeSlotFactory = $timeSlotsFactory;
        $this->resource = $resourceConnection;
        $this->helperSimulator = $helperOrderSimulator;
        $this->subscriptionPageHelper = $subscriptionPageHelper;
        $this->coreRegistry = $registry;
        $this->profileModel = $profile;
        $this->profileResourceModel = $profileResource;
        $this->helperPrice = $helperPrice;
        $this->dateTime = $dateTime;
        $this->addressModel = $address;
        $this->helperProfile = $helperProfile;
        $this->deliveryType = $deliverytype;
        $this->timezone = $context->getLocaleDate();
        $this->profileRepository = $profileRepository;
        $this->courseFactory = $courseFactory;
        $this->frequencyHelper = $frequencyHelper;
        $this->profileIndexerHelper = $profileIndexerHelper;
        $this->_template = $this->template;
        $this->disengageHelper = $disengageHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('Delivery schedule of regular flights'));
    }

    /**
     * @return bool|\Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSubscriptionsProfile()
    {
        if (!($customerId = $this->getCustomerId())) {
            return false;
        }
        if (!$this->profile) {
            $this->profile = $this->profileModel->getCustomerSubscriptionProfileExcludeHanpukai($customerId);
        }
        return $this->profile;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getSubscriptionsProfile()) {
            $pager = $this->getLayout()->createBlock(
                BlockHtmlPager::class,
                'customer.subscription.profile.pager'
            )->setCollection(
                $this->getSubscriptionsProfile()
            );
            $this->setChild('pager', $pager);
            $this->getSubscriptionsProfile()->load();
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('customer/account/');
    }

    public function getCustomerId()
    {
        $customerId = $this->coreRegistry->registry('current_subscription_profile_customer');
        return $customerId;
    }

    /**
     * Get Shipping Free
     *
     * @param $profileId
     * @param $storeId
     *
     * @return float|string
     */
    public function getShippingFee($profileId, $storeId)
    {
        return $this->helperProfile->getShippingFeeByProfileId($profileId, $storeId);
    }

    /**
     * Get Date Subscription
     *
     * @param $date
     *
     * @return string
     */
    public function getDateSubscription($date)
    {
        return $this->dateTime->date('Y/m/d', $date);
    }

    /**
     * Get base url subscription profile
     *
     * @param $id
     *
     * @return string
     */
    public function getBaseUrlSubcriptionProfile($id)
    {
        return $this->getUrl('subscriptions/profile/edit', ['id' => (int)$id, 'list' => 1]);
    }

    public function getBaseUrlManagePoint()
    {
        return $this->getUrl('loyalty/reward/');
    }

    /**
     * Get base url subscription profile skip next delivery
     *
     * @param $profileId
     * @return string
     */
    public function getUrlSubProfileSkipNextDelivery($profileId)
    {
        return $this->getUrl('subscriptions/profile/skipnextdelivery/', ['id' => $profileId]);
    }

    /**
     * Get base url subscription profile change frequency
     *
     * @param $profileId
     * @return string
     */
    public function getUrlSubProfileChangeFrequency($profileId)
    {
        return $this->getUrl('subscriptions/profile/changefrequency/', ['id' => $profileId]);
    }

    /**
     * Get base url subscription
     *
     * @return string
     */
    public function getBaseUrlSubscription()
    {
        return $this->getUrl('subscriptions/profile/ajax');
    }

    /**
     * Get delivery type
     *
     * @param int $profile_id
     *
     * @return array
     */
    public function getDeliveryType($profile_id = 0)
    {
        $arr_delivery = [];
        if ($profile_id) {
            $products = $this->helperProfile->getProductSubscriptionProfile($profile_id);
            if (!empty($products) > 0) {
                $deliveryTypeCollection = $this->helperProfile->getAttributesProduct($products);
                foreach ($deliveryTypeCollection as $delivery) {
                    if ($delivery->getDeliveryType() != null) {
                        $arr_delivery[] = $delivery->getDeliveryType();
                    }
                }
            }
            return $arr_delivery;
        }
        return $arr_delivery;
    }

    /**
     * Get delivery type text
     *
     * @param $key
     *
     * @return null
     */
    public function getDeliveryTypeText($key)
    {
        $arr_delivery = $this->deliveryType->getOptionArray();
        if ($arr_delivery) {
            if (isset($arr_delivery[$key])) {
                return $arr_delivery[$key];
            }
        }
        return null;
    }

    public function allowChangeSkipNextDelivery($subscription)
    {
        $dateCheck = $this->checkDateToEditSkipNextDelivery($subscription);
        $orderFlag = $subscription->getData('create_order_flag');
        if ($this->subscriptionPageHelper->getSubscriptionType($subscription->getData('course_id'))
            == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI
        ) {
            $isHanpukai = true;
        } else {
            $isHanpukai = false;
        }

        if ($dateCheck && !$isHanpukai && $orderFlag != 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check date to edit skip next delivery
     *
     * @param $subscription
     *
     * @return bool
     */

    public function checkDateToEditSkipNextDelivery($subscription)
    {
        $nextOrderDate = $subscription->getData('next_order_date');
        $OrderDate = $this->dateTime->gmtDate('Ymd', $nextOrderDate);
        $nextDeliveryDate = $subscription->getData('next_delivery_date');
        $DeliveryDate = $this->dateTime->gmtDate('Ymd', $nextDeliveryDate);
        $origin_date = $this->timezone->formatDateTime($this->dateTime->gmtDate(), 2);
        $currentDate = $this->dateTime->gmtDate('Ymd', $origin_date);
        if ($currentDate >= $OrderDate && $currentDate <= $DeliveryDate) {
            return false;
        }
        return true;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function threeDeliveryDate()
    {
        $arrResult = [];
        $profileCollection = $this->getSubscriptionsProfile();
        $pointUsed = $this->getPointUsed();
        foreach ($profileCollection as $profileItem) {
            /**
             * @var \Riki\Subscription\Model\Profile\Profile $profileItem
             */
            $profileId = $profileItem->getData('profile_id');
            $arrThreeDelivery = $this->helperProfile->calculateNextDelivery($profileItem);
            $arrResult[$profileId]['course_name'] = $profileItem->getData('course_name');
            $arrResult[$profileId]['frequency'] = $this->frequencyHelper->formatFrequency(
                $profileItem->getData('frequency_interval'),
                $profileItem->getData('frequency_unit')
            );
//            $dataSimulate = $profileItem->getSimulateDataFromCache();
//            if (is_array($dataSimulate) && array_key_exists('TotalAmount', $dataSimulate)) {
//                $arrResult[$profileId]['next_delivery_amount']['total_amount'] = $dataSimulate['TotalAmount'];
//            } else {
//                $arrResult[$profileId]['next_delivery_amount']['total_amount'] = $this->getOrderTotalAmount($profileId);
//            }
            $arrResult[$profileId]['next_delivery_amount']['total_amount'] = 0;
            $arrResult[$profileId]['next_delivery_amount']['number_of_point_used'] = $pointUsed;
            $arrResult[$profileId]['next_delivery_1'] = $arrThreeDelivery[0];
            $arrResult[$profileId]['next_delivery_2'] = $arrThreeDelivery[1];
            $arrResult[$profileId]['next_delivery_3'] = $arrThreeDelivery[2];

            $arrResult[$profileId]['stock_point_profile_bucket_id'] = $profileItem->getData(
                'stock_point_profile_bucket_id'
            );
            $arrResult[$profileId]['stock_point_delivery_type'] = $profileItem->getData(
                'stock_point_delivery_type'
            );
            $arrResult[$profileId]['stock_point_delivery_information'] = $profileItem->getData(
                'stock_point_delivery_information'
            );
            //NED-1534
            $arrResult[$profileId]['allow_change_product'] = $profileItem->getData(
                'allow_change_product'
            );
        }
        if (empty($arrResult)) {
            // [NED-1019] log urlRewrites list
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/NED-1019.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);

            $logger->info('************************************');
            $logger->info('Customer id ' . $this->getCustomerId() . '- SQL get profile Collection : ' . $profileCollection->getSelect()->__toString());
            $logger->info('************************************');
        }
        return $arrResult;
    }

    /**
     * Get Slot Name
     *
     * @param $profileItem
     * @return string
     */
    public function getSlotName($profileItem)
    {
        $collectionProduct = $this->helperProfile->getProductHaveTimeSlot($profileItem);
        if ($collectionProduct->getSize() > 0) {
            $firstItem = current($collectionProduct->getItems());
            $timeSlotObj = $this->timeSlotFactory->create()->load($firstItem->getData('delivery_time_slot'));
            if ($timeSlotObj->getId()) {
                return $timeSlotObj->getData('slot_name');
            }
        }
        return 'unspecified';
    }

    /**
     * Calculate status
     *
     * @param $profileItem
     * @return mixed
     */
    public function calculateStatus($profileItem)
    {
        $isTmpProfile = $this->isProfileHaveTmp($profileItem->getData('profile_id'));
        if ($isTmpProfile === false) {
            $arrResult[0] = self::PROFILE_STATUS_EDITABLE;
            $arrResult[1] = self::PROFILE_STATUS_FOR_REFERENCE;
            $arrResult[2] = self::PROFILE_STATUS_FOR_REFERENCE;
        } else {
            $arrResult[0] = self::PROFILE_STATUS_PLANED;
            $arrResult[1] = self::PROFILE_STATUS_EDITABLE;
            $arrResult[2] = self::PROFILE_STATUS_FOR_REFERENCE;
        }
        return $arrResult;
    }

    public function isProfileHaveTmp($profileId)
    {
        if ($this->helperProfile->getTmpProfile($profileId) == false) {
            return false;
        } else {
            return $this->helperProfile->getTmpProfile($profileId)->getData('linked_profile_id');
        }
    }

    public function getSimulatorOrderOfProfile($profileId)
    {
        $isList = true; // if simulate from list not get point
        try {
            $simulatorOrder = $this->helperSimulator->createMageOrder($profileId, null, true, null, $isList);
            if ($simulatorOrder instanceof \Riki\Subscription\Model\Emulator\Order) {
                return $simulatorOrder;
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }

        return false;
    }

    public function getOrderTotalAmount($profileId)
    {
        $orderSimulator = $this->getSimulatorOrderOfProfile($profileId);
        if ($orderSimulator === false) {
            return __('Not Yet Calculator');
        } else {
            $this->addCacheProfileIndexer($profileId, $orderSimulator);
            return $orderSimulator->getGrandTotal();
        }
    }

    public function getLinkTmpSubProfile($profileId)
    {
        if ($profileId) {
            return $this->getBaseUrlSubcriptionProfile($profileId);
        } else {
            return '';
        }
    }

    public function isHanpukai($profileId)
    {
        $isHanpukai = false;
        $subProfileModel = $this->profileModel->load($profileId);
        if ($subProfileModel && $subProfileModel->getId()) {
            $courseId = $subProfileModel->getData('course_id');
            if ($this->subscriptionPageHelper->getSubscriptionType($courseId)
                == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI
            ) {
                $isHanpukai = true;
            }
        }
        return $isHanpukai;
    }

    /**
     * Convert all date to true format YYYY/mm/dd
     *
     * @param string $date
     *
     * @return string
     */
    public function convertDateToTrueFormat($date)
    {
        if ($date === 'N/A') {
            return $date;
        }
        return $this->timezone->date($date)->format('Y/m/d');
    }

    public function checkAllowSkipNextDelivery($profileId)
    {
        try {
            $profileModel = $this->profileRepository->get($profileId);

            $courseId = $profileModel->getCourseId();
            $courseModel = $this->courseFactory->create()->load($courseId);
            $allowSkipNextDelivery = $courseModel->getData('allow_skip_next_delivery');
            if ($allowSkipNextDelivery) {
                return true;
            }
        } catch (NoSuchEntityException $e) {
            return false;
        }

        return false;
    }

    /**
     * Format currency
     *
     * @param $price
     * @param null $websiteId
     *
     * @return mixed
     */

    public function formatCurrency($price)
    {
        if (is_numeric($price)) {
            return $this->_storeManager->getWebsite($this->_storeManager->getStore()->getWebsiteId())
                ->getBaseCurrency()->format($price);
        } else {
            return $price;
        }
    }

    /**
     * Get use point amount setting of this customer
     *
     * @param $customerId
     *
     * @return mixed
     */
    public function getPointUsed()
    {
        $customerId = $this->getCustomerId();
        $customer = $this->customerRepository->getById($customerId);
        $customerCode = $customer->getCustomAttribute('consumer_db_id')->getValue();
        $pointSetting = $this->rewardManagement->getRewardUserSetting($customerCode);
        if (array_key_exists('use_point_type', $pointSetting)) {
            if ($pointSetting['use_point_type'] == 0) {
                return 0;
            } elseif ($pointSetting['use_point_type'] == 1) {
                return $this->rewardManagement->getPointBalance($customerCode);
            } else {
                return $pointSetting['use_point_amount'];
            }
        }
        return $pointSetting['use_point_amount'];
    }

    /**
     * is show changing payment method link
     *
     * @param $profileId
     * @return bool
     */
    public function showChangePaymentMethodLink($profileId)
    {
        if (!$this->modelProfile) {
            $this->modelProfile = $this->profileModel->load($profileId);
        }
        $profileModel = $this->modelProfile;
        if ($this->helperProfile->checkProfileHaveTmp($profileId) and ($profileModel->getPaymentMethod() === null)) {
            return true;
        }
        return false;
    }

    /**
     * Check membership of customer to show message on list profile
     *
     * @param $profileId
     * @return \Magento\Framework\Phrase|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkMemberShip($profileId)
    {
        if (!$this->modelProfile) {
            $this->modelProfile = $this->profileModel->load($profileId);
        }
        $profileModel = $this->modelProfile;
        $result = null;
        $mess = null;
        if ($profileModel->getId()) {
            /**
             * @var \Magento\Customer\Model\Customer $customer
             */
            $customer = $profileModel->getCustomer();
            $membership = $customer->getCustomAttribute('membership');
            if ($membership) {
                $membership = $membership->getValue();
                $arrMembership = explode(',', $membership);
                if (is_array($arrMembership)) {
                    if (in_array(15, $arrMembership)) {
                        $result = 3;
                    } elseif (in_array(3, $arrMembership)) {
                        $result = 2;
                    } else {
                        $result = 1;
                    }
                } else {
                    $result = 1;
                }
            }
            switch ($result) {
                case 2:
                    $mess = __('Nescafe Ambassador Call Center: 0120-252-166');
                    break;
                case 3:
                    $mess = __('Nestle Wellness Ambassador Call Center: 0120-070-838');
                    break;
                default:
                    $mess = __('Nestle Call Center: 0120-600-868');
                    break;
            }
        }
        return $mess;
    }

    /**
     * Add cache for profile list when does not have cache
     *
     * @param $profileId
     * @param $simulatorOrder
     * @throws \Exception
     */
    public function addCacheProfileIndexer($profileId, $simulatorOrder)
    {
        $this->coreRegistry->unregister('reindex_cache_profile');
        $this->coreRegistry->register('reindex_cache_profile', true);
        $dataSimulate = $this->profileIndexerHelper->prepareData($simulatorOrder);

        if ($dataSimulate) {
            $serializedData = \Zend\Serializer\Serializer::serialize($dataSimulate);
            $dataTable = [
                'profile_id' => $profileId,
                'customer_id' => $simulatorOrder->getCustomerId(),
                'data_serialized' => $serializedData
            ];
            $profileIds[] = $profileId;
            $this->profileIndexerHelper->saveToTable($dataTable);
        }
        /*** end get data from simulator ***/
        /*update reindex flag*/
        $this->profileIndexerHelper->updateProfile($profileId);
    }

    /**Check profile has stock point
     * @param $profile
     * @return bool
     */
    public function checkProfileHasStockPoint($profile)
    {
        if (isset($profile['stock_point_profile_bucket_id']) && (int)$profile['stock_point_profile_bucket_id'] > 0) {
            return true;
        }
        return false;
    }

    /**
     * @param $profileId
     * @param $profile
     * @return bool
     */
    public function isHiddenChangeDeliveryDate($profileId, $profile)
    {
        $result = $this->checkProfileHasStockPoint($profile);
        $data = null;
        if (!$result) {
            $url = $this->getUrlSubProfileChangeFrequency($profileId);
            $data = "<a href='$url' class='margin'>" . __('Change Frequency') . "</a>";
        }
        return $data;
    }

    /**
     * @param $profileId
     * @param $profile
     * @return bool
     */
    public function isHiddenSkipNextDelivery($profileId, $profile)
    {
        $data = null;
        if ($this->checkAllowSkipNextDelivery($profileId)) {
            $result = $this->checkProfileHasStockPoint($profile);
            if (!$result) {
                $url = $this->getUrlSubProfileSkipNextDelivery($profileId);
                $data = "<a href='$url' class='margin'>" . __('Skip Next Delivery') . "</a>";
            }
        }
        return $data;
    }

    /**
     * @param $profileId
     * @return string
     */
    public function getLinkAddItemsOutSideTheCourse($profileId)
    {
        $url = $this->getUrl('subscriptions/profile/addSpotProduct/id/' . $profileId);
        $data = "<a href='$url' class='margin'>" . __('Add items outside the course') . "</a>";
        return $data;
    }

    /**
     * @param $status
     * @return bool
     */
    public function checkProfileStatusEditTable($status)
    {
        if ($status == \Riki\Subscription\Block\Frontend\Profile\Index::PROFILE_STATUS_EDITABLE) {
            return true;
        }
        return false;
    }

    /**
     * @param $status
     * @return bool
     */
    public function checkProfileStatusPlaned($status)
    {
        if ($status == \Riki\Subscription\Block\Frontend\Profile\Index::PROFILE_STATUS_PLANED) {
            return true;
        }
        return false;
    }

    /**
     * @param $profileId
     * @return string
     */
    public function getUrlAddSpotProduct($profileId)
    {
        return $this->getUrl('subscriptions/profile/addSpotProduct/id/' . $profileId);
    }

    /**
     * Get Shipping address riki nick name of subscription profile
     *
     * @param $profileId
     * @return string
     */
    public function getShippingAddressNickName($profileId)
    {
        $multiNickName = $this->helperProfile->getAddressArrOfProfile($profileId, 'riki_nickname');
        if (isset($multiNickName[0])) {
            return $multiNickName[0];
        }
        return '';
    }

    /**
     * Get data stock point for profile has temp
     * @param $item
     * @param $profileId
     * @return mixed
     */
    public function convertDataProfile($profileId, $item)
    {
        if (array_key_exists('stock_point_profile_bucket_id', $item)) {
            $isTmpProfile = $this->isProfileHaveTmp($profileId);
            if ($isTmpProfile && $isTmpProfile != $profileId) {
                $profileTmp = $this->profileModel->load($isTmpProfile);
                if ($profileTmp) {
                    $item['stock_point_profile_bucket_id'] = $profileTmp->getData('stock_point_profile_bucket_id');
                }
            }
        }
        return $item;
    }

    /**
     * @param $profileId
     * @return bool
     */
    public function isDisengageProfile($profileId)
    {
        return $this->disengageHelper->isDisengageMode($profileId);
    }

    /**
     * @param $profileId
     * @return string
     */
    public function getLabel($profileId)
    {
        return $this->isProfileHaveTmp($profileId) ? self::PROFILE_STATUS_PLANED : self::PROFILE_STATUS_EDITABLE;
    }

    /**
     * @param $profileId
     * @return string
     */
    public function getClass($profileId)
    {
        return $this->isProfileHaveTmp($profileId) ? self::CSS_CLASS_PREPARE_SHIP : self::CSS_CLASS_NEXT_SHIP;
    }
}
