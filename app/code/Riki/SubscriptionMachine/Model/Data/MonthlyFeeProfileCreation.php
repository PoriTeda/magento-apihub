<?php

namespace Riki\SubscriptionMachine\Model\Data;

use \Magento\Framework\DataObject;
use Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileCreationInterface;

class MonthlyFeeProfileCreation extends DataObject implements MonthlyFeeProfileCreationInterface
{
    /**
     * {@inheritdoc}
     */
    public function setConsumerdbCustomerId($consumerdbCustomerId)
    {
        return $this->setData(self::CONSUMERDB_CUSTOMER_ID, $consumerdbCustomerId);
    }

    /**
     * {@inheritdoc}
     */
    public function getConsumerdbCustomerId()
    {
        return $this->getData(self::CONSUMERDB_CUSTOMER_ID);
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
        return $this->getData(self::REFERENCE_PROFILE_ID);
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
        return $this->getData(self::VARIABLE_FEE);
    }

    /**
     * {@inheritdoc}
     */
    public function setNextOrderDate($nextOrderDate)
    {
        return $this->setData(self::NEXT_ORDER_DATE, $nextOrderDate);
    }

    /**
     * {@inheritdoc}
     */
    public function getNextOrderDate()
    {
        return $this->getData(self::NEXT_ORDER_DATE);
    }

    /**
     * {@inheritdoc}
     */
    public function setNextDeliveryDate($nextOrderDate)
    {
        return $this->setData(self::NEXT_DELIVERY_DATE, $nextOrderDate);
    }

    /**
     * {@inheritdoc}
     */
    public function getNextDeliveryDate()
    {
        return $this->getData(self::NEXT_DELIVERY_DATE);
    }

    /**
     * {@inheritdoc}
     */
    public function setSubscriptionCourseCode($subscriptionCourseCode)
    {
        return $this->setData(self::SUBSCRIPTION_COURSE_CODE, $subscriptionCourseCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscriptionCourseCode()
    {
        return $this->getData(self::SUBSCRIPTION_COURSE_CODE);
    }

    /**
     * {@inheritdoc}
     */
    public function setFrequencyInterval($frequencyInterval)
    {
        return $this->setData(self::FREQUENCY_INTERVAL, $frequencyInterval);
    }

    /**
     * {@inheritdoc}
     */
    public function getFrequencyInterval()
    {
        return $this->getData(self::FREQUENCY_INTERVAL);
    }

    /**
     * {@inheritdoc}
     */
    public function setFrequencyUnit($frequencyUnit)
    {
        return $this->setData(self::FREQUENCY_UNIT, $frequencyUnit);
    }

    /**
     * {@inheritdoc}
     */
    public function getFrequencyUnit()
    {
        return $this->getData(self::FREQUENCY_UNIT);
    }

    /**
     * {@inheritdoc}
     */
    public function setProducts($products)
    {
        return $this->setData(self::PRODUCTS, $products);
    }

    /**
     * {@inheritdoc}
     */
    public function getProducts()
    {
        return $this->getData(self::PRODUCTS);
    }

    /**
     * {@inheritdoc}
     */
    public function getMonthlyFeeLabel()
    {
        return $this->getData(self::MONTHLY_FEE_LABEL);
    }

    /**
     * {@inheritdoc}
     */
    public function setMonthlyFeeLabel($monthlyFeeLabel)
    {
        return $this->setData(self::MONTHLY_FEE_LABEL, $monthlyFeeLabel);
    }
}
