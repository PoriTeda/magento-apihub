<?php

namespace Riki\Customer\Api\CustomerSegment;

interface CustomerEventInterface
{
    /**
     * @param int $customerId
     *
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * @return int
     */
    public function getCustomerId();
    /**
     * @param string $eventName
     * @return $this
     */
    public function setEventName($eventName);

    /**
     * @return string $eventName
     */
    public function getEventName();
}
