<?php

namespace Riki\NpAtobarai\Block\Adminhtml\Rma\Edit\Tab\General\Details;

use Riki\NpAtobarai\Model\Payment\NpAtobarai;

class GetPaymentDate extends \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\AbstractGeneral
{
    /**
     * @var \Riki\NpAtobarai\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * GetPaymentDate constructor.
     *
     * @param \Riki\NpAtobarai\Api\TransactionRepositoryInterface $transactionRepository
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Riki\NpAtobarai\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->dateTime = $dateTime;
        parent::__construct($context, $registry, $data);
    }

    /**
     * Get payment date of all transactions by order id
     *
     * @return mixed
     */
    public function getPaymentDateOfAllTransactionsByOrderId()
    {
        $transactionsLatest = [];
        $order = $this->getOrder();
        if (!$order instanceof \Magento\Sales\Model\Order) {
            return json_encode($transactionsLatest, JSON_UNESCAPED_UNICODE);
        }

        $payment = $order->getPayment();
        if (!$payment instanceof \Magento\Sales\Model\Order\Payment) {
            return json_encode($transactionsLatest, JSON_UNESCAPED_UNICODE);
        }

        $paymentMethod = $payment->getMethod();
        if ($paymentMethod != NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE) {
            return json_encode($transactionsLatest, JSON_UNESCAPED_UNICODE);
        }

        // Get all transactions of order
        $transactions = $this->transactionRepository->getListByOrderId($order->getId());
        if ($transactions->getTotalCount()) {
            /** @var \Riki\NpAtobarai\Model\Transaction $transaction */
            foreach ($transactions->getItems() as $transaction) {
                try {
                    $shipment = $transaction->getShipment();
                } catch (\Exception $e){
                    continue;
                }
                if (!empty($transaction->getNpCustomerPaymentDate())) {
                    $paymentDateFormatted = $this->dateTime->date(
                        'M d, Y, h:i:s A',
                        $transaction->getNpCustomerPaymentDate()
                    );

                    $transactionsLatest[$shipment->getIncrementId()] = $paymentDateFormatted;
                } else {
                    $transactionsLatest[$shipment->getIncrementId()] = '';
                }
            }
        }

        return json_encode($transactionsLatest, JSON_UNESCAPED_UNICODE);
    }
}
