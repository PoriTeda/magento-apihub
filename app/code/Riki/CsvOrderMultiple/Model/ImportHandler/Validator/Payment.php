<?php
namespace Riki\CsvOrderMultiple\Model\ImportHandler\Validator;

use Riki\CsvOrderMultiple\Model\ImportHandler\RowValidatorInterface;

class Payment extends AbstractImportValidator
{

    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        $this->_clearMessages();

        $result = true;

        $paymentMethod = $value['payment_method'];

        if ($paymentMethod == 'invoicedbasedpayment') {
            $result = $this->validateRequiredFields([
                'business_code'
            ], $value);
        }

        //

        if (in_array($value['order_type'], [
            \Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_REPLACEMENT,
            \Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_FREE_SAMPLE
        ]) && $value['payment_method'] != 'free') {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_INVALID_PAYMENT_METHOD
                        ),
                        $value['payment_method']
                    )
                ]
            );

            $result = false;
        }

        return $result;
    }
}
