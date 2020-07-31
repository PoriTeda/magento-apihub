<?php
namespace Riki\NpAtobarai\Gateway\Validator\Registration;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;

class ResultValidator extends AbstractValidator
{
    /**
     * @param array $validationSubject
     *
     * @return ResultInterface|void
     */
    public function validate(array $validationSubject)
    {
        $response = SubjectReader::readResponse($validationSubject);
        $successes = [];
        if (isset($response['results'])) {
            $successes = $response['results'];
        }
        $fails = [];
        foreach ($successes as $resultItem) {
            $item = array_flip(['shop_transaction_id', 'np_transaction_id', 'authori_result']);
            if (array_diff_key($item, $resultItem)) {
                $fails['results'] = __('Wrong format data');
                break;
            }
        }
        if (empty($fails)) {
            return $this->createResult(true);
        } else {
            return $this->createResult(false, $fails);
        }
    }
}
