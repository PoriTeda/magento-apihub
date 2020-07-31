<?php

namespace Nestle\Gillette\Model\Data;

use Magento\Framework\DataObject;
use Nestle\Gillette\Api\Data\CartEstimationInterface;

Class CartEstimation extends DataObject  implements CartEstimationInterface {

    /* @var \Riki\TimeSlots\Model\TimeSlots */
    protected $timeSlotModel;

    public function __construct(
        \Riki\TimeSlots\Model\TimeSlots $timeSlotModel,
        array $data = []
    )
    {
        $this->timeSlotModel = $timeSlotModel;
        parent::__construct($data);
    }

    /**
     * {@inheritdoc}
     */
    public function setConsumerDbId($consumerDbCustomerId) {
        return $this->setData(self::CONSUMER_DB_ID,$consumerDbCustomerId);
    }

    /**
     * {@inheritdoc}
     */
    public function getConsumerDbId() {
        return $this->getData(self::CONSUMER_DB_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setCourseCode($courseCode) {
        return $this->setData(self::COURSE_CODE, $courseCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getCourseCode() {
        return $this->getData(self::COURSE_CODE);
    }

    /**
     * {@inheritdoc}
     */
    public function setFrequencyId($frequencyId) {
        return $this->setData(self::FREQUENCY_ID, $frequencyId);
    }

    /**
     * {@inheritdoc}
     */
    public function getFrequencyId() {
        return $this->getData(self::FREQUENCY_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setDeliveryDate($deliveryDate) {
        return $this->setData(self::DELIVERY_DATE, $deliveryDate);
    }

    /**
     * {@inheritDoc}
     */
    public function setDeliveryTime($deliveryTime) {
        return $this->setData(self::DELIVERY_TIME,$deliveryTime);
    }

    /**
     * {@inheritDoc}
     */
    public function getDeliveryTime() {
        $timeSlotId = $this->getData(self::DELIVERY_TIME);
        $timeSlotModel = $this->timeSlotModel->load($timeSlotId);
        if ($timeSlotModel->getId()) {
             return $timeSlotModel;
        }
        return null;
    }
    /**
     * {@inheritdoc}
     */
    public function getDeliveryDate() {
        return $this->getData(self::DELIVERY_DATE);
    }

    /**
     * {@inheritdoc}
     */
    public function setPaymentMethod($paymentMethod) {
        return $this->setData(self::PAYMENT_METHOD,$paymentMethod);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentMethod() {
        return $this->getData(self::PAYMENT_METHOD);
    }

    /**
     * {@inheritdoc}
     */
    public function setProducts($products) {
        return $this->setData(self::PRODUCTS, $products);
    }

    /**
     * {@inheritdoc}
     */
    public function getProducts() {
        return $this->getData(self::PRODUCTS);
    }

    /**
     * {@inheritdoc}
     */
    public function setShippingAddressId($shippingAddressId) {
        return $this->setData(self::SHIPPING_ADDRESS_ID, $shippingAddressId);
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingAddressId() {
        return $this->getData(self::SHIPPING_ADDRESS_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setBillingAddressId($billingAddressId) {
        return $this->setData(self::BILLING_ADDRESS_ID, $billingAddressId);
    }

    /**
     * {@inheritdoc}
     */
    public function getBillingAddressId() {
        return $this->getData(self::BILLING_ADDRESS_ID);
    }
    /**
     * {@inheritdoc}
     */
    public function setAddress($address)
    {
        return $this->setData(self::ADDRESS,$address);
    }
    /**
     * {@inheritdoc}
     */
    public function getAddress()
    {
        return $this->getData(self::ADDRESS);
    }

    /**
     * {@inheritDoc}
     */
    public function setPaymentMethodAvailable($paymentMethodAvailable)
    {
        return $this->setData(self::PAYMENT_METHOD_AVAILABLE, $paymentMethodAvailable);
    }
    /**
     * {@inheritDoc}
     */
    public function getPaymentMethodAvailable()
    {
        return $this->getData(self::PAYMENT_METHOD_AVAILABLE);
    }
    /**
     * {@inheritDoc}
     */
    public function setDateRange($dateRange)
    {
        return $this->setData(self::DATE_RANGE, $dateRange);
    }
    /**
     * {@inheritDoc}
     */
    public function getDateRange()
    {
        return $this->getData(self::DATE_RANGE);
    }

    /**
     * {@inheritDoc}
     */
    public function setRewardPoint($rewardPoint)
    {
        return $this->setData(self::REWARD_POINT, $rewardPoint);
    }

    /**
     * {@inheritDoc}
     */
    public function getRewardPoint()
    {
        return $this->getData(self::REWARD_POINT);
    }

    /**
     * {@inheritDoc}
     */
    public function setCouponCode($couponCode)
    {
        return $this->setData(self::COUPON_CODE, $couponCode);
    }

    /**
     * {@inheritDoc}
     */
    public function getCouponCode()
    {
        return $this->getData(self::COUPON_CODE);
    }

}
