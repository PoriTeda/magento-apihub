<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\Subscription\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use \Riki\Subscription\Api\Data\ApiProfileInterface;

/**
 * Class Customer
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ApiProfile extends AbstractExtensibleObject     implements ApiProfileInterface
{
    /**
     * @return int
     */
    public function getProfileId()
    {
        return $this->_get(self::ID);
    }

    /**
     * @param $profileId
     * @return ApiProfileInterface|ApiProfile
     */
    public function setProfileId($profileId)
    {
        return $this->setData(self::ID, $profileId);
    }

    /**
     * Get course id
     *
     * @return mixed|null
     */
    public function getCourseId()
    {
        return $this->_get(self::COURSE_ID);
    }

    /**
     * Set course Id
     *
     * @param $courseId
     * @return $this
     */
    public function setCourseId($courseId)
    {
        return $this->setData(self::COURSE_ID, $courseId);
    }

    /**
     * Get course name
     *
     * @return mixed|null
     */
    public function getCourseName()
    {
        return $this->_get(self::COURSE_NAME);
    }

    /**
     * Set course name
     *
     * @param $courseName
     * @return $this
     */
    public function setCourseName($courseName)
    {
        return $this->setData(self::COURSE_NAME, $courseName);
    }

    /**
     * get customer id
     *
     * @return mixed|null
     */
    public function getCustomerId()
    {
        return $this->_get(self::CUSTOMER_ID);
    }

    /**
     * Set customer id
     *
     * @param $customerId
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * get Store Id
     *
     * @return mixed|null
     */
    public function getStoreId()
    {
        return $this->_get(self::STORE_ID);
    }

    /**
     * set Store id
     *
     * @param $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * get frequency unit
     *
     * @return mixed|null
     */
    public function getFrequencyUnit()
    {
        return $this->_get(self::FREQUENCY_UNIT);
    }

    /**
     * Set frequency unit
     *
     * @param $frequencyUnit
     * @return $this
     */
    public function setFrequencyUnit($frequencyUnit)
    {
        return $this->setData(self::FREQUENCY_UNIT, $frequencyUnit);
    }

    /**
     * get frequency interval
     *
     * @return mixed|null
     */
    public function getFrequencyInterval()
    {
        return $this->_get(self::FREQUENCY_INTERVAL);
    }

    /**
     * set frequency interval
     *
     * @param $frequencyInterval
     * @return $this
     */
    public function setFrequencyInterval($frequencyInterval)
    {
        return $this->setData(self::FREQUENCY_INTERVAL, $frequencyInterval);
    }

    /**
     * Get payment method
     *
     * @return mixed|null
     */
    public function getPaymentMethod()
    {
        return $this->_get(self::PAYMENT_METHOD);
    }

    /**
     * Set payment method
     *
     * @param $paymentMethod
     * @return $this
     */
    public function setPaymentMethod($paymentMethod)
    {
        return $this->setData(self::PAYMENT_METHOD, $paymentMethod);
    }

    /**
     * Get shipping fee
     *
     * @return mixed|null
     */
    public function getShippingFee()
    {
        return $this->_get(self::SHIPPING_FEE);
    }

    /**
     * set shipping fee
     *
     * @param $shippingFee
     * @return $this
     */
    public function setShippingFee($shippingFee)
    {
        return $this->setData(self::SHIPPING_FEE, $shippingFee);
    }

    /**
     * get shipping condition
     *
     * @return mixed|null
     */
    public function getShippingCondition()
    {
        return $this->_get(self::SHIPPING_CONDITION);
    }

    /**
     * Set shipping condition
     *
     * @param $shippingCondition
     * @return $this
     */
    public function setShippingCondition($shippingCondition)
    {
        return $this->setData(self::SHIPPING_CONDITION, $shippingCondition);
    }

    /**
     * get skip next delivery
     *
     * @return mixed|null
     */
    public function getSkipNextDelivery()
    {
        return $this->_get(self::SKIP_NEXT_DELIVERY);
    }

    /**
     * set skip next delivery
     *
     * @param $skipNextDelivery
     * @return $this
     */
    public function setSkipNextDelivery($skipNextDelivery)
    {
        return $this->setData(self::SKIP_NEXT_DELIVERY, $skipNextDelivery);
    }

    /**
     * get Penalty Amount
     *
     * @return mixed|null
     */
    public function getPenaltyAmount()
    {
        return $this->_get(self::PENALTY_AMOUNT);
    }

    /**
     * set Penalty Amount
     *
     * @param $penaltyAmount
     * @return $this
     */
    public function setPenaltyAmount($penaltyAmount)
    {
        return $this->setData(self::PENALTY_AMOUNT, $penaltyAmount);
    }

    /**
     * get Next delivery date
     *
     * @return mixed|null
     */
    public function getNextDeliveryDate()
    {
        return $this->_get(self::NEXT_DELIVERY_DATE);
    }

    /**
     * set Next delivery date
     *
     * @param $nextDeliveryDate
     * @return $this
     */
    public function setNextDeliveryDate($nextDeliveryDate)
    {
        return $this->setData(self::NEXT_DELIVERY_DATE, $nextDeliveryDate);
    }

    /**
     * Get next order date
     *
     * @return mixed|null
     */
    public function getNextOrderDate()
    {
        return $this->_get(self::NEXT_ORDER_DATE);
    }

    /**
     * Set next order date
     *
     * @param $nextOrderDate
     * @return $this
     */
    public function setNextOrderDate($nextOrderDate)
    {
        return $this->setData(self::NEXT_ORDER_DATE, $nextOrderDate);
    }

    /**
     * Get status
     *
     * @return mixed|null
     */
    public function getStatus()
    {
        return $this->_get(self::STATUS);
    }

    /**
     * Set status
     *
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get order times
     *
     * @return mixed|null
     */
    public function getOrderTimes()
    {
        return $this->_get(self::ORDER_TIMES);
    }

    /**
     * Set order times
     *
     * @param $orderTimes
     * @return $this
     */
    public function setOrderTimes($orderTimes)
    {
        return $this->setData(self::ORDER_TIMES, $orderTimes);
    }

    /**
     * Get order times
     *
     * @return mixed|null
     */
    public function getOrderChannel()
    {
        return $this->_get(self::ORDER_CHANNEL);
    }

    /**
     * Set order times
     *
     * @param $orderTimes
     * @return $this
     */
    public function setOrderChannel($orderChannel)
    {
        return $this->setData(self::ORDER_CHANNEL, $orderChannel);
    }

    /**
     * Get order times
     *
     * @return mixed|null
     */
    public function getHanpukaiQty()
    {
        return $this->_get(self::HANPUKAI_QTY);
    }

    /**
     * Get order times
     *
     * @return mixed|null
     */
    public function setHanpukaiQty($hanpukaiQty)
    {
        return $this->setData(self::HANPUKAI_QTY, $hanpukaiQty);
    }

    /**
     * get sales count
     *
     * @return mixed|null
     */
    public function getSalesCount()
    {
        return $this->_get(self::SALES_COUNT);
    }

    /**
     * Set sales count
     *
     * @param $salesCount
     * @return $this
     */
    public function setSalesCount($salesCount)
    {
        return $this->setData(self::SALES_COUNT, $salesCount);
    }

    /**
     * Get coupon code
     *
     * @return string
     */
    public function getCouponCode()
    {
        return $this->_get(self::COUPON_CODE);
    }

    /**
     * set Coupon code
     *
     * @param $couponCode
     * @return $this
     */
    public function setCouponCode($couponCode)
    {
        return $this->setData(self::COUPON_CODE, $couponCode);
    }

    /**
     * Get point allow used
     *
     * @return mixed|null
     */
    public function getPointAllowUsed()
    {
        return $this->_get(self::POINT_ALLOW_USED);
    }

    /**
     * set point allow used
     *
     * @param $pointAllowUsed
     * @return $this
     */
    public function setPointAllowUsed($pointAllowUsed)
    {
        return $this->setData(self::POINT_ALLOW_USED, $pointAllowUsed);
    }

    /**
     * Get trading id
     *
     * @return mixed|null
     */
    public function getTradingId()
    {
        return $this->_get(self::TRADING_ID);
    }

    /**
     * set trading id
     *
     * @param $tradingId
     * @return $this
     */
    public function setTradingId($tradingId)
    {
        return $this->setData(self::TRADING_ID, $tradingId);
    }

    /**
     * get create order flag
     *
     * @return mixed|null
     */
    public function getCreateOrderFlag()
    {
        return $this->_get(self::CREATE_ORDER_FLAG);
    }

    /**
     * set create order flag
     *
     * @param $createOrderFlag
     * @return $this
     */
    public function setCreateOrderFlag($createOrderFlag)
    {
        return $this->setData(self::CREATE_ORDER_FLAG, $createOrderFlag);
    }

    /**
     * get reindex flag
     *
     * @return mixed|null
     */
    public function getReindexFlag()
    {
        return $this->_get(self::REINDEX_FLAG);
    }

    /**
     * set reindex flag
     *
     * @param $reindexFlag
     * @return $this
     */
    public function setReindexFlag($reindexFlag)
    {
        return $this->setData(self::REINDEX_FLAG, $reindexFlag);
    }

    /**
     * get Type
     *
     * @return string
     */
    public function getType()
    {
        return $this->_get(self::TYPE);
    }

    /**
     * set type
     *
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * get earn point on order
     *
     * @return mixed|null
     */
    public function getEarnPointOnOrder()
    {
        return $this->_get(self::EARN_POINT_ON_ORDER);
    }

    /**
     * set earn point on order
     *
     * @param $earnPointOnOrder
     * @return $this
     */
    public function setEarnPointOnOrder($earnPointOnOrder)
    {
        return $this->setData(self::EARN_POINT_ON_ORDER, $earnPointOnOrder);
    }

    /**
     * get sales value count - sum of grand total of orders
     *
     * @return mixed|null
     */
    public function getSalesValueCount()
    {
        return $this->_get(self::SALES_VALUE_COUNT);
    }

    /**
     * set sales value count - sum of grand total of orders
     *
     * @param $salesValueCount
     * @return $this
     */
    public function setSalesValueCount($salesValueCount)
    {
        return $this->setData(self::SALES_VALUE_COUNT, $salesValueCount);
    }

    /**
     * get Created date
     *
     * @return mixed|null
     */
    public function getCreatedDate()
    {
        return $this->_get(self::CREATED_AT);
    }

    /**
     * set create date
     *
     * @param $createdDate
     * @return $this
     */
    public function setCreatedDate($createdDate)
    {
        return $this->setData(self::CREATED_AT, $createdDate);
    }

    /**
     * get update date
     *
     * @return string
     */
    public function getUpdatedDate()
    {
        return $this->_get(self::UPDATED_AT);
    }

    /**
     * set update date
     *
     * @param $updatedDate
     * @return $this
     */
    public function setUpdatedDate($updatedDate)
    {
        return $this->setData(self::UPDATED_AT, $updatedDate);
    }

    /**
     * @param $date
     * @return $this
     */
    public function setDisengagementDate($date)
    {
        return $this->setData(self::DISENGAGEMENT_DATE, $date);
    }

    /**
     * @return mixed|null
     */
    public function getDisengagementDate()
    {
        return $this->_get(self::DISENGAGEMENT_DATE);
    }

    /**
     * @param $reasonId
     * @return $this
     */
    public function setDisengagementReason($reasonId)
    {
        return $this->setData(self::DISENGAGEMENT_REASON, $reasonId);
    }

    /**
     * @return mixed|null
     */
    public function getDisengagementReason()
    {
        return $this->_get(self::DISENGAGEMENT_REASON);
    }

    /**
     * @param $username
     * @return mixed|ApiProfile
     */
    public function setDisengagementUser($username)
    {
        return $this->setData(self::DISENGAGEMENT_USER, $username);
    }

    /**
     * @return mixed|null
     */
    public function getDisengagementUser()
    {
        return $this->_get(self::DISENGAGEMENT_USER);
    }

    /**
     * @param $bucketId
     * @return $this
     */
    public function setStockPointProfileBucketId($bucketId)
    {
        return $this->setData(self::STOCK_POINT_PROFILE_BUCKET_ID, $bucketId);
    }

    /**
     * @return mixed|null
     */
    public function getStockPointProfileBucketId()
    {
        return $this->_get(self::STOCK_POINT_PROFILE_BUCKET_ID);
    }

    /**
     * @param $deliveryIntormation
     * @return $this
     */
    public function setStockPointDeliveryInformation($deliveryIntormation)
    {
        return $this->setData(self::STOCK_POINT_DELIVERY_INFORMATION, $deliveryIntormation);
    }

    /**
     * @return mixed|null
     */
    public function getStockPointDeliveryInformation()
    {
        return $this->_get(self::STOCK_POINT_DELIVERY_INFORMATION);
    }

    /**
     * @return mixed|null
     */
    public function getStockPointDeliveryType()
    {
        return $this->_get(self::STOCK_POINT_DELIVERY_TYPE);
    }

    /**
     * @param $deliveryType
     * @return $this
     */
    public function setStockPointDeliveryType($deliveryType)
    {
        return $this->setData(self::STOCK_POINT_DELIVERY_TYPE, $deliveryType);
    }

    /**
     * @return mixed|null
     */
    public function getAutoStockPointAssignStatus()
    {
        return $this->_get(self::AUTO_STOCK_POINT_ASSIGN_STATUS);
    }

    /**
     * @param $autoAssignStatus
     * @return $this
     */
    public function setAutoStockPointAssignStatus($autoAssignStatus)
    {
        return $this->setData(self::AUTO_STOCK_POINT_ASSIGN_STATUS, $autoAssignStatus);
    }

    /**
     * @return mixed|null
     */
    public function getDayOfWeek()
    {
        return $this->_get(self::DAY_OF_WEEK);
    }

    /**
     * @param $dayOfWeek
     * @return $this
     */
    public function setDayOfWeek($dayOfWeek)
    {
        return $this->setData(self::DAY_OF_WEEK, $dayOfWeek);
    }

    /**
     * @return mixed|null
     */
    public function getNthWeekdayOfMonth()
    {
        return $this->_get(self::NTH_WEEKDAY_OF_MONTH);
    }

    /**
     * @param $nthWeekDayOfMonth
     * @return $this
     */
    public function setNthWeekdayOfMonth($nthWeekDayOfMonth)
    {
        return $this->setData(self::NTH_WEEKDAY_OF_MONTH, $nthWeekDayOfMonth);
    }

    /**
     * {@inheritdoc}
     */
    public function setReferenceProfileId($referenceProfileId)
    {
        return $this->setData(self::REFERENCE_PROFILE_ID, $referenceProfileId);
    }

    /**
     * {@inheritdoc}
     */
    public function getReferenceProfileId()
    {
        return $this->_get(self::REFERENCE_PROFILE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setVariableFee($variableFee)
    {
        return $this->setData(self::VARIABLE_FEE, $variableFee);
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableFee()
    {
        return $this->_get(self::VARIABLE_FEE);
    }

    /**
     * {@inheritdoc}
     */
    public function setIsMonthlyFeeConfirmed($isMonthlyFeeConfirmed)
    {
        return $this->setData(self::IS_MONTHLY_FEE_CONFIRMED, $isMonthlyFeeConfirmed);
    }

    /**
     * {@inheritdoc}
     */
    public function getIsMonthlyFeeConfirmed()
    {
        return $this->_get(self::IS_MONTHLY_FEE_CONFIRMED);
    }

    /**
     * {@inheritdoc}
     */
    public function setDataGenerateDeliveryDate($dataGenerateDeliveryDate)
    {
        return $this->setData(self::DATA_GENERATE_DELIVERY_DATE, $dataGenerateDeliveryDate);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataGenerateDeliveryDate()
    {
        return $this->_get(self::DATA_GENERATE_DELIVERY_DATE);
    }

    /**
     * {@inheritdoc}
     */
    public function getMonthlyFeeLabel()
    {
        return $this->_get(self::MONTHLY_FEE_LABEL);
    }

    /**
     * {@inheritdoc}
     */
    public function setMonthlyFeeLabel($monthlyFeeLabel)
    {
        return $this->setData(self::MONTHLY_FEE_LABEL, $monthlyFeeLabel);
    }

    /**
     * @param int $authorizationFailedTime
     * @return $this
     */
    public function setAuthorizationFailedTime($authorizationFailedTime)
    {
        return $this->setData(self::AUTHORIZATION_FAILED_TIME, $authorizationFailedTime);
    }

    /**
     * @return int|null
     */
    public function getAuthorizationFailedTime()
    {
        return $this->_get(self::AUTHORIZATION_FAILED_TIME);
    }
}
