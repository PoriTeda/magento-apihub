<?php
namespace Riki\Rma\Helper;

use Riki\Rma\Api\Data\Rma\ReturnStatusInterface;
use Riki\Rma\Api\Data\Rma\RefundStatusInterface;

class Status extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $depends = [
        ReturnStatusInterface::CLOSED => [
            ReturnStatusInterface::CREATED,
            ReturnStatusInterface::REJECTED_BY_CC,
            ReturnStatusInterface::CC_FEEDBACK_REJECTED,
            ReturnStatusInterface::CS_FEEDBACK_REJECTED
        ],
        ReturnStatusInterface::CREATED => [
            ReturnStatusInterface::APPROVED_BY_CC,
        ],
        ReturnStatusInterface::REJECTED_BY_CC => [
            ReturnStatusInterface::REVIEWED_BY_CC,
            ReturnStatusInterface::CREATED,
        ],
        ReturnStatusInterface::REVIEWED_BY_CC => [
            ReturnStatusInterface::CC_FEEDBACK_REJECTED,
            ReturnStatusInterface::CREATED
        ],
        ReturnStatusInterface::CC_FEEDBACK_REJECTED => [
            ReturnStatusInterface::REVIEWED_BY_CC
        ],
        ReturnStatusInterface::CS_FEEDBACK_REJECTED => [
            ReturnStatusInterface::CREATED,
            ReturnStatusInterface::APPROVED_BY_CC
        ],
        ReturnStatusInterface::APPROVED_BY_CC => [
            ReturnStatusInterface::CREATED,
            ReturnStatusInterface::REVIEWED_BY_CC
        ],
        ReturnStatusInterface::COMPLETED => [
            ReturnStatusInterface::APPROVED_BY_CC
        ],
        RefundStatusInterface::WAITING_APPROVAL => [],
        RefundStatusInterface::GAC_FEEDBACK_REJECTED_NEED_ADJUSTMENT => [
            RefundStatusInterface::WAITING_APPROVAL,
            RefundStatusInterface::APPROVED
        ],
        RefundStatusInterface::GAC_FEEDBACK_REJECTED_NO_NEED_REFUND => [
            RefundStatusInterface::WAITING_APPROVAL,
            RefundStatusInterface::APPROVED
        ],
        RefundStatusInterface::APPROVED => [
            RefundStatusInterface::WAITING_APPROVAL
        ],
        RefundStatusInterface::CARD_COMPLETED => [
            RefundStatusInterface::APPROVED
        ],
        RefundStatusInterface::CHANGE_TO_CHECK => [
            RefundStatusInterface::SENT_TO_AGENT,
            RefundStatusInterface::APPROVED
        ],
        RefundStatusInterface::CHANGE_TO_BANK => [
            RefundStatusInterface::SENT_TO_AGENT,
            RefundStatusInterface::APPROVED
        ],
        RefundStatusInterface::SENT_TO_AGENT => [
            RefundStatusInterface::CHANGE_TO_CHECK,
            RefundStatusInterface::CHANGE_TO_BANK,
            RefundStatusInterface::APPROVED
        ],
        RefundStatusInterface::BT_COMPLETED => [
            RefundStatusInterface::SENT_TO_AGENT
        ],
        RefundStatusInterface::CHECK_ISSUED => [
            RefundStatusInterface::CHANGE_TO_CHECK
        ],
        RefundStatusInterface::MANUALLY_CARD_COMPLETED => [
            RefundStatusInterface::CHANGE_TO_CHECK
        ],
    ];

    /**
     * @var string|\Magento\Framework\Phrase
     */
    protected $message;

    /**
     * @var \Riki\Rma\Model\Config\Source\Rma\ReturnStatus
     */
    protected $returnStatusSource;

    /**
     * @var \Riki\Rma\Model\Config\Source\Rma\RefundStatus
     */
    protected $refundStatusSource;

    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $authorization;

    /**
     * Status constructor.
     *
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param \Riki\Rma\Model\Config\Source\Rma\ReturnStatus $returnStatusSource
     * @param \Riki\Rma\Model\Config\Source\Rma\RefundStatus $refundStatusSource
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\AuthorizationInterface $authorization,
        \Riki\Rma\Model\Config\Source\Rma\ReturnStatus $returnStatusSource,
        \Riki\Rma\Model\Config\Source\Rma\RefundStatus $refundStatusSource,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->authorization = $authorization;
        $this->returnStatusSource = $returnStatusSource;
        $this->refundStatusSource = $refundStatusSource;
        parent::__construct($context);
    }

    /**
     * Setter for $message
     *
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Getter for $message
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get label of status
     *
     * @param $status
     * @return \Magento\Framework\Phrase|string
     */
    public function getLabel($status)
    {
        $statuses = (array)$status;
        $sources = [
            $this->returnStatusSource,
            $this->refundStatusSource
        ];
        $labels = [];
        /** @var \Riki\Framework\Model\Source\AbstractOption $source */
        foreach ($sources as $source) {
            foreach ($statuses as $status) {
                $label = $source->getLabel($status);
                if ($label instanceof \Magento\Framework\Phrase) {
                    $labels[] = $label;
                }
            }
        }

        return implode(', ', $labels);
    }

    /**
     * @param $status
     * @return \Magento\Framework\Phrase|string
     */
    public function getRefundStatusLabel($status)
    {
        return $this->refundStatusSource->getLabel($status);
    }

    /**
     * Get allowed status which can be change to status
     *
     * @param $status
     *
     * @return array
     */
    public function getAllowed($status)
    {
        $allowed = isset($this->depends[$status])
            ? $this->depends[$status]
            : [];
        array_unshift($allowed, $status);
        return $allowed;
    }

    /**
     * Get allowed status which can be change to status when Mass Action
     *
     * @param $status
     * @return array|mixed
     */
    public function getAllowedMassAction($status)
    {
        $allowed = isset($this->depends[$status])
            ? $this->depends[$status]
            : [];
        return $allowed;
    }

    /**
     * Can status old change to new ?
     *
     * @param $old
     * @param $new
     * @return bool
     */
    public function isAllowed($old, $new)
    {
        $this->setMessage(__('There are error when update status'));
        $allowed = $this->getAllowed($new);
        $isAllowed = empty($old) ? true : in_array($old, $allowed);
        if (!$isAllowed) {
            $allowedLabel = array_map([$this, 'getLabel'], $allowed);
            $allowedLabel = array_map(function ($v) { return "“{$v}”";}, $allowedLabel);
            $allowedLabel = implode(', ', $allowedLabel);
            $message = 'Unable to change status from “%1” to “%2”, must be status [%3] first!';
            $message = __($message, $this->getLabel($old),  $this->getLabel($new), $allowedLabel);
            $this->setMessage(__($message));
        }

        return $isAllowed;
    }

    /**
     * Get default status
     *
     * @return int
     */
    public function getDefaultNewStatus()
    {
        return ReturnStatusInterface::CREATED;
    }
}
