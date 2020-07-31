<?php

namespace Riki\Rma\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\OfflinePayments\Model\Cashondelivery;
use Riki\Rma\Api\Data\Rma\ReturnStatusInterface;
use Riki\Rma\Api\Data\Rma\RefundStatusInterface;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment;
use Riki\NpAtobarai\Exception\ApproveRmaNpAtobaraiException;
use Riki\NpAtobarai\Exception\NotRefundPaidTransactionException;

class RmaManagement implements \Riki\Rma\Api\RmaManagementInterface
{
    const COD_REASON_VALIDATION = [11,12,13,14,15,16,21,22,23,24];

    const IS_APPROVE_REQUESTED_FLAG_NAME = 'is_approve_requested';

    /**
     * @var \Riki\Rma\Api\Data\RmaInterface
     */
    protected $lastProceedRma;

    /**
     * @var \Riki\Rma\Helper\Status
     */
    protected $statusHelper;

    /**
     * @var \Riki\Framework\Helper\Datetime
     */
    protected $datetimeHelper;

    /**
     * @var \Riki\Framework\Helper\Transaction\Database
     */
    protected $dbTransaction;

    /**
     * @var \Riki\Rma\Helper\Amount
     */
    protected $amountHelper;

    /**
     * @var \Riki\Rma\Model\RewardPoint
     */
    protected $rewardPoint;

    /**
     * @var \Riki\Rma\Api\RmaRepositoryInterface
     */
    protected $rmaRepository;

    /**
     * @var \Riki\Rma\Api\ItemRepositoryInterface
     */
    protected $rmaItemRepository;

    /**
     * @var \Riki\Rma\Helper\Refund
     */
    protected $rikiRefundHelper;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\Validator\Factory
     */
    protected $validatorFactory;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $rikiReturnHelper;

    /**
     * @var ResourceModel\Reason\CollectionFactory
     */
    protected $reasonCollectionFactory;

    /**
     * @var \Riki\Sales\Api\ShipmentManagementInterface
     */
    protected $shipmentManagement;

    /**
     * @var \Magento\Rma\Model\Rma\Status\HistoryFactory
     */
    protected $historyFactory;

    /**
     * @var \Riki\Rma\Api\Data\RmaInterface
     */
    protected $rmaData;

    /**
     * @var \Riki\Rma\Api\Data\NewRmaResultInterface
     */
    protected $newRmaResultData;

    /**
     * @var \Magento\Rma\Helper\Data
     */
    protected $returnHelper;

    /**
     * @var \Riki\Rma\Api\Data\ItemInterface
     */
    protected $itemData;

    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $orderHelper;

