<?php
namespace Riki\AdvancedInventory\Model\ReAssignation\ImportHandler;

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
     * @var array
     */
    protected $_uniqueAttributes;

    /**
     * @var array
     */
    protected $_rowData;

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

        foreach ($this->validators as $validator) {
            $validator->setValidator($this);
        }
    }

    /**
     * @param $attrCode
     * @param null $maxLen
     * @return bool
     */
    protected function textValidation($attrCode, $maxLen = null)
    {
        $val = $this->string->cleanString($this->_rowData[$attrCode]);

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
        $this->_rowData = $rowData;

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

        if (!strlen(trim($rowData[$attrCode]))) {
            return true;
        }
        switch ($attrParams['type']) {
            case 'varchar':
            case 'text':
                $valid = $this->textValidation($attrCode, isset($attrParams['len']) ? $attrParams['len'] : null);
                break;
            case 'select':
            case 'boolean':
            case 'multiselect':
                $values = explode(',', $rowData[$attrCode]);
                $valid = true;
                foreach ($values as $value) {
                    $valid = $valid && in_array($value, $attrParams['options']);
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
                break;
            default:
                $valid = true;
                break;
        }

        if ($valid && !empty($attrParams['is_unique'])) {
            if (isset($this->_uniqueAttributes[$attrCode][$rowData[$attrCode]])) {
                $this->_addMessages([RowValidatorInterface::ERROR_DUPLICATE_UNIQUE_ATTRIBUTE]);
                return false;
            }
            $this->_uniqueAttributes[$attrCode][$rowData[$attrCode]] = $rowData[$attrCode];
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

        foreach ($this->_rowData as $attrCode => $attrValue) {
            $attrParams = $this->context->getAttributeProperties($attrCode);
            if ($attrParams) {
                $this->isAttributeValid($attrCode, $attrParams, $this->_rowData);
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
        $this->_rowData = $value;
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
     * @param \Riki\AdvancedInventory\Model\ReAssignation\ImportHandler\ReAssignation $context
     * @return $this
     */
    public function init($context)
    {
        $this->context = $context;
        foreach ($this->validators as $validator) {
            $validator->init($context);
        }

        return $this;
    }
}
