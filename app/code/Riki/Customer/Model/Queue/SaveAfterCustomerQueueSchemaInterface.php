<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Model\Queue;

/**
 * Interface SaveAfterCustomerQueueSchemaInterface
 * @package Riki\Customer\Model\Queue
 */
interface SaveAfterCustomerQueueSchemaInterface
{
    /**
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * @return int $customerId
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
