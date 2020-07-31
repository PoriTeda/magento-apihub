<?php
/**
 * Helper Data
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ImportExport\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ImportExport\Helper;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
/**
 * Class Data
 *
 * @category  RIKI
 * @package   Riki\ImportExport\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Data extends \Magento\ImportExport\Helper\Data
{
    /**
     * AttributeRepositoryInterface
     *
     * @var AttributeRepositoryInterface
     */
    protected $attributeRepository;
    /**
     * SearchCriteriaInterface
     *
     * @var SearchCriteriaInterface
     */
    protected $searchCriteriaInterface;
    /**
     * Filter
     *
     * @var Filter
     */
    protected $filter;
    /**
     * FilterGroup
     *
     * @var FilterGroup
     */
    protected $filterGroup;

    /**
     * @var array
     */
    protected $requiredAttributes;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context                 Context
     * @param \Magento\Framework\File\Size          $fileSize                Size
     * @param AttributeRepositoryInterface          $attributeRepository     AttributeRepositoryInterface
     * @param SearchCriteriaInterface               $searchCriteriaInterface SearchCriteriaInterface
     * @param Filter                                $filter                  Filter
     * @param FilterGroup                           $filerGroup              FilterGroup
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\File\Size $fileSize,
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaInterface $searchCriteriaInterface,
        Filter $filter,
        FilterGroup $filerGroup
    ) {
        parent::__construct($context, $fileSize);
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaInterface = $searchCriteriaInterface;
        $this->filter = $filter;
        $this->filterGroup = $filerGroup;
    }

    /**
     * Get All Customer Required Attributes
     *
     * @return array
     */
    protected function _getAllCustomerAddressRequiredAttributes()
    {
        if (!isset($this->requiredAttributes)) {
            $customerAttributeTypeAddress = \Magento\Customer\Api\AddressMetadataInterface::ENTITY_TYPE_ADDRESS;
            $isRequiredAttribute  = \Magento\Eav\Api\Data\AttributeInterface::IS_REQUIRED;

            $filters[] = $this->filter
                ->setField($isRequiredAttribute)
                ->setConditionType('eq')
                ->setValue('1');

            $filterGroup[] = $this->filterGroup->setFilters($filters);

            $searchCriteria = $this->searchCriteriaInterface
                ->setFilterGroups($filterGroup);

            $attributeInterfaceArray = $this->attributeRepository
                ->getList($customerAttributeTypeAddress, $searchCriteria)->getItems();

            $attributes = array();
            /**
             * AttributeInterface
             *
             * @var \Magento\Eav\Api\Data\AttributeInterface $attribute
             */
            foreach ($attributeInterfaceArray as $attribute) {
                $attributes[] = $attribute->getAttributeCode();
            }

            $this->requiredAttributes = $attributes;
        }

        return $this->requiredAttributes;
    }

    /**
     * Validate required attribute is empty
     *
     * @param string $attributeCode string
     * @param array  $rowData       array
     *
     * @return bool
     */
    public function validateRequiredAttributes($attributeCode, $rowData)
    {
        $attributes = $this->_getAllCustomerAddressRequiredAttributes();
        $attributes[] = 'dob';
        $attributes[] = 'gender';
        $attributes[] = 'consumer_db_id';

        if (in_array($attributeCode, $attributes)) {
            if ($rowData[$attributeCode] == '') {
                return false;
            }
        }

        if ($attributeCode == 'postcode') {
            $valid = preg_match('/^\d{3}[-]?\d{4}$/', $rowData[$attributeCode]);
            if (!$valid) {
                return false;
            }
        }

        if ($attributeCode == 'email') {
            $valid = preg_match('/^[-!#$%&*+\.\/0-9=?A-Z\^_`a-z{|}~\\\]+@[0-9a-zA-Z\.\-]+\.[0-9a-zA-Z\-]+$/', $rowData[$attributeCode]);
            if (!$valid) {
                return false;
            }
        }

        return true;
    }

}
