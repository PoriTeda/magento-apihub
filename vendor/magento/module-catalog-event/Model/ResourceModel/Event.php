<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Catalog Event resource model
 */
namespace Magento\CatalogEvent\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection as AppResource;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * @api
 * @since 100.0.2
 */
class Event extends AbstractDb
{
    const EVENT_FROM_PARENT_FIRST = 1;

    const EVENT_FROM_PARENT_LAST = 2;

    /**
     * Child to parent list
     *
     * @var array
     */
    protected $_childToParentList;

    /**
     * var which represented catalogevent collection
     *
     * @var array
     */
    protected $_eventCategories;

    /**
     * Store model manager
     *
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Category collection factory
     *
     * @var CollectionFactory
     */
    protected $_categoryCollectionFactory;

    /**
     * @var MetadataPool
     * @since 100.1.0
     */
    protected $metadataPool;

    protected $_eventRecursive = 0;
    protected $_recursiveCheck = [];

    /**
     * Construct
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $categoryCollectionFactory
     * @param MetadataPool $metadataPool
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        StoreManagerInterface $storeManager,
        CollectionFactory $categoryCollectionFactory,
        MetadataPool $metadataPool,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);

        $this->_storeManager = $storeManager;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->metadataPool = $metadataPool;
    }

    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_catalogevent_event', 'event_id');
        $this->addUniqueField(['field' => 'category_id', 'title' => __('Event for selected category')]);
    }

    /**
     * Before model save
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(AbstractModel $object)
    {
        if (strlen($object->getSortOrder()) === 0) {
            $object->setSortOrder(null);
        }

        return parent::_beforeSave($object);
    }

    /**
     * Retrieve category ids with events
     *
     * @param int|string|Store $storeId
     * @return array
     */
    public function getCategoryIdsWithEvent($storeId = null)
    {
        $rootCategoryId = $this->_storeManager->getStore($storeId)->getRootCategoryId();

        /* @var $select \Magento\Framework\DB\Select */
        $select = $this->_categoryCollectionFactory->create()->setStoreId(
            $this->_storeManager->getStore($storeId)->getId()
        )->addIsActiveFilter()->addPathsFilter(
            \Magento\Catalog\Model\Category::TREE_ROOT_ID . '/' . $rootCategoryId
        )->getSelect();

        $parts = $select->getPart(\Magento\Framework\DB\Select::FROM);

        if (isset($parts['main_table'])) {
            $categoryCorrelationName = 'main_table';
        } else {
            $categoryCorrelationName = 'e';
        }

        $meta = $this->metadataPool->getMetadata(CategoryInterface::class);
        $categoryIdentifierFiled = $meta->getIdentifierField();

        $select->reset(\Magento\Framework\DB\Select::COLUMNS);
        $select->columns([$categoryIdentifierFiled, 'level', 'path'], $categoryCorrelationName);

        $select->joinLeft(
            ['event' => $this->getMainTable()],
            'event.category_id = ' . $categoryCorrelationName . '.' . $categoryIdentifierFiled,
            'event_id'
        )->order(
            $categoryCorrelationName . '.level ASC'
        );

        $this->_eventCategories = $this->getConnection()->fetchAssoc($select);

        if (empty($this->_eventCategories)) {
            return [];
        }
        $this->_setChildToParentList();

        foreach ($this->_eventCategories as $categoryId => $category) {
            $this->_eventCategories = 0;
            $this->_recursiveCheck = [];
            if ($category['event_id'] === null && isset($category['level']) && $category['level'] > 2) {
                $result[$categoryId] = $this->_getEventFromParent($categoryId, self::EVENT_FROM_PARENT_LAST);
                if($result[$categoryId] == false){
                    $result[$categoryId] = null;
                    \Magento\Framework\App\ObjectManager::getInstance()->get("Nestle\Debugging\Helper\DebuggingHelper")
                        ->inClass($this)
                        ->addMessage("Max recursive loop for category id: " . $categoryId)
                        ->logServerIp()
                        ->logBacktrace()
                        ->save("MGU-574");
                }
            } else {
                if ($category['event_id'] !== null) {
                    $result[$categoryId] = $category['event_id'];
                } else {
                    $result[$categoryId] = null;
                }
            }
        }

        return $result;
    }

    /**
     * Method for building relates between child and parent node
     *
     * @return $this
     */
    protected function _setChildToParentList()
    {
        if (is_array($this->_eventCategories)) {
            foreach ($this->_eventCategories as $row) {
                $category = explode('/', $row['path']);
                $amount = count($category);
                if ($amount > 2) {
                    $key = $category[$amount - 1];
                    $val = $category[$amount - 2];
                    if (empty($this->_childToParentList[$key])) {
                        $this->_childToParentList[$key] = $val;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Retrieve Event from close parent
     *
     * @param int $categoryId
     * @param int $flag
     * @return int
     */
    protected function _getEventFromParent($categoryId, $flag = 2)
    {
        if(++$this->_eventRecursive > 1000){
            return false;
        }
        if (isset($this->_childToParentList[$categoryId])) {
            $parentId = $this->_childToParentList[$categoryId];
        }
        if (!isset($parentId)) {
            return null;
        }
        if(in_array($categoryId,$this->_recursiveCheck)){
            \Magento\Framework\App\ObjectManager::getInstance()->get("Nestle\Debugging\Helper\DebuggingHelper")
                ->inClass($this)
                ->addMessage("Recursive reverse dependencies found: " . $categoryId . " and " . $parentId)
                ->logServerIp()
                ->logBacktrace()
                ->save("MGU-574");

            return false;
        }
        array_push($this->_recursiveCheck, $categoryId);
        $eventId = null;
        if (isset($this->_eventCategories[$parentId])) {
            $eventId = $this->_eventCategories[$parentId]['event_id'];
        }
        if ($flag == self::EVENT_FROM_PARENT_LAST) {
            if (isset($eventId) && $eventId !== null) {
                return $eventId;
            } else {
                if ($eventId === null) {
                    return $this->_getEventFromParent($parentId, $flag);
                }
            }
        }
        return null;
    }

    /**
     * After model save (save event image)
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _afterSave(AbstractModel $object)
    {
        $where = [$object->getIdFieldName() . '=?' => $object->getId(), 'store_id = ?' => $object->getStoreId()];

        $connection = $this->getConnection();
        $connection->delete($this->getTable('magento_catalogevent_event_image'), $where);

        if ($object->getImage() !== null) {
            $data = [
                $object->getIdFieldName() => $object->getId(),
                'store_id' => $object->getStoreId(),
                'image' => $object->getImage()
            ];

            $connection->insert($this->getTable('magento_catalogevent_event_image'), $data);
        }
        return parent::_afterSave($object);
    }

    /**
     * After model load (loads event image)
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterLoad(AbstractModel $object)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getTable('magento_catalogevent_event_image'),
            ['type' => $connection->getCheckSql('store_id = 0', "'default'", "'store'"), 'image']
        )->where(
            $object->getIdFieldName() . '=?',
            $object->getId()
        )->where(
            'store_id IN (0, ?)',
            $object->getStoreId()
        );

        $images = $connection->fetchPairs($select);

        if (isset($images['store'])) {
            $object->setImage($images['store']);
            $object->setImageDefault(isset($images['default']) ? $images['default'] : '');
        }

        if (isset($images['default']) && !isset($images['store'])) {
            $object->setImage($images['default']);
        }

        return parent::_afterLoad($object);
    }
}
