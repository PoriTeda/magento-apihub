<?php

namespace Riki\Subscription\Logger;

use \Magento\Framework\Exception\LocalizedException;

/**
 * Class LoggerStateProfile
 * @package Riki\Subscription\Logger
 */
class LoggerStateProfile extends \Monolog\Logger
{
    const LOGGER_SUBSCRIPTION_STATE_PROFILE = 'loggersetting/subscriptionlogger/logger_state_profile_active';

    const SUBSCRIPTION_CREATED_DATE = '2018-12-14';

    /**
     * @var \Riki\Framework\Helper\Logger\LoggerBuilder
     */
    protected $traceLogger;

    /**
     * list of holidays
     * @var array
     */
    protected $holidays = [
        '2019-01-14',
        '2019-02-11',
        '2019-03-21',
        '2019-04-29',
        '2019-05-03',
        '2019-05-04',
        '2019-05-06',
    ];

    public function __construct(
        \Riki\Framework\Helper\Logger\LoggerBuilder $traceLogger,
        $name,
        $handlers = [],
        $processors = []
    ) {
        parent::__construct($name, $handlers, $processors);
        $this->traceLogger = $traceLogger;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();

        $enableLogger = $om->get('\Magento\Framework\App\Config\ScopeConfigInterface')
            ->getValue(self::LOGGER_SUBSCRIPTION_STATE_PROFILE);

        if ($enableLogger) {
            return true;
        }

        return false;
    }

