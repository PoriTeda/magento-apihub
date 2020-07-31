<?php
namespace Riki\Catalog\Model\ResourceModel;

class Product extends \Magento\Catalog\Model\ResourceModel\Product
{
    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * Product constructor.
     *
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Eav\Model\Entity\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Factory $modelFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category $catalogCategory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Eav\Model\Entity\Attribute\SetFactory $setFactory
     * @param \Magento\Eav\Model\Entity\TypeFactory $typeFactory
     * @param \Magento\Catalog\Model\Product\Attribute\DefaultAttributes $defaultAttributes
     * @param array $data
     */
    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Eav\Model\Entity\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Factory $modelFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Category $catalogCategory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Entity\Attribute\SetFactory $setFactory,
        \Magento\Eav\Model\Entity\TypeFactory $typeFactory,
        \Magento\Catalog\Model\Product\Attribute\DefaultAttributes $defaultAttributes,
        $data = []
    ) {
        $this->functionCache = $functionCache;
        parent::__construct($context, $storeManager, $modelFactory, $categoryCollectionFactory, $catalogCategory, $eventManager, $setFactory, $typeFactory, $defaultAttributes, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Catalog\Model\Product $object
     *
     * @return array
     */
    public function getAvailableInCategories($object)
    {
        $cacheKey = [$object->getId()];
        if ($this->functionCache->has($cacheKey)) {
            return $this->functionCache->load($cacheKey);
        }

        $result = parent::getAvailableInCategories($object);
        $this->functionCache->store($result, $cacheKey);

        return $result;
    }

    /**
     * Override function to allow for EAV varchar attributes to have leading zeros
     *
     * @see \Magento\Eav\Model\Entity\AbstractEntity::_collectSaveData
     * @param \Magento\Framework\Model\AbstractModel $newObject
     * @return array
     */
    protected function _collectSaveData($newObject)
    {
        $newData = $newObject->getData();
        $entityId = $newObject->getData($this->getEntityIdField());

        // define result data
        $entityRow = [];
        $insert = [];
        $update = [];
        $delete = [];

        if (!empty($entityId)) {
            $origData = $newObject->getOrigData();
            /**
             * get current data in db for this entity if original data is empty
             */
            if (empty($origData)) {
                $origData = $this->_getOrigObject($newObject)->getOrigData();
            }

            if (is_null($origData)) {
                $origData = [];
            }

            /**
             * drop attributes that are unknown in new data
             * not needed after introduction of partial entity loading
             */
            foreach ($origData as $k => $v) {
                if (!array_key_exists($k, $newData)) {
                    unset($origData[$k]);
                }
            }
        } else {
            $origData = [];
        }

        $staticFields = $this->getConnection()->describeTable($this->getEntityTable());
        $staticFields = array_keys($staticFields);
        $attributeCodes = array_keys($this->_attributesByCode);

        foreach ($newData as $k => $v) {
            /**
             * Check if data key is presented in static fields or attribute codes
             */
            if (!in_array($k, $staticFields) && !in_array($k, $attributeCodes)) {
                continue;
            }

            $attribute = $this->getAttribute($k);
            if (empty($attribute)) {
                continue;
            }

            if (!$attribute->isInSet($newObject->getAttributeSetId()) && !in_array($k, $staticFields)) {
                $this->_aggregateDeleteData($delete, $attribute, $newObject);
                continue;
            }

            $attrId = $attribute->getAttributeId();

            /**
             * Only scalar values can be stored in generic tables
             */
            if (!$attribute->getBackend()->isScalar()) {
                continue;
            }

            /**
             * if attribute is static add to entity row and continue
             */
            if ($this->isAttributeStatic($k)) {
                $entityRow[$k] = $this->_prepareStaticValue($k, $v);
                continue;
            }

            /**
             * Check comparability for attribute value
             *
             * Replace condition
             * from (!is_numeric($v) && $v !== $origData[$k] || is_numeric($v) && $v != $origData[$k])
             * to ($v !== $origData[$k])
             */
            if ($this->_canUpdateAttribute($attribute, $v, $origData)) {
                if ($this->_isAttributeValueEmpty($attribute, $v)) {
                    $this->_aggregateDeleteData($delete, $attribute, $newObject);
                } elseif ($v !== $origData[$k]) {
                    $update[$attrId] = [
                        'value_id' => $attribute->getBackend()->getEntityValueId($newObject),
                        'value' => is_array($v) ? array_shift($v) : $v,//@TODO: MAGETWO-44182,
                    ];
                }
            } elseif (!$this->_isAttributeValueEmpty($attribute, $v)) {
                $insert[$attrId] = is_array($v) ? array_shift($v) : $v;//@TODO: MAGETWO-44182
            }
        }

        $result = compact('newObject', 'entityRow', 'insert', 'update', 'delete');
        return $result;
    }

    /**
     * Override function : Aggregate Data for attributes that will be deleted
     *
     * @see \Magento\Eav\Model\Entity\AbstractEntity::_aggregateDeleteData
     * @param array &$delete
     * @param AbstractAttribute $attribute
     * @param \Magento\Eav\Model\Entity\AbstractEntity $object
     * @return void
     */
    private function _aggregateDeleteData(&$delete, $attribute, $object)
    {
        foreach ($attribute->getBackend()->getAffectedFields($object) as $tableName => $valuesData) {
            if (!isset($delete[$tableName])) {
                $delete[$tableName] = [];
            }
            $delete[$tableName] = array_merge((array)$delete[$tableName], $valuesData);
        }
    }
}
