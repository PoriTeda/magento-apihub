<?php
namespace Riki\Catalog\Helper;

use Magento\Framework\Exception\InputException;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

class SapImportValidator extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $requiredFields = [];

    /**
     * @var ProductAttributeOption
     */
    protected $productAttributeOption;

    /**
     * @var \Magento\Catalog\Api\ProductTypeListInterface
     */
    protected $productTypeList;

    /**
     * @var \Magento\Catalog\Api\AttributeSetRepositoryInterface
     */
    protected $attributeSetRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaInterface
     */
    protected $searchCriteria;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * SapImportValidator constructor.
     *
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @param \Magento\Catalog\Api\ProductTypeListInterface $productTypeList
     * @param \Magento\Catalog\Api\AttributeSetRepositoryInterface $attributeSetRepository
     * @param ProductAttributeOption $productAttributeOption
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Riki\Framework\Helper\Search $searchHelper,
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria,
        \Magento\Catalog\Api\ProductTypeListInterface $productTypeList,
        \Magento\Catalog\Api\AttributeSetRepositoryInterface $attributeSetRepository,
        \Riki\Catalog\Helper\ProductAttributeOption $productAttributeOption,
        \Magento\Framework\App\Helper\Context $context
    ){
        $this->searchHelper = $searchHelper;
        $this->searchCriteria = $searchCriteria;
        $this->productTypeList = $productTypeList;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->productAttributeOption = $productAttributeOption;
        parent::__construct($context);
    }


    /**
     * Set required fields
     *
     * @param $fields
     * @return $this
     */
    public function setRequiredFields($fields)
    {
        $this->requiredFields = $fields;
        return $this;
    }

    /**
     * Get required fields
     *
     * @return array
     */
    public function getRequiredFields()
    {
        return $this->requiredFields;
    }

    /**
     * Filter inputs data
     *
     * @param array $data
     * @return array
     */
    public function filter($data = [])
    {
        foreach ($data as $key => $value) {
            $method = 'filter' . $this->upperCase($key);
            if (method_exists($this, $method)) {
                $data[$key] = $this->$method($value);
            }

            if (is_null($data[$key])) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * Validate inputs data
     *
     * @param array $data
     * @return bool
     * @throws InputException
     */
    public function validate($data = [])
    {
        $exception = new InputException();

        $requiredFields = $this->getRequiredFields();
        $missingFields = array_diff($requiredFields, array_keys($data));
        foreach ($missingFields as $field) {
            $exception->addError(__(InputException::REQUIRED_FIELD, [
                'fieldName' => $field
            ]));
        }

        foreach ($data as $key => $value) {
            if (in_array($key, $requiredFields)) {
                if (!\Zend_Validate::is($value, 'NotEmpty', ['type' => [\Zend_Validate_NotEmpty::STRING, \Zend_Validate_NotEmpty::NULL]])
                    && $value !== false
                ) {
                    $exception->addError(__(InputException::REQUIRED_FIELD, [
                        'fieldName' => $key
                    ]));
                }
            }

            if (is_null($value)) {
                continue;
            }

            $method = 'validate' . $this->upperCase($key);
            if (method_exists($this, $method)) {
                if (!$this->$method($value)) {
                    $exception->addError(__(InputException::INVALID_FIELD_VALUE, [
                        'fieldName' => $key
                    ]));
                }
            }
        }

        if ($exception->wasErrorAdded()) {
            throw $exception;
        }

        return true;
    }

    /**
     * Convert underscore to upper case
     *
     * abc_cde => AbcCde
     *
     * @param $str
     * @return string
     */
    public function upperCase($str)
    {
        $peace = array_map(function ($v) {
            return ucfirst($v);
        }, explode('_', trim($str, '_')));

        $result = implode('', $peace);

        return $result;
    }

    /**
     * get/set value for option attribute (input type: select, multiselect, radio, checkbox)
     *
     * @param $attributeCode
     * @param $label
     * @param $type
     *
     * @return mixed
     */
    public function handleForOptionAttribute($attributeCode, $label, $type = 'value')
    {
        $value = $this->productAttributeOption->hasOptionLabel($attributeCode, $label, $type);
        if ($value) {
            return $value;
        }

        if (!strlen($label)) {
            return null;
        }

        try {
            if ($this->productAttributeOption->addOptionLabel($attributeCode, $label)) {
                $value = $this->productAttributeOption->hasOptionLabel($attributeCode, $label);
                if ($value) {
                    return $value;
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Filter function
     *
     * @param $value
     * @return mixed
     */
    public function filterMaterialType($value)
    {
        return $this->handleForOptionAttribute(\Riki\Catalog\Api\Data\SapProductInterface::MATERIAL_TYPE, $value, 'label');
    }

    /**
     * Filter function
     *
     * @param $value
     * @return mixed
     */
    public function filterWeightUnit($value)
    {
        return $this->handleForOptionAttribute(\Riki\Catalog\Api\Data\SapProductInterface::WEIGHT_UNIT, $value);
    }

    /**
     * Filter function
     *
     * @param $value
     * @return mixed
     */
    public function filterDimensionUnit($value)
    {
        $convertTable = [
            'MMT' => 'mm',
            'CMT' => 'cm',
            'MTR' => 'm'
        ];

        $value = isset($convertTable[$value])
            ? $convertTable[$value]
            : $value;

        return $this->handleForOptionAttribute(\Riki\Catalog\Api\Data\SapProductInterface::DIMENSION_UNIT, $value);
    }

    /**
     * Filter function
     *
     * @param $value
     * @return mixed
     */
    public function filterPhCode($value)
    {
        return $this->handleForOptionAttribute(\Riki\Catalog\Api\Data\SapProductInterface::PH_CODE, $value);
    }

    /**
     * Filter function
     *
     * @param $value
     * @return mixed
     */
    public function filterPh1Description($value)
    {
        return $this->handleForOptionAttribute(\Riki\Catalog\Api\Data\SapProductInterface::PH1_DESCRIPTION, $value);
    }

    /**
     * Filter function
     *
     * @param $value
     * @return mixed
     */
    public function filterPh2Description($value)
    {
        return $this->handleForOptionAttribute(\Riki\Catalog\Api\Data\SapProductInterface::PH2_DESCRIPTION, $value);
    }

    /**
     * Filter function
     *
     * @param $value
     * @return mixed
     */
    public function filterPh3Description($value)
    {
        return $this->handleForOptionAttribute(\Riki\Catalog\Api\Data\SapProductInterface::PH3_DESCRIPTION, $value);
    }

    /**
     * Filter function
     *
     * @param $value
     * @return mixed
     */
    public function filterPh4Description($value)
    {
        return $this->handleForOptionAttribute(\Riki\Catalog\Api\Data\SapProductInterface::PH4_DESCRIPTION, $value);
    }

    /**
     * Filter function
     *
     * @param $value
     * @return mixed
     */
    public function filterPh5Description($value)
    {
        return $this->handleForOptionAttribute(\Riki\Catalog\Api\Data\SapProductInterface::PH5_DESCRIPTION, $value);
    }

    /**
     * Filter function
     *
     * @param $value
     * @return mixed
     */
    public function filterBhSap($value)
    {
        return $this->handleForOptionAttribute(\Riki\Catalog\Api\Data\SapProductInterface::BH_SAP, $value);
    }

    /**
     * Filter function
     *
     * @param $value
     * @return mixed
     */
    public function filterUnitSap($value)
    {
        return $this->handleForOptionAttribute(\Riki\Catalog\Api\Data\SapProductInterface::UNIT_SAP, $value, 'label');
    }

    /**
     * Filter function
     *
     * @param $value
     * @return mixed
     */
    public function filterSalesOrganization($value)
    {
        return $this->handleForOptionAttribute(\Riki\Catalog\Api\Data\SapProductInterface::SALES_ORGANIZATION, $value);
    }

    /**
     * Validate function
     *
     * @param $value
     * @return bool
     */
    public function validateTypeId($value)
    {
        $types = $this->productTypeList->getProductTypes();
        foreach ($types as $type) {
            if ($value == $type->getName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate function
     *
     * @param $value
     * @return bool
     */
    public function validateAttributeSetId($value)
    {
        $attributeSets = $this->searchHelper
            ->getAll()
            ->execute($this->attributeSetRepository);
        foreach ($attributeSets as $attributeSet) {
            if ($value == $attributeSet->getAttributeSetId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter status
     *
     * @param $value
     *
     * @return mixed
     */
    public function filterStatus($value)
    {
        if (is_null($value)
            || !in_array($value, [Status::STATUS_DISABLED, Status::STATUS_ENABLED])
        ) {
            return Status::STATUS_DISABLED;
        }

        return $value;
    }
}

