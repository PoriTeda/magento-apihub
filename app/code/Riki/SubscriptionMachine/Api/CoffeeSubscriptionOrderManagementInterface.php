<?php

namespace Riki\SubscriptionMachine\Api;

/**
 * Interface CoffeeSubscriptionOrderManagementInterface
 * @package Riki\SubscriptionMachine\Api
 */
interface CoffeeSubscriptionOrderManagementInterface
{
    /**
     * Approve orders by updating status from pending_for_machine to not_shipped
     * @param string $consumerDbId
     * @return bool
     * @throws \Riki\SubscriptionMachine\Exception\InputException
     * @throws \Zend_Validate_Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function approve($consumerDbId);
}
