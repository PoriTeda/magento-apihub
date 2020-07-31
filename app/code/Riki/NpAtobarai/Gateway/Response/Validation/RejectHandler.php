<?php
namespace Riki\NpAtobarai\Gateway\Response\Validation;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Riki\ArReconciliation\Model\ResourceModel\Status\PaymentStatus;
use Riki\NpAtobarai\Model\Config\Source\TransactionStatus;
use Psr\Log\LoggerInterface;

/**
 * Class TransactionHandler
 */
class RejectHandler implements HandlerInterface
{
    const CANCEL_STATUS = [
        TransactionStatus::PENDING_STATUS_VALUE,
        TransactionStatus::NG_STATUS_VALUE,
        TransactionStatus::ER_STATUS_VALUE
        ];

    /**
     * @inheritdoc
     */
    protected $authoriRequiredDate;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var \Riki\NpAtobarai\Model\TransactionAuthorizeFailureEmail
     */
    protected $authorizeFailureEmail;

    /**
     * RejectHandler constructor.
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Riki\NpAtobarai\Model\TransactionAuthorizeFailureEmail $authorizeFailureEmail
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Riki\NpAtobarai\Model\TransactionAuthorizeFailureEmail $authorizeFailureEmail

    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
        $this->authorizeFailureEmail = $authorizeFailureEmail;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($response['results'])) {
            return;
        }
        $salesConnection = $this->resourceConnection->getConnection('sales');
        $groupTransactionList = [];
        $codeCancel = '';
        foreach ($response['results'] as $item) {
            $statusId = $item['authori_result'];
            $npTransactionId = $item['np_transaction_id'];
            if (in_array($statusId, self::CANCEL_STATUS)) {
                foreach ($handlingSubject as $transaction) {
                    if ($transaction->getNpTransactionId() == $npTransactionId) {
                        $transaction->setNpTransactionStatus($statusId);
                        if (isset($item['authori_hold'])) {
                            $pendingReasonCode = implode(',', $item['authori_hold']);
                            $transaction->setAuthorizePendingReasonCodes($pendingReasonCode);
                            $codeCancel = $pendingReasonCode;
                        }
                        if (isset($item['authori_ng'])) {
                            $transaction->setAuthoriNg($item['authori_ng']);
                            $codeCancel = $item['authori_ng'];
                        }
                        $transaction->setAuthorizeErrorCodes(null);
                        $groupTransactionList[$transaction->getOrderId()][] = $transaction;
                    }
                }
            }
        }
        foreach ($groupTransactionList as $transactionList) {
            try {
                $salesConnection->beginTransaction();
                $this->_processHandle($transactionList, $codeCancel);
                $salesConnection->commit();
            } catch (\Exception $e) {
                $salesConnection->rollBack();
                $this->logger->critical($e->getMessage());
            }
        }
    }

    /**
     * @param array $transactionList
     * @param string $codeCancel
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function _processHandle($transactionList, $codeCancel)
    {
        $order = null;
        $orderPendingStatus = TransactionStatus::PENDING_STATUS_VALUE;
        /** @var \Riki\NpAtobarai\Model\Transaction $transaction */
        foreach ($transactionList as $transaction) {
            $transaction->save();
            $orderPendingStatus = $transaction->getNpTransactionStatus();
            /** @var \Magento\Sales\Api\Data\OrderInterface $order */
            $order = $transaction->getOrder();
        }
        if (!$order) {
            return;
        }
        $messageCancel = $this->getMessageCancel($orderPendingStatus, $codeCancel);
        if ($order->canCancel()) {
            $order->cancel();
            if ($messageCancel) {
                $order->addStatusHistoryComment($messageCancel);
            }
            $order->setPaymentStatus(PaymentStatus::PAYMENT_AUTHORIZED_FAILED)->save();
            if ($orderPendingStatus == TransactionStatus::PENDING_STATUS_VALUE || $orderPendingStatus == TransactionStatus::NG_STATUS_VALUE || $orderPendingStatus == TransactionStatus::ER_STATUS_VALUE) {
                $this->authorizeFailureEmail->sendEmailAuthorizationFailure($order);
            }
        }
    }

    /**
     * @param int $statusId
     * @param string $codeCancel
     * @return \Magento\Framework\Phrase
     */
    private function getMessageCancel($statusId, $codeCancel)
    {
        if ($statusId == TransactionStatus::PENDING_STATUS_VALUE) {
            $message = __(
                'The order has been pending by NP-Atobarai system due to reason %1',
                $codeCancel
            );
        } else {
            $message = __(
                'The order has been rejected by NP-Atobarai system due to reason %1',
                $codeCancel
            );
        }
        return $message;
    }
}
