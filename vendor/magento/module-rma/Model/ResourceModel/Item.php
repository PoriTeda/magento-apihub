<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Model\ResourceModel;

use Magento\Catalog\Model\ResourceModel\AbstractResource;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend;
use Magento\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Eav\Model\Entity\AttributeLoader;
use Magento\Framework\App\ObjectManager;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Sales\Model\Order\Item as OrderItem;

/**
 * RMA entity resource model
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Item extends \Magento\Eav\Model\Entity\AbstractEntity
{
    /**
     * Store firstly set attributes to filter selected attributes when used specific store_id
     *
     * @var array
     */
    protected $_attributes = [];

    /**
     * Rma data
     *
     * @var \Magento\Rma\Helper\Data
     */
    protected $_rmaData;

    /**
     * Sales order item collection
     *
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory
     */
    protected $_ordersFactory;

    /**
     * Catalog product factory
     *
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * Rma refundable list
     *
     * @var \Magento\Catalog\Model\ProductTypes\ConfigInterface
     */
    protected $refundableList;

    /**
     * @var \Magento\Sales\Model\Order\Admin\Item
     */
    protected $adminOrderItem;

    /**
     * @param \Magento\Eav\Model\Entity\Context $context
     * @param \Magento\Rma\Helper\Data $rmaData
     * @param \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $ordersFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $refundableList
     * @param \Magento\Sales\Model\Order\Admin\Item $adminOrderItem
     * @param array $data
     */
    public function __construct(
        \Magento\Eav\Model\Entity\Context $context,
        \Magento\Rma\Helper\Data $rmaData,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $ordersFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $refundableList,
        \Magento\Sales\Model\Order\Admin\Item $adminOrderItem,
        $data = []
    ) {
        $this->adminOrderItem = $adminOrderItem;
        $this->_rmaData = $rmaData;
        $this->_ordersFactory = $ordersFactory;
        $this->_productFactory = $productFactory;
        $this->refundableList = $refundableList;
        parent::__construct($context, $data);
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    public function _construct()
    {
        $this->setType('rma_item');
        $this->setConnection('rma_item');
    }

    /**
     * Redeclare attribute model
     *
     * @return string
     */
    protected function _getDefaultAttributeModel()
    {
        return \Magento\Rma\Model\Item\Attribute::class;
    }

    /**
     * Returns default Store ID
     *
     * @return int
     */
    public function getDefaultStoreId()
    {
        return \Magento\Store\Model\Store::DEFAULT_STORE_ID;
    }

    /**
     * Check whether the attribute is Applicable to the object
     *
     * @param \Magento\Framework\DataObject $object
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     * @return boolean
     */
    protected function _isApplicableAttribute($object, $attribute)
    {
        $applyTo = $attribute->getApplyTo();
        return null === $applyTo ||
            is_array($applyTo) && (count($applyTo) == 0 || in_array($object->getTypeId(), $applyTo));
    }

    /**
     * Check whether attribute instance (attribute, backend, frontend or source) has method and applicable
     *
     * @param AbstractAttribute|AbstractBackend|AbstractFrontend|AbstractSource $instance
     * @param string $method
     * @param array $args array of arguments
     * @return bool
     */
    protected function _isCallableAttributeInstance($instance, $method, $args)
    {
        if ($instance instanceof AbstractBackend && ($method == 'beforeSave' || ($method = 'afterSave'))) {
            $attributeCode = $instance->getAttribute()->getAttributeCode();
            if (isset($args[0])
                && $args[0] instanceof \Magento\Framework\DataObject
                && $args[0]->getData($attributeCode) === false
            ) {
                return false;
            }
        }

        return parent::_isCallableAttributeInstance($instance, $method, $args);
    }

    /**
     * Reset firstly loaded attributes
     *
     * @param \Magento\Framework\DataObject $object
     * @param integer $entityId
     * @param array|null $attributes
     * @return AbstractResource
     */
    public function load($object, $entityId, $attributes = [])
    {
        $this->_attributes = [];
        return parent::load($object, $entityId, $attributes);
    }

    /**
     * Gets rma authorized items ids an qty by rma id
     *
     * @param  int $rmaId
     * @return array
     */
    public function getAuthorizedItems($rmaId)
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            $this->getTable('magento_rma_item_entity'),
            []
        )->where(
            'rma_entity_id = ?',
            $rmaId
        )->where(
            'status = ?',
            \Magento\Rma\Model\Rma\Source\Status::STATE_AUTHORIZED
        )->group(
            ['order_item_id', 'product_name']
        )->columns(
            [
                'order_item_id' => 'order_item_id',
                'qty' => new \Zend_Db_Expr('SUM(qty_authorized)'),
                'product_name' => new \Zend_Db_Expr('MAX(product_name)'),
            ]
        );

        $return = $connection->fetchAll($select);
        $itemsArray = [];
        if (!empty($return)) {
            foreach ($return as $item) {
                $itemsArray[$item['order_item_id']] = $item;
            }
        }
        return $itemsArray;
    }

    /**
     * Gets rma items ids by order
     *
     * @param  int $orderId
     * @return array
     */
    public function getReturnableItems($orderId)
    {
        $connection = $this->getConnection();
        $salesAdapter = $this->_resource->getConnection('sales');
        $shippedSelect = $salesAdapter->select()
            ->from(
                ['order_item' => $this->getTable('sales_order_item')],
                [
                    'order_item.item_id',
                    'order_item.qty_shipped'
                ]
            )->where('order_item.order_id = ?', $orderId);

        $orderItemsShipped = $salesAdapter->fetchPairs($shippedSelect);

        $requestedSelect = $connection->select()
            ->from(
                ['rma' => $this->getTable('magento_rma')],
                [
                    'rma_item.order_item_id',
                    new \Zend_Db_Expr('SUM(qty_requested)')
                ]
            )
            ->joinInner(
                ['rma_item' => $this->getTable('magento_rma_item_entity')],
                'rma.entity_id = rma_item.rma_entity_id',
                []
            )->where(
                'rma_item.order_item_id IN (?)',
                array_keys($orderItemsShipped)
            )->where(
                sprintf(
                    '%s NOT IN (?)',
                    $connection->getIfNullSql('rma.status', $connection->quote(Status::STATE_CLOSED))
                ),
                [Status::STATE_CLOSED, Status::STATE_PROCESSED_CLOSED]
            )->group('rma_item.order_item_id');
        $orderItemsRequested = $connection->fetchPairs($requestedSelect);
        $result = [];
        foreach ($orderItemsShipped as $itemId => $shipped) {
            $requested = 0;
            if (isset($orderItemsRequested[$itemId])) {
                $requested = $orderItemsRequested[$itemId];
            }

            $result[$itemId] = 0;
            if ($shipped > $requested) {
                $result[$itemId] = $shipped - $requested;
            }
        }

        return $result;
    }

    /**
     * Gets order items collection
     *
     * @param int $orderId
     * @return \Magento\Sales\Model\ResourceModel\Order\Item\Collection
     */
    public function getOrderItemsCollection($orderId)
    {
        $connection = $this->getConnection();
        $expression = new \Zend_Db_Expr(
            '('
            . $connection->quoteIdentifier('qty_shipped') . ' - ' . $connection->quoteIdentifier('qty_returned')
            . ')'
        );
        /** @var $collection \Magento\Sales\Model\ResourceModel\Order\Item\Collection */
        $collection = $this->_ordersFactory->create();
        return $collection->addExpressionFieldToSelect(
            'available_qty',
            $expression,
            ['qty_shipped', 'qty_returned']
        )->addFieldToFilter(
            'order_id',
            $orderId
        )->addFieldToFilter(
            'product_type',
            ["in" => $this->refundableList->filter('refundable')]
        )->addFieldToFilter(
            $expression,
            ["gt" => 0]
        );
    }

    /**
     * Gets available order items collection
     *
     * @param  int $orderId
     * @param  int|bool $parentId if need retrieves only bundle and its children
     * @return \Magento\Sales\Model\ResourceModel\Order\Item\Collection
     */
    public function getOrderItems($orderId, $parentId = false)
    {
        /** @var $orderItemsCollection \Magento\Sales\Model\ResourceModel\Order\Item\Collection */
        $orderItemsCollection = $this->getOrderItemsCollection($orderId);

        if (!$orderItemsCollection->count()) {
            return $orderItemsCollection;
        }

        /** @var $product \Magento\Catalog\Model\Product */
        $product = $this->_productFactory->create();

        $returnableItems = $this->getReturnableItems($orderId);

        foreach ($orderItemsCollection as $item) {
            /* retrieves only bundle and children by $parentId */
            if ($parentId && $item->getId() != $parentId && $item->getParentItemId() != $parentId) {
                $orderItemsCollection->removeItemByKey($item->getId());
                continue;
            }
            $canReturn = isset($returnableItems[$item->getId()]);
            $canReturnProduct = $this->_rmaData->canReturnProduct($product, $item->getStoreId());
            if (!$canReturn || !$canReturnProduct) {
                $orderItemsCollection->removeItemByKey($item->getId());
                continue;
            }
            $item->setName($this->getProductName($item));
            $item->setAvailableQty($returnableItems[$item->getId()]);
        }
        return $orderItemsCollection;
    }

    /**
     * Gets Product Name
     *
     * @param OrderItem $item
     * @return string
     */
    public function getProductName($item)
    {
        $name = $item->getName();
        $result = [];
        if ($options = $item->getProductOptions()) {
            if (isset($options['options'])) {
                $result = array_merge($result, $options['options']);
            }
            if (isset($options['additional_options'])) {
                $result = array_merge($result, $options['additional_options']);
            }
            if (isset($options['attributes_info'])) {
                $result = array_merge($result, $options['attributes_info']);
            }

            if (!empty($result)) {
                $implode = [];
                foreach ($result as $val) {
                    $implode[] = isset($val['print_value']) ? $val['print_value'] : $val['value'];
                }
                return $name . ' (' . implode(', ', $implode) . ')';
            }
        }
        return $name;
    }

    /**
     * The getter function to get the AttributeLoader
     *
     * @return AttributeLoader
     *
     * @deprecated 100.1.0
     */
    protected function getAttributeLoader()
    {
        if ($this->attributeLoader === null) {
            $this->attributeLoader= ObjectManager::getInstance()->get(AttributeLoader::class);
        }
        return $this->attributeLoader;
    }
}
