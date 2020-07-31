<?php

namespace Riki\ShipmentImporter\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus as OrderStatus;

class CompleteBucket extends AbstractHelper
{
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $shipmentRepository;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var Data
     */
    protected $dataHelper;
    /**
     * @var Email
     */
    protected $emailHelper;
    /**
     * @var
     */
    protected $logger;
    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $orderHelper;

    /**
     * @var \Riki\SubscriptionProfileDisengagement\Helper\Data
     */
    private $disengageProfileHelper;

    /**
     * CompleteBucket constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param Data $dataHelper
     * @param Email $emailHelper
     * @param \Riki\Sales\Helper\Order $orderHelper
     * @param \Riki\SubscriptionProfileDisengagement\Helper\Data $disengageProfileHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Riki\ShipmentImporter\Helper\Data $dataHelper,
        \Riki\ShipmentImporter\Helper\Email $emailHelper,
        \Riki\Sales\Helper\Order $orderHelper,
        \Riki\SubscriptionProfileDisengagement\Helper\Data $disengageProfileHelper
    ) {
        $this->orderRepository = $orderRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->dataHelper = $dataHelper;
        $this->emailHelper = $emailHelper;
        $this->orderHelper = $orderHelper;
        $this->disengageProfileHelper = $disengageProfileHelper;
        parent::__construct($context);
    }

    /**
     * @param $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param $bucketId
     * @return bool|\Magento\Sales\Api\Data\OrderSearchResultInterface
     */
    public function getBucketOrders($bucketId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            'stock_point_delivery_bucket_id',
            $bucketId
        )
            ->create();
        $bucketOrders = $this->orderRepository->getList($searchCriteria);
        if ($bucketOrders->getTotalCount()) {
            return $bucketOrders->getItems();
        }
        return false;
    }

    /**
     * @param $orderId
     * @return \Magento\Sales\Api\Data\ShipmentInterface[]
     */
    public function getShipmentsByOrderId($orderId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            'order_id',
            $orderId
        )->create();
        return $this->shipmentRepository->getList($searchCriteria)->getItems();
    }

    /**
     * @param $message
     */
    public function writeToLog($message)
    {
        if ($this->dataHelper->isEnableLogger()) {
            $this->logger->info($message);
        }
    }

    /**
     * @param $orders
     * @return bool
     */
    public function canDeliveryCompletedBucketShipments($orders)
    {
        foreach ($orders as $order) {
            if ($order->getStatus() == OrderStatus::STATUS_ORDER_IN_PROCESSING) {
                $shipments = $this->getShipmentsByOrderId($order->getId());
                foreach ($shipments as $shipment) {
                    $shipmentStatus = $shipment->getShipmentStatus();
                    $isChirashi = $shipment->getData('is_chirashi');
                    $isZshim = $shipment->getData('ship_zsim');
                    if (!$isChirashi && !$isZshim) {
                        if ($shipmentStatus != ShipmentStatus::SHIPMENT_STATUS_EXPORTED) {
                            $message = __('Shipment : %1 is not exported.', $shipment->getIncrementId());
                            $this->writeToLog($message);
                            return false;
                        }
                    }
                }
            } else {
                $message = __(
                    'Status of #order : %1 is %2. Can not ship order out.',
                    $order->getIncrementId(),
                    $order->getStatus()
                );
                $this->writeToLog($message);
                return false;
            }
        }
        return true;
    }

    /**
     * @param $bucketOrders
     * @param $data
     */
    public function importBucketOrder($bucketOrders, $data)
    {
        foreach ($bucketOrders as $order) {
            if ($this->canCompleteBucketShipment($order)) {
                $shipments = $this->getShipmentsByOrderId($order->getId());
                foreach ($shipments as $shipment) {
                    $this->importBucketShipment($shipment, $data);
                }
                if (!$this->orderHelper->isDelayPaymentOrder($order)
                    || (
                        $this->orderHelper->isDelayPaymentOrder($order)
                        && $this->disengageProfileHelper->isDisengageMode(
                            $order->getData('subscription_profile_id')
                        )
                    )) {
                    $this->dataHelper->createInvoiceOrder($order, $data['shipmentDate']);
                }
            }
        }
    }

    /**
     * @param $order
     * @return bool
     */
    public function canCompleteBucketShipment($order)
    {
        $allowStatusShipment = [
            ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT,
            ShipmentStatus::SHIPMENT_STATUS_REJECTED
        ];
        if ($order->getStatus() == OrderStatus::STATUS_ORDER_SHIPPED_ALL) {
            $shipments = $this->getShipmentsByOrderId($order->getId());
            foreach ($shipments as $shipment) {
                $shipmentStatus = $shipment->getShipmentStatus();
                $isChirashi = $shipment->getData('is_chirashi');
                $isZshim = $shipment->getData('ship_zsim');
                if (!$isChirashi && !$isZshim) {
                    if (!in_array($shipmentStatus, $allowStatusShipment)) {
                        $message = __(
                            'Shipment : %s is not correct to import shipment complete.',
                            $shipment->getIncrementId()
                        );
                        $this->writeToLog($message);
                        return false;
                    }
                }
            }
            return true;
        } else {
            $message = __(
                'Status of #order : %1 is %2. Can not delivery order complete.',
                $order->getIncrementId(),
                $order->getStatus()
            );
            $this->writeToLog($message);
            return false;
        }
    }

    /**
     * @param $shipments
     * @param $data
     */
    public function importBucketShipment($shipment, $data)
    {
        if ($data['isRejected']) {
            $shipment->setShipmentStatus(ShipmentStatus::SHIPMENT_STATUS_REJECTED);
            $this->writeToLog('Reject shipment #'.$shipment->getIncrementId());
        } else {
            $shipment->setShipmentStatus(ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED);
            $this->writeToLog('Delivery complete shipment #'.$shipment->getIncrementId());
        }
        $shipment->setDeliveryCompleteDate($data['shipmentDate']);
        //$shipment->setShipmentDate($data['systemDate']);
        try {
            $shipment->save();
        } catch (\Exception $e) {
            $this->writeToLog('Can not save shipment #'.$shipment->getIncrementId());
            $this->writeToLog($e->getMessage());
        }
    }
}
