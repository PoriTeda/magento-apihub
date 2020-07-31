<?php
namespace Riki\Rma\Model\Config\Source\Rma;

class ReturnStatus extends \Riki\Framework\Model\Source\AbstractOption implements \Riki\Rma\Api\Data\Rma\ReturnStatusInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel($option)
    {
        switch ($option) {
            case self::CLOSED:
                return __('Closed');

            case self::CREATED:
                return __('Created');

            case self::REJECTED_BY_CC:
                return __('Rejected by CC');

            case self::REVIEWED_BY_CC:
                return __('Reviewed by CC');

            case self::CC_FEEDBACK_REJECTED:
                return __('CC feedback - Rejected');

            case self::APPROVED_BY_CC:
                return __('Approved by CC');

            case self::CS_FEEDBACK_REJECTED:
                return __('CS feedback - Rejected');

            case self::COMPLETED:
                return __('Completed');
        }

        return parent::getLabel($option);
    }
}