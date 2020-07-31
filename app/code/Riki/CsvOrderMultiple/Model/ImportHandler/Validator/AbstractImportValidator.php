<?php
namespace Riki\CsvOrderMultiple\Model\ImportHandler\Validator;

use Magento\Framework\Validator\AbstractValidator;
use \Riki\CsvOrderMultiple\Model\ImportHandler\RowValidatorInterface;

abstract class AbstractImportValidator extends AbstractValidator implements RowValidatorInterface
{
    /**
     * @var \Riki\CsvOrderMultiple\Model\ImportHandler\Order
     */
    protected $context;

    /** @var  \Riki\CsvOrderMultiple\Model\ImportHandler\Validator  */
    protected $validator;

    /**
     * @param \Riki\CsvOrderMultiple\Model\ImportHandler\Order $context
     * @return $this
     */
    public function init($context)
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @param \Riki\CsvOrderMultiple\Model\ImportHandler\Validator $validator
     */
    public function setValidator(\Riki\CsvOrderMultiple\Model\ImportHandler\Validator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param string $attrCode
     * @param array $rowData
     * @return bool
     */
    public function isRequiredAttributeValid($attrCode, array $rowData)
    {
        return isset($rowData[$attrCode]) && strlen(trim($rowData[$attrCode]));
    }

    /**
     * @param array $requiredFields
     * @param array $value
     * @return bool
     */
    protected function validateRequiredFields(array $requiredFields, array $value)
    {
        $result = true;

        foreach ($requiredFields as $field) {
            $fieldProperties = $this->context->getAttributeProperties($field);
            $fieldProperties['is_required'] = true;

            if (!$this->validator->isRequiredAttributeValid($field, $fieldProperties, $value)) {
                $this->_addMessages(
                    [
                        sprintf(
                            $this->context->retrieveMessageTemplate(
                                RowValidatorInterface::ERROR_VALUE_IS_REQUIRED
                            ),
                            $field
                        )
                    ]
                );

                $result = false;
            }
        }

        return $result;
    }
}
