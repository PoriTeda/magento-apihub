<?php

namespace Riki\Subscription\Model;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class ProfileCacheRepository
 * @package Riki\Subscription\Model
 */
class ProfileCacheRepository extends \Magento\Framework\App\Helper\AbstractHelper
{
    const ALIAS = 'SUBSCRIPTION_PROFILE';

    const LIFETIME_CACHE = 'subscriptioncourse/subprofilesession/lifetime_sub_session';

    const DEFAULT_CACHE_LIFETIME = 20;

    /**
     * @var \Riki\Subscription\Model\App\Profile\Cache
     */
    protected $cache;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\App\Cache\StateInterface
     */
    protected $cacheState;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;
    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileHelper;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var array
     */
    protected $profileData = [];

    /**
     * @var \Riki\Subscription\Logger\Logger
     */
    protected $logger;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    private $serializer;

    /**
     * ProfileCacheRepository constructor.
     * @param Context $context
     * @param \Riki\Subscription\Model\App\Profile\Cache $cache
     * @param \Riki\Subscription\Helper\Profile\Data $profileHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Riki\Subscription\Logger\Logger $logger
     */
    public function __construct(
        Context $context,
        \Riki\Subscription\Model\App\Profile\Cache $cache,
        \Riki\Subscription\Helper\Profile\Data $profileHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Riki\Subscription\Logger\Logger $logger,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        parent::__construct($context);
        $this->cache = $cache;
        $this->profileHelper = $profileHelper;
        $this->scopeConfig = $context->getScopeConfig();
        $this->date = $date;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    /**
     * @param $profileId
     * @param bool $reset
     * @param null $profileModel
     * @return mixed|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initProfile($profileId, $reset = false, $profileModel = null)
    {
        if (isset($this->profileData[$profileId])) {
            return $this->profileData[$profileId];
        }

        /*current timestamp - use to set and validate profile cache*/
        $timestamp = $this->date->gmtTimestamp();

        /*cache lifetime - use to check expire profile cache*/
        $lifetimeCache = $this->getCacheLifetime();

        if (is_null($profileModel) || !is_object($profileModel) || $profileModel->getProfileId() != $profileId) {
            $profileModel = $this->getProfileById($profileId);
        }

        if (!$profileModel->getProfileId() || $profileModel->getProfileId() != $profileId) {
            return null;
        }

        /*current list applied coupon*/
        $appliedCoupon = !empty($profileModel->getCouponCode())
            ? explode(',', $profileModel->getCouponCode())
            : [];

        $identifier = $this->initIdentifier($profileId);

        if ($reset) {
            $this->removeCache($profileId);
        }
        if (($profileDataCache = $this->getCache($profileId)) && $profileDataCache instanceof DataObject && !$reset) {
            $profileCacheCacheDetail = $profileDataCache->getProfileData()[$profileId];

            if (isset($profileCacheCacheDetail)) {
                $hasDataChanged = $this->profileHelper->hasDataChanged($profileModel, $profileCacheCacheDetail);
                if ($hasDataChanged) {
                    $profileDataCache->getProfileData()[$profileId][\Riki\Subscription\Model\Constant::SUBSCRIPTION_PROFILE_HAS_CHANGED] = true;
                }
                if (isset($profileCacheCacheDetail['lifetime_cache'])) {
                    if (($timestamp - $profileCacheCacheDetail['lifetime_cache']) > ($lifetimeCache * 60)) {
                        return $this->createNewProfileCache(
                            $profileModel,
                            $appliedCoupon,
                            $timestamp,
                            $identifier,
                            $lifetimeCache
                        );
                    } else {
                        $profileDataCache->getProfileData()[$profileId]['lifetime_cache'] = $timestamp;
                        /*get current applied coupon from cache*/
                        if (isset($profileCacheCacheDetail['appliedCoupon'])) {
                            $appliedCoupon = $profileCacheCacheDetail['appliedCoupon'];
                        }
                    }
                } else {
                    $profileDataCache->getProfileData()[$profileId]['lifetime_cache'] = $timestamp;
                    /*get current applied coupon from cache*/
                    if (isset($profileDataCache->getProfileData()[$profileId]['appliedCoupon'])) {
                        $appliedCoupon = $profileDataCache->getProfileData()[$profileId]['appliedCoupon'];
                    }
                }
            }
            $profileDataCache->getProfileData()[$profileId]['coupon_code'] = implode(',', $appliedCoupon);
            $this->profileData[$profileId] = $profileDataCache;
            return $this->profileData[$profileId];
        }

        try {
            /** Create new data if not exists */
            $profileCache = $this->createNewProfileCache(
                $profileModel,
                $appliedCoupon,
                $timestamp,
                $identifier,
                $lifetimeCache
            );
        } catch (\Exception $e) {
            return null;
        }
        $this->profileData[$profileId] = $profileCache;
        return $this->profileData[$profileId];
    }

    /**
     * Create new profile cache
     *
     * @param $profileModel
     * @param $appliedCoupon
     * @param $timestamp
     * @param $identifier
     * @param $lifetimeCache
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function createNewProfileCache($profileModel, $appliedCoupon, $timestamp, $identifier, $lifetimeCache)
    {
        $profileObject = $this->generateProfileDataObject(
            $profileModel->getData(),
            [
                'course_data' => $profileModel->getCourseData(),
                'product_cart' => $profileModel->getProductCartData(),
                'appliedCoupon' => $appliedCoupon,
                'lifetime_cache' => $timestamp
            ]
        );
        $profileCache = $this->dataObjectFactory->create();
        $profileCache->setProfileData([$profileModel->getId() => $profileObject]);
        $profileCache->setIdentifier($identifier);
        $profileCache->setLifeTime($lifetimeCache * 60);
        /** save cache */
        $this->save($profileCache);

        return $profileCache;
    }

    /**
     * @param $profileId
     * @return bool | \Magento\Framework\DataObject
     */
    public function getCache($profileId)
    {
        $identifier = $this->initIdentifier($profileId);
        $cache = $this->cache->load($identifier);
        try {
            if ($cache) {
                $data = $this->serializer->unserialize($cache);

                /** @var \Magento\Framework\DataObject $ob */
                $ob = $this->dataObjectFactory->create();
                $ob->addData([
                    'identifier' => $data['identifier'],
                    'life_time' => $data['life_time']
                ]);
                $profileData = [];
                if (isset($data['profile_data']) && is_array($data['profile_data'])) {
                    foreach ($data['profile_data'] as $profileId => $profileData) {
                        $profileData[$profileId] = $this->convertProfileDataModel($profileData, false);
                    }
                }

                return $ob->setData('profile_data', $profileData);
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * @param $profileData
     * @param bool $isToJson
     * @return array|DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function convertProfileDataModel($profileData, $isToJson = true)
    {
        if ($isToJson) {
            if ($profileData instanceof \Magento\Framework\DataObject) {
                $data = $profileData->getData();
            } else {
                if (is_array($profileData)) {
                    $data = $profileData;
                } else {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Can not convert profile data model'));
                }
            }

            if (isset($data['product_cart']) && is_array($data['product_cart']) && current($data['product_cart']) instanceof \Magento\Framework\DataObject) {
                $productCartData = [];
                foreach ($data['product_cart'] as $key => $datum) {
                    $productCartData[$key] = $datum->getData();
                }

                $data['product_cart'] = $productCartData;
            }
            return $data;
        } else {
            if (is_array($profileData)) {
                /** @var \Magento\Framework\DataObject $ob */
                $ob = $this->dataObjectFactory->create()->setData($profileData);
            } else {
                if ($profileData instanceof \Magento\Framework\DataObject) {
                    $ob = $profileData;
                } else {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Can not convert profile data model'));
                }
            }

            if (is_array($ob->getData("product_cart")) && is_array(current($ob->getData("product_cart")))) {
                $productCartData = [];
                foreach ($ob->getData("product_cart") as $key => $datum) {
                    $productCartData[$key] = $this->dataObjectFactory->create()->setData($datum);
                }

                $ob->setData("product_cart", $productCartData);
            }

            return $ob;
        }
    }

    /**
     * Retrieve profile model in cache
     * @param $profileId
     * @return \Riki\Subscription\Model\Profile\Profile | bool
     */
    public function getProfileDataCache($profileId)
    {
        $profileCache = $this->getCache($profileId);
        if (is_object($profileCache) && $profileCacheData = $profileCache->getProfileData()) {
            return $profileCacheData[$profileId];
        }
        return false;
    }

    /**
     * @param $id
     * @return string
     */
    public function initIdentifier($id)
    {
        return self::ALIAS . '_' . 'CACHE' . '_' . $id;
    }

    /**
     * @param $profileId
     * @return \Riki\Subscription\Model\Profile\Profile
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getProfileById($profileId)
    {
        /** @var \Riki\Subscription\Model\Data\ApiProfile $profileDataModel */
        try {
            return $this->profileHelper->load($profileId);
        } catch (NoSuchEntityException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('This subscription profile do not exists.'));
        }
    }

    /**
     * @return int
     */
    public function getCacheLifetime()
    {
        $rs = $this->scopeConfig->getValue(self::LIFETIME_CACHE);

        if (empty($rs) || !is_numeric($rs) || (int)$rs <= 0) {
            $rs = self::DEFAULT_CACHE_LIFETIME;
        }

        return $rs;
    }

    /**
     * generate profile data object
     *
     * @param $profileData
     * @param [] $additionalData
     * @return \Magento\Framework\DataObject
     */
    protected function generateProfileDataObject($profileData, $additionalData)
    {
        /*generate profile object by profile data*/
        $profileObject = $this->dataObjectFactory->create();

        $profileObject->setData($profileData);

        if (!empty($additionalData)) {
            /*set additional data for profile object*/
            foreach ($additionalData as $key => $data) {
                $profileObject->setData($key, $data);
            }
        }

        return $profileObject;
    }

    /**
     * @param $profileCache
     * @return ProfileCacheRepository
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save($profileCache)
    {
        if (is_object($profileCache)) {
            $identifier = $profileCache->getIdentifier();
            if (!$profileCache->getProfileData()) {
                if (!$profileCache->getProfileId()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('This subscription profile do not exists.'));
                }
                $identifier = $this->initIdentifier($profileCache->getProfileId());
                $lifeTime = $this->getCacheLifetime();

                $profileCacheNew = $this->dataObjectFactory->create();
                $profileCacheNew->setProfileData([$profileCache->getProfileId() => $this->convertProfileDataModel($profileCache)]);
                $profileCacheNew->setIdentifier($identifier);
                $profileCacheNew->setLifeTime($lifeTime * 60);
                $profileCache = $profileCacheNew;
            }

            if ($profileCache->getProfileData()) {
                $data = $profileCache->getData();
                if (is_array($data["profile_data"]) && current($data["profile_data"]) instanceof \Magento\Framework\DataObject) {
                    $profileData = [];
                    foreach ($data["profile_data"] as $key => $item) {
                        $profileData[$key] = $this->convertProfileDataModel($item->getData(),true);
                    }
                    $data["profile_data"] = $profileData;
                }

                $this->cache->save(
                    $this->serializer->serialize($data),
                    $identifier,
                    [],
                    $this->getCacheLifetime() * 60
                );
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__('Can not save profile data to cache'));
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('Can not convert profile data model'));
        }

        return $this;
    }

    /**
     * @param $profileId
     * @return ProfileCacheRepository
     */
    public function removeCache($profileId)
    {
        $identifier = $this->initIdentifier($profileId);
        $this->cache->remove($identifier);

        return $this;
    }
}
