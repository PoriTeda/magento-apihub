<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;

/**
 * Class RefundValidator
 */
class RefundValidator extends AbstractResponseValidator
{
    /**
     * @inheritdoc
     */
    public function validate(array $validationSubject)
    {
        $response = SubjectReader::readResponse($validationSubject);
        $amount = SubjectReader::readAmount($validationSubject);

        $paymentDO = SubjectReader::readPayment($validationSubject);
        $transactionId = $paymentDO->getPayment()->getParentTransactionId();

        $errorMessages = [];
        $validationResult = $this->validateErrors($response)
            && $this->validateTotalAmount($response, $amount)
            && $this->validateTransactionStatus($response)
            && $this->validateTransactionId($response)
            && $this->validateResponseCode($response)
            && $this->validateResponseMessage($response)
            && $this->validateAuthorisationCode($response)
            && $this->validateRefundData($response, $transactionId);

        if (!$validationResult) {
            $errorMessages = [__('Transaction has been declined. Please try again later.')];
        }

        return $this->createResult($validationResult, $errorMessages);
    }

    /**
     * Validate total amount.
     *
     * @param array $response
     * @param array|number|string $amount
     * @return bool
     */
    protected function validateTotalAmount(array $response, $amount)
    {
        return isset($response[self::REFUND][self::TOTAL_AMOUNT])
        && (string)($response[self::REFUND][self::TOTAL_AMOUNT] / 100) === (string)$amount;
    }

    /**
     * Validate refund data.
     *
     * @param array $response
     * @param int $transactionId
     * @return bool
     */
    private function validateRefundData(array $response, $transactionId)
    {
        return !empty($response[self::REFUND][self::TRANSACTION_ID])
            && (int)$response[self::REFUND][self::TRANSACTION_ID] == (int)$transactionId;
    }
}
