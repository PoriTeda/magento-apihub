<?php

namespace Riki\NpAtobarai\Gateway\Validator\ShippedOutRegistration;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Riki\NpAtobarai\Gateway\Response\GetPaymentStatus\PaidHandler;

class ResultValidator extends AbstractValidator
{
    /**
     * Performs domain-related validation for business object
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $response = SubjectReader::readResponse($validationSubject);
        if (!isset($response[PaidHandler::SUCCESSES])) {
            return $this->createResult(true);
        }
        $successes = $response[PaidHandler::SUCCESSES];

        $fails = [];
        foreach ($successes as $success) {
            if (!isset($success['np_transaction_id'])) {
                $fails['notAvailable'] = __('Result Missing Response Data.');
                continue;
            }
        }
        if (empty($fails)) {
            return $this->createResult(true);
        } else {
            return $this->createResult(false, $fails);
        }
    }
}
