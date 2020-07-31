<?php

namespace Riki\SubscriptionCourse\Api\Data;

interface SubscriptionCourseInterface
{
    const COURSE_ID = 'course_id';

    const COURSE_NAME = 'course_name';

    const COURSE_CODE = 'course_code';

    const NTH_DELIVERY_SIMULATION = 'nth_delivery_simulation';

    const SUBSCRIPTION_TYPE = 'subscription_type';

    const HANPUKAI_MAXIMUM_ORDER_TIMES = 'hanpukai_maximum_order_times';

    const ORDER_TOTAL_AMOUNT_OPTION = 'order_total_amount_option';

    const ALLOW_CHANGE_NEXT_DELIVERY_DATE = 'allow_change_next_delivery_date';

    const HANPUKAI_DELIVERY_DATE_ALLOWED = 'hanpukai_delivery_date_allowed';

    const ALLOW_CHOOSE_DELIVERY_DATE = 'allow_choose_delivery_date';

    const TERMS_OF_USE = 'terms_of_use';

    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getCode();

    /**
     * @return string
     */
    public function getSubscriptionType();

    /**
     * @param int $nth
     *
     * @return $this
     */
    public function setNthDeliverySimulation($nth);

    /**
     * @return int
     */
    public function getNthDeliverySimulation();

    /**
     * @return int|null
     */
    public function getHanpukaiMaximumOrderTimes();

    /**
     * @param $orderTotalAmountOption
     * @return $this
     */
    public function setOrderTotalAmountOption($orderTotalAmountOption);

    /**
     * @return int
     */
    public function getOrderTotalAmountOption();

    /**
     * @param int $allowChangeNextDeliveryDate
     * @return mixed
     */
    public function setAllowChangeNextDeliveryDate($allowChangeNextDeliveryDate);

    /**
     * @return mixed
     */
    public function getAllowChangeNextDeliveryDate();

    /**
     * @param int $hanpukaiDeliveryDateAllowed
     * @return mixed
     */
    public function setHanpukaiDeliveryDateAllowed($hanpukaiDeliveryDateAllowed);

    /**
     * @return mixed
     */
    public function getHanpukaiDeliveryDateAllowed();

    /**
     * @param string $termsOfUse
     * @return mixed
     */
    public function setTermsOfUse($termsOfUse);

    /**
     * @return mixed
     */
    public function getTermsOfUse();
}