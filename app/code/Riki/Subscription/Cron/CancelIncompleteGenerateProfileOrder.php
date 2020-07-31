<?php

namespace Riki\Subscription\Cron;

use Riki\Subscription\Helper\Order\Data as SubscriptionOrderHelper;

class CancelIncompleteGenerateProfileOrder
{
    const MAX_PROCESSED_ITEM = 100;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Riki\Subscription\Logger\LoggerCancelIncompleteGenerateProfileOrder
     */
    protected $logger;

    /**
     * CancelIncompleteGenerateProfileOrder constructor.
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Riki\Subscription\Logger\LoggerCancelIncompleteGenerateProfileOrder $logger
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Riki\Subscription\Logger\LoggerCancelIncompleteGenerateProfileOrder $logger
    ) {
    
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->date = $date;
        $this->logger = $logger;
    }

    /**
     *
     */
    public function execute()
    {
        $maxId = 0;
        $continue = true;
        while ($continue) {
            $currentTimestamp = $this->date->timestamp();
            //order must be created greater than 2 minutes
            $maxCreatedAt = $this->date->date('Y-m-d H:i:s', $currentTimestamp - (2 * 60));

            /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
            $orderCollection = $this->orderCollectionFactory->create();
            $orderCollection->addFieldToFilter(SubscriptionOrderHelper::IS_INCOMPLETE_GENERATE_PROFILE_ORDER, 1)
                ->addFieldToFilter('state', ['neq' =>  \Magento\Sales\Model\Order::STATE_CANCELED])
                ->addFieldToFilter('entity_id', ['gt'   =>  $maxId])
                ->addFieldToFilter('created_at', ['to'    =>  $maxCreatedAt])
                ->setOrder('entity_id', 'ASC')
                ->setPageSize(self::MAX_PROCESSED_ITEM);

            $continue = false;

            /** @var \Magento\Sales\Model\Order $order */
            foreach ($orderCollection as $order) {
                $continue = true;
                $maxId = $order->getId();

                try {
                    $order->cancel();
                    $order->addStatusHistoryComment('Canceled by The delete incomplete generate profile order cron.');
                    $order->save();
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
        }
    }
}
