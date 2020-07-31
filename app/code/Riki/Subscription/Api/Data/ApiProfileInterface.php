<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface ApiProfileInterface extends ExtensibleDataInterface
{
    /**#@+
     * Constants defined for keys of the data array. Identical to the name of the getter in snake case
     */

    const ID = 'profile_id';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const COURSE_ID = 'course_id';
    const COURSE_NAME = 'course_name';
    const CUSTOMER_ID = 'customer_id';
    const STORE_ID = 'store_id';
    const FREQUENCY_UNIT = 'frequency_unit';
    const FREQUENCY_INTERVAL = 'frequency_interval';
    const PAYMENT_METHOD = 'payment_method';
    const SHIPPING_FEE = 'shipping_fee';
    const SHIPPING_CONDITION = 'shipping_condition';
    const SKIP_NEXT_DELIVERY = 'skip_next_delviery';
    const PENALTY_AMOUNT = 'penalty_amount';
    const NEXT_DELIVERY_DATE = 'next_delivery_date';
    const NEXT_ORDER_DATE = 'next_order_date';
    const STATUS = 'status';
    const ORDER_TIMES = 'order_times';
    const ORDER_CHANNEL = 'order_channel';
    const HANPUKAI_QTY = 'hanpukai_qty';
    const SALES_COUNT = 'sales_count';
    const SALES_VALUE_COUNT = 'sales_value_count';
    const COUPON_CODE = 'coupon_code';
    const POINT_ALLOW_USED = 'point_allow_used';
    const TRADING_ID = 'trading_id';
    const CREATE_ORDER_FLAG = 'create_order_flag';
    const REINDEX_FLAG = 'reindex_flag';
    const TYPE = 'type';
    const EARN_POINT_ON_ORDER = 'earn_point_on_order';
    const DISENGAGEMENT_DATE = 'disengagement_date';
    const DISENGAGEMENT_REASON = 'disengagement_reason';
    const DISENGAGEMENT_USER = 'disengagement_user';
    const STOCK_POINT_PROFILE_BUCKET_ID = 'stock_point_profile_bucket_id';
    const STOCK_POINT_DELIVERY_TYPE = 'stock_point_delivery_type';
    const STOCK_POINT_DELIVERY_INFORMATION = 'stock_point_delivery_information';
    const DAY_OF_WEEK = 'day_of_week';
    const NTH_WEEKDAY_OF_MONTH = 'nth_weekday_of_month';
    const AUTO_STOCK_POINT_ASSIGN_STATUS = 'auto_stock_point_assign_status';
    const VARIABLE_FEE = 'variable_fee';
    const REFERENCE_PROFILE_ID = 'reference_profile_id';
    const IS_MONTHLY_FEE_CONFIRMED = 'is_monthly_fee_confirmed';
    const DATA_GENERATE_DELIVERY_DATE = 'data_generate_delivery_date';
    const MONTHLY_FEE_LABEL = 'monthly_fee_label';
    const AUTHORIZATION_FAILED_TIME = 'authorization_failed_time';

    /**
     * @return int
     */
    public function getProfileId();

    /**
     * @param $profileId
     * @return $this
     */
    public function setProfileId($profileId);

    /**
     * Get course id
     *
     * @return mixed|null
     */
    public function getCourseId();

    /**
     * Set course Id
     *
     * @param $courseId
     * @return $this
     */
    public function setCourseId($courseId);

    /**
     * Get course name
     *
     * @return mixed|null
     */
    public function getCourseName();

    /**
     * Set course name
     *
     * @param $courseName
     * @return $this
     */
    public function setCourseName($courseName);

    /**
     * get customer id
     *
     * @return mixed|null
     */
    public function getCustomerId();

    /**
     * Set customer id
     *
     * @param $customerId
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * get Store Id
     *
     * @return mixed|null
     */
    public function getStoreId();

    /**
     * set Store id
     *
     * @param $storeId
     * @return $this
     */
    public function setStoreId($storeId);

    /**
     * get frequency unit
     *
     * @return mixed|null
     */
    public function getFrequencyUnit();

    /**
     * Set frequency unit
     *
     * @param $frequencyUnit
     * @return $this
     */
    public function setFrequencyUnit($frequencyUnit);

    /**
     * get frequency interval
     *
     * @return mixed|null
     */
    public function getFrequencyInterval();

    /**
     * set frequency interval
     *
     * @param $frequencyInterval
     * @return $this
     */
    public function setFrequencyInterval($frequencyInterval);

    /**
     * Get payment method
     *
     * @return mixed|null
     */
    public function getPaymentMethod();

    /**
     * Set payment method
     *
     * @param $paymentMethod
     * @return $this
     */
    public function setPaymentMethod($paymentMethod);

    /**
     * Get shipping fee
     *
     * @return mixed|null
     */
    public function getShippingFee();

    /**
     * set shipping fee
     *
     * @param $shippingFee
     * @return $this
     */
    public function setShippingFee($shippingFee);

    /**
     * get shipping condition
     *
     * @return mixed|null
     */
    public function getShippingCondition();

    /**
     * Set shipping condition
     *
     * @param $shippingCondition
     * @return $this
     */
    public function setShippingCondition($shippingCondition);

    /**
     * get skip next delivery
     *
     * @return mixed|null
     */
    public function getSkipNextDelivery();

    /**
     * set skip next delivery
     *
     * @param $skipNextDelivery
     * @return $this
     */
    public function setSkipNextDelivery($skipNextDelivery);

    /**
     * get Penalty Amount
     *
     * @return mixed|null
     */
    public function getPenaltyAmount();

    /**
     * set Penalty Amount
     *
     * @param $penaltyAmount
     * @return $this
     */
    public function setPenaltyAmount($penaltyAmount);

    /**
     * get Next delivery date
     *
     * @return mixed|null
     */
    public function getNextDeliveryDate();

    /**
     * set Next delivery date
     *
     * @param $nextDeliveryDate
     * @return $this
     */
    public function setNextDeliveryDate($nextDeliveryDate);

    /**
     * Get next order date
     *
     * @return mixed|null
     */
    public function getNextOrderDate();

    /**
     * Set next order date
     *
     * @param $nextOrderDate
     * @return $this
     */
    public function setNextOrderDate($nextOrderDate);

    /**
     * Get status
     *
     * @return mixed|null
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Get order times
     *
     * @return mixed|null
     */
    public function getOrderTimes();

    /**
     * Set order times
     *
     * @param $orderTimes
     * @return $this
     */
    public function setOrderTimes($orderTimes);

    /**
     * @return mixed
     */
    public function getOrderChannel();

    /**
     * @param $orderChannel
     * @return mixed
     */
    public function setOrderChannel($orderChannel);

    /**
     * @return mixed
     */
    public function getHanpukaiQty();

    /**
     * @param $hanpukaiQty
     * @return mixed
     */
    public function setHanpukaiQty($hanpukaiQty);

    /**
     * get sales count
     *
     * @return mixed|null
     */
    public function getSalesCount();

    /**
     * Set sales count
     *
     * @param $salesCount
     * @return $this
     */
    public function setSalesCount($salesCount);

    /**
     * Get coupon code
     *
     * @return string
     */
    public function getCouponCode();

    /**
     * set Coupon code
     *
     * @param $couponCode
     * @return $this
     */
    public function setCouponCode($couponCode);

    /**
     * Get point allow used
     *
     * @return mixed|null
     */
    public function getPointAllowUsed();

    /**
     * set point allow used
     *
     * @param $pointAllowUsed
     * @return $this
     */
    public function setPointAllowUsed($pointAllowUsed);

    /**
     * Get trading id
     *
     * @return mixed|null
     */
    public function getTradingId();

    /**
     * set trading id
     *
     * @param $tradingId
     * @return $this
     */
    public function setTradingId($tradingId);

    /**
     * get create order flag
     *
     * @return mixed|null
     */
    public function getCreateOrderFlag();

    /**
     * set create order flag
     *
     * @param $createOrderFlag
     * @return $this
     */
    public function setCreateOrderFlag($createOrderFlag);

    /**
     * get Type
     *
     * @return string
     */
    public function getType();

    /**
     * set type
     *
     * @param $type
     * @return $this
     */
    public function setType($type);

    /**
     * get earn point on order
     *
     * @return mixed|null
     */
    public function getEarnPointOnOrder();

    /**
     * set earn point on order
     *
     * @param $earnPointOnOrder
     * @return $this
     */
    public function setEarnPointOnOrder($earnPointOnOrder);

    /**
     * get sales value count - sum of grand total of orders
     *
     * @return mixed|null
     */
    public function getSalesValueCount();

    /**
     * set sales value count - sum of grand total of orders
     *
     * @param $salesValueCount
     * @return $this
     */
    public function setSalesValueCount($salesValueCount);

    /**
     * get Created date
     *
     * @return mixed|null
     */
    public function getCreatedDate();

    /**
     * set create date
     *
     * @param $createdDate
     * @return $this
     */
    public function setCreatedDate($createdDate);

    /**
     * get update date
     *
     * @return string
     */
    public function getUpdatedDate();

    /**
     * set update date
     *
     * @param $updatedDate
     * @return $this
     */
    public function setUpdatedDate($updatedDate);

    /**
     * @param $date
     * @return mixed
     */
    public function setDisengagementDate($date);

    /**
     * @return mixed
     */
    public function getDisengagementDate();

    /**
     * @param $reasonId
     * @return mixed
     */
    public function setDisengagementReason($reasonId);

    /**
     * @return mixed
     */
    public function getDisengagementReason();

    /**
     * @param $username
     * @return mixed
     */
    public function setDisengagementUser($username);

    /**
     * @return mixed
     */
    public function getDisengagementUser();

    /**
     * @return mixed
     */
    public function getStockPointProfileBucketId();

    /**
     * @param $bucketId
     * @return mixed
     */
    public function setStockPointProfileBucketId($bucketId);

    /**
     * @param $deliveryType
     * @return mixed
     */
    public function setStockPointDeliveryType($deliveryType);

    /**
     * @return mixed
     */
    public function getStockPointDeliveryType();

    /**
     * @param $deliveryIntormation
     * @return mixed
     */
    public function setStockPointDeliveryInformation($deliveryIntormation);

    /**
     * @return mixed
     */
    public function getStockPointDeliveryInformation();

    /**
     * @param $dayOfWeek
     * @return mixed
     */
    public function setDayOfWeek($dayOfWeek);

    /**
     * @return mixed
     */
    public function getDayOfWeek();

    /**
     * @param $nthWeekDayOfMonth
     * @return mixed
     */
    public function setNthWeekdayOfMonth($nthWeekDayOfMonth);

    /**
     * @return mixed
     */
    public function getNthWeekdayOfMonth();

    /**
     * @param $autoAssignStatus
     * @return mixed
     */
    public function setAutoStockPointAssignStatus($autoAssignStatus);

    /**
     * @return mixed
     */
    public function getAutoStockPointAssignStatus();

    /**
     * @param float $variableFee
     * @return $this
     */
    public function setVariableFee($variableFee);

    /**
     * @return float|null
     */
    public function getVariableFee();

    /**
     * @param int $referenceProfileId
     * @return $this
     */
    public function setReferenceProfileId($referenceProfileId);

    /**
     * @return int|null
     */
    public function getReferenceProfileId();

    /**
     * @param boolean $isMonthlyFeeConfirmed
     * @return $this
     */
    public function setIsMonthlyFeeConfirmed($isMonthlyFeeConfirmed);

    /**
     * @return boolean|null
     */
    public function getIsMonthlyFeeConfirmed();

    /**
     * @param string $dataGenerateDeliveryDate
     * @return $this
     */
    public function setDataGenerateDeliveryDate($dataGenerateDeliveryDate);

    /**
     * @return string|null
     */
    public function getDataGenerateDeliveryDate();

    /**
     * @return string|null
     */
    public function getMonthlyFeeLabel();

    /**
     * @param string $monthlyFeeLabel
     * @return $this
     */
    public function setMonthlyFeeLabel($monthlyFeeLabel);

    /**
     * @param int $authorizationFailedTime
     * @return $this
     */
    public function setAuthorizationFailedTime($authorizationFailedTime);

    /**
     * @return int|null
     */
    public function getAuthorizationFailedTime();
}
