<?php

namespace Riki\Subscription\Api\Simulator;


interface CalendarInterface
{

    /**
     * @param  $profileId
     * @param  $addressId
     * @return mixed
     */
    public function getRestrictCalendar($profileId,$addressId);

    /**
     * @param $storeId
     * @return mixed
     */
    public function setStoreId($storeId);

    /**
     * @return mixed
     */
    public function getStoreId();

    /**
     * @param $deliveryType
     * @return mixed
     */
    public function setDeliveryType($deliveryType);

    /**
     * @return mixed
     */
    public function getDeliveryType();

    /**
     * @param $bufferDay
     * @return mixed
     */
    public function setBufferDay($bufferDay);

    /**
     * @return mixed
     */
    public function getBufferDay();

    /**
     * @param $profileId
     * @return mixed
     */
    public function checkProfileExistOnCustomer($profileId);


}