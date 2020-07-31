<?php

namespace Riki\SubscriptionProfileDisengagement\Observer;

use Magento\Framework\Event\ObserverInterface;

class SubscriptionProfileCreateOrderAfter implements ObserverInterface
{
    /**
     * @var \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile
     */
    protected $profileIndexer;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfileData;
    /**
     * @var \Riki\Subscription\Logger\LoggerOrder
     */
    protected $_loggerOrder;

    /**
     * @var \Riki\Subscription\Helper\StockPoint\Data
     */
    protected $stockPointHelper;

    /**
     * SubscriptionProfileCreateOrderAfter constructor.
     * @param \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile $profileIndexer
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfileData
     * @param \Riki\Subscription\Helper\StockPoint\Data $stockPointHelper
     * @param \Riki\Subscription\Logger\LoggerOrder $loggerOrder
     */
    public function __construct(
        \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile $profileIndexer,
        \Riki\Subscription\Helper\Profile\Data $helperProfileData,
        \Riki\Subscription\Helper\StockPoint\Data $stockPointHelper,
        \Riki\Subscription\Logger\LoggerOrder $loggerOrder
    ) {
        $this->stockPointHelper = $stockPointHelper;
        $this->profileIndexer = $profileIndexer;
        $this->helperProfileData = $helperProfileData;
        $this->_loggerOrder = $loggerOrder;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $observer->getProfile();

        if ($profile->isWaitingToDisengaged()) {
            $profile->setStatus(0);
            $profile->setSpecifiedWarehouseId(null);

            if ($profile->isStockPointProfile()) {
                $mainProfileId = $this->helperProfileData->getMainFromTmpProfile($profile->getData('profile_id'));
                $resultApi = $this->stockPointHelper->removeFromBucket($mainProfileId);
                if (isset($resultApi['success']) && !$resultApi['success']) {
                    $message = __('Profile Id: %1 - call removeFromBucket API unsuccessfully.', $mainProfileId);
                    $this->_loggerOrder->addError($message);
                }
                $profile->setStockPointProfileBucketId(null)
                    ->setStockPointDeliveryType(null)
                    ->setStockPointDeliveryInformation(null);
            }
        }
        if ($this->helperProfileData->checkProfileHaveTmp($profile->getId())) {
            $tmpProfile = $this->helperProfileData->getTmpProfile($profile->getId());
            if ($tmpProfile->getId()) {
                $tmpProfileId = $tmpProfile->getData('linked_profile_id');
            } else {
                return false;
            }

            /** @var \Riki\Subscription\Model\Profile\Profile $profileModel */
            $profileModel = $this->helperProfileData->load($tmpProfileId);
            if ($profileModel && $profileModel->getId() && $profileModel->isWaitingToDisengaged()) {
                $profileModel->setStatus(0);
                $profileModel->setSpecifiedWarehouseId(null);

                /** remove stock point  */
                if ($profileModel->isStockPointProfile()) {
                    $profileModel->setStockPointProfileBucketId(null)
                        ->setStockPointDeliveryType(null)
                        ->setStockPointDeliveryInformation(null)
                        ->setSpecifiedWarehouseId(null);
                }

                try {
                    $profileModel->save();
                } catch (\Exception $e) {
                    $this->_loggerOrder->info('Profile tmp of profile #' . $profile->getId() . 'cannot disengage');
                    $this->_loggerOrder->critical($e);
                }
            }
        }

        // check hanpukai to clear cache
        if ($profile->getData('hanpukai_qty')) {
            $this->profileIndexer->removeCacheInvalid($profile->getId());
        }
    }
}
