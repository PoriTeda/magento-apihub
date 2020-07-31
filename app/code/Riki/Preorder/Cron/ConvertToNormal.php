<?php
namespace Riki\Preorder\Cron;

use Riki\Preorder\Model\Order\OrderType;

class ConvertToNormal
{
    /**
     * @var \Riki\Preorder\Logger\Logger $logger
     */
    protected $logger;

    /**
     * @var \Riki\Preorder\Model\ResourceModel\OrderItemPreorder\CollectionFactory
     */
    protected $preOrderItemCollectionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory
     */
    protected $orderItemCollection;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollection;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;
    /**
     * Date
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Riki\AdvancedInventory\Model\Assignation
     */
    protected $modelAssignation;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Riki\AdvancedInventory\Model\StockFactory
     */
    protected $stockFactory;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * ConvertToNormal constructor.
     * @param \Magento\Eav\Model\Entity\Context $context
     * @param \Riki\Preorder\Logger\Logger $logger
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory
     * @param \Riki\AdvancedInventory\Model\Assignation $modelAssignation
     * @param \Riki\Preorder\Model\ResourceModel\OrderItemPreorder\CollectionFactory $preOrderItemCollectionFactory
     * @param \Riki\AdvancedInventory\Model\StockFactory $stockFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Magento\Eav\Model\Entity\Context $context,
        \Riki\Preorder\Logger\Logger $logger,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory,
        \Riki\AdvancedInventory\Model\Assignation $modelAssignation,
        \Riki\Preorder\Model\ResourceModel\OrderItemPreorder\CollectionFactory $preOrderItemCollectionFactory,
        \Riki\AdvancedInventory\Model\StockFactory $stockFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->preOrderItemCollectionFactory = $preOrderItemCollectionFactory;
        $this->orderItemCollection = $orderItemCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->orderCollection = $orderCollectionFactory;
        $this->resource = $context->getResource();
        $this->date = $date;
        $this->logger = $logger;
        $this->modelAssignation = $modelAssignation;
        $this->jsonHelper = $jsonHelper;
        $this->stockFactory = $stockFactory;
        $this->eventManager = $eventManager;
    }
    /**
     * Get connection
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     * @codeCoverageIgnore
     */
    public function getConnection($resourceName = \Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION)
    {
        return $this->resource->getConnection($resourceName);
    }
    /**
     * @return $this
     */
    public function process()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->joinAttribute(
            'fulfilment_date',
            'catalog_product/fulfilment_date',
            'entity_id',
            null,
            'left'
        );
        $productCollection->joinField(
            'backorders',
            'cataloginventory_stock_item',
            'backorders',
            'product_id=entity_id',
            '{{table}}.stock_id=1',
            'left'
        );
        $productCollection->addAttributeToFilter(
            'fulfilment_date',
            [
                'to'  =>  $this->date->gmtDate('Y-m-d 23:59:59')
            ]
        );

        $productCollection->addFieldToFilter('backorders', 0);

        $productIds = $productCollection->getAllIds();

        $this->logger->info(__('Product IDs: %1', implode(', ', $productIds)));

        if (count($productIds)) {
            $preOrderItemSelect = $this->preOrderItemCollectionFactory->create()
                ->addFieldToSelect('order_item_id')
                ->addFieldToFilter('is_preorder', OrderType::PREORDER)
                ->getSelect();

            /** @var \Magento\Sales\Model\ResourceModel\Order\Item\Collection $orderItemCollection */
            $orderItemCollection = $this->orderItemCollection->create();
            $orderItems = $orderItemCollection->addFieldToFilter('product_id', ['in' => [$productIds]])
                ->addFieldToFilter('item_id', ['in' => $preOrderItemSelect])
                ->getItems();

            $orderIdsToItems = [];

            foreach ($orderItems as $item) {
                $orderId = $item->getOrderId();
                $productId = $item->getProductId();

                if (!isset($orderIdsToItems[$orderId])) {
                    $orderIdsToItems[$orderId] = [];
                }

                $orderIdsToItems[$orderId][$item->getId()] = [
                    'product_id'    =>  $productId,
                    'qty'   =>  $item->getQtyOrdered()
                ];
            }

            $this->convertOrderToNormalByIds($orderIdsToItems);
        }

        return $this;
    }

    /**
     * @param array $orderIdsToItems
     * @return $this
     */
    protected function convertOrderToNormalByIds(array $orderIdsToItems)
    {
        if (count($orderIdsToItems)) {
            /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
            $orderCollection = $this->orderCollection->create();
            $orderCollection->addFieldToFilter('entity_id', ['in'   =>  array_keys($orderIdsToItems)])
                ->getItems();

            $salesConnection = $orderCollection->getResource()->getConnection();

            /** @var \Riki\AdvancedInventory\Model\ResourceModel\Stock $stockResource */
            $stockResource = $this->stockFactory->create()->getResource();

            $stockConnection = $stockResource->getTransactionConnection();

            /** @var \Magento\Sales\Model\Order $order */
            foreach ($orderCollection as $order) {
                $orderId = (int)$order->getId();

                $salesConnection->beginTransaction();
                $stockConnection->beginTransaction();

                try {
                    /*get assignation data for this order*/
                    $assignation = $this->modelAssignation->generateAssignationByOrder($order);

                    $assignTo = $assignation['inventory'];

                    $assignedPlaceIds = explode(',', $assignTo['place_ids']);

                    if (!isset($assignTo['status'])
                        || !$assignTo['status']
                        || count($assignedPlaceIds) == 0
                        || in_array(0, $assignedPlaceIds)
                    ) {
                        throw new \Exception(__('Not all of your products are available in the requested quantity.'));
                    }

                    /*order assignation main data*/
                    $orderAssignationData = $this->jsonHelper->jsonEncode($assignTo);

                    $order->setAssignation($orderAssignationData);

                    if (!empty($assignTo)
                        && !empty($assignTo["place_ids"])
                    ) {
                        /*place id list*/
                        $order->setAssignedTo($assignation["inventory"]["place_ids"]);
                    }

                    /*deduct warehouse stock, add record to advanced inventory assignation table*/
                    $this->modelAssignation->insert($orderId, $assignation);

                    $bind = [
                        'is_preorder' => \Riki\Preorder\Model\Order\OrderType::BACKNORMAL
                    ];

                    $where = [
                        'order_id = ?' => (int)$orderId
                    ];

                    $salesConnection->update(
                        $this->resource->getTableName('riki_preorder_order_preorder'),
                        $bind,
                        $where
                    );

                    //

                    $orderItemIds = $order->getItemsCollection()->getAllIds();

                    foreach ($orderItemIds as $orderItemId) {
                        $bind = [
                            'is_preorder' => 0,
                            'is_confirmed' => 1
                        ];
                        $where = ['order_item_id = ?' => (int)$orderItemId];
                        $salesConnection->update(
                            $this->resource->getTableName('riki_preorder_order_item_preorder'),
                            $bind,
                            $where
                        );
                    }

                    $order->save();

                    $this->eventManager->dispatch(
                        'preorder_convert_normal_after',
                        [
                            'order'    =>  $order
                        ]
                    );

                    $salesConnection->commit();
                    $stockConnection->commit();
                } catch (\Exception $e) {
                    $salesConnection->rollBack();
                    $stockConnection->rollBack();
                    $this->logger->critical($e);
                    $this->logger->error(__(
                        'Can not convert to normal order for order #%1, error: %2',
                        $order->getIncrementId(),
                        $e->getMessage()
                    ));
                }
            }
        }

        return $this;
    }
}