<?php
namespace Riki\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomProcessCustomerEventObserver implements ObserverInterface
{
    const ENABLE_UPDATE_SEGMENT_BY_QUEUE_CONFIG = 'sso_login_setting/update_segment_queue_customer/use_queue_to_update_segment';
    const UNNECESSARY_EVENT = 'customer_address_save_commit_after';
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $publisher;
    /**
     * @var \Magento\CustomerSegment\Model\Customer
     */
    protected $customer;
    /**
     * @var \Riki\Customer\Model\Queue\SaveAfterCustomerQueueSchemaInterfaceFactory
     */
    protected $customerEventInterfaceFactory;

    /**
     * CustomProcessCustomerEventObserver constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     * @param \Magento\CustomerSegment\Model\Customer $customer
     * @param \Riki\Customer\Api\CustomerSegment\CustomerEventInterfaceFactory $customerEventInterfaceFactory
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Magento\CustomerSegment\Model\Customer $customer,
        \Riki\Customer\Model\Queue\SaveAfterCustomerQueueSchemaInterfaceFactory $customerEventInterfaceFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->publisher = $publisher;
        $this->customer = $customer;
        $this->customerEventInterfaceFactory = $customerEventInterfaceFactory;
    }

    /**
     * Process customer related data changing. Method can process just events with customer object
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $dataObject = $observer->getEvent()->getDataObject();

        $customerId = false;
        if ($customer) {
            $customerId = $customer->getId();
        }
        if (!$customerId && $dataObject) {
            $customerId = $dataObject->getCustomerId();
        }
        if ($customerId) {

            $eventName = $observer->getEvent()->getName();

            if ($eventName != self::UNNECESSARY_EVENT) {
                $this->updateCustomerSegment($eventName, $customerId);
            }
        }
    }

    /**
     * Update customer segment flow
     *      by queue or by default magento flow
     *
     * @param $eventName
     * @param $customerId
     */
    public function updateCustomerSegment($eventName, $customerId)
    {
        if ($this->enableUpdateSegmentByQueue()) {
            $this->publishMessageToQueue($eventName, $customerId);
        } else {
            $this->customer->processCustomerEvent($eventName, $customerId);
        }
    }

    /**
     * Publish message to queue
     *
     * @param $eventName
     * @param $customerId
     */
    public function publishMessageToQueue($eventName, $customerId)
    {
        /** @var \Riki\Customer\Model\Queue\SaveAfterCustomerQueueSchemaInterface $customerEvent */
        $customerEvent = $this->customerEventInterfaceFactory->create();
        $customerEvent->setEventName($eventName);
        $customerEvent->setCustomerId($customerId);
        $this->publisher->publish('customer.update.segment',$customerEvent);
    }


    /**
     * Update segment by queue is enable or not
     *
     * @return mixed
     */
    public function enableUpdateSegmentByQueue()
    {
        return $this->scopeConfig->getValue(self::ENABLE_UPDATE_SEGMENT_BY_QUEUE_CONFIG);
    }
}
