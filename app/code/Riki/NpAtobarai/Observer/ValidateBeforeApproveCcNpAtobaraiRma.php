<?php

namespace Riki\NpAtobarai\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Riki\NpAtobarai\Exception\ApproveRmaNpAtobaraiException;

class ValidateBeforeApproveCcNpAtobaraiRma implements ObserverInterface
{
    /**
     * @var \Riki\NpAtobarai\Model\RmaNpAtobarai
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
     * @param \Riki\NpAtobarai\Model\RmaNpAtobarai $rmaNpAtobarai
     * @param \Riki\NpAtobarai\Model\Method\Adapter $adapter
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Rma\Helper\Data $dataHelper
     */
    public function __construct(
        \Riki\NpAtobarai\Model\RmaNpAtobarai $rmaNpAtobarai,
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
     * @throws \Exception|ApproveRmaNpAtobaraiException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Rma\Model\Rma $rma */
        $rma = $observer->getEvent()->getRma();

        // Not process this logic for case rma is without goods.
        if ($rma->getData('is_without_goods')) {
            return;
        }

        // For NP-Atobarai, the system need to update payment status
        if ($transaction = $this->rmaNpAtobarai->getTransactionNotPaidYetByRma($rma)) {
            try {
                // Call [NP API] Get Payment status If shipment[np_customer_payment_status] != 20
                $this->adapter->getPaymentStatus([$transaction->getId() => $transaction]);
            } catch (\Exception $e) {
                $this->logger->info(__(
                    'Rma #%1 - %2',
                    $rma->getIncrementId(),
                    $e->getMessage()
                ));
                throw new \Exception($e);
            }

            // Check return approval validation for NP-Atobarai
            $isValid = $this->isValid($rma);
            if (!$isValid) {
                $this->logger->info(__(
                    'Rma #%1 - The return could not approve due to 
                    the shipment not paid yet while we allowed to refund it',
                    $rma->getIncrementId()
                ));
                throw new ApproveRmaNpAtobaraiException(__(
                    'The return could not approve due to the shipment not paid yet while we allowed to refund it'
                ));
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
        $shipmentPaymentStatus = [
            PaymentStatus::SHIPPING_PAYMENT_STATUS_NULL,
            PaymentStatus::SHIPPING_PAYMENT_STATUS_AUTHORIZED
        ];

        $shipment = $this->dataHelper->getRmaShipment($rma, false);
        if ($rma->getRefundAllowed() &&
            in_array($shipment->getPaymentStatus(), $shipmentPaymentStatus)
        ) {
            return false;
        }

        return true;
    }
}
