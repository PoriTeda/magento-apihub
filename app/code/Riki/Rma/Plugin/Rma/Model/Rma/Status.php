<?php
namespace Riki\Rma\Plugin\Rma\Model\Rma;

use Riki\Rma\Api\Data\Rma\ReturnStatusInterface;
use Riki\Rma\Api\Data\Rma\RefundStatusInterface;

/**
 * Class Status
 * @package Riki\Rma\Plugin\Rma\Model\Rma
 *
 * @deprecated
 */
class Status
{
    /**
     * @var \Riki\Rma\Helper\Status
     */
    protected $statusHelper;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Riki\Rma\Api\ItemRepositoryInterface
     */
    protected $rmaItemRepository;

    /**
     * @var \Riki\Rma\Helper\Refund
     */
    protected $refundHelper;

    /**
     * Status constructor.
     *
     * @param \Riki\Rma\Helper\Refund $refundHelper
     * @param \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository
     * @param \Riki\Rma\Helper\Data $dataHelper
     * @param \Riki\Rma\Helper\Status $statusHelper
     */
    public function __construct(
        \Riki\Rma\Helper\Refund $refundHelper,
        \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository,
        \Riki\Rma\Helper\Data $dataHelper,
        \Riki\Rma\Helper\Status $statusHelper
    ) {
        $this->refundHelper = $refundHelper;
        $this->rmaItemRepository = $rmaItemRepository;
        $this->dataHelper = $dataHelper;
        $this->statusHelper = $statusHelper;
    }

    /**
     * Extra logic for return_status, refund_status
     *
     * @param \Magento\Rma\Model\Rma $subject
     *
     * @return mixed[]
     */
    public function beforeBeforeSave(\Magento\Rma\Model\Rma $subject)
    {
        $this->processReturnStatus($subject);
        $this->processRefundStatus($subject);

        return [];
    }

    /**
     * Process for return_status
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return void
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processReturnStatus(\Magento\Rma\Model\Rma $rma)
    {
        $oldStatus = $rma->getOrigData('return_status');
        $returnStatus = $rma->getData('return_status');
        if (!$this->statusHelper->isAllowed($oldStatus, $returnStatus)) {
            throw new \Magento\Framework\Exception\LocalizedException($this->statusHelper->getMessage());
        }
    }

    /**
     * Process for refund_status
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return void
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processRefundStatus(\Magento\Rma\Model\Rma $rma)
    {
        if (!$rma->dataHasChangedFor('refund_status')) {
            return;
        }

        $oldStatus = $rma->getOrigData('refund_status');
        $refundStatus = $rma->getData('refund_status');
        if (!$this->statusHelper->isAllowed($oldStatus, $refundStatus)) {
            throw new \Magento\Framework\Exception\LocalizedException($this->statusHelper->getMessage());
        }

        // @should: refactor by extract method or extract class
        if (in_array($refundStatus, [RefundStatusInterface::CHANGE_TO_CHECK])) {
            if (!in_array($rma->getData('refund_method'), [
                \Magento\OfflinePayments\Model\Checkmo::PAYMENT_METHOD_CHECKMO_CODE,
                \Bluecom\Paygent\Model\Paygent::CODE
            ])) {
                throw new \Magento\Framework\Exception\LocalizedException(__(
                    'Unable change refund status to %1 because the refund have refund method %2',
                    $this->statusHelper->getLabel($refundStatus),
                    $this->refundHelper->getRefundMethodLabel($rma->getData('refund_method'))
                ));
            }
        }

        // @should: refactor by extract method or extract class
        if (in_array($refundStatus, [RefundStatusInterface::CHECK_ISSUED])) {
            if ($rma->getData('refund_method') != \Magento\OfflinePayments\Model\Checkmo::PAYMENT_METHOD_CHECKMO_CODE) {
                throw new \Magento\Framework\Exception\LocalizedException(__(
                    'Unable change refund status to %1 because the refund have refund method %2',
                    $this->statusHelper->getLabel($refundStatus),
                    $this->refundHelper->getRefundMethodLabel($rma->getData('refund_method'))
                ));
            }
        }

        // @should: refactor by extract method or extract class
        if (in_array($refundStatus, [
            RefundStatusInterface::SENT_TO_AGENT,
            RefundStatusInterface::BT_COMPLETED
        ])) {
            if ($rma->getData('refund_method') != \Magento\OfflinePayments\Model\Banktransfer::PAYMENT_METHOD_BANKTRANSFER_CODE) {
                throw new \Magento\Framework\Exception\LocalizedException(__(
                    'Unable change refund status to %1 because the refund have refund method %2',
                    $this->statusHelper->getLabel($refundStatus),
                    $this->refundHelper->getRefundMethodLabel($rma->getData('refund_method'))
                ));
            }
        }
    }
}