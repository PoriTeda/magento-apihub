<?php
namespace Riki\CsvOrderMultiple\Model\ImportHandler\Validator;

use \Riki\Sales\Model\Config\Source\OrderType as OrderTypeSource;
use \Riki\CsvOrderMultiple\Model\ImportHandler\RowValidatorInterface;

class OrderType extends AbstractImportValidator
{


    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        $this->_clearMessages();

        $orderType = $value['order_type'];

        switch ($orderType) {
            case OrderTypeSource::ORDER_TYPE_NORMAL:
                $result = true;
                break;
            case OrderTypeSource::ORDER_TYPE_REPLACEMENT:
                $result = $this->validateReplacementOrder($value);
                break;
            case OrderTypeSource::ORDER_TYPE_FREE_SAMPLE:
                $result = $this->validateFreeSampleOrder($value);
                break;
            default:
                $this->_addMessages(
                    [
                        sprintf(
                            $this->context->retrieveMessageTemplate(RowValidatorInterface::ERROR_INVALID_ATTRIBUTE_OPTION),
                            'order_type'
                        )
                    ]
                );
                $result = false;
        }

        return $result;
    }

    /**
     * @param array $value
     * @return bool
     */
    protected function validateReplacementOrder(array $value)
    {
        return $this->validateRequiredFields([
            'original_order_id',
            'siebel_enquiry_id',
            'replacement_reason'
        ], $value);
    }

    /**
     * @param array $value
     * @return bool
     */
    protected function validateFreeSampleOrder(array $value)
    {
        return $this->validateRequiredFields([
            'order_wbs'
        ], $value);
    }
}
