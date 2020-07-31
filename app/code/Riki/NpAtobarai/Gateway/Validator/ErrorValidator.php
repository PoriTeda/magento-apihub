<?php
namespace Riki\NpAtobarai\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Riki\NpAtobarai\Gateway\Response\GetPaymentStatus\ErrorHandler;

class ErrorValidator extends AbstractValidator
{
    /**
     * @param array $validationSubject
     *
     * @return ResultInterface|void
     */
    public function validate(array $validationSubject)
    {
        $response = SubjectReader::readResponse($validationSubject);
        $errors = [];
        if (isset($response[ErrorHandler::ERRORS])) {
            $errors = $response[ErrorHandler::ERRORS];
        }
        $fails = [];
        if (is_array($errors)) {
            foreach ($errors as $error) {
                if (!isset($error['codes']) || !isset($error['id'])) {
                    $fails['notAvailable'] = __('Error Missing Response Data.');
                    continue;
                }

                if (!is_array($error['codes'])) {
                    $fails['wrongFormat'] = __('Error Code is not Array.');
                }
            }
        } else {
            $fails['notAvailable'] = __('Result Response Data is wrong type.');
        }
        if (empty($fails)) {
            return $this->createResult(true);
        } else {
            return $this->createResult(false, $fails);
        }
    }
}
