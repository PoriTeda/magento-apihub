<?php

namespace Riki\NpAtobarai\Gateway\Validator\Cancel;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Riki\NpAtobarai\Gateway\Response\GetPaymentStatus\PaidHandler;

class ResultValidator extends AbstractValidator
{
    /**
     * @param array $validationSubject
     *
     * @return ResultInterface|void
     */
    public function validate(array $validationSubject)
    {
        $fails = [];
        $response = SubjectReader::readResponse($validationSubject);
        if (isset($response[PaidHandler::SUCCESSES])) {
            $successes = $response[PaidHandler::SUCCESSES];
            if (is_array($successes)) {
                foreach ($successes as $success) {
                    if (empty($success['np_transaction_id'])) {
                        $fails['notAvailable'] = __('Result Missing Response Data.');
                    }
                }
            } else {
                $fails['notAvailable'] = __('Result Response Data is wrong type.');
            }
        }
        return $fails ? $this->createResult(false, $fails) : $this->createResult(true);
    }
}
