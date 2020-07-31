<?php
namespace Riki\CsvOrderMultiple\Model\ImportHandler\Validator;

class FreeWbs extends AbstractImportValidator
{

    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        $this->_clearMessages();

        $result = true;

        if (intval($value['free_delivery'])) {
            $result = $this->validateRequiredFields([
                'free_delivery_wbs'
            ], $value);
        }

        if (intval($value['cod_free_free'])) {
            $result =$this->validateRequiredFields([
                'free_payment_wbs'
            ], $value) && $result;
        }

        return $result;
    }
}
