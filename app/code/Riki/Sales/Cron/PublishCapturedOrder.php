<?php
namespace Riki\Sales\Cron;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Model\Order;
use Riki\Framework\Helper\Logger\LoggerBuilder;
use Riki\MessageQueue\Model\ResourceModel\QueueLock;
use Riki\Sales\Model\CaptureOrder\Consumer\FailureOrderCapture;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus as OrderStatus;
use Riki\Sales\Api\CaptureItemInterfaceFactory;
use Riki\Sales\Model\CaptureOrder\Consumer\OrderCapture as OrderCaptureConsumer;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;

class PublishCapturedOrder
{
    const MAX_NUMBER_ITEM_CAPTURE = 1000;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory
     */
    protected $shipmentCollectionFactory;

    /**
     * @var \Riki\ShipmentImporter\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Riki\SubscriptionProfileDisengagement\Helper\Data
     */
    protected $subscriptionDisengageHelper;

    /**
     * @var \Riki\Subscription\Helper\Data
     */
    private $subscriptionHelper;

    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $orderHelper;

    /**
     * @var QueueLock
     */
    protected $queueLock;

    /**
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * @var CaptureItemInterfaceFactory
     */
    protected $capturedItemFactory;

    /**
     * @var
     */
    protected $logger;

    /**
     * @var LoggerBuilder
     */
    protected $loggerBuilder;

