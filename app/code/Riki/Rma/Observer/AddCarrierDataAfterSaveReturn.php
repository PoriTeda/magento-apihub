<?php

namespace Riki\Rma\Observer;

class AddCarrierDataAfterSaveReturn implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Rma\Api\TrackRepositoryInterface
     */
    protected $rmaTrackRepository;

    /**
     * @var \Magento\Rma\Model\ShippingFactory
     */
    protected $rmaShippingFactory;

    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $rmaHelper;

    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Rma\Api\TrackRepositoryInterface $rmaTrackRepository,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Rma\Model\ShippingFactory $rmaShippingFactory,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Rma\Helper\Data $rmaHelper
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->rmaTrackRepository = $rmaTrackRepository;
        $this->rmaShippingFactory = $rmaShippingFactory;
        $this->shipmentRepository = $shipmentRepository;
        $this->logger = $logger;
        $this->rmaHelper = $rmaHelper;

    }

    /**
     * Customized data will be handled before a RMA is saved.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Rma\Model\Rma $rma */
        $rma = $observer->getRma();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $rma->getOrder();

        if (!$order || $order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return;
        }

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();

        if (!$payment
            || !$payment->getMethod()
        ) {
            return;
        }

        /*payment method from original order*/
        $originalOrderPaymentMethod = $payment->getMethod();

        if (!$this->canAddCarrierAutomatically($rma, $originalOrderPaymentMethod)) {
            return;
        }

        /*for case rma did not provided any shipment,
        find the first shipment with carrier data
        apply the tracking data if possible
        Else default carrier data will be add*/
        if (!$rma->getRmaShipmentNumber()) {
            $firstShipmentData = $this->getFirstShipmentDataForRma($rma);
            if (!$firstShipmentData) {
                $this->addDefaultCarrierForRma($rma);
            } else {
                $firstShipmentTrackData = $firstShipmentData->getAllTracks();

                if (!$firstShipmentTrackData) {
                    $this->addDefaultCarrierForRma($rma);
                } else {
                    /*get carrier data from original shipment*/
                    $carrierData = $this->getCarrierDataByShipmentTrackData($firstShipmentTrackData);

                    if (!$carrierData) {
                        $this->addDefaultCarrierForRma($rma);
                    }

                    $carrierData['rma_entity_id'] = $rma->getId();
                    $this->createCarrierByData($carrierData);
                }
            }
        }

        /** @var \Magento\Sales\Model\Order\Shipment $shipmentData */
        $shipmentData = $this->getShipmentDataForRma($rma);

        if (!$shipmentData) {
            return;
        }

        $shipmentTrackData = $shipmentData->getAllTracks();

        if (!$shipmentTrackData) {
            return;
        }

        /*get carrier data from original shipment*/
        $carrierData = $this->getCarrierDataByShipmentTrackData($shipmentTrackData);

        if (!$carrierData) {
            return;
        }

        $carrierData['rma_entity_id'] = $rma->getId();
        $this->createCarrierByData($carrierData);
    }

    /**
     * can add carrier automatically
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @param $originalOrderPaymentMethod
     * @return bool
     */
    private function canAddCarrierAutomatically(
        \Magento\Rma\Model\Rma $rma,
        $originalOrderPaymentMethod
    ) {
        $allowedPaymentMethod = \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE;

        if ($originalOrderPaymentMethod != $allowedPaymentMethod) {
            return false;
        }

        if ($this->hasCarrierData($rma)) {
            return false;
        }

        return true;
    }

    /**
     * rma has carrier data or not
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool
     */
    private function hasCarrierData(
        \Magento\Rma\Model\Rma $rma
    ) {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('rma_entity_id', $rma->getId())
            ->create();

        $carrierData = $this->rmaTrackRepository->getList($criteria);

        if ($carrierData->getSize()) {
            return true;
        }

        return false;
    }

    /**
     * add default carrier for rma
     *
     * @param \Magento\Rma\Model\Rma $rma
     */
    private function addDefaultCarrierForRma(
        \Magento\Rma\Model\Rma $rma
    ) {
        $carrierData = $this->rmaHelper->getDefaultCarrierDataForCod();
        $carrierData['rma_entity_id'] = $rma->getId();
        $this->createCarrierByData($carrierData);
    }

    /**
     * get shipment data for rma
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool|\Magento\Framework\DataObject
     */
    private function getShipmentDataForRma(
        \Magento\Rma\Model\Rma $rma
    ) {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('order_id', $rma->getOrderId())
            ->addFilter('ship_zsim', 1, 'neq')
            ->addFilter('increment_id', $rma->getRmaShipmentNumber(), 'eq')
            ->create();

        $shipmentCollection = $this->shipmentRepository->getList($criteria);

        if ($shipmentCollection->getSize()) {
            return $shipmentCollection->setPageSize(1)->getFirstItem();
        }

        return false;
    }

    /**
     * get shipment data for rma
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool|\Magento\Framework\DataObject
     */
    private function getFirstShipmentDataForRma(
        \Magento\Rma\Model\Rma $rma
    ) {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('order_id', $rma->getOrderId())
            ->addFilter('ship_zsim', 1, 'neq')
            ->create();

        $shipmentCollection = $this->shipmentRepository->getList($criteria);

        if ($shipmentCollection->getSize()) {
            return $shipmentCollection->setPageSize(1)->getFirstItem();
        }

        return false;
    }

    /**
     * get carrier data by shipment track data
     *
     * @param array $shipmentTrackData
     * @return array
     */
    private function getCarrierDataByShipmentTrackData(array $shipmentTrackData)
    {
        $carrierData = [];

        /** @var \Magento\Sales\Model\Order\Shipment\Track $shipmentTrack */
        foreach ($shipmentTrackData as $shipmentTrack) {
            $carrierData['carrier_code'] = $shipmentTrack->getCarrierCode();
            $carrierData['carrier_title'] = $shipmentTrack->getTitle();
            $carrierData['track_number'] = $shipmentTrack->getTrackNumber();
            break;
        }

        return $carrierData;
    }

    /**
     * create carrier by data
     *
     * @param $carrierData
     */
    private function createCarrierByData($carrierData)
    {
        $carrierModel = $this->rmaShippingFactory->create();
        $carrierModel->setData($carrierData)
            ->setIsAdmin(true);

        try {
            $carrierModel->save();
        } catch (\Exception $e) {
            $this->logger->info('Cannot create carrier for RMA.');
            $this->logger->info($e->getMessage());
        }
    }
}