    /**
     * @param \Riki\Subscription\Model\Profile\Profile $profileModel
     * @param array $context
     * @return bool
     * @throws \Exception
     */
    public function infoProfile(\Riki\Subscription\Model\Profile\Profile $profileModel, array $context = [])
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($profileModel) {
            $aInfoProfile =  [
                'next_order_date' => $profileModel->getNextOrderDate(),
                'next_delivery_date' => $profileModel->getNextDeliveryDate(),
                'payment_method' => $profileModel->getPaymentMethod(),
                'trading_id' => $profileModel->getTradingId(),
                'type' => $profileModel->getType(),
                'status' => $profileModel->getStatus(),
                'updated_user' => $profileModel->getUpdatedUser(),
                'order_times' => $profileModel->getOrderTimes(),
                'disengagement_date' => $profileModel->getData('disengagement_date'),
                'disengagement_user' => $profileModel->getData('disengagement_user'),
                'disengagement_reason' => $profileModel->getData('disengagement_reason'),
                'coupon_code' => $profileModel->getData('coupon_code'),
                'specified_warehouse_id' => $profileModel->getData('specified_warehouse_id')
            ];

            $message = 'profile : '.$profileModel->getProfileId().' : '.\Zend_Json_Encoder::encode($aInfoProfile);
            return $this->addRecord(static::INFO, $message, $context);
        }
    }

    /**
     * @param \Riki\Subscription\Model\Profile\Profile $profileModel
     * @param array $context
     * @return bool
     * @throws \Exception
     */
    public function infoHolidayProfile(\Riki\Subscription\Model\Profile\Profile $profileModel, array $context = []){

        if (!$this->isActive()) {
            return false;
        }

        $logger = $this->traceLogger
            ->setName('SubscriptionHolidayProfile')
            ->setFileName('ned307.log')
            ->pushHandlerByAlias(\Riki\Framework\Helper\Logger\LoggerBuilder::ALIAS_DATE_HANDLER)
            ->create();

        $logger->critical(
            new LocalizedException(__(
                'Profile ID #%1, NEW-DeliveryDate #%2, NEW-NextOrderDate #%3, OLD-DeliveryDate #%4, OLD-NextOrderDate #%5',
                $profileModel->getProfileId(),
                $profileModel->getNextDeliveryDate(),
                $profileModel->getNextOrderDate(),
                $profileModel->getOrigData('next_delivery_date'),
                $profileModel->getOrigData('next_order_date')
            ))
        );
    }

    /**
     * Check if the next_order_date of profile is either Sunday or holiday
     * @param $nextOrderDate
     * @return bool
     */
    public function isHoliday($nextOrderDate)
    {
        if (!strtotime($nextOrderDate)) {
            return false;
        }
        // sunday
        if (date('l', strtotime($nextOrderDate)) == 'Sunday') {
            return true;
        }
        //in list holidays
        if (in_array(date('Y-m-d', strtotime($nextOrderDate)), $this->holidays)) {
            return true;
        }
        return false;
    }

    /**
     * @param \Riki\Subscription\Model\Version\Version $profileModelVersion
     * @param array $context
     * @return bool
     */
    public function infoProfileVersion(
        \Riki\Subscription\Model\Version\Version $profileModelVersion,
        array $context = []
    ) {
        if (!$this->isActive()) {
            return false;
        }

        if ($profileModelVersion) {
            $aInfoProfileVersion =  [
                'rollback_id' => $profileModelVersion->getRollbackId(),
                'moved_to' => $profileModelVersion->getMovedTo(),
                'status' => $profileModelVersion->getStatus()
            ];
            $message = 'profile_version : '.\Zend_Json_Encoder::encode($aInfoProfileVersion);
            return $this->addRecord(static::INFO, $message, $context);
        }
    }

    /**
     * @param \Riki\Subscription\Model\Profile\ProfileLink $profileModelTemp
     * @param array $context
     * @return bool
     */
    public function infoProfileTemp(\Riki\Subscription\Model\Profile\ProfileLink $profileModelTemp, array $context = [])
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($profileModelTemp) {
            $aInfoProfileTemp=  [
                'profile_id' => $profileModelTemp->getProfileId(),
                'temp_profile_id' => $profileModelTemp->getLinkedProfileId(),
                'type' => $profileModelTemp->getChangeType()
            ];

            $message = 'profile_tmp : '.\Zend_Json_Encoder::encode($aInfoProfileTemp);
            return $this->addRecord(static::INFO, $message, $context);
        }
    }

    /**
     * @param \Riki\Subscription\Model\Profile\ProfileLink $profileModelTemp
     * @param array $context
     * @return bool
     */
    public function infoProfileTempDeleted(
        \Riki\Subscription\Model\Profile\ProfileLink $profileModelTemp,
        array $context = []
    ) {
        if (!$this->isActive()) {
            return false;
        }

        if ($profileModelTemp) {
            $aInfoProfileTemp=  [
                'profile_id' => $profileModelTemp->getProfileId(),
                'temp_profile_id' => $profileModelTemp->getLinkedProfileId(),
                'type' => $profileModelTemp->getChangeType()
            ];

            $message = 'profile_tmp_deleted : '.\Zend_Json_Encoder::encode($aInfoProfileTemp);
            return $this->addRecord(static::INFO, $message, $context);
        }
    }

    /**
     * @param \Riki\Subscription\Model\ProductCart\ProductCart $profileCart
     * @param array $context
     * @return bool
     */
    public function infoProfileCart(\Riki\Subscription\Model\ProductCart\ProductCart $profileCart, array $context = [])
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($profileCart) {
            $profileId = $profileCart->getProfileId();
            $aInfoProfileCart =  [
                'product_id' => $profileCart->getProductId(),
                'billing_address_id' => $profileCart->getBillingAddressId(),
                'shipping_address_id' => $profileCart->getShippingAddressId(),
                'qty' => $profileCart->getQty(),
                'is_spot' => $profileCart->getIsSpot(),
                'delivery_date' => $profileCart->getNextDeliveryDate()
            ];

            $message = 'profile_cart : '.$profileId.' : '.\Zend_Json_Encoder::encode($aInfoProfileCart);
            return $this->addRecord(static::INFO, $message, $context);
        }
    }

    /**
     * @param \Riki\Subscription\Model\ProductCart\ProductCart $profileCart
     * @param array $context
     * @return bool
     */
    public function infoProfileCartDeleted(
        \Riki\Subscription\Model\ProductCart\ProductCart $profileCart,
        array $context = []
    ) {
        if (!$this->isActive()) {
            return false;
        }

        if ($profileCart) {
            $profileId = $profileCart->getProfileId();
            $aInfoProfileCart =  [
                'product_id' => $profileCart->getProductId(),
                'billing_address_id' => $profileCart->getBillingAddressId(),
                'shipping_address_id' => $profileCart->getShippingAddressId(),
                'qty' => $profileCart->getQty(),
                'is_spot' => $profileCart->getIsSpot(),
                'delivery_date' => $profileCart->getNextDeliveryDate()
            ];

            $message = 'profile_cart_deleted : '.$profileId.' : '.\Zend_Json_Encoder::encode($aInfoProfileCart);
            return $this->addRecord(static::INFO, $message, $context);
        }
    }
}
