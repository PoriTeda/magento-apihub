<?php

namespace Riki\Rma\Plugin\Rma;

use Magento\Framework\Exception\LocalizedException;

class ValidateFinalReturnRefundAmount
{
    /**
     * Validate total_return_amount_adjusted
     * @param $subject
     * @param $data
     * @return array
     * @throws LocalizedException
     */
    public function beforeSaveRma($subject, $data)
    {
        if (isset($data['total_return_amount_adjusted']) && (float)$data['total_return_amount_adjusted'] < 0) {
            throw new LocalizedException(__('Final return / Refund amount is not be a negative value'));
        }

        return [$data];
    }
}