    /**
     * @var \Magento\Sales\Model\Order\Shipment
     */
    protected $shipment;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * RmaManagement constructor.
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Riki\Rma\Helper\Status $statusHelper
     * @param \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository
     * @param \Riki\Framework\Helper\Datetime $datetimeHelper
     * @param \Riki\Framework\Helper\Transaction\Database $dbTransaction
     * @param \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository
     * @param RewardPoint $rewardPoint
     * @param \Riki\Rma\Helper\Refund $refundHelper
     * @param \Riki\Rma\Helper\Amount $amountHelper
     * @param \Magento\Framework\Validator\Factory $validatorFactory
     * @param ResourceModel\Reason\CollectionFactory $reasonCollectionFactory
     * @param \Riki\Sales\Api\ShipmentManagementInterface $shipmentManagement
     * @param \Magento\Rma\Model\Rma\Status\HistoryFactory $historyFactory
     * @param \Riki\Rma\Api\Data\RmaInterface $rmaData
     * @param \Riki\Rma\Api\Data\NewRmaResultInterface $newRmaResult
     * @param \Magento\Rma\Helper\Data $returnHelper
     * @param \Riki\Rma\Api\Data\ItemInterface $itemData
     * @param \Riki\Sales\Helper\Order $orderHelper
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Riki\Rma\Helper\Status $statusHelper,
        \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository,
        \Riki\Framework\Helper\Datetime $datetimeHelper,
        \Riki\Framework\Helper\Transaction\Database $dbTransaction,
        \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Riki\Rma\Model\RewardPoint $rewardPoint,
        \Riki\Rma\Helper\Refund $refundHelper,
        \Riki\Rma\Helper\Amount $amountHelper,
        \Magento\Framework\Validator\Factory $validatorFactory,
        \Riki\Rma\Model\ResourceModel\Reason\CollectionFactory $reasonCollectionFactory,
        \Riki\Sales\Api\ShipmentManagementInterface $shipmentManagement,
        \Magento\Rma\Model\Rma\Status\HistoryFactory $historyFactory,
        \Riki\Rma\Api\Data\RmaInterface $rmaData,
        \Riki\Rma\Api\Data\NewRmaResultInterface $newRmaResult,
        \Magento\Rma\Helper\Data $returnHelper,
        \Riki\Rma\Api\Data\ItemInterface $itemData,
        \Riki\Sales\Helper\Order $orderHelper,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->request = $request;
        $this->statusHelper = $statusHelper;
        $this->rmaItemRepository = $rmaItemRepository;
        $this->datetimeHelper = $datetimeHelper;
        $this->dbTransaction = $dbTransaction;
        $this->rmaRepository = $rmaRepository;
        $this->rewardPoint = $rewardPoint;
        $this->amountHelper = $amountHelper;
        $this->rikiRefundHelper = $refundHelper;
        $this->lastProceedRma = $this->rmaRepository->createFromArray();
        $this->validatorFactory = $validatorFactory;
        $this->rikiReturnHelper = $amountHelper->getDataHelper();
        $this->reasonCollectionFactory = $reasonCollectionFactory;
        $this->shipmentManagement = $shipmentManagement;
        $this->historyFactory = $historyFactory;
        $this->rmaData = $rmaData;
        $this->newRmaResultData = $newRmaResult;
        $this->returnHelper = $returnHelper;
        $this->itemData = $itemData;
        $this->orderHelper = $orderHelper;
        $this->eventManager = $eventManager;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return float
     */
    public function getReturnedGoodsAmount(\Magento\Rma\Model\Rma $rma)
    {
        return $this->amountHelper->getReturnedGoodsAmount($rma);
    }

    /**
     * Get last proceed rma
     *
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function getLastProceedRma()
    {
        return $this->lastProceedRma;
    }

    /**
     * Accept return request
     *
     * @param int|string $entityId
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function acceptRequest($entityId)
    {
        try {
            $this->dbTransaction->beginTransaction();

            $entityId = $this->rmaRepository->lockIdForUpdate($entityId);
            /** @var \Riki\Rma\Model\Rma $rma */
            $rma = $this->rmaRepository->getById($entityId);
            $this->lastProceedRma = $rma;

            $rma->validateSaveAgain();

            $status = $rma->getReturnStatus();
            if ($status == ReturnStatusInterface::REVIEWED_BY_CC) {
                $this->dbTransaction->commit();
                return true;
            }
            $allowedStatus = [
                ReturnStatusInterface::CREATED,
                ReturnStatusInterface::CC_FEEDBACK_REJECTED,
            ];
            if (!in_array($status, $allowedStatus)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The return status must be %1 before approved', $this->statusHelper->getLabel($allowedStatus)));
            }

            $rma->setReturnStatus(ReturnStatusInterface::REVIEWED_BY_CC);
            $rma->setUsePostData(true);
            $this->rmaRepository->save($rma);

            if (is_null($rma->getTotalReturnAmountAdjusted())) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The return is missed return amount data, please save again before approve it'));
            }

            $rma->addReturnStatusHistoryComment();

            $this->dbTransaction->commit();
        } catch (\Exception $e) {
            $this->dbTransaction->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * Deny return request
     *
     * @param int|string $entityId
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function denyRequest($entityId)
    {
        try {
            $this->dbTransaction->beginTransaction();

            $entityId = $this->rmaRepository->lockIdForUpdate($entityId);
            /** @var \Riki\Rma\Model\Rma $rma */
            $rma = $this->rmaRepository->getById($entityId);
            $this->lastProceedRma = $rma;

            $rma->validateSaveAgain();

            $status = $rma->getReturnStatus();
            if ($status == ReturnStatusInterface::REJECTED_BY_CC) {
                $this->dbTransaction->commit();
                return true;
            }
            $allowedStatus = [
                ReturnStatusInterface::CREATED,
                ReturnStatusInterface::REVIEWED_BY_CC,
            ];
            if (!in_array($status, $allowedStatus)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The return status must be %1 before rejected', $this->statusHelper->getLabel($allowedStatus)));
            }

            $rma->setReturnStatus(ReturnStatusInterface::REJECTED_BY_CC);
            $rma->setUsePostData(true);
            $this->rmaRepository->save($rma);

            $rma->addReturnStatusHistoryComment();

            $this->dbTransaction->commit();
        } catch (\Exception $e) {
            $this->dbTransaction->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * Reject return request
     *
     * @param int|string $entityId
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function rejectRequest($entityId)
    {
        try {
            $this->dbTransaction->beginTransaction();

            $entityId = $this->rmaRepository->lockIdForUpdate($entityId);
            /** @var \Riki\Rma\Model\Rma $rma */
            $rma = $this->rmaRepository->getById($entityId);
            $this->lastProceedRma = $rma;

            $rma->validateSaveAgain();

            $status = $rma->getReturnStatus();
            if ($status == ReturnStatusInterface::CC_FEEDBACK_REJECTED) {
                $this->dbTransaction->commit();
                return true;
            }
            $allowedStatus = [
                ReturnStatusInterface::REVIEWED_BY_CC,
            ];
            if (!in_array($status, $allowedStatus)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The return status must be %1 before rejected', $this->statusHelper->getLabel($allowedStatus)));
            }

            $rma->setReturnStatus(ReturnStatusInterface::CC_FEEDBACK_REJECTED);
            $rma->setUsePostData(true);
            $this->rmaRepository->save($rma);

            $rma->addReturnStatusHistoryComment();

            $this->dbTransaction->commit();
        } catch (\Exception $e) {
            $this->dbTransaction->rollback();
            throw $e;
        }
        return true;
    }

    /**
     * Approve return request
     *
     * @param int|string $entityId
     *
     * @return bool
     *
     * @throws \Exception|\Riki\NpAtobarai\Exception\ApproveRmaNpAtobaraiException
     */
    public function approveRequest($entityId)
    {
        try {
            $this->dbTransaction->beginTransaction();

            $entityId = $this->rmaRepository->lockIdForUpdate($entityId);
            /** @var \Riki\Rma\Model\Rma $rma */
            $rma = $this->rmaRepository->getById($entityId);
            $this->lastProceedRma = $rma;

            $status = $rma->getReturnStatus();
            if ($status == ReturnStatusInterface::APPROVED_BY_CC) {
                $this->dbTransaction->commit();
                return true;
            }
            $allowedStatus = [
                ReturnStatusInterface::CREATED,
                ReturnStatusInterface::REVIEWED_BY_CC,
            ];
            if (!in_array($status, $allowedStatus)) {
                throw new \Magento\Framework\Exception\LocalizedException(__(
                    'The return status must be %1 before approved',
                    $this->statusHelper->getLabel($allowedStatus)
                ));
            }

            $this->eventManager->dispatch('rma_approve_cc_before', ['rma' => $rma]);

            $rma->validateSaveAgain();

            $rma->setReturnStatus(ReturnStatusInterface::APPROVED_BY_CC);
            $rma->setUsePostData(true);
            $this->rmaRepository->save($rma);

            if (is_null($rma->getTotalReturnAmountAdjusted())) {
                throw new \Magento\Framework\Exception\LocalizedException(__(
                    'The return is missed return amount data, please save again before approve it'
                ));
            }

            $rma->addReturnStatusHistoryComment();

            $this->dbTransaction->commit();
        } catch (\Exception $e) {
            $this->dbTransaction->rollback();
            throw $e;
        }
        return true;
    }

    /**
     * Approve return
     *
     * @param int|string $entityId
     *
     * @return bool
     *
     * @throws \Exception|ApproveRmaNpAtobaraiException|NotRefundPaidTransactionException
     */
    public function approve($entityId)
    {
        try {
            $this->dbTransaction->beginTransaction();

            $entityId = $this->rmaRepository->lockIdForUpdate($entityId);
            /** @var \Riki\Rma\Model\Rma $rma */
            $rma = $this->rmaRepository->getById($entityId);
            $this->lastProceedRma = $rma;

            $status = $rma->getReturnStatus();
            if ($status == ReturnStatusInterface::COMPLETED) {
                $this->dbTransaction->commit();
                return true;
            }
            $allowedStatus = [
                ReturnStatusInterface::APPROVED_BY_CC,
            ];
            if (!in_array($status, $allowedStatus)) {
                throw new \Magento\Framework\Exception\LocalizedException(__(
                    'The return status must be %1 before approved',
                    $this->statusHelper->getLabel($allowedStatus)
                ));
            }

            $rma->validateSaveAgain();

            $rma->setReturnStatus(ReturnStatusInterface::COMPLETED);
            $rma->setStatus(\Magento\Rma\Model\Rma\Source\Status::STATE_PROCESSED_CLOSED);
            $rma->setIsExportedSap(\Riki\SapIntegration\Model\Api\Shipment::WAITING_FOR_EXPORT); // should move out Riki_Rma

            $rma->setData(self::IS_APPROVE_REQUESTED_FLAG_NAME, true);

            $rma->setReturnApprovalDate($this->datetimeHelper->toDb());
            $rma->setUsePostData(true);

            foreach ($rma->getRmaItems() as $rmaItem) {
                $rmaItem->setQtyAuthorized($rmaItem->getQtyRequested());
                $rmaItem->setQtyApproved($rmaItem->getQtyRequested());
                $rmaItem->setQtyReturned($rmaItem->getQtyRequested());

                // Workaround Magento bug causes status becomes null
                $rmaItem->setData('status', \Magento\Rma\Model\Rma\Source\Status::STATE_APPROVED);

                $this->rmaItemRepository->save($rmaItem);
            }

            if (is_null($rma->getTotalReturnAmountAdjusted())) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The return amount of this RMA is invalid, please save it before approval.'));
            }

            $this->rmaRepository->save($rma);

            /**
             * Using validator here in order to avoid using save again.
             */
            $validator = $this->validatorFactory->createValidator('rma', 'rma_before_approval_validation');
            if (!$validator->isValid($rma)) {
                throw new LocalizedException(__('Please review the RMA again. Error detail: %1', implode('; ', $validator->getMessages())));
            }

            $this->rewardPoint->cancelPoint($rma);

            $this->rewardPoint->returnPoint($rma);

            $rma->addReturnStatusHistoryComment();
            if ($rma->getRefundStatus() == RefundStatusInterface::WAITING_APPROVAL) {
                $rma->addRefundStatusHistoryComment();
            }

            $this->eventManager->dispatch('rma_approve_cs_after', ['rma' => $rma]);

            $this->dbTransaction->commit();
        } catch (ApproveRmaNpAtobaraiException $e) {
            $this->dbTransaction->commit();
            throw $e;
        } catch (\Exception $e) {
            $this->dbTransaction->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * Reject return
     *
     * @param string|int $entityId
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function reject($entityId)
    {
        try {
            $this->dbTransaction->beginTransaction();

            $entityId = $this->rmaRepository->lockIdForUpdate($entityId);
            /** @var \Riki\Rma\Model\Rma $rma */
            $rma = $this->rmaRepository->getById($entityId);
            $this->lastProceedRma = $rma;

            $rma->validateSaveAgain();

            $status = $rma->getReturnStatus();
            if ($status == ReturnStatusInterface::CS_FEEDBACK_REJECTED) {
                $this->dbTransaction->commit();
                return true;
            }
            $allowedStatus = [
                ReturnStatusInterface::CREATED,
                ReturnStatusInterface::APPROVED_BY_CC
            ];
            if (!in_array($status, $allowedStatus)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The return status must be %1 before rejected', $this->statusHelper->getLabel($allowedStatus)));
            }

            $rma->setReturnStatus(ReturnStatusInterface::CS_FEEDBACK_REJECTED);
            $rma->setUsePostData(true);
            $this->rmaRepository->save($rma);

            $rma->addReturnStatusHistoryComment();

            $comment = $this->request->getParam('comment');
            if (isset($comment['comment']) && $comment['comment']) {
                $rma->addHistoryComment($comment['comment']);
            }

            $this->dbTransaction->commit();
        } catch (\Exception $e) {
            $this->dbTransaction->rollback();
            throw $e;
        }
        return true;
    }

    /**
     * Close return
     *
     * @param string|int $entityId
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function close($entityId)
    {
        try {
            $this->dbTransaction->beginTransaction();

            $entityId = $this->rmaRepository->lockIdForUpdate($entityId);
            /** @var \Riki\Rma\Model\Rma $rma */
            $rma = $this->rmaRepository->getById($entityId);
            $this->lastProceedRma = $rma;

            $status = $rma->getReturnStatus();
            if ($status == ReturnStatusInterface::CLOSED) {
                $this->dbTransaction->commit();
                return true;
            }
            $allowedStatus = [
                ReturnStatusInterface::CREATED,
                ReturnStatusInterface::REJECTED_BY_CC,
                ReturnStatusInterface::CC_FEEDBACK_REJECTED,
                ReturnStatusInterface::CS_FEEDBACK_REJECTED,
                ReturnStatusInterface::COMPLETED,
            ];
            if (!in_array($status, $allowedStatus)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The return status must be %1 before closed', $this->statusHelper->getLabel($allowedStatus)));
            }

            if ($status != ReturnStatusInterface::COMPLETED) {
                foreach ($rma->getRmaItems() as $rmaItem) {
                    // Workaround Magento bug causes status becomes null
                    $rmaItem->setData('status', \Magento\Rma\Model\Rma\Source\Status::STATE_REJECTED);

                    $this->rmaItemRepository->save($rmaItem);
                }
            }

            $rma->setReturnStatus(ReturnStatusInterface::CLOSED);
            $rma->setStatus(\Magento\Rma\Model\Rma\Source\Status::STATE_CLOSED);

            $rma->setData('is_closed', true);

            $rma->setUsePostData(true);
            $this->rmaRepository->save($rma);

            $rma->addReturnStatusHistoryComment();

            $this->dbTransaction->commit();
        } catch (\Exception $e) {
            $this->dbTransaction->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * Save RMA
     *
     * @param \Riki\Rma\Api\Data\RmaInterface $rmaDataObject
     * @return \Magento\Rma\Api\Data\RmaInterface
     */
    public function saveRma(\Riki\Rma\Api\Data\RmaInterface $rmaDataObject)
    {
        return $this->rmaRepository->save($rmaDataObject);
    }

    /**
     * @param \Riki\Rma\Api\Data\NewRmaInterface $rmaDataObject
     * @return \Magento\Framework\DataObject
     * @throws LocalizedException
     */
    public function createRmaByApi(\Riki\Rma\Api\Data\NewRmaInterface $rmaDataObject)
    {

        $this->prepareShipmentNumber($rmaDataObject->getRmaShipmentNumber())
            ->prepareDateRequested($rmaDataObject->getDateRequested())
            ->prepareReason($rmaDataObject->getReasonId())
            ->prepareReturnedWarehouse($rmaDataObject->getReturnedWarehouse())
            ->prepareItems($rmaDataObject->getItems())
            ->prepareFullPartial($rmaDataObject);


        if ($substitutionOrder = $rmaDataObject->getSubstitutionOrder()) {
            $this->rmaData->setSubstitutionOrder($substitutionOrder);
        }

        $rma = $this->rmaRepository->save($this->rmaData);

        /** @var \Magento\Rma\Model\Rma\Status\History $systemComment */
        $systemComment = $this->historyFactory->create();
        $systemComment->setRmaEntityId($rma->getEntityId());
        $systemComment->saveSystemComment();

        if ($comment = $rmaDataObject->getComments()) {
            /** @var \Magento\Rma\Model\Rma\Status\History $customComment */
            $customComment = $this->historyFactory->create();
            $customComment->setRmaEntityId($rma->getEntityId());
            $customComment->saveComment($comment, false, true);
        }

        return $this->newRmaResultData->setReturnId($rma->getIncrementId());
    }

    /**
     * @param $shipmentNumber
     * @return $this
     * @throws LocalizedException
     */
    protected function prepareShipmentNumber($shipmentNumber)
    {
        if ($shipmentNumber) {
            /** @var \Magento\Sales\Model\Order\Shipment $shipment */
            $shipment = $this->shipmentManagement->getByIncrementId($shipmentNumber);

            if ($shipment && $shipment->getId()) {
                $order = $shipment->getOrder();
                $this->shipment = $shipment;

                /*not allowed create return for order which is used delay payment and not captured yet*/
                if ($this->orderHelper->isDelayPaymentOrder($order)) {
                    /*this order is delay payment and not allowed to create new return*/
                    if (!$this->orderHelper->isDelayPaymentOrderAllowedReturn($order)) {
                        throw new LocalizedException(__(
                            'This order #%1 is used delay payment, is not allowed to create new return right now.',
                            $order->getIncrementId()
                        ));
                    }
                }
                $this->rmaData->setOrderId($order->getId());
                $this->rmaData->setRmaShipmentNumber($shipmentNumber);
            } else {
                throw new LocalizedException(__('The Shipment number doesn\'t exist'));
            }
        } else {
            throw new LocalizedException(__('The Shipment number is required'));
        }

        return $this;
    }

    /**
     * @param $dateRequested
     * @return $this
     * @throws LocalizedException
     */
    protected function prepareDateRequested($dateRequested)
    {
        if ($dateRequested) {
            if (date('Y-m-d', strtotime($dateRequested)) == $dateRequested ||
                date('Y-m-d H:i:s', strtotime($dateRequested)) == $dateRequested
            ) {
                $this->rmaData->setReturnedDate($dateRequested);
            } else {
                throw new LocalizedException(__('Date requested is invalid'));
            }
        } else {
            throw new LocalizedException(__('Date requested is required'));
        }

        return $this;
    }

    /**
     * @param $reasonCode
     * @return $this
     * @throws LocalizedException
     */
    protected function prepareReason($reasonCode)
    {
        if ($reasonCode) {
            $reason = $this->reasonCollectionFactory->create()
                ->getReasonByCode($reasonCode);

            if ($reason && $reason->getId()) {
                $this->rmaData->setReasonId($reason->getId());
            } else {
                throw new LocalizedException(__('Reason code is invalid'));
            }
        } else {
            throw new LocalizedException(__('Reason code is required'));
        }

        $this->validateReasonForCODPayment($reasonCode);

        return $this;
    }

    /**
     * Validate return reason in case of COD payment method
     * @param $reasonId
     * @throws LocalizedException
     */
    protected function validateReasonForCODPayment($reasonId)
    {
        $order = $this->shipment->getOrder();
        $isCODPaymentMethod = $order->getPayment()->getMethod() == Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE;
        $paymentStatus = $this->shipment->getPaymentStatus();

        if ($isCODPaymentMethod) {
            if ($paymentStatus == Payment::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED
                && in_array($reasonId, self::COD_REASON_VALIDATION)) {
                throw new LocalizedException(__(
                    'The selected reason code is invalid since payment_status is payment_collected'
                ));
            }

            if ((empty($paymentStatus) || $paymentStatus == Payment::SHIPPING_PAYMENT_STATUS_NOT_APPLICABLE)
                && !in_array($reasonId, self::COD_REASON_VALIDATION)) {
                throw new LocalizedException(__(
                    'Payment status need to be updated in advance for this return reason'
                ));
            }
        }
    }

    /**
     * @param \Riki\Rma\Api\Data\NewRmaInterface $rmaDataObject
     * @return $this
     * @throws LocalizedException
     */
    protected function prepareFullPartial($rmaDataObject)
    {
        $availableItems = $this->returnHelper->getOrderItems($this->rmaData->getOrderId());
        $requestedQty = $shippedQty = 0;

        /** @var \Magento\Sales\Model\Order\Item $availableItem */
        foreach ($availableItems as $availableItem) {
            if ($availableItem->getHasChildren()) {
                continue;
            }
            $shippedQty += $availableItem->getQtyShipped();
        }

        foreach ($rmaDataObject->getItems() as $item) {
            $requestedQty += $item->getQtyRequested();
        }

        if ($requestedQty == $shippedQty) {
            $this->rmaData->setFullPartial(\Riki\Rma\Api\Data\Rma\TypeInterface::FULL);
        } else {
            $this->rmaData->setFullPartial(\Riki\Rma\Api\Data\Rma\TypeInterface::PARTIAL);
        }

        return $this;
    }

    /**
     * @param $warehouseCode
     * @return $this
     * @throws LocalizedException
     */
    protected function prepareReturnedWarehouse($warehouseCode)
    {
        if ($warehouseCode) {
            $warehouses = $this->rikiReturnHelper->getWarehouses();

            $valid = false;
            foreach ($warehouses as $warehouse) {
                if ($warehouseCode == $warehouse->getStoreCode()) {
                    $this->rmaData->setReturnedWarehouse($warehouse->getId());
                    $valid = true;
                    break;
                }
            }

            if (!$valid) {
                throw new LocalizedException(__('Returned warehouse is invalid'));
            }
        } else {
            throw new LocalizedException(__('Returned warehouse is required'));
        }

        return $this;
    }

    /**
     * @param $items
     * @return $this
     * @throws LocalizedException
     */
    protected function prepareItems($items)
    {
        if (is_array($items) && count($items)) {
            $newItems = [];

            foreach ($items as $item) {
                if ($sku = $item->getSku()) {
                    $preparedItems = $this->prepareRmaItemBySku($this->rmaData, $sku, $item->getQtyRequested());

                    foreach ($preparedItems as $preparedItem) {
                        $newItems[] = $preparedItem;
                    }
                } else {
                    throw new LocalizedException(__('SKU is a required value.'));
                }
            }

            $this->rmaData->setItems($newItems);
        } else {
            throw new LocalizedException(__('Items is required'));
        }

        return $this;
    }

    /**
     * @param \Riki\Rma\Api\Data\RmaInterface $rmaDataObject
     * @param $sku
     * @param $requestedQty
     * @return array
     * @throws LocalizedException
     */
    protected function prepareRmaItemBySku(\Riki\Rma\Api\Data\RmaInterface $rmaDataObject, $sku, $requestedQty)
    {
        $shipment = $this->rikiReturnHelper->getRmaShipment($rmaDataObject);

        $availableItems = $this->returnHelper->getOrderItems($shipment->getOrderId());

        $sameSkuOrderItems = [];

        /** @var \Magento\Sales\Model\Order\Item $availableItem */
        foreach ($availableItems as $availableItem) {
            if ($availableItem->getSku() == $sku) {
                $sameSkuOrderItems[$availableItem->getId()] = $availableItem->getAvailableQty();
            }
        }

        $sameSkuShipmentItems = [];
        foreach ($shipment->getItems() as $shipmentItem) {
            if ($shipmentItem->getSku() == $sku) {
                $orderItem = $shipmentItem->getOrderItem();

                if ($orderItem->getHasChildren()) {
                    throw new LocalizedException(__('Bundle SKU "%1" is not allowed.', $sku));
                } else {
                    $sameSkuShipmentItems[] = $shipmentItem->getOrderItemId();
                }
            }
        }

        if (!count($sameSkuShipmentItems)) {
            throw new LocalizedException(__('SKU "%1" not existed in original shipment.', $sku));
        }

        $result = [];

        foreach ($sameSkuShipmentItems as $sameSkuShipmentItem) {
            if ($requestedQty > 0 &&
                array_key_exists($sameSkuShipmentItem, $sameSkuOrderItems) &&
                $sameSkuOrderItems[$sameSkuShipmentItem] > 0
            ) {
                $availableQty = min($requestedQty, $sameSkuOrderItems[$sameSkuShipmentItem]);

                $newItemData = clone $this->itemData;

                $result[] = $newItemData->setOrderItemId($sameSkuShipmentItem)
                    ->setQtyRequested($availableQty);

                $requestedQty -= $sameSkuOrderItems[$sameSkuShipmentItem];
            }
        }

        if (!count($result)) {
            $newItemData = clone $this->itemData;
            return [$newItemData->setOrderItemId($sameSkuShipmentItems[0])->setQtyRequested($requestedQty)];
        }

        if ($requestedQty > 0) {
            $result[0]->setQtyRequested($result[0]->getQtyRequested() + $requestedQty);
        }

        return $result;
    }
}
