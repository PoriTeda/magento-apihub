<?php

namespace Riki\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riki\Customer\Api\GridIndexer\ItemInterfaceFactory;

class CustomerRepositorySaveAfter implements ObserverInterface
{
    /**
     * @var ItemInterfaceFactory
     */
    protected $itemFactory;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $publisher;

    /**
     * @var \Riki\Customer\Cron\ReindexCustomer
     */
    protected $reindexCustomer;

    /**
     * CustomerRepositorySaveAfter constructor.
     * @param ItemInterfaceFactory $itemFactory
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     * @param \Riki\Customer\Cron\ReindexCustomer $reindexCustomer
     */
    public function __construct(
        ItemInterfaceFactory $itemFactory,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Riki\Customer\Cron\ReindexCustomer $reindexCustomer
    )
    {
        $this->itemFactory = $itemFactory;
        $this->publisher = $publisher;
        $this->reindexCustomer = $reindexCustomer;
    }

    /**
     * Push reindex job to queue
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->reindexCustomer->isQueueEnabled()) {
            $dataCustomer = $observer->getData('customer_data_object');
            $customerId = $dataCustomer->getId();
            if ($customerId) {
                $item = $this->itemFactory->create();
                $item->setCustomerId($customerId);
                $this->publisher->publish('customer.reindex.grid', $item);
            }
        }
    }
}
