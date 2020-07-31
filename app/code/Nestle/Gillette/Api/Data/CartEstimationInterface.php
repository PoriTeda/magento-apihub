<?php

namespace Nestle\Gillette\Api\Data;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use phpDocumentor\Reflection\Types\This;

interface CartEstimationInterface
{
    const CONSUMER_DB_ID = 'consumer_db_id';
    const PRODUCTS = 'products';
    const DELIVERY_DATE = 'delivery_date';
    const COURSE_CODE = 'course_code';
    const FREQUENCY_ID = 'frequency_id';
    const PAYMENT_METHOD = 'payment_method';
    const SHIPPING_ADDRESS_ID = 'shipping_address_id';
    const BILLING_ADDRESS_ID = 'billing_address_id';
    const REWARD_POINT = 'reward_point';
    const ADDRESS = 'address';
    const DELIVERY_TIME = 'delivery_time';
    const PAYMENT_METHOD_AVAILABLE = 'payment_method_available';
    const DATE_RANGE = 'date_range';
    const COUPON_CODE = 'coupon_code';
    /**
     * @param $consumerDbCustomerId
     * @return $this
     */
    public function setConsumerDbId($consumerDbCustomerId);

    /**
     * @return string
     */
    public function getConsumerDbId();

    /**
     * @param $courseCode
     * @return $this
     */
    public function setCourseCode($courseCode);

    /**
     * @return string
     */
    public function getCourseCode();

    /**
     * @param int $frequencyId
     * @return $this
     */
    public function setFrequencyId($frequencyId);

    /**
     * @return int|null
     */
    public function getFrequencyId();

    /**
     * @param string $nextDeliveryDate
     * @return $this
     */
    public function setDeliveryDate($nextDeliveryDate);

    /**
     * @return string|null
     */
    public function getDeliveryDate();

    /**
     * @param string $deliveryTime
     * @return $this
     */
    public function setDeliveryTime($deliveryTime);

    /**
     * @return mixed
     */
    public function getDeliveryTime();

    /**
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @return $this
     */
    public function setPaymentMethod($paymentMethod);

    /**
     * @return \Magento\Quote\Api\Data\PaymentInterface
     */
    public function getPaymentMethod();

    /**
     * @param \Nestle\Gillette\Api\Data\ProductInfoInterface[] $products
     * @return $this
     */
    public function setProducts($products);

    /**
     * @return \Nestle\Gillette\Api\Data\ProductInfoInterface[]
     */
    public function getProducts();

    /**
     * @param int $shippingAddressId
     * @return $this
     */
    public function setShippingAddressId($shippingAddressId);

    /**
     * @return mixed
     */
    public function getShippingAddressId();

    /**
     * @param int $billingAddressId
     * @return $this
     */
    public function setBillingAddressId($billingAddressId);

    /**
     * @return mixed
     */
    public function getBillingAddressId();

    /**
     * @param ShippingInformationInterface|null $address
     * @return $this
     */
    public function setAddress($addressInformation);

    /**
     * @return mixed
     */
    public function getAddress();

    /**
     * @param $paymentMethodAvailable
     * @return $this
     */
    public function setPaymentMethodAvailable($paymentMethodAvailable);

    /**
     * @return mixed
     */
    public function getPaymentMethodAvailable();

    /**
     * @param $dateRange
     * @return $this
     */
    public function setDateRange($dateRange);

    /**
     * @return mixed
     */
    public function getDateRange();

    /**
     * @param $rewardPoint
     * @return $this
     */
    public function setRewardPoint($rewardPoint);

    /**
     * @return mixed
     */
    public function getRewardPoint();

    /**
     * @param string $couponCode
     * @return $this
     */
    public function setCouponCode($couponCode);

    /**
     * @return string
     */
    public function getCouponCode();

}