    /**
     * PublishCapturedOrder constructor.
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory
     * @param \Riki\ShipmentImporter\Helper\Data $dataHelper
     * @param \Riki\SubscriptionProfileDisengagement\Helper\Data $subscriptionProfileDisengageHelper
     * @param \Riki\Subscription\Helper\Data $subscriptionHelper
     * @param \Riki\Sales\Helper\Order $orderHelper
     * @param LoggerBuilder $loggerBuilder
     * @param QueueLock $queueLock
     * @param PublisherInterface $publisher
     * @param CaptureItemInterfaceFactory $capturedItemFactory
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory,
        \Riki\ShipmentImporter\Helper\Data $dataHelper,
        \Riki\SubscriptionProfileDisengagement\Helper\Data $subscriptionProfileDisengageHelper,
        \Riki\Subscription\Helper\Data $subscriptionHelper,
        \Riki\Sales\Helper\Order $orderHelper,
        LoggerBuilder $loggerBuilder,
        QueueLock $queueLock,
        PublisherInterface $publisher,
        CaptureItemInterfaceFactory $capturedItemFactory
    ) {
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->dataHelper = $dataHelper;
        $this->subscriptionDisengageHelper = $subscriptionProfileDisengageHelper;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->orderHelper = $orderHelper;
        $this->loggerBuilder = $loggerBuilder;
        $this->queueLock = $queueLock;
        $this->publisher = $publisher;
        $this->capturedItemFactory = $capturedItemFactory;
    }

    /**
     * Push captured order to MQ
     */
    public function execute()
    {
        /*get order list will be captured*/
        $orderData = $this->getOrderData();

        while ($orderData) {
            $maxOrderEntityId = 0;
            /** @var \Magento\Sales\Model\Order $order */
            foreach ($orderData as $order) {
                $maxOrderEntityId = $order->getId();

                if (!$this->canCapture($order)
                    || !$order->canInvoice()
                ) {
                    continue;
                }

                try {
                    $this->queueLock->getConnection()->beginTransaction();
                    $this->capture($order);
                    $this->queueLock->getConnection()->commit();
                } catch (\Exception $e) {
                    $this->queueLock->getConnection()->rollBack();
                    $this->getLogger()->error(__('ERROR : %1 : %2', $order->getIncrementId(), $e->getMessage()));
                    $this->getLogger()->critical($e);
                }
            }

            $orderData = $this->getOrderData($maxOrderEntityId);
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return $this
     * @throws LocalizedException
     */
    protected function capture(\Magento\Sales\Model\Order $order)
    {
        $this->getLogger()->info(__('PUSH: %1', $order->getIncrementId()));

        $this->queueLock->lock(OrderCaptureConsumer::CAPTURE_ORDER_QUEUE_NAME, $order->getId());
        /** @var \Riki\Sales\Api\CaptureItemInterface $messageItem */
        $messageItem = $this->capturedItemFactory->create();
        $messageItem->setOrderId($order->getId());

        $this->publisher->publish(OrderCaptureConsumer::CAPTURE_ORDER_QUEUE_NAME, $messageItem);

        return $this;
    }

    /**
     * Check order if it can be capture or not
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    private function canCapture(\Magento\Sales\Model\Order $order)
    {
        if (!$this->orderHelper->isDelayPaymentOrder($order)) {
            return true;
        }

        $subscriptionProfileId = $order->getData('subscription_profile_id');

        if ($this->subscriptionDisengageHelper->isDisengageMode($subscriptionProfileId)) {
            return true;
        }

        if ($this->isOrderShipmentDeliveryCompleted($order)) {
            if ($order->getSubscriptionOrderTime() == 1) {
                $secondOrder = $this->subscriptionHelper->getProfileOrderAtSpecificTime(
                    $subscriptionProfileId,
                    2
                );

                $shippedOutSecondOrderShipment = null;

                if ($secondOrder) {
                    $shippedOutSecondOrderShipment = $this->getMinDateShippedOutShipment($secondOrder);
                }

                if ($order->getIsShoppingPointDeduction()) {
                    if ($shippedOutSecondOrderShipment) {
                        return true;
                    }
                } else {
                    if ($order->getIsOverCaptureDateCreatedDate()) {
                        return true;
                    }
                }

                if ($order->getIsAutoBox() && $shippedOutSecondOrderShipment) {
                    if ($this->dateTime->date('Y-m-d', $order->getAutoBoxDate())
                        >= $this->dateTime->date('Y-m-d', $shippedOutSecondOrderShipment->getShippedOutDate())
                    ) {
                        return true;
                    }
                }
            } else {
                if ($order->getIsOverCaptureDateCreatedDate()) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * @param int $maxOrderEntityId
     * @return bool|\Magento\Sales\Model\ResourceModel\Order\Collection
     * @throws LocalizedException
     */
    public function getOrderData($maxOrderEntityId = 0)
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->join(
            'sales_order_payment',
            'main_table.entity_id = sales_order_payment.parent_id',
            ['method']
        );
        $orderCollection->getSelect()->joinLeft(
            ['si' =>  $orderCollection->getConnection()->getTableName('sales_order_item')],
            'main_table.entity_id=si.order_id',
            [
                'min_delivery_date_item'    =>  new \Zend_Db_Expr('MIN(si.delivery_date)')
            ]
        )->joinLeft(
            ['p' =>  $orderCollection->getConnection()->getTableName('subscription_profile')],
            'main_table.subscription_profile_id=p.profile_id',
            [
                'course_id'
            ]
        )->joinLeft(
            ['c' =>  $orderCollection->getConnection()->getTableName('subscription_course')],
            'p.course_id=c.course_id',
            [
                'is_over_capture_date_created_date' =>  new \Zend_Db_Expr($orderCollection->getConnection()->quoteInto(
                    'IF(DATE(main_table.created_at) <= ?, 1, 0)',
                    new \Zend_Db_Expr('(CURRENT_DATE() - INTERVAL c.payment_delay_time DAY)')
                )),
                'auto_box_date' =>  new \Zend_Db_Expr(
                    'DATE(MIN(si.delivery_date)) + INTERVAL c.payment_delay_time DAY'
                ),
                'is_auto_box',
                'is_shopping_point_deduction'
            ]
        )->group('main_table.entity_id');

        $orderCollection->addFieldToFilter(
            'main_table.status',
            OrderStatus::STATUS_ORDER_SHIPPED_ALL
        )->addFieldToFilter(
            'entity_id',
            ['gt'   =>  $maxOrderEntityId]
        )->addFieldToFilter(
            'method',
            \Bluecom\Paygent\Model\Paygent::CODE
        );

        $inProcessingIds = $this->queueLock->getInProcessingMessages(OrderCaptureConsumer::CAPTURE_ORDER_QUEUE_NAME);
        $inProcessingIds += $this->queueLock->getInProcessingFailureMessages(
            FailureOrderCapture::FAILURE_CAPTURE_EXECUTOR_NAME
        );

        if (!empty($inProcessingIds)) {
            $orderCollection->addFieldToFilter(
                'entity_id',
                ['nin' => $inProcessingIds]
            );
        }

        $orderCollection->setPageSize(self::MAX_NUMBER_ITEM_CAPTURE);

        if ($orderCollection->getSize()) {
            return $orderCollection;
        } else {
            return false;
        }
    }

    /**
     * @param Order $order
     * @return bool
     */
    protected function isOrderShipmentDeliveryCompleted(Order $order)
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $shipmentCollection */
        $shipmentCollection = $this->shipmentCollectionFactory->create();
        $shipmentCollection->addFieldToFilter('order_id', $order->getId())
            ->addFieldToFilter('shipment_status', ['nin' =>  [
                ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED,
                ShipmentStatus::SHIPMENT_STATUS_REJECTED
            ]]);

        return $shipmentCollection->getSize() == 0;
    }

    /**
     * @param Order $order
     * @return \Magento\Framework\DataObject|null
     */
    protected function getMinDateShippedOutShipment(Order $order)
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $shipmentCollection */
        $shipmentCollection = $this->shipmentCollectionFactory->create();
        $shipmentCollection->addFieldToFilter('order_id', $order->getId())
            ->addFieldToFilter('shipped_out_date', ['notnull'    =>  true])
            ->addFieldToFilter('shipment_status', ['in' =>  [
                ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT,
                ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED,
                ShipmentStatus::SHIPMENT_STATUS_REJECTED
            ]])
            ->setOrder('shipped_out_date', \Magento\Framework\Data\Collection::SORT_ORDER_ASC)
            ->setPageSize(1);
        $shipment = $shipmentCollection->getFirstItem();
        if ($shipment->getId()) {
            return $shipment;
        }
        return null;
    }

    /**
     * @return \Riki\Framework\Helper\Logger\Monolog
     * @throws \Exception
     */
    private function getLogger()
    {
        if (!$this->logger) {
            $this->logger = $this->loggerBuilder
                ->setName('Riki_Paygent')
                ->setFileName('publish_queue_cron' . '.log')
                ->pushHandlerByAlias(LoggerBuilder::ALIAS_DATE_HANDLER)
                ->create();
        }

        return $this->logger;
    }
}
