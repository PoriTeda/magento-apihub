<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model;

use Magento\Framework\DB\Select;

/**
 * Class Rules
 *
 * @method bool getIsActive()
 * @method string getConditionsSerialized()
 *
 * @package Magento\VisualMerchandiser\Model
 * @api
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Rules extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Additional attributes available to smart category rules
     */
    const XML_PATH_AVAILABLE_ATTRIBUTES = 'visualmerchandiser/options/smart_attributes';

    /**
     * @var array
     */
    protected $notices = [];

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     */
    protected $attribute;

    /**
     * @var Rules\Factory
     */
    protected $ruleFactory;

    /**
     * @var Rules\Rule\Collection\Fetcher
     */
    protected $fetcher;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     * @param Rules\Factory $ruleFactory
     * @param Rules\Rule\Collection\Fetcher $fetcher
     * @param array $data
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute,
        \Magento\VisualMerchandiser\Model\Rules\Factory $ruleFactory,
        \Magento\VisualMerchandiser\Model\Rules\Rule\Collection\Fetcher $fetcher,
        array $data = [],
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->_messageManager = $messageManager;
        $this->_scopeConfig = $scopeConfig;
        $this->attribute = $attribute;
        $this->ruleFactory = $ruleFactory;
        $this->fetcher = $fetcher;
    }

    /**
     * Init resource model and id field
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(\Magento\VisualMerchandiser\Model\ResourceModel\Rules::class);
        $this->setIdFieldName('rule_id');
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    public function beforeSave()
    {
        $this->validateData();
        return parent::beforeSave();
    }

    /**
     * Validate the obvious
     *
     * @return void
     */
    protected function validateData()
    {
        if (!$this->getData('is_active')) {
            return;
        }
        try {
            $conditionsJson = $this->getData('conditions_serialized');
            if ($conditionsJson) {
                \Zend_Json::decode($conditionsJson);
            }
        } catch (\Zend_Exception $e) {
            $this->_messageManager->addException($e, __("Category rules validation failed"));
            $this->setData('conditions_serialized', null);
        }
    }

    /**
     * @return int
     */
    public function getMode()
    {
        return (int) $this->getData('mode');
    }

    /**
     * Get the attributes usable with VisualMerchandiser rules
     * @return array
     */
    public function getAvailableAttributes()
    {
        $attributesString = $this->_scopeConfig->getValue(self::XML_PATH_AVAILABLE_ATTRIBUTES);
        $attributes = explode(',', $attributesString);
        $attributes = array_map('trim', $attributes);

        $result = [];
        foreach ($attributes as $attributeCode) {
            $attribute = $this->attribute->loadByCode(
                \Magento\Catalog\Model\Product::ENTITY,
                $attributeCode
            );
            if (!$attribute->getId()) {
                continue;
            }
            $result[$attribute->getAttributeCode()] = $attribute->getFrontendLabel();
        }

        $this->addStaticOptions($result);

        asort($result);
        return $result;
    }

    /**
     * @param array $options
     * @return void
     */
    protected function addStaticOptions(array &$options)
    {
        $options['category_id'] = __('Clone category ID(s)');
        $options['created_at'] = __('Date Created (days ago)');
        $options['updated_at'] = __('Date Modified (days ago)');
    }

    /**
     * @return array
     */
    public static function getLogicVariants()
    {
        return [
            Select::SQL_OR,
            Select::SQL_AND
        ];
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function getProductCollection()
    {
        return $this->_productCollectionFactory->create()->setStoreId(
            $this->_storeManager->getStore()->getId()
        );
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @return Rules
     */
    public function loadByCategory(\Magento\Catalog\Model\Category $category)
    {
        return $this->load($category->getId(), 'category_id');
    }

    /**
     * @return mixed|null
     * @throws \Zend_Json_Exception
     */
    public function getConditions()
    {
        if (!$this->getId()) {
            return null;
        }

        $conditionsSerialized = $this->getData('conditions_serialized');
        if (!$conditionsSerialized) {
            return null;
        }

        return \Zend_Json::decode($conditionsSerialized);
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return void
     */
    public function applyAllRules(
        \Magento\Catalog\Model\Category $category,
        \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
    ) {
        $rules = $this->loadByCategory($category);

        if (!$rules || !$rules->getIsActive()) {
            return;
        }

        try {
            $conditions = $rules->getConditions();
        } catch (\Zend_Exception $e) {
            $this->_messageManager->addException($e, __("Error in reading category rules"));
            return;
        }

        if (!is_array($conditions) || count($conditions) == 0) {
            $this->_messageManager->addError(__("There was no category rules to apply"));
            return;
        }

        $this->applyConditions($category, $collection, $conditions);

        if (!empty($this->notices)) {
            foreach ($this->notices as $notice) {
                $this->_messageManager->addNotice($notice);
            }
        }

        if ($this->_messageManager->hasMessages()) {
            return;
        }

        $this->_messageManager->addSuccess(__("Category rules applied"));
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param array $conditions
     * @return void
     */
    public function applyConditions(
        \Magento\Catalog\Model\Category $category,
        \Magento\Catalog\Model\ResourceModel\Product\Collection $collection,
        array $conditions
    ) {
        $ids = [];
        $logic = "";

        foreach ($conditions as $rule) {
            $_collection = $this->getProductCollection();

            $ruleType = $this->ruleFactory->create($rule);
            $ruleType->applyToCollection($_collection);

            $ids = ($logic == Select::SQL_AND)
                ? array_intersect($ids, $this->fetcher->fetchIds($_collection))
                : array_merge($ids, $this->fetcher->fetchIds($_collection));

            $logic = strtoupper($rule['logic']);

            if ($ruleType->hasNotices()) {
                $this->notices = $this->notices + $ruleType->getNotices();
            }
        }

        $collection->addAttributeToFilter('entity_id', ['in' => $ids]);
        if (count($ids) > 0) {
            $collection->getSelect()->reset(Select::ORDER);
            $collection->getSelect()->order(new \Zend_Db_Expr('FIELD(e.entity_id, ' . implode(',', $ids) . ')'));
        }

        $positions = [];
        $idx = 0;
        foreach ($collection as $item) {
            /* @var $item \Magento\Catalog\Api\Data\ProductInterface */
            $positions[$item->getId()] = $idx;
            $idx++;
        }

        $category->setPostedProducts($positions);

        // Clear any data that collection cached so far
        if ($collection->isLoaded()) {
            $collection->clear();
        }
    }
}
