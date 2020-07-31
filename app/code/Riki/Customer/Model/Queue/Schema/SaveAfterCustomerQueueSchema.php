<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Model\Queue\Schema;

/**
 * @codeCoverageIgnore
 */
class SaveAfterCustomerQueueSchema implements \Riki\Customer\Model\Queue\SaveAfterCustomerQueueSchemaInterface
{
    /**
     * @var int
     */
    private $customerId;

    /**
     * @var string
     */
    private $eventName;


    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;

        return $this;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * {@inheritdoc}
     *  @codeCoverageIgnore
     * @param string $eventName
     */
    public function setEventName($eventName){
        $this->eventName = $eventName;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     * @return string
     */
    public function getEventName(){
        return $this->eventName;
    }
}
