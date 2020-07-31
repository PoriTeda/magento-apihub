<?php
namespace Riki\NpAtobarai\Gateway\Validator\GetPaymentStatus;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Riki\NpAtobarai\Gateway\Response\GetPaymentStatus\PaidHandler;
use Riki\NpAtobarai\Model\Config\Source\TransactionPaymentStatus;

class ResultValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $allowedStatus = [
        TransactionPaymentStatus::NOT_PAID_YET_STATUS_VALUE,
        TransactionPaymentStatus::PAID_STATUS_VALUE,
        TransactionPaymentStatus::SECRET_STATUS_VALUE
    ];

    /**
     * @param array $validationSubject
     *
     * @return ResultInterface|void
     */
    public function validate(array $validationSubject)
    {
        $response = SubjectReader::readResponse($validationSubject);
        $successes = [];
        if (isset($response[PaidHandler::SUCCESSES])) {
            $successes = $response[PaidHandler::SUCCESSES];
        }
        $fails = [];
        foreach ($successes as $success) {
            if (!isset($success['payment_status']) || !isset($success['np_transaction_id'])) {
                $fails['notAvailable'] = __('Result Missing Response Data.');
                continue;
            }
            if (!in_array($success['payment_status'], $this->allowedStatus)) {
                $fails['notAllowed'] = __('Result Payment Status is not allowed.');
                continue;
            }
            if ($success['payment_status'] == TransactionPaymentStatus::PAID_STATUS_VALUE
                && !isset($success['customer_payment_date'])
            ) {
                $fails['paymentStatusNotAvailable'] = __('customer_payment_date is not available.');
            }
        }
        if (empty($fails)) {
            return $this->createResult(true);
        } else {
            return $this->createResult(false, $fails);
        }
    }
}
