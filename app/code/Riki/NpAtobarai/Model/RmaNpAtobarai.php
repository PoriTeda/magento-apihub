<?php

namespace Riki\NpAtobarai\Model;

use Riki\NpAtobarai\Model\Payment\NpAtobarai;
use Riki\NpAtobarai\Exception\ApproveRmaNpAtobaraiException;
use Magento\Framework\Exception\NoSuchEntityException;

class RmaNpAtobarai
{
    const CANCEL_ERROR_CODE_WILL_REJECT_RMA = 'E0100118';

    /**
     * @var \Riki\NpAtobarai\Api\TransactionRepositoryInterface
     */
    protected $npTransactionRepository;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * RmaAtobarai constructor.
     *
     * @param \Riki\NpAtobarai\Api\TransactionRepositoryInterface $npTransactionRepository
     * @param \Riki\Rma\Helper\Data $dataHelper
     */
    public function __construct(
        \Riki\NpAtobarai\Api\TransactionRepositoryInterface $npTransactionRepository,
        \Riki\Rma\Helper\Data $dataHelper
    ) {
        $this->npTransactionRepository = $npTransactionRepository;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Get transaction not paid yet by Rma
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return bool|\Riki\NpAtobarai\Model\Transaction
     * @throws ApproveRmaNpAtobaraiException
     */
    public function getTransactionNotPaidYetByRma(\Magento\Rma\Model\Rma $rma)
    {
        $order = $rma->getOrder();
        $payment = $order->getPayment();
        if ($payment) {
            $paymentMethod = $payment->getMethod();

            if ($paymentMethod == NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE) {
                $shipment = $this->dataHelper->getRmaShipment($rma);
                if ($shipment instanceof \Magento\Sales\Model\Order\Shipment) {
                    try {
                        $transaction = $this->npTransactionRepository->getByShipmentId($shipment->getId());
                        /** @var \Riki\NpAtobarai\Model\Transaction $transaction */
                        if ($transaction) {
                            if (!$transaction->isTransactionPaid()) {
                                return $transaction;
                            }
                        }
                    } catch (NoSuchEntityException $e) {
                        // For case NpAtobarai payment with shipment has no transaction
                        // (due to shipment amount = 0, then we didn't register order to NP)
                        return null;
                    }
                }
            }
        }

        return null;
    }
}
