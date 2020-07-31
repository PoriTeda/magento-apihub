<?php

namespace Riki\CsvOrderMultiple\Model\ImportHandler;

use \Magento\Framework\Validator\AbstractValidator;

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
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

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
     * Validator constructor.
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param RowValidatorInterface[] $validators
     */
    public function __construct(
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        $validators = []
    ) {
        $this->string = $string;
        $this->validators = $validators;
        $this->customerRepository = $customerRepository;
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
     * @param mixed $attrCode
     * @param string $type
     * @return bool
     */
    protected function numericValidation($attrCode, $type)
    {
        $val = trim($this->_owData[$attrCode]);
        if ($type == 'int') {
            $valid = (string)(int)$val === $val;
        } else {
            $valid = is_numeric($val);
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
        return $valid;
    }

    /**
     * @param $attrCode
     * @return bool|mixed
     */
    protected function emailValidation($attrCode)
    {
        $val = $this->string->cleanString($this->rowData[$attrCode]);

        // skip format checking if customer is already existed.
        try {
            $customer = $this->customerRepository->get($val);
            $valid = true;
        } catch (\Exception $exception) {
            $valid = filter_var($val, FILTER_VALIDATE_EMAIL);

            if (!$valid) {
                $this->_addMessages(
                    [
                        sprintf(
                            $this->context->retrieveMessageTemplate(RowValidatorInterface::ERROR_INVALID_EMAIL),
                            $val
                        )
                    ]
                );
            }
        }

        return $valid;
    }

    /**
     * @param $attrCode
     * @return int
     */
    protected function phoneNumberValidation($attrCode)
    {
        $val = $this->rowData[$attrCode];

        $valid = preg_match('/^\d{10,11}$/', $val);

        if (!$valid) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_INVALID_PHONE_NUMBER_FORMAT
                        ),
                        $val
                    )
                ]
            );
        }

        return $valid;
    }

    /**
     * @param $attrCode
     * @return int
     */
    protected function postCodeValidation($attrCode)
    {
        $val = $this->string->cleanString($this->rowData[$attrCode]);

        $valid = preg_match('/^\d{3}\-\d{4}$/', $val);

        if (!$valid) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(RowValidatorInterface::ERROR_INVALID_ZIP_CODE_FORMAT),
                        $val
                    )
                ]
            );
        }

        return $valid;
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
            if (isset($this->uniqueAttributes[$attrCode][$rowData[$attrCode]]) &&
                ($this->uniqueAttributes[$attrCode][$rowData[$attrCode]] != $rowData[Order::COL_ORIGINAL_UNIQUE_ID])
            ) {
                $this->_addMessages([RowValidatorInterface::ERROR_DUPLICATE_UNIQUE_ATTRIBUTE]);
                return false;
            }
            $this->uniqueAttributes[$attrCode][$rowData[$attrCode]] = $rowData[Order::COL_ORIGINAL_UNIQUE_ID];
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
     * @param $result
     */
    public function setIsListProductValid($result)
    {
        $this->isListProductValid = $result;
    }

    /**
     * @return bool
     */
    public function getIsListProductValid()
    {
        return $this->isListProductValid;
    }

    /**
     * @param $result
     */
    public function setListProductAvailability($result)
    {
        $this->lisListProductAvailability = $result;
    }

    /**
     * @return array
     */
    public function getListProductAvailability()
    {
        return $this->lisListProductAvailability;
    }

    /**
     * @param $deliveryType
     */
    public function setDeliveryTypeListProductImport($deliveryType)
    {
        $this->deliveryTypeListProduct = $deliveryType;
    }

    /**
     * @return array
     */
    public function getDeliveryTypeListProductImport()
    {
        return $this->deliveryTypeListProduct;
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
        if ($type == 'zipcode') {
            $valid = $this->textValidation($attrCode, isset($attrParams['len']) ? $attrParams['len'] : null) &&
                $this->postCodeValidation($attrCode);
        } elseif ($type == 'wbs') {
            $valid = $this->textValidation($attrCode, isset($attrParams['len']) ? $attrParams['len'] : null) &&
                $this->wbsValidation($attrCode);
        } elseif ($type == 'email') {
            $valid = $this->textValidation($attrCode, isset($attrParams['len']) ? $attrParams['len'] : null) &&
                $this->emailValidation($attrCode);
        } elseif ($type == 'phone_number') {
            $valid = $this->textValidation($attrCode, isset($attrParams['len']) ? $attrParams['len'] : null) &&
                $this->phoneNumberValidation($attrCode);
        } elseif ($type == 'varchar' || $type == 'text') {
            $valid = $this->textValidation($attrCode, isset($attrParams['len']) ? $attrParams['len'] : null);
        } elseif ($type == 'decimal' || $type == 'int') {
            $valid = $this->numericValidation($attrCode, $attrParams['type']);
        } elseif ($type == 'select' || $type == 'boolean' || $type == 'multiselect') {
            $valid = $this->validateMultiSelect($rowData, $attrParams, $attrCode);
        } elseif ($type == 'date') {
            $valid = $this->validateDate($rowData, $attrCode);
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
        $values = explode(',', $rowData[$attrCode]);
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
        $valid = $val == date('Y/m/d', strtotime($val));
        if (!$valid) {
            $this->_addMessages([
                sprintf(
                    $this->context->retrieveMessageTemplate(
                        RowValidatorInterface::ERROR_INVALID_DATE_FORMAT
                    ),
                    $attrCode
                )
            ]);
        }
        return $valid;
    }
}
