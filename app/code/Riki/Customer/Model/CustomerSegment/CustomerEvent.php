<?php

namespace Riki\Customer\Model\CustomerSegment;

class CustomerEvent implements \Riki\Customer\Api\CustomerSegment\CustomerEventInterface
{
    /**
     * @var int
     */
    private $customerId;

    /**
     * @var varchar
     */
    private $eventName;

    /**
     * {@inheritdoc}
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @param string $eventName
     * @return $this
     */
    public function setEventName($eventName)
    {
        $this->eventName = $eventName;
        return $this;
    }

    /**
     * @return string $eventName
     */
    public function getEventName()
    {
        return $this->eventName;
    }
}
