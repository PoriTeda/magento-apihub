<?php

namespace Riki\SubscriptionCourse\Model\ImportHandler;

use \Magento\Framework\Validator\AbstractValidator;
use \Riki\SubscriptionCourse\Model\ImportHandler\SubscriptionCourse;

class Validator extends AbstractValidator implements RowValidatorInterface
{
    /**
     * @var RowValidatorInterface[]|AbstractValidator[]
     */
    protected $validators = [];

    /**
     * @var \Magento\CatalogImportExport\Model\Import\Product
     */
    protected $context;

    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;

    /**
     * @var array
     */
    protected $uniqueAttributes;

    /**
     * @var array
     */
    protected $rowData;

    /**
     * @var bool
     */
    protected $isListProductValid = true;

    /**
     * @var array
     */
    protected $deliveryTypeListProduct = [];

    /**
     * @var array
     */
    protected $lisListProductAvailability = [];

    /**
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param RowValidatorInterface[] $validators
     */
    public function __construct(
        \Magento\Framework\Stdlib\StringUtils $string,
        $validators = []
    ) {
        $this->string = $string;
        $this->validators = $validators;
    }

    /**
     * @param $attrCode
     * @param null $maxLen
     * @return bool
     */
    protected function textValidation($attrCode, $maxLen = null)
    {
        $val = $this->string->cleanString($this->rowData[$attrCode]);

        $valid = true;

        if ($maxLen) {
            $valid = $this->string->strlen($val) <= $maxLen;
        }

        if (!$valid) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(RowValidatorInterface::ERROR_EXCEEDED_MAX_LENGTH),
                        $attrCode,
                        $maxLen
                    )
                ]
            );
        }
        return $valid;
    }

    /**
     * @param $attrCode
     * @param $type
     * @param $unsigned
     * @return bool
     */
    protected function numericValidation($attrCode, $type, $unsigned)
    {
        $val = trim($this->rowData[$attrCode]);
        $valid = is_numeric($val);

        if ($valid) {
            switch ($type) {
                case 'int':
                    $valid = (int)$val;
                    break;
                case 'smallint':
                    $valid = (int)$val && $this->string->strlen($val) <= 5;
                    break;
                case 'decimal':
                    $value = explode('.', $val);
                    if (isset($value[0]) && isset($value[1])) {
                        $valid = $this->string->strlen($value[0]) <= 12 && $this->string->strlen($value[1]) <= 4;
                    }
                    break;
            }
        }

        if (!$valid) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(RowValidatorInterface::ERROR_INVALID_ATTRIBUTE_TYPE),
                        $attrCode,
                        $type
                    )
                ]
            );
        }

        if ($unsigned && $val < 0) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(RowValidatorInterface::ERROR_VALUE_IS_POSITIVE_NUMBER),
                        $attrCode
                    )
                ]
            );
        }

        return true;
    }

    /**
     * @param $attrCode
     * @return int
     */
    protected function wbsValidation($attrCode)
    {
        $val = $this->string->cleanString($this->rowData[$attrCode]);

        $valid = preg_match('/^AC\-\d{8}$/', $val);

        if (!$valid) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(RowValidatorInterface::ERROR_INVALID_WBS_FORMAT),
                        $val
                    )
                ]
            );
        }

        return $valid;
    }

    /**
     * @param string $attrCode
     * @param array $attributeParams
     * @param array $rowData
     * @return bool
     */
    public function isRequiredAttributeValid($attrCode, array $attributeParams, array $rowData)
    {
        $doCheck = false;
        if (isset($attributeParams['is_required']) && $attributeParams['is_required']) {
            $doCheck = true;
        }

        return $doCheck ? isset($rowData[$attrCode]) && strlen(trim($rowData[$attrCode])) : true;
    }

    /**
     * @param string $attrCode
     * @param array $attrParams
     * @param array $rowData
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function isAttributeValid($attrCode, array $attrParams, array $rowData)
    {
        $this->rowData = $rowData;

        if (!$this->isRequiredAttributeValid($attrCode, $attrParams, $rowData)) {
            $valid = false;
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_VALUE_IS_REQUIRED
                        ),
                        $attrCode
                    )
                ]
            );
            return $valid;
        }

        if (empty(trim($rowData[$attrCode]))) {
            return true;
        }

        $valid = $this->validateTypeData($rowData, $attrParams, $attrCode);
        if ($valid && !empty($attrParams['is_unique'])) {
            if (isset($this->uniqueAttributes[$attrCode][$rowData[$attrCode]])
                && ($this->uniqueAttributes[$attrCode][$rowData[$attrCode]] != $rowData[SubscriptionCourse::COL_COURSE_CODE])
            ) {
                $this->_addMessages([RowValidatorInterface::ERROR_DUPLICATE_UNIQUE_ATTRIBUTE]);
                return false;
            }
            $this->uniqueAttributes[$attrCode][$rowData[$attrCode]] = $rowData[SubscriptionCourse::COL_COURSE_CODE];
        }
        return (bool)$valid;
    }

    /**
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function isValidAttributes()
    {
        $this->_clearMessages();

        foreach ($this->rowData as $attrCode => $attrValue) {
            $attrParams = $this->context->getAttributeProperties($attrCode);
            if ($attrParams) {
                $this->isAttributeValid($attrCode, $attrParams, $this->rowData);
            }
        }
        if ($this->getMessages()) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        $this->rowData = $value;
        $this->_clearMessages();
        $returnValue = $this->isValidAttributes();
        foreach ($this->validators as $validator) {
            if (!$validator->isValid($value)) {
                $returnValue = false;
                $this->_addMessages($validator->getMessages());
            }
        }
        return $returnValue;
    }

    /**
     * @param \Magento\CatalogImportExport\Model\Import\Product $context
     * @return $this
     */
    public function init($context)
    {
        foreach ($this->validators as $validator) {
            $validator->setValidator($this);
        }

        $this->context = $context;
        foreach ($this->validators as $validator) {
            $validator->init($context);
        }
    }

    /**
     * @param $rowData
     * @param $attrParams
     * @param $attrCode
     * @return bool
     */
    public function validateTypeData($rowData, $attrParams, $attrCode)
    {
        $valid = true;
        $type = $attrParams['type'];

        if ($type == 'varchar' || $type == 'text') {
            $valid = $this->textValidation($attrCode, isset($attrParams['len']) ? $attrParams['len'] : null);
        } elseif ($type == 'decimal' || $type == 'int' || $type == 'smallint') {
            $valid = $this->numericValidation(
                $attrCode,
                $attrParams['type'],
                isset($attrParams['unsigned']) ? $attrParams['unsigned'] : null
            );
        } elseif ($type == 'select') {
            $valid = $this->validateMultiSelect($rowData, $attrParams, $attrCode);
        } elseif ($type == 'date') {
            $valid = $this->validateDate($rowData, $attrCode);
        } elseif ($type == 'json') {
            $valid = $this->validateJson($rowData, $attrCode);
        } elseif ($type == 'wbs') {
            $valid = $this->textValidation($attrCode, isset($attrParams['len']) ? $attrParams['len'] : null) &&
                $this->wbsValidation($attrCode);
        }

        return $valid;
    }

    /**
     * @param $rowData
     * @param $attrParams
     * @param $attrCode
     * @return bool
     */
    public function validateMultiSelect($rowData, $attrParams, $attrCode)
    {
        $values = is_array($rowData[$attrCode]) ? explode(',', $rowData[$attrCode]) : [$rowData[$attrCode]];
        $valid = true;
        foreach ($values as $value) {
            $valid = $valid && in_array(strtolower($value), $attrParams['options']);
        }
        if (!$valid) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_INVALID_ATTRIBUTE_OPTION
                        ),
                        $attrCode
                    )
                ]
            );
        }
        return $valid;
    }

    /**
     * @param $rowData
     * @param $attrCode
     * @return bool
     */
    public function validateDate($rowData, $attrCode)
    {
        $val = trim($rowData[$attrCode]);
        $valid = $val == date('Y-m-d H:i:s', strtotime($val));
        if (!$valid) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_INVALID_DATE_FORMAT
                        ),
                        $attrCode
                    )
                ]
            );
        }
        return $valid;
    }

    /**
     * @param $rowData
     * @param $attrCode
     * @return bool
     */
    public function validateJson($rowData, $attrCode)
    {
        $val = trim($rowData[$attrCode]);
        $valid = json_decode($val, true);
        if (!$valid) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_INVALID_JSON_FORMAT
                        ),
                        $attrCode
                    )
                ]
            );
        }
        return $valid;
    }
}
