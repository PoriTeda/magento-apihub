<?php

namespace Riki\Customer\Model\Queue;

class UpdateSegment
{

    /**
     * @var \Magento\CustomerSegment\Model\Customer
     */
    protected $_customer;

    /**
     * @param \Magento\CustomerSegment\Model\Customer $customer
     */
    public function __construct(
        \Magento\CustomerSegment\Model\Customer $customer
    ) {
        $this->_customer = $customer;
    }

    /**
     * Process customer related data changing. Method can process just events with customer object
     *
     * @param \Riki\Customer\Api\CustomerSegment\CustomerEventListInterface $message
     * @return void
     */
    public function execute(\Riki\Customer\Api\CustomerSegment\CustomerEventListInterface $message)
    {
        foreach ($message as $customerEvent) {
            $customerId = $customerEvent->getCustomerId();
            $eventName = $customerEvent->getEventName();
            if ($customerId) {
                $this->_customer->processCustomerEvent($eventName, $customerId);
            }
        }
    }

}