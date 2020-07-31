<?php
namespace Riki\Rma\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Riki\Rma\Api\Data\Rma\RefundStatusInterface;

class RefundManagement
{
    protected $lastProceedRma;

    /**
     * @var \Riki\Framework\Helper\Datetime
     */
    protected $datetimeHelper;

    /**
     * @var \Riki\Rma\Api\RmaRepositoryInterface
     */
    protected $rmaRepository;

    /**
     * @var \Riki\Rma\Helper\Refund
     */
    protected $refundHelper;

    /**
     * @var \Riki\Framework\Helper\Transaction\Database
     */
    protected $dbTransaction;

    /** @var ResourceModel\Rma  */
    protected $rikiRmaResourceModel;

    /** @var \Riki\Rma\Helper\Status  */
    protected $statusHelper;

    /**
     * @var \Riki\Rma\Logger\Refund\Logger
     */
    protected $logger;

    protected $sendMailNotifyObserver;
    /**
     * RefundManagement constructor.
     *
     * @param \Riki\Framework\Helper\Transaction\Database $dbTransaction
     * @param \Riki\Rma\Helper\Refund $refundHelper
     * @param \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository
     * @param \Riki\Rma\Model\ResourceModel\Rma $rikiRmaResourceModel
     * @param \Riki\Rma\Helper\Status $statusHelper
     * @param \Riki\Framework\Helper\Datetime $datetimeHelper
     */
    public function __construct(
        \Riki\Rma\Observer\SendMailNotify $sendMailNotifyObserver,
        \Riki\Framework\Helper\Transaction\Database $dbTransaction,
        \Riki\Rma\Helper\Refund $refundHelper,
        \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Riki\Rma\Model\ResourceModel\Rma $rikiRmaResourceModel,
        \Riki\Rma\Helper\Status $statusHelper,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Riki\Framework\Helper\Datetime $datetimeHelper
    ) {
        $this->dbTransaction = $dbTransaction;
        $this->refundHelper = $refundHelper;
        $this->datetimeHelper = $datetimeHelper;
        $this->rmaRepository = $rmaRepository;
        $this->rikiRmaResourceModel = $rikiRmaResourceModel;
        $this->statusHelper = $statusHelper;
        $this->sendMailNotifyObserver = $sendMailNotifyObserver;
        $this->logger = $refundHelper->getLogger();

        $this->lastProceedRma = $this->rmaRepository->createFromArray();
    }

    /**
     * @return \Riki\Rma\Api\RmaRepositoryInterface
     */
    public function getRmaRepository()
    {
        return $this->rmaRepository;
    }

    /**
     * Get last proceed approve/reject refund
     *
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function getLastProceedRefund()
    {
        return $this->lastProceedRma;
    }

    /**
     * Reject no need refund
     *
     * @param string|int $entityId
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function rejectWithoutAdj($entityId)
    {
        try {
            $this->dbTransaction->beginTransaction();

            $entityId = $this->rmaRepository->lockIdForUpdate($entityId);
            /** @var \Riki\Rma\Model\Rma $rma */
            $rma = $this->rmaRepository->getById($entityId);
            $this->lastProceedRma = $rma;

            $status = $rma->getRefundStatus();
            if ($status == RefundStatusInterface::GAC_FEEDBACK_REJECTED_NO_NEED_REFUND) {
                $this->dbTransaction->commit();
                return true;
            }
            $allowedStatus = [
                RefundStatusInterface::WAITING_APPROVAL,
                RefundStatusInterface::CHANGE_TO_CHECK,
                RefundStatusInterface::CHANGE_TO_BANK,
                RefundStatusInterface::APPROVED
            ];
            if (!in_array($status, $allowedStatus)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The refund status must be %1 before rejected', $this->statusHelper->getLabel($allowedStatus)));
            }

            $rma->setRefundStatus(RefundStatusInterface::GAC_FEEDBACK_REJECTED_NO_NEED_REFUND);
            $this->rmaRepository->save($rma);

            $rma->addRefundStatusHistoryComment();

            $this->dbTransaction->commit();
        } catch (\Exception $e) {
            $this->dbTransaction->rollback();

            throw $e;
        }

        return true;
    }

    /**
     * Reject need adjustment
     *
     * @param string|int $entityId
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function rejectWithAdj($entityId)
    {
        try {
            $this->dbTransaction->beginTransaction();

            $entityId = $this->rmaRepository->lockIdForUpdate($entityId);
            /** @var \Riki\Rma\Model\Rma $rma */
            $rma = $this->rmaRepository->getById($entityId);
            $this->lastProceedRma = $rma;

            $status = $rma->getRefundStatus();
            if ($status == RefundStatusInterface::GAC_FEEDBACK_REJECTED_NEED_ADJUSTMENT) {
                $this->dbTransaction->commit();
                return true;
            }
            $allowedStatus = [
                RefundStatusInterface::WAITING_APPROVAL,
                RefundStatusInterface::CHANGE_TO_CHECK,
                RefundStatusInterface::CHANGE_TO_BANK,
                RefundStatusInterface::APPROVED
            ];
            if (!in_array($status, $allowedStatus)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The refund status must be %1 before rejected', $this->statusHelper->getLabel($allowedStatus)));
            }

            $rma->setRefundStatus(RefundStatusInterface::GAC_FEEDBACK_REJECTED_NEED_ADJUSTMENT);
            $this->rmaRepository->save($rma);

            $rma->addRefundStatusHistoryComment();

            $this->dbTransaction->commit();
        } catch (\Exception $e) {
            $this->dbTransaction->rollback();

            throw $e;
        }

        return true;
    }

    /**
     * Approve the refund
     *
     * @param string|int $entityId
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function approve($entityId)
    {
        try {
            $this->dbTransaction->beginTransaction();

            $entityId = $this->rmaRepository->lockIdForUpdate($entityId);
            /** @var \Riki\Rma\Model\Rma $rma */
            $rma = $this->rmaRepository->getById($entityId);
            $this->lastProceedRma = $rma;

            $status = $rma->getRefundStatus();
            if ($status == RefundStatusInterface::APPROVED) {
                $this->dbTransaction->commit();
                return true;
            }
            $allowedStatus = [
                RefundStatusInterface::WAITING_APPROVAL
            ];
            if (!in_array($status, $allowedStatus)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The refund status must be %1 before approved', $this->statusHelper->getLabel($allowedStatus)));
            }

            $rma->setRefundStatus(RefundStatusInterface::APPROVED);
            $rma->setRefundApprovalDate($this->datetimeHelper->toDb());
            if ($rma->getRefundMethod() == \Bluecom\Paygent\Model\Paygent::CODE) {
                $this->sendMailNotifyObserver->setRma($rma);
                $creditMemo = $this->refundHelper->refund($rma);
                if ($creditMemo && $creditMemo->getEntityId()) {
                    $rma->setCreditmemoId($creditMemo->getEntityId());
                    $rma->setCreditmemoIncrementId($creditMemo->getIncrementId());
                    $refundStatus = RefundStatusInterface::CARD_COMPLETED;
                } else {
                    $customer = $rma->getCustomer();
                    $offlineCustomerAttr = $customer->getCustomAttribute('offline_customer');
                    $isOfflineCustomer = is_object($offlineCustomerAttr) ? $offlineCustomerAttr->getValue() : 0;
                    if ($isOfflineCustomer == 0) {
                        $refundStatus = RefundStatusInterface::CHANGE_TO_BANK;
                        $rma->setRefundMethod(
                            \Magento\OfflinePayments\Model\Banktransfer::PAYMENT_METHOD_BANKTRANSFER_CODE
                        );
                    } else {
                        $refundStatus = RefundStatusInterface::CHANGE_TO_CHECK;
                    }
                }
                $rma->setRefundStatus($refundStatus);
            }

            $this->rmaRepository->save($rma);

            $rma->addRefundStatusHistoryComment();

            $this->dbTransaction->commit();
        } catch (\Bluecom\Paygent\Exception\PaygentRefundException $e) {
            $this->dbTransaction->rollback();
            /** @var \Riki\Rma\Model\Rma $rma */
            if (isset($rma) && $rma instanceof \Riki\Rma\Model\Rma) {
                $rma->addHistoryComment($e->getMessage());
            }

            throw $e;
        } catch (CouldNotSaveException $e) {
            $this->dbTransaction->rollback();

            if ($e->getPrevious()) {
                if ($e->getPrevious() instanceof \Bluecom\Paygent\Exception\PaygentRefundException) {
                    if (isset($rma) && $rma instanceof \Riki\Rma\Model\Rma) {
                        $rma->addHistoryComment($e->getPrevious()->getMessage());
                    }
                }

                $this->logger->critical($e->getPrevious());
            }

            throw $e;
        } catch (\Exception $e) {
            $this->dbTransaction->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * @deprecated
     *
     * @return \Riki\Rma\Model\Rma
     */
    public function updateApprovedRefundData(\Riki\Rma\Model\Rma $rma)
    {
        $refundStatus = RefundStatusInterface::APPROVED;
        if ($rma->getRefundMethod() == \Bluecom\Paygent\Model\Paygent::CODE) {
            $creditMemo = $this->refundHelper->refund($rma);
            if ($creditMemo && $creditMemo->getEntityId()) {
                $rma->setData('creditmemo_id', $creditMemo->getEntityId());
                $rma->setData('creditmemo_increment_id', $creditMemo->getIncrementId());
                $rma->setOrigData('refund_status', RefundStatusInterface::APPROVED);
                $refundStatus = RefundStatusInterface::CARD_COMPLETED;
            } else {
                $rma->setOrigData('refund_status', RefundStatusInterface::APPROVED);
                $customer = $rma->getCustomer();
                $offlineCustomerAttr = $customer->getCustomAttribute('offline_customer');
                $isOfflineCustomer = is_object($offlineCustomerAttr) ? $offlineCustomerAttr->getValue() : 0;
                if ($isOfflineCustomer == 0) {
                    $refundStatus = RefundStatusInterface::CHANGE_TO_BANK;
                    $rma->setRefundMethod(
                        \Magento\OfflinePayments\Model\Banktransfer::PAYMENT_METHOD_BANKTRANSFER_CODE
                    );
                } else {
                    $refundStatus = RefundStatusInterface::CHANGE_TO_CHECK;
                }
            }
        }
        $rma->setData('refund_status', $refundStatus);
        $rma->setData('refund_approval_date', $this->datetimeHelper->toDb());

        return $rma;
    }

    /**
     *
     */
    public function getAllowedStatusForApprove()
    {
        return [
            RefundStatusInterface::WAITING_APPROVAL
        ];
    }

    /**
     * Process refund by check
     *
     * @param string|int $entityId
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function processByCheck($entityId)
    {
        try {
            $this->dbTransaction->beginTransaction();

            $entityId = $this->rmaRepository->lockIdForUpdate($entityId);
            /** @var \Riki\Rma\Model\Rma $rma */
            $rma = $this->rmaRepository->getById($entityId);
            $this->lastProceedRma = $rma;

            $refundMethod = $rma->getRefundMethod();
            $status = $rma->getRefundStatus();
            if ($status == RefundStatusInterface::CHANGE_TO_CHECK) {
                $this->dbTransaction->commit();
                return true;
            }
            $allowedStatus = [
                RefundStatusInterface::SENT_TO_AGENT,
                RefundStatusInterface::APPROVED,
                RefundStatusInterface::CHANGE_TO_BANK
            ];
            if ($refundMethod == \Bluecom\Paygent\Model\Paygent::CODE) {
                $allowedStatus[] = RefundStatusInterface::WAITING_APPROVAL;
            }
            if (!in_array($status, $allowedStatus)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The refund status must be %1 before proceed', $this->statusHelper->getLabel($allowedStatus)));
            }

            $allowedRefundMethod = [
                \Magento\OfflinePayments\Model\Checkmo::PAYMENT_METHOD_CHECKMO_CODE,
                \Bluecom\Paygent\Model\Paygent::CODE
            ];
            if (!in_array($refundMethod, $allowedRefundMethod)) {
                throw new \Magento\Framework\Exception\LocalizedException(__(
                    'Unable change refund status to %1 because the refund have refund method %2',
                    $this->statusHelper->getLabel(RefundStatusInterface::CHANGE_TO_CHECK),
                    $this->refundHelper->getRefundMethodLabel($rma->getRefundMethod())
                ));
            }
            $rma->setRefundStatus(RefundStatusInterface::CHANGE_TO_CHECK);
            $this->rmaRepository->save($rma);

            $rma->addRefundStatusHistoryComment();

            $this->dbTransaction->commit();
        } catch (\Exception $e) {
            $this->dbTransaction->rollback();

            throw $e;
        }

        return true;
    }

    /**
     * Process refund by bank
     *
     * @param string|int $entityId
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function processByBank($entityId)
    {
        try {
            $this->dbTransaction->beginTransaction();

            $entityId = $this->rmaRepository->lockIdForUpdate($entityId);
            /** @var \Riki\Rma\Model\Rma $rma */
            $rma = $this->rmaRepository->getById($entityId);
            $this->lastProceedRma = $rma;

            $status = $rma->getRefundStatus();
            if ($status == RefundStatusInterface::SENT_TO_AGENT) {
                $this->dbTransaction->commit();
                return true;
            }
            $allowedStatus = [
                RefundStatusInterface::CHANGE_TO_CHECK,
                RefundStatusInterface::CHANGE_TO_BANK,
                RefundStatusInterface::APPROVED
            ];
            if (!in_array($status, $allowedStatus)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The refund status must be %1 before proceed', $this->statusHelper->getLabel($allowedStatus)));
            }

            $allowedRefundMethod = [
                \Magento\OfflinePayments\Model\Banktransfer::PAYMENT_METHOD_BANKTRANSFER_CODE
            ];
            if (!in_array($rma->getRefundMethod(), $allowedRefundMethod)) {
                throw new \Magento\Framework\Exception\LocalizedException(__(
                    'Unable change refund status to %1 because the refund have refund method %2',
                    $this->statusHelper->getLabel(RefundStatusInterface::SENT_TO_AGENT),
                    $this->refundHelper->getRefundMethodLabel($rma->getRefundMethod())
                ));
            }

            $rma->setRefundStatus(RefundStatusInterface::SENT_TO_AGENT);
            $this->rmaRepository->save($rma);

            $rma->addRefundStatusHistoryComment();

            $this->dbTransaction->commit();
        } catch (\Exception $e) {
            $this->dbTransaction->rollback();

            throw $e;
        }

        return true;
    }

    /**
     * Complete refund by check
     *
     * @param string|int $entityId
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function completeByCheck($entityId)
    {
        try {
            $this->dbTransaction->beginTransaction();

            $entityId = $this->rmaRepository->lockIdForUpdate($entityId);
            /** @var \Riki\Rma\Model\Rma $rma */
            $rma = $this->rmaRepository->getById($entityId);
            $this->lastProceedRma = $rma;

            $status = $rma->getRefundStatus();
            if ($status == RefundStatusInterface::CHECK_ISSUED) {
                $this->dbTransaction->commit();
                return true;
            }
            $allowedStatus = [
                RefundStatusInterface::CHANGE_TO_CHECK,
            ];
            if (!in_array($status, $allowedStatus)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The refund status must be %1 before completed', $this->statusHelper->getLabel($allowedStatus)));
            }

            $allowedRefundMethod = [
                \Magento\OfflinePayments\Model\Checkmo::PAYMENT_METHOD_CHECKMO_CODE
            ];
            if (!in_array($rma->getRefundMethod(), $allowedRefundMethod)) {
                throw new \Magento\Framework\Exception\LocalizedException(__(
                    'Unable change refund status to %1 because the refund have refund method %2',
                    $this->statusHelper->getLabel(RefundStatusInterface::CHECK_ISSUED),
                    $this->refundHelper->getRefundMethodLabel($rma->getRefundMethod())
                ));
            }

            $rma->setRefundStatus(RefundStatusInterface::CHECK_ISSUED);
            $creditMemo = $this->refundHelper->refund($rma);
            if (!$creditMemo || !$creditMemo->getEntityId()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Trigger credit memo failed, please try again'));
            }
            $rma->setCreditmemoId($creditMemo->getEntityId());
            $rma->setCreditmemoIncrementId($creditMemo->getIncrementId());
            $this->rmaRepository->save($rma);

            $rma->addRefundStatusHistoryComment();

            $this->dbTransaction->commit();
        } catch (CouldNotSaveException $e) {
            $this->dbTransaction->rollback();

            if ($e->getPrevious()) {
                $this->logger->critical($e->getPrevious());
            }

            throw $e;
        } catch (\Exception $e) {
            $this->dbTransaction->rollback();

            throw $e;
        }

        return true;
    }

    /**
     * @param $entityId
     * @return bool
     * @throws \Exception|\Magento\Framework\Exception\LocalizedException
     */
    public function completeByManuallyCardComplete($entityId)
    {
        try {
            $this->dbTransaction->beginTransaction();

            $entityId = $this->rmaRepository->lockIdForUpdate($entityId);
            /** @var \Riki\Rma\Model\Rma $rma */
            $rma = $this->rmaRepository->getById($entityId);
            $this->lastProceedRma = $rma;

            $status = $rma->getRefundStatus();
            if ($status == RefundStatusInterface::MANUALLY_CARD_COMPLETED) {
                $this->dbTransaction->commit();
                return true;
            }
            $allowedStatus = [
                RefundStatusInterface::CHANGE_TO_CHECK,
                RefundStatusInterface::CHANGE_TO_BANK,
            ];
            if (!in_array($status, $allowedStatus)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __(
                        'The refund status must be %1 before completed',
                        $this->statusHelper->getLabel($allowedStatus)
                    )
                );
            }

            $allowedRefundMethod = [
                \Bluecom\Paygent\Model\Paygent::CODE
            ];
            if (!in_array($rma->getRefundMethod(), $allowedRefundMethod)) {
                throw new \Magento\Framework\Exception\LocalizedException(__(
                    'Unable change refund status to %1 because the refund have refund method %2',
                    $this->statusHelper->getLabel(RefundStatusInterface::CHECK_ISSUED),
                    $this->refundHelper->getRefundMethodLabel($rma->getRefundMethod())
                ));
            }

            $rma->setRefundStatus(RefundStatusInterface::MANUALLY_CARD_COMPLETED);
            $rma->setMustOfflineRefund(true);
            $creditMemo = $this->refundHelper->refund($rma);
            if (!$creditMemo || !$creditMemo->getEntityId()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Trigger credit memo failed, please try again')
                );
            }
            $rma->setCreditmemoId($creditMemo->getEntityId());
            $rma->setCreditmemoIncrementId($creditMemo->getIncrementId());
            $this->rmaRepository->save($rma);

            $rma->addRefundStatusHistoryComment();

            $this->dbTransaction->commit();
        } catch (CouldNotSaveException $e) {
            $this->dbTransaction->rollback();

            if ($e->getPrevious()) {
                $this->logger->critical($e->getPrevious());
            }

            throw $e;
        } catch (\Exception $e) {
            $this->dbTransaction->rollback();

            throw $e;
        }

        return true;
    }

    /**
     * Complete refund by check
     *
     * @param string|int $entityId
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function completeByBank($entityId)
    {
        try {
            $this->dbTransaction->beginTransaction();

            $entityId = $this->rmaRepository->lockIdForUpdate($entityId);
            $rma = $this->rmaRepository->getById($entityId);
            $this->lastProceedRma = $rma;

            $status = $rma->getRefundStatus();
            if ($status == RefundStatusInterface::BT_COMPLETED) {
                $this->dbTransaction->commit();
                return true;
            }
            $allowedStatus = [
                RefundStatusInterface::SENT_TO_AGENT,
            ];
            if (!in_array($status, $allowedStatus)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The refund status must be %1 before completed', $this->statusHelper->getLabel($allowedStatus)));
            }

            $allowedRefundMethod = [
                \Magento\OfflinePayments\Model\Banktransfer::PAYMENT_METHOD_BANKTRANSFER_CODE
            ];
            if (!in_array($rma->getRefundMethod(), $allowedRefundMethod)) {
                throw new \Magento\Framework\Exception\LocalizedException(__(
                    'Unable change refund status to %1 because the refund have refund method %2',
                    $this->statusHelper->getLabel(RefundStatusInterface::BT_COMPLETED),
                    $this->refundHelper->getRefundMethodLabel($rma->getRefundMethod())
                ));
            }

            $rma->setRefundStatus(RefundStatusInterface::BT_COMPLETED);
            $creditMemo = $this->refundHelper->refund($rma);
            if (!$creditMemo || !$creditMemo->getEntityId()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Trigger credit memo failed, please try again'));
            }
            $rma->setCreditmemoId($creditMemo->getEntityId());
            $rma->setCreditmemoIncrementId($creditMemo->getIncrementId());
            $this->rmaRepository->save($rma);

            $rma->addRefundStatusHistoryComment();

            $this->dbTransaction->commit();
        } catch (CouldNotSaveException $e) {
            $this->dbTransaction->rollback();

            if ($e->getPrevious()) {
                $this->logger->critical($e->getPrevious());
            }

            throw $e;
        } catch (\Exception $e) {
            $this->dbTransaction->rollback();

            throw $e;
        }

        return true;
    }

    /**
     * @param $entityId
     * @return void
     * @throws \Exception
     */
    public function updateFailedCcRefund($entityId)
    {
        try {
            $this->dbTransaction->beginTransaction();

            $entityId = $this->rmaRepository->lockIdForUpdate($entityId);
            /** @var \Riki\Rma\Model\Rma $rma */
            $rma = $this->rmaRepository->getById($entityId);
            $this->lastProceedRma = $rma;

            $refundMethod = $rma->getRefundMethod();
            if ($refundMethod == \Bluecom\Paygent\Model\Paygent::CODE) {
                $customer = $rma->getCustomer();
                $offlineCustomerAttr = $customer->getCustomAttribute('offline_customer');
                $isOfflineCustomer = is_object($offlineCustomerAttr) ? $offlineCustomerAttr->getValue() : 0;
                if (!$isOfflineCustomer) {
                    $refundStatus = RefundStatusInterface::CHANGE_TO_BANK;
                    $rma->setRefundMethod(
                        \Magento\OfflinePayments\Model\Banktransfer::PAYMENT_METHOD_BANKTRANSFER_CODE
                    );
                } else {
                    $refundStatus = RefundStatusInterface::CHANGE_TO_CHECK;
                }
                $rma->setRefundStatus($refundStatus);
                $this->rmaRepository->save($rma);
                $rma->addRefundStatusHistoryComment();
                $this->dbTransaction->commit();
            }
        } catch (\Exception $e) {
            $this->dbTransaction->rollback();
            throw $e;
        }
    }
}
