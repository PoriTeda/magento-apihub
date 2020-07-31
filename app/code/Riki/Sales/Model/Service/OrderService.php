<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Sales\Model\Service;

use Riki\Sales\Api\OrderManagementInterface;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;

/**
 * Class OrderService
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderService implements OrderManagementInterface
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface
     */
    protected $historyRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var \Magento\Sales\Model\OrderNotifier
     */
    protected $notifier;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender
     */
    protected $orderCommentSender;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory
     */
    protected $collectionOrderStatusHistoryFactory;


    protected $orderItem;

    protected $dataListItem;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    protected $_orderStatusFactory;

    protected $_shipmentRepository;

    protected $_orderPaymentShipmentStatusCollectionFactory;

    /**
     * Constructor
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $collectionOrderStatusHistoryFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $historyRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Sales\Model\OrderNotifier $notifier
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender $orderCommentSender
     * @param \Riki\Stock\Model\StockItemCollection $orderItem
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Riki\Sales\Api\Data\OrderStatusInterfaceFactory $orderStatusInterfaceFactory
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @internal param \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $collectionOrderStatusHistoryFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $historyRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Model\OrderNotifier $notifier,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender $orderCommentSender,
        \Riki\Stock\Model\StockItemCollection $orderItem,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Riki\Sales\Api\Data\OrderStatusInterfaceFactory $orderStatusInterfaceFactory,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Riki\Sales\Model\ResourceModel\OrderPayshipStatus\CollectionFactory $orderPaymentShipmentStatusCollectionFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->historyRepository = $historyRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->notifier = $notifier;
        $this->eventManager = $eventManager;
        $this->orderCommentSender = $orderCommentSender;
        $this->orderItem   = $orderItem;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->collectionOrderStatusHistoryFactory = $collectionOrderStatusHistoryFactory;
        $this->_timezone = $timezone;
        $this->dateTime = $dateTime;
        $this->_orderStatusFactory = $orderStatusInterfaceFactory;
        $this->_shipmentRepository = $shipmentRepository;
        $this->_orderPaymentShipmentStatusCollectionFactory = $orderPaymentShipmentStatusCollectionFactory;
    }


    /**
     * Get items
     * @parram
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface[]
     */
    public function getItems()
    {
        return $this->dataListItem;
    }

    /**
     * @inheritdoc
     *
     */
    public function setItems(array $items)
    {
        $this->dataListItem = $items;
    }

    /**
     * @param string $mmOrderId
     *
     * @return \Magento\Sales\Model\Order
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getOrderByMachineOrderId($mmOrderId)
    {
        $collection = $this->orderCollectionFactory->create()
            ->addFieldToFilter('mm_order_id', $mmOrderId)
            ->setPageSize(1);

        $order = $collection->getFirstItem();

        if ($order->getId()) {
            return $order;
        } else {
            throw new \Magento\Framework\Exception\NoSuchEntityException(
                __(
                    'Order not exist',
                    $mmOrderId
                )
            );
        }
    }

    protected function _getOrderShipment($order)
    {
        $criteria = $this->_searchCriteriaBuilder->addFilter('order_id', $order->getId())
            ->create();

        $shipmentCollection = $this->_shipmentRepository->getList($criteria);

        if ($shipmentCollection->getTotalCount()) {
            // MM order has only 1 shipment
            return $shipmentCollection->getFirstItem();
        }
    }

    /**
     * Get order status
     *
     * @param string $id Machine Maintenance Order ID
     *
     * @return \Riki\Sales\Api\Data\OrderStatusInterface
     */
    public function getStatus($id)
    {
        $order = $this->getOrderByMachineOrderId($id);

        $orderStatus = $this->_orderStatusFactory->create();

        $orderStatus->setStatus($order->getStatusLabel());

        $expectedStatus = ['shipped_all', 'delivery_completed'];
        $orderPaymentShipmentStatusCollection = $this->_orderPaymentShipmentStatusCollectionFactory->create();
        $orderPaymentShipmentStatusCollection->addFieldToFilter('order_id', $order->getId())
            ->addFieldToFilter('status_shipment', ['in' => $expectedStatus]);

        foreach ($orderPaymentShipmentStatusCollection as $status) {
            switch ($status->getStatusShipment()) {
                case 'shipped_all':
                    $orderStatus->setShipOutDate($status->getStatusDate());
                    break;
                case 'delivery_completed':
                    $orderStatus->setDeliveryCompletionDate($status->getStatusDate());
                    break;
            }
        }

        return $orderStatus;
    }

    /**
     * @param $incrementId
     * @return \Magento\Framework\DataObject
     */
    public function getByIncrementId($incrementId)
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
        $orderCollection = $this->orderCollectionFactory->create();

        return $orderCollection->addFieldToFilter('increment_id', $incrementId)
            ->setPageSize(1)
            ->getFirstItem();
    }
}
