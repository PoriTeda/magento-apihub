<?php

namespace Riki\SubscriptionMachine\Observer;

use Magento\Framework\Event\Observer;
use Riki\Sales\Model\ResourceModel\Order\OrderAdditionalInformation as AdditionalInformationResourceModel;

class UpdateOrderAdditionalInformation implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var AdditionalInformationResourceModel
     */
    private $orderAdditionalInformationResource;

    public function __construct(
        AdditionalInformationResourceModel $orderAdditionalInformationResource,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->orderAdditionalInformationResource = $orderAdditionalInformationResource;
        $this->logger = $logger;
    }


    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $observer->getEvent()->getProfile();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        $data = [
            'order_id' => $order->getId(),
            'monthly_fee_label' => $profile->getMonthlyFeeLabel()
        ];

        $connection = $this->orderAdditionalInformationResource->getConnection();
        try {
            $connection->insertOnDuplicate($connection->getTableName('sales_order_additional_information'), $data);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

    }
}