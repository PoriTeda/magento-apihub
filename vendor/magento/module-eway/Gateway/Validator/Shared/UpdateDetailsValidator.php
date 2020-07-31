<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Gateway\Validator\Shared;

use Magento\Eway\Gateway\Helper;
use Magento\Eway\Gateway\Validator\AbstractResponseValidator;
use Magento\Payment\Gateway\Helper\SubjectReader;

/**
 * Class UpdateDetailsValidator
 */
class UpdateDetailsValidator extends AbstractResponseValidator
{
    /**
     * @inheritdoc
     */
    public function validate(array $validationSubject)
    {
        $response = SubjectReader::readResponse($validationSubject);
        $amount = SubjectReader::readAmount($validationSubject);
        $transactionId = Helper\SubjectReader::readTransactionId($validationSubject);

        $errorMessages = [];
        $validationResult = $this->validateErrors($response)
            && $this->validateTotalAmount($response, $amount)
            && $this->validateTransactionStatus($response)
            && $this->validateResponseCode($response)
            && $this->validateResponseMessage($response)
            && $this->validateAuthorisationCode($response)
            && $this->validateTransactionId($response)
            && $this->validateTransactionConsistency($response, $transactionId);

        if (!$validationResult) {
            $errorMessages = [__('Transaction has been declined. Please try again later.')];
        }

        return $this->createResult($validationResult, $errorMessages);
    }

    /**
     * @param array $response
     * @param array|number|string $amount
     * @return bool
     */
    protected function validateTotalAmount(array $response, $amount)
    {
        return isset($response[self::TOTAL_AMOUNT])
            && (string)($response[self::TOTAL_AMOUNT] / 100) === (string)$amount;
    }

    /**
     * @param array $response
     * @param string $transactionId
     * @return bool
     */
    private function validateTransactionConsistency(array $response, $transactionId)
    {
        return (string)$response[self::TRANSACTION_ID] === (string)$transactionId;
    }
}
