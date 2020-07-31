<?php

namespace Riki\NpAtobarai\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riki\NpAtobarai\Model\Config\Source\TransactionStatus;

class UpdateShipmentIdForTransaction implements ObserverInterface
{
    /**
     * @var \Riki\NpAtobarai\Api\TransactionManagementInterface
     */
    protected $npTransactionManagement;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * UpdateShipmentIdForTransaction constructor.
     *
     * @param \Riki\NpAtobarai\Api\TransactionManagementInterface $npTransactionManagement
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Riki\NpAtobarai\Api\TransactionManagementInterface $npTransactionManagement,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->npTransactionManagement = $npTransactionManagement;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();

        // Only process this logic for case created new shipment
        if (!$shipment->getData('is_new_np_atobarai_shipment')
        ) {
            return;
        }

        // Only process this logic for case shipment has grand_total > 0
        if ($shipment->getData('grand_total') == 0
        ) {
            return;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $shipment->getOrder();

        // After shipment created, check if this order has NP Transaction
        // Update shipment_id to table NP Transaction by order
        if (!$order instanceof \Riki\Subscription\Model\Emulator\Order) {
            if ($npTransactions = $this->npTransactionManagement->getOrderTransactions($order)) {
                if (!$this->updateShipmentIdForTransaction($npTransactions, $shipment)) {
                    $this->logger->error(__(
                        'Can\'t update shipment_id #%1 due to system 
                        can\'t find any NP Transactions meet conditions',
                        $shipment->getId()
                    ));
                }
            }
        }
    }

    /**
     * Update shipment id for NP Transaction
     *
     * @param \Riki\NpAtobarai\Api\Data\TransactionInterface[] $npTransactions
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     *
     * @return bool
     */
    protected function updateShipmentIdForTransaction($npTransactions, $shipment)
    {
        /** @var \Riki\NpAtobarai\Model\Transaction $npTransaction */
        foreach ($npTransactions as $npTransaction) {
            if ($this->isShipmentOfNpTransaction($shipment, $npTransaction)) {
                $npTransaction->setShipmentId($shipment->getId());
                try {
                    $npTransaction->save();

                    $this->logger->info(__(
                        'Updated shipment_id #%1 to NP Transactions #%2 successfully',
                        $shipment->getId(),
                        $npTransaction->getId()
                    ));

                    return true;
                } catch (\Exception $e) {
                    $this->logger->error(__(
                        'Can\'t update shipment_id #%1 to NP Transaction #%2 due to %3',
                        $shipment->getId(),
                        $npTransaction->getId(),
                        $e->getMessage()
                    ));
                }
            }
        }

        return false;
    }

    /**
     * Is shipment of NP Transaction
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param \Riki\NpAtobarai\Model\Transaction $npTransaction
     *
     * @return bool
     */
    private function isShipmentOfNpTransaction($shipment, $npTransaction)
    {
        if (!$npTransaction->getShipmentId() &&
            $npTransaction->getNpTransactionStatus() != TransactionStatus::CANCELLED_STATUS_VALUE &&
            $npTransaction->getOrderShippingAddressId() == $shipment->getShippingAddressId() &&
            $npTransaction->getWarehouse() == $shipment->getWarehouse() &&
            $npTransaction->getDeliveryType() == $shipment->getDeliveryType()
        ) {
            return true;
        }

        return false;
    }
}
