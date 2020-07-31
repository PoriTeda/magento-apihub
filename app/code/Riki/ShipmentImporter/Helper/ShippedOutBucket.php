<?php

namespace Riki\ShipmentImporter\Helper;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Riki\SapIntegration\Model\Api\Shipment;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus as OrderStatus;

class ShippedOutBucket extends AbstractHelper
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
     * @var \Magento\Sales\Api\Data\ShipmentTrackInterface
     */
    protected $shipmentTrackInterface;
    /**
     * @var \Magento\Sales\Api\ShipmentTrackRepositoryInterface
     */
    protected $shipmentTrackRepository;
    /**
     * @var
     */
    protected $shipmentTractCollectionFactory;

    /**
     * @var \Riki\SapIntegration\Api\ShipmentSapExportedRepositoryInterface
     */
    protected $shipmentSapExportedRepository;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * ShippedOutBucket constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param Data $dataHelper
     * @param Email $emailHelper
     * @param \Magento\Sales\Api\Data\ShipmentTrackInterface $shipmentTrack
     * @param \Magento\Sales\Model\Order\Shipment\TrackFactory $shipmentTrackRepository
     * @param \Magento\Sales\Api\ShipmentTrackRepositoryInterface $shipmentTrackRepositoryFactory
     * @param \Riki\SapIntegration\Api\ShipmentSapExportedRepositoryInterface $shipmentSapExportedRepository
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Riki\ShipmentImporter\Helper\Data $dataHelper,
        \Riki\ShipmentImporter\Helper\Email $emailHelper,
        \Magento\Sales\Api\Data\ShipmentTrackInterface $shipmentTrack,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $shipmentTrackRepository,
        \Magento\Sales\Api\ShipmentTrackRepositoryInterface $shipmentTrackRepositoryFactory,
        \Riki\SapIntegration\Api\ShipmentSapExportedRepositoryInterface $shipmentSapExportedRepository,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->dataHelper = $dataHelper;
        $this->shipmentTrackInterface = $shipmentTrack;
        $this->shipmentTrackRepository = $shipmentTrackRepository;
        $this->shipmentTractCollectionFactory = $shipmentTrackRepositoryFactory;
        $this->emailHelper = $emailHelper;
        $this->shipmentSapExportedRepository = $shipmentSapExportedRepository;
        $this->registry = $registry;
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
     * @param $bucketId
     * @param $data
     */
    public function importBucketOrder($bucketId, $data)
    {
        $orders = $this->getBucketOrders($bucketId);
        if ($orders) {
            foreach ($orders as $order) {
                $shipments = $this->getShipmentsByOrderId($order->getId());
                if ($this->canShippedOutBucketShipment($order)) {
                    $this->importBucketShipments($order, $shipments, $data);
                }
            }
        }
    }

    /**
     * @param $shipments
     */
    public function importBucketShipments($order, $shipments, $data)
    {
        $sapFlag = $data['sapFlag'];
        $allowStatusShipment = [
            ShipmentStatus::SHIPMENT_STATUS_EXPORTED,
            ShipmentStatus::SHIPMENT_STATUS_REJECTED,
            ShipmentStatus::SHIPMENT_STATUS_CREATED,
        ];
        $allowStatusOrder = [
            OrderStatus::STATUS_ORDER_IN_PROCESSING,
            OrderStatus::STATUS_ORDER_PARTIALLY_SHIPPED
        ];
        foreach ($shipments as $shipment) {
            $shipStatus = $shipment->getShipmentStatus();
            $orderStatus = $order->getStatus();
            if (in_array($shipStatus, $allowStatusShipment) && in_array($orderStatus, $allowStatusOrder)) {
                $shipment->setShipmentStatus(ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT);
                $shipment->setIsReconciliationExported(1);
                /*System date when we receive the ship-out message*/
                $shipment->setShipmentDate($data['systemDate']);
                /*The actual Ship-out date mentioned in the ship-out message*/
                $shipment->setShippedOutDate($data['shipDate']);
                /*Waiting for export to SAP for shipped_out shipment*/

                /** $sapFlag in (0, 1, null) */
                if (trim($sapFlag) === '0') { //0 <=> import 0
                    $shipment->setIsExportedSap(Shipment::NO_NEED_TO_EXPORT);
                } else { // 1 and null <=> import 1
                    $shipment->setIsExportedSap(Shipment::WAITING_FOR_EXPORT);
                }

                /* set payment status. Bucket Shipment for Paygent method only */
                $shipment->setData('payment_date', $data['shipDate']);
                $this->importShipmentTracking($data, $shipment);
                $this->updateResult($shipment, $order);
                $this->writeToLog(__('Shipped shipment %1 out', $shipment->getIncrementId()));
            } else {
                $this->writeToLog(
                    __(
                        'Import failed. Please check status of shipment %1 or their order status',
                        $shipment->getIncrementId()
                    )
                );
            }
        }
    }

    /**
     * @param $shipment
     * @param $order
     * @throws \Exception
     */
    public function updateResult($shipment, $order)
    {
        try {
            $shipment->save();

            /*sync data for shipment sap exported after shipment was shipped out*/
            $this->syncDataForShipmentSapExported($shipment);

            //update order
            $order->setStatus(OrderStatus::STATUS_ORDER_SHIPPED_ALL);
            $order->addStatusToHistory(
                OrderStatus::STATUS_ORDER_SHIPPED_ALL,
                __('Imported from 3PL - Shipment Shipped out, shipment number #'.$shipment->getIncrementId()),
                false
            );
            $paymentAgent = $this->dataHelper->getPaymentAgentByOrderIncrementId($order->getIncrementId());
            if (!empty($paymentAgent)) {
                $order->setData('payment_agent', $paymentAgent);
            }
            $order->save();
            $this->writeToLog(__('Import shipment %1 success.', $shipment->getIncrementId()));
        } catch (\Exception $e) {
            $this->writeToLog(__('Import shipment %1 failed.', $shipment->getIncrementId()));
            $this->writeToLog($e->getMessage());
            throw $e;
        }
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
    public function canShippedOutBucketShipment($order)
    {
        if ($order->getStatus() == OrderStatus::STATUS_ORDER_IN_PROCESSING) {
            $shipments = $this->getShipmentsByOrderId($order->getId());
            foreach ($shipments as $shipment) {
                $shipmentStatus = $shipment->getShipmentStatus();
                $isChirashi = $shipment->getData('is_chirashi');
                $isZshim = $shipment->getData('ship_zsim');
                if (!$isChirashi && !$isZshim) {
                    if ($shipmentStatus!=ShipmentStatus::SHIPMENT_STATUS_EXPORTED) {
                        $message = __(
                            'Could not be shipped out because shipment: %1 is not exported.',
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
                'Status of order# : %1 is %2. Can not ship order out.',
                $order->getIncrementId(),
                $order->getStatus()
            );
            $this->writeToLog($message);
            return false;
        }
    }

    /**
     * @param $item
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     */
    protected function importShipmentTracking($item, & $shipment)
    {
        $trackingNumber = $item[5];
        if ($trackingNumber) {
            if (!$this->checkExistTracking($trackingNumber, $shipment->getEntityId())) {
                $trackings = explode(";", $item[5]);
                if ($item[9]) {
                    $this->importTracking($item, $trackings, $shipment);
                }
            } else {
                $this->writeToLog(__(
                    'Tracking number %1 is already exists in shipment : %2',
                    $trackingNumber,
                    $shipment->getIncrementId()
                ));
            }
        }
    }

    /**
     * Import tracking number of shipment
     *
     * @param $item
     * @param $trackings
     * @param $shipment
     */
    public function importTracking($item, $trackings, & $shipment)
    {
        $carrierCode = $this->dataHelper->getCarrierCode($item[9]);
        $carrierTitle = $this->dataHelper->getCarrierTitle($item[9]);
        if ($carrierCode && $carrierTitle) {
            $trackingCodes = [];
            $trackingUrl = [];
            foreach ($trackings as $track) {
                $trackingCodes[] = $track;
                $trackingUrl[] = $this->dataHelper->getCarrierUrl($carrierCode, $track);
                //validate exist track number
                if ($track && $carrierCode) {
                    $shipmentTrack = $this->shipmentTrackRepository->create()
                        ->setTrackNumber($track)
                        ->setCarrierCode($carrierCode)
                        ->setTitle($carrierTitle);
                    $shipment->addTrack($shipmentTrack);
                }
            }
            //send email
            $canSendBeforeXdays = $this->dataHelper->checkXdaysBeforeSendmail($item['shipDate'], $item['systemDate']);
            if ($trackingCodes && $trackingUrl && $canSendBeforeXdays) {
                $emailTemplateVariables = $this->emailHelper->getEmailParameters($shipment);
                $this->dataHelper->sendTrackingCodeEmail(
                    $emailTemplateVariables
                );
                $this->registry->unregister('last_time_email_sent');
                $this->registry->register('last_time_email_sent', time());
            }
        }
    }
    /**
     * @param $trackingNumber
     * @param $shipmentId
     * @return bool
     */
    public function checkExistTracking($trackingNumber, $shipmentId)
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('track_number', $trackingNumber)
            ->addFilter('parent_id', $shipmentId)
            ->create();
        $trackingCollection = $this->shipmentTractCollectionFactory->getList($criteria);
        if ($trackingCollection->getTotalCount()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Change SAP flag after shipment was shipped out
     *
     * @param $shipment
     */
    protected function syncDataForShipmentSapExported(
        \Magento\Sales\Model\Order\Shipment $shipment
    ) {
        try {
            $shipmentSapExported = $this->shipmentSapExportedRepository->getById($shipment->getId());
        } catch (\Exception $e) {
            $this->writeToLog('Cannot get SAP data for shipment #'.$shipment->getIncrementId());
            return;
        }

        $shipmentSapExported->setIsExportedSap($shipment->getIsExportedSap());

        try {
            $this->shipmentSapExportedRepository->save($shipmentSapExported);
            $this->writeToLog(
                'SAP flag of shipment #'.$shipment->getIncrementId().
                ' has been changed to '.$shipmentSapExported->getIsExportedSap()
            );
        } catch (\Exception $e) {
            $this->writeToLog(
                'Cannot change SAP flag to '.$shipment->getIsExportedSap().' for shipment #'.$shipment->getIncrementId()
            );
            return;
        }
    }
}
