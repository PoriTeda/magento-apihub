<?php

namespace Riki\NpAtobarai\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riki\NpAtobarai\Exception\ApproveRmaNpAtobaraiException;
use Riki\NpAtobarai\Exception\NotRefundPaidTransactionException;
use Riki\NpAtobarai\Model\RmaNpAtobarai;

class CancelTransactionAfterCompleteRma implements ObserverInterface
{
    /**
     * @var RmaNpAtobarai
     */
    protected $rmaNpAtobarai;

    /**
     * @var \Riki\NpAtobarai\Model\Method\Adapter
     */
    protected $adapter;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * ValidateApproveRequestForRma constructor.
     *
     * @param RmaNpAtobarai $rmaNpAtobarai
     * @param \Riki\NpAtobarai\Model\Method\Adapter $adapter
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Rma\Helper\Data $dataHelper
     */
    public function __construct(
        RmaNpAtobarai $rmaNpAtobarai,
        \Riki\NpAtobarai\Model\Method\Adapter $adapter,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Rma\Helper\Data $dataHelper
    ) {
        $this->rmaNpAtobarai = $rmaNpAtobarai;
        $this->adapter = $adapter;
        $this->logger = $logger;
        $this->dataHelper = $dataHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Exception|ApproveRmaNpAtobaraiException|NotRefundPaidTransactionException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Rma\Model\Rma $rma */
        $rma = $observer->getEvent()->getRma();

        // For NP-Atobarai, the system need to check and cancel the Payment Registration
        /** @var \Riki\NpAtobarai\Model\Transaction $transaction */
        if (!$this->isValid($rma) &&
            $transaction = $this->rmaNpAtobarai->getTransactionNotPaidYetByRma($rma)
        ) {
            try {
                // Call [NP API] Cancel Order for the transaction
                $this->adapter->cancel([$transaction->getId() => $transaction]);
            } catch (\Exception $e) {
                $this->logger->info(__(
                    'Rma #%1 - %2',
                    $rma->getIncrementId(),
                    $e->getMessage()
                ));
                throw new \Exception($e);
            }

            // If the cancellation has error code
            if ($transaction->getCancelErrorCodes()) {
                $cancelErrorCodes = explode(',', $transaction->getCancelErrorCodes());
                // If the error return is 'E0100118'
                // Rejected by CS this return
                if (in_array(RmaNpAtobarai::CANCEL_ERROR_CODE_WILL_REJECT_RMA, $cancelErrorCodes)) {
                    $this->logger->info(__(
                        'Rma #%1 - The return status was changed to CS feedback - Rejected as it was already paid',
                        $rma->getIncrementId()
                    ));
                    throw new NotRefundPaidTransactionException(__(
                        'The return status was changed to CS feedback - Rejected as it was already paid'
                    ));
                } else {
                    $this->logger->info(__(
                        'Rma #%1 - Magento cannot cancel the payment registration due to errors %2',
                        $rma->getIncrementId(),
                        $transaction->getCancelErrorCodes()
                    ));
                    throw new ApproveRmaNpAtobaraiException(__(
                        'Magento cannot cancel the payment registration due to errors %1',
                        $transaction->getCancelErrorCodes()
                    ));
                }
            }
        }
    }

    /**
     * Validate approve request for Rma Np Atobarai
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @return boolean
     */
    private function isValid(\Magento\Rma\Model\Rma $rma)
    {
        if (!$rma->getRefundAllowed() &&
            $this->isAllProductsOfShipmentInReturn($rma)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Is all products of the Shipment is in this Return
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @return boolean
     */
    private function isAllProductsOfShipmentInReturn(\Magento\Rma\Model\Rma $rma)
    {
        $shipment = $this->dataHelper->getRmaShipment($rma);
        if (!$shipment instanceof \Magento\Sales\Model\Order\Shipment) {
            return false;
        }

        /** @var \Magento\Sales\Model\Order\Shipment\Item $shipmentItem */
        foreach ($shipment->getItems() as $shipmentItem) {
            $isShipmentItemInReturn = false;
            $orderItem = $shipmentItem->getOrderItem();
            if ($orderItem->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                continue;
            }

            /** @var \Magento\Rma\Model\Item $rmaItem */
            foreach ($this->dataHelper->getRmaItems($rma) as $rmaItem) {
                if ($shipmentItem->getOrderItemId() == $rmaItem->getOrderItemId()) {
                    if ($shipmentItem->getQty() != $rmaItem->getQtyRequested()) {
                        return false;
                    }

                    $isShipmentItemInReturn = true;
                    break;
                }
            }

            if (!$isShipmentItemInReturn) {
                return false;
            }
        }

        return true;
    }
}
