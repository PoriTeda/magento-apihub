<?php
namespace Riki\Rma\Model\Config\Source\Rma;

class RefundStatus extends \Riki\Framework\Model\Source\AbstractOption implements \Riki\Rma\Api\Data\Rma\RefundStatusInterface
{
    /**
     * @param $option
     * @return \Magento\Framework\Phrase|string
     */
    public function getLabel($option)
    {
        switch ($option) {
            case self::WAITING_APPROVAL:
                return __('Waiting for approval');

            case self::APPROVED:
                return __('Approved');

            case self::GAC_FEEDBACK_REJECTED_NEED_ADJUSTMENT:
                return __('GAC feedback - Rejected (adjustment needed)');

            case self::GAC_FEEDBACK_REJECTED_NO_NEED_REFUND:
                return __('GAC feedback - Rejected (No need refund)');

            case self::GAC_FEEDBACK_REVIEWED_BY_CC:
                return __('GAC feedback - Reviewed by CC');

            case self::GAC_FEEDBACK_APPROVED_BY_CC:
                return __('GAC feedback - Approved by CC');

            case self::CARD_COMPLETED:
                return __('Card Completed');

            case self::MANUALLY_CARD_COMPLETED:
                return __('Manually Card Completed');

            case self::SENT_TO_AGENT:
                return __('Sent to Agent');

            case self::BT_COMPLETED:
                return __('BT Completed');

            case self::CHANGE_TO_CHECK:
                return __('Change to check');

            case self::CHANGE_TO_BANK:
                return __('Change to Bank');

            case self::CHECK_ISSUED:
                return __('Check issued');
        }

        return parent::getLabel($option);
    }
}