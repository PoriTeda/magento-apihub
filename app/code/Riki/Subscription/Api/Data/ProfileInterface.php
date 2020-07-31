<?php
namespace Riki\Subscription\Api\Data;

/**
 * @api
 */
interface ProfileInterface
{
    // internal
    const CUSTOMER_ID = 'customer_id';
    const UPDATED_DATE = 'updated_date';
    const FREQUENCY_UNIT = 'frequency_unit';
    const FREQUENCY_INTERVAL = 'frequency_interval';
    const DURATION_UNIT = 'duration_unit';
    const DURATION_INTERVAL = 'duration_interval';
    const PAYMENT_METHOD = 'payment_method';
    const STATUS = 'status';

    // external
    const COURSE_NAME = 'course_name';
    const COURSE_SETTING = 'course_setting';
    const PROFILE_PRODUCT_CART = 'profile_product_cart';

    /**
     * @return int
     */
    public function getId();

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * @return int
     */
    public function getSubProfileID();
    /**
     * @param int $id
     * @return $this
     */
    public function setSubProfileID($id);

    /**
     * @return int
     */
    public function getCustomerID();

    /**
     * @param int $id
     * @return $this
     */
    public function setCustomerID($id);

    /**
     * @return string
     */
    public function getCourseName();

    /**
     * @param string $string
     * @return $this
     */
    public function setCourseName($string);

    /**
     * @return string
     */
    public function getUpdatedDate();

    /**
     * @param string $string
     * @return $this
     */
    public function setUpdatedDate($string);

    /**
     * @return string
     */
    public function getFrequencyUnit();

    /**
     * @param string $string
     * @return $this
     */
    public function setFrequencyUnit($string);

    /**
     * @return string
     */
    public function getFrequencyInterval();

    /**
     * @param string $string
     * @return $this
     */
    public function setFrequencyInterval($string);

    /**
     * @return string
     */
    public function getDurationUnit();

    /**
     * @param string $string
     * @return $this
     */
    public function setDurationUnit($string);

    /**
     * @return string
     */
    public function getDurationInterval();

    /**
     * @param string $string
     * @return $this
     */
    public function setDurationInterval($string);

    /**
     * @return string
     */
    public function getPaymentMethod();

    /**
     * @param string $string
     * @return $this
     */
    public function setPaymentMethod($string);

    /**
     * @return string[]
     */
    public function getCourseSetting();

    /**
     * @param array $array
     * @return $this
     */
    public function setCourseSetting($array);

    /**
     * @return string
     */
    public function getProfileProductCart();

    /**
     * @param string $string
     * @return $this
     */
    public function setProfileProductCart($string);

    /**
     * @return int
     */
    public function getStatus();

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * @return \Riki\Subscription\Api\WebApi\SubProfileCartOrderInterface[]|null
     */
    public function getSubProfileCartOrder();

    /**
     * @param \Riki\Subscription\Api\WebApi\SubProfileCartOrderInterface[] $subProfileCartOrder
     * @return $this
     */
    public function setSubProfileCartOrder(array $subProfileCartOrder = null);

    /**
     * @return int
     */
    public function getSubProfileFrequencyID();

    /**
     * @param int $id
     * @return $this
     */
    public function setSubProfileFrequencyID($id);

    /**
     * @return int
     */
    public function getChangeType();

    /**
     * @param int $type
     * @return $this
     */
    public function setChangeType($type);

    /**
     * @return string
     */
    public function getLastUpdate();

    /**
     * @param string $date
     * @return $this
     */
    public function setLastUpdate($date);

    /**
     * @param string $id
     * @return $this
     */
    public function setConsumerCustomerID($id);

    /**
     * @return string
     */
    public function getConsumerCustomerID();
}

