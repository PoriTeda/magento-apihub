<?php

namespace Riki\SubscriptionMachine\Api\Data;

interface MonthlyFeeProfileCreationInterface
{
    const CONSUMERDB_CUSTOMER_ID = 'consumerdb_customer_id';
    const REFERENCE_PROFILE_ID = 'reference_profile_id';
    const PRODUCTS = 'products';
    const VARIABLE_FEE = 'variable_fee';
    const NEXT_ORDER_DATE = 'next_order_date';
    const NEXT_DELIVERY_DATE = 'next_delivery_date';
    const SUBSCRIPTION_COURSE_CODE = 'subscription_course_code';
    const FREQUENCY_INTERVAL = 'frequency_interval';
    const FREQUENCY_UNIT = 'frequency_unit';
    const MONTHLY_FEE_LABEL = 'monthly_fee_label';

    /**
     * @param string $consumerdbCustomerId
     * @return $this
     */
    public function setConsumerdbCustomerId($consumerdbCustomerId);

    /**
     * @return string
     */
    public function getConsumerdbCustomerId();

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
     * @param float $variableFee
     * @return $this
     */
    public function setVariableFee($variableFee);

    /**
     * @return float|null
     */
    public function getVariableFee();

    /**
     * @param string $nextOrderDate
     * @return $this
     */
    public function setNextOrderDate($nextOrderDate);

    /**
     * @return string|null
     */
    public function getNextOrderDate();

    /**
     * @param string $nextDeliveryDate
     * @return $this
     */
    public function setNextDeliveryDate($nextDeliveryDate);

    /**
     * @return string
     */
    public function getNextDeliveryDate();

    /**
     * @param string $subscriptionCourseCode
     * @return $this
     */
    public function setSubscriptionCourseCode($subscriptionCourseCode);

    /**
     * @return string
     */
    public function getSubscriptionCourseCode();

    /**
     * @param int $frequencyInterval
     * @return $this
     */
    public function setFrequencyInterval($frequencyInterval);

    /**
     * @return int
     */
    public function getFrequencyInterval();

    /**
     * @param string $frequencyUnit
     * @return $this
     */
    public function setFrequencyUnit($frequencyUnit);

    /**
     * @return string
     */
    public function getFrequencyUnit();

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
     * @param \Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileProductInterface[] $products
     * @return $this
     */
    public function setProducts($products);

    /**
     * @return \Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileProductInterface[]
     */
    public function getProducts();
}
