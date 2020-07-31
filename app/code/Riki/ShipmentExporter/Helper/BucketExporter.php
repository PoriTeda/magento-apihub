<?php
namespace Riki\ShipmentExporter\Helper;

use Magento\Framework\Exception\LocalizedException;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;

class BucketExporter extends \Riki\ShipmentExporter\Helper\AbstractExporter
{
    /**
     * @var \Riki\StockPoint\Api\StockPointRepositoryInterface
     */
    protected $stockPointRepository;

    /**
     * @var string
     */
    protected $currentBucket;

    /**
     * @var array
     */
    protected $itemExported;

    /**
     * BucketExporter constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param DataExporter $dataExporter
     * @param \Riki\Customer\Helper\ConverKana $convertKana
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Riki\Sales\Helper\Order $orderHelper
     * @param \Riki\Tax\Helper\Data $taxHelper
     * @param \Riki\StockPoint\Api\StockPointDeliveryBucketRepositoryInterface $stockpointRepository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Riki\ShipmentExporter\Helper\DataExporter $dataExporter,
        \Riki\Customer\Helper\ConverKana $convertKana,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\Sales\Helper\Order $orderHelper,
        \Riki\Tax\Helper\Data $taxHelper,
        \Riki\StockPoint\Api\StockPointDeliveryBucketRepositoryInterface $stockpointRepository
    ) {
        $this->stockPointRepository = $stockpointRepository;
        $this->currentBucket = '';
        $this->itemExported = [];
        parent::__construct($context, $dataExporter, $convertKana, $dateTime, $orderHelper, $taxHelper);
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param $shippingAddress
     * @param $row
     * @param $b2bflag
     * @param $extraData
     * @return array
     */
    public function getInfoHeader(
        \Magento\Sales\Model\Order\Shipment $shipment,
        $shippingAddress,
        $row,
        $b2bflag,
        $extraData
    ) {
        $order = $shipment->getOrder();
        $bucketId = $shipment->getData('stock_point_delivery_bucket_id');
        $orderBucketId = $order->getData('stock_point_delivery_bucket_id');
        $stockPoint = $this->stockPointRepository->getById($bucketId);
        $shippingPostCode = $this->dataExporterHelper->formatPostCode($stockPoint->getPostcode());
        if (!$shippingPostCode) {
            $shippingPostCode = $this->dataExporterHelper->formatPostCode($shippingAddress->getPostcode());
        }
        $shippingRegion = $this->dataExporterHelper->getPrefectureNameById($stockPoint->getRegionId());
        $orderType = $this->dataExporterHelper->getOrderType($order->getRikiType());
        /* Payment Type */
        $paymentCode = $this->dataExporterHelper->getPaymentType(
            $order->getPayment(),
            $order->getUsedPoint()
        );

        if ($paymentCode == DataExporter::PAYMENT_CODE_CASHONDELIVERY) {
            if ((int) $shipment->getData('base_shopping_point_amount') > 0
                && $shipment->getData('grand_total') == $shipment->getData('base_shopping_point_amount')) {
            // NED-1300 : switch payment code to 01 if shipment grand_total is equal to shopping point allocation.
                $paymentCode = DataExporter::PAYMENT_CODE_NO_PAYMENT_REQUIRED_USE_POINT;
            } elseif ($shipment->getData('grand_total') == 0) {
            // NED-1300 : switch payment code to 01 if shipment grand_total is 0 without shopping point allocation.
                $paymentCode = DataExporter::PAYMENT_CODE_NO_PAYMENT_REQUIRED;
            }
        }

        $warehouseShipment = $this->dataExporterHelper->getShipmentWarehouse($shipment);
        $currentWh = $extraData['currentWarehouse'][$warehouseShipment];
        $customerNameKana = __('Billing name kana 2');
        $customerNameNormal = __('Billing name 2');
        $customerPostcode = $this->dataExporterHelper->formatPostCode(
            $currentWh->getData('postal_code')
        );
        $customerPhone = $currentWh->getData('main_phone');
        $customerAddress1 = $this->dataExporterHelper->getPrefectureNameByCode($currentWh->getData('state'));
        $customerAddress2 = $currentWh->getData('address_line_1');
        $customerAddress3 = $currentWh->getData('address_line_2');
        $customerAddress4 = '';
        $shippingName = $this->dataExporterHelper->convertEncode(
            sprintf(__('Shipping name 1'), $stockPoint->getLastname(), $stockPoint->getFirstname())
        );
        $shippingNameKana = $stockPoint->getLastnameKana().' '.$stockPoint->getFirstnameKana();
        $createdDate = $this->dataExporterHelper->formatDateTime($order->getCreatedAt(), 2, 2);
        /* calculate delivery date */
        if ($stockPoint->getDeliveryDate()) {
            //compare delivery date
            $deliveryDate = $this->dateTime->gmtDate('Ymd', $stockPoint->getDeliveryDate());
            if (strtotime($deliveryDate) < strtotime($extraData['compareDate'])) {
                $deliveryDate = '';
            }
        } else {
            $deliveryDate = '';
        }
        //shipping type
        $shippingType = '0000';
        $deliveryTypeCode = $shipment->getData('delivery_type');
        if (array_key_exists($deliveryTypeCode, $extraData['internalDeliveryCodes'])) {
            $shippingType = $extraData['internalDeliveryCodes'][$deliveryTypeCode];
        }
        //Subscription information
        list($courseName, $courseCode, $courseId, $nextDeliveryDate, $subscriptionOrderTime) =
            $this->dataExporterHelper->getSubscriptionInformation($order);
        $mchDeliveryChoiceCode = '';
        if ($paymentCode == '07' && $courseId) {
            $mchDeliveryChoiceCode = '9001';
        }
        $memberType2 = $order->getData('customer_offline_customer') ? 2 : 1;
        $shippingSystemType = $this->dataExporterHelper->getShipmentSystemType($order);
        $shoshaBusinessCode = $order->getData('shosha_business_code');
        if (array_key_exists($shoshaBusinessCode, $extraData['internalShoshaCode'])) {
            $shoshaCode = $extraData['internalShoshaCode'][$shoshaBusinessCode];
        } else {
            $shoshaCode = '';
        }
        $paymentDetailNumber = $this->dataExporterHelper->getPaymentDetailNumber($order, $shoshaCode);
        //rebuild b2bFlag
        $b2bData = [$stockPoint->getFirstname(), $stockPoint->getLastname(),$stockPoint->getStreet()];
        $b2bflag = $this->dataExporterHelper->getB2bFlagBucket($b2bData);
        if ($b2bflag) {
            $deliveryTime = 0;
        } else {
            $timeslot = $shipment->getDeliveryTime();
            if ($timeslot) {
                $deliveryTime = $this->dataExporterHelper->getDeliveryTimeSlot($timeslot);
            } else {
                $deliveryTime = 0;
            }
        }
        $space = ' ';
        return  [
            '',
            $this->dataExporterHelper->addFullLengthChar('0', 4, $row),
            $this->dataExporterHelper->addFullLengthChar($space, 16, $bucketId, false),
            $this->dataExporterHelper->addFullLengthChar($space, 1, $orderType), //Order type
            $this->dataExporterHelper->addFullLengthChar($space, 1), //Customer type
            $this->dataExporterHelper->addFullLengthChar($space, 16, '0000000000000000', false),
            $this->dataExporterHelper->addFullLengthChar(
                $space,
                102,
                $customerNameKana,
                false
            ),
            $this->dataExporterHelper->addFullLengthChar($space, 102, $customerNameNormal, false),
            $this->dataExporterHelper->addFullLengthChar($space, 7, str_replace('-', '', $customerPostcode), false),
            $this->dataExporterHelper->addFullLengthChar($space, 8, $customerAddress1, false),
            $this->dataExporterHelper->addFullLengthChar($space, 200, $customerAddress2, false),
            $this->dataExporterHelper->addFullLengthChar($space, 100, $customerAddress3, false),
            $this->dataExporterHelper->addFullLengthChar($space, 136, $customerAddress4, false), //Customer address 4
            $this->dataExporterHelper->addFullLengthChar($space, 16, $customerPhone, false),
            $this->dataExporterHelper->addFullLengthChar($space, 1, '0'),
            $this->dataExporterHelper->addFullLengthChar(
                $space,
                102,
                $this->convertKanaHelper->convertKanaToOneByte($shippingNameKana),
                false
            ),
            $this->dataExporterHelper->addFullLengthChar($space, 102, $shippingName, false, false),
            $this->dataExporterHelper->addFullLengthChar($space, 7, $shippingPostCode, false),
            $this->dataExporterHelper->addFullLengthChar($space, 8, $shippingRegion, false),
            $this->dataExporterHelper->addFullLengthChar(
                $space,
                200,
                $stockPoint->getStreet(),
                false
            ),
            $this->dataExporterHelper->addFullLengthChar($space, 100),
            $this->dataExporterHelper->addFullLengthChar($space, 136, '', false), //Delivery address 4
            $this->dataExporterHelper->addFullLengthChar(
                $space,
                16,
                $this->dataExporterHelper->formatPhone($stockPoint->getTelephone()),
                false
            ),
            $this->dataExporterHelper->addFullLengthChar('0', 1),
            $this->dataExporterHelper->addFullLengthChar(
                $space,
                8,
                $this->dateTime->gmtDate('Ymd', $createdDate),
                false
            ),
            $this->dataExporterHelper->addFullLengthChar('0', 6),
            $this->dataExporterHelper->addFullLengthChar('0', 8),
            $this->dataExporterHelper->addFullLengthChar('0', 8),
            $this->dataExporterHelper->addFullLengthChar('0', 15),
            $this->dataExporterHelper->addFullLengthChar($space, 8, $deliveryDate, false),
            $this->dataExporterHelper->addFullLengthChar($space, 2, '00', false),
            $this->dataExporterHelper->addFullLengthChar('0', 1, $deliveryTime, false),
            $this->dataExporterHelper->addFullLengthChar('0', 2),
            $this->dataExporterHelper->addFullLengthChar($space, 8, '00', false), //Delivery type
            $this->dataExporterHelper->addFullLengthChar($space, 2), //Packing slip code
            $this->dataExporterHelper->addFullLengthChar($space, 6), //Shop code
            $this->dataExporterHelper->addFullLengthChar($space, 4),
            $this->dataExporterHelper->addFullLengthChar($space, 20),
            $this->dataExporterHelper->addFullLengthChar($space, 40),
            $this->dataExporterHelper->addFullLengthChar($space, 40),
            $this->dataExporterHelper->addFullLengthChar('0', 15),
            $this->dataExporterHelper->addFullLengthChar($space, 16, $orderBucketId, false),
            $this->dataExporterHelper->addFullLengthChar('0', 1, 0, false), //Member type
            $this->dataExporterHelper->addFullLengthChar(
                '0',
                15,
                (int)$this->dataExporterHelper->getWrappingFeeShipment($shipment),
                true
            ),
            $this->dataExporterHelper->addFullLengthChar('0', 1, '0', false), //Order type
            $this->dataExporterHelper->addFullLengthChar('0', 8, 0, true), //Trade in discount amount
            $this->dataExporterHelper->addFullLengthChar('0', 1, 0, false), //Assortment instructtion
            $this->dataExporterHelper->addFullLengthChar('0', 1, '0', false), //Pickup delivery data
            $this->dataExporterHelper->addFullLengthChar('0', 8, $shippingType, true), //Shipping type
            $this->dataExporterHelper->addFullLengthChar('0', 8),
            $this->dataExporterHelper->addFullLengthChar('0', 8),
            $this->dataExporterHelper->addFullLengthChar('0', 8),
            $this->dataExporterHelper->addFullLengthChar($space, 80), //Delivery course name
            $this->dataExporterHelper->addFullLengthChar($space, 3),
            $this->dataExporterHelper->addFullLengthChar($space, 14),
            $this->dataExporterHelper->addFullLengthChar('0', 15, 0, true),
            $this->dataExporterHelper->addFullLengthChar($space, 4), //Apology letter type
            $this->dataExporterHelper->addFullLengthChar($space, 8, $extraData['currentDate'], false),
            $this->dataExporterHelper->addFullLengthChar($space, 1, $memberType2, false), //Member type
            $this->dataExporterHelper->addFullLengthChar('0', 1), //Order format
            $this->dataExporterHelper->addFullLengthChar($space, 256), //Company name
            $this->dataExporterHelper->addFullLengthChar($space, 1, $shippingSystemType, false), //Shipping system type
            $this->dataExporterHelper->addFullLengthChar(
                $space,
                5,
                $mchDeliveryChoiceCode,
                false
            ), //MCH delivery choice code
            $this->dataExporterHelper->addFullLengthChar($space, 1, $paymentDetailNumber, false),
            $this->dataExporterHelper->addFullLengthChar('0', 1), // receipt flag
            $this->dataExporterHelper->addFullLengthChar('0', 1, $b2bflag, true),
            $this->dataExporterHelper->addFullLengthChar($space, 1), //Eng flg
            $this->dataExporterHelper->addFullLengthChar($space, 1), // Magento flg
            $this->dataExporterHelper->addFullLengthChar($space, 5) // Filter
        ];
    }

    /**
     * @param $shipment
     * @param $orderItemData
     * @param $fileDetail
     * @param $rowDetailFinal
     */
    public function putShipmentDetailData(
        $shipment,
        $orderItemData,
        $fileDetail,
        &$rowDetailFinal
    ) {
        $orderItem = $orderItemData['orderItem'];
        if (!in_array($orderItem->getSku(), $this->itemExported[$this->currentBucket])) {
            $this->itemExported[$this->currentBucket][] = $orderItem->getSku();
            $indexer = array_search($orderItem->getSku(), $this->itemExported[$this->currentBucket]) + 1;
            $shipmentItem = $orderItemData['shipmentItem'];
            $productName = $this->dataExporterHelper->cleanProductName($orderItem->getName());
            $wrappingObject = $this->dataExporterHelper->getWrappingDetail($orderItem->getGwId());
            $wrappingName = $wrappingObject[0];
            $wrappingCode = $wrappingObject[1];
            $totalAmountGiftWrap = $orderItemData['totalAmountGiftWrap'];
            $totalAmountGiftWrapTax = $orderItemData['totalAmountGiftWrapTax'];
            $productUnitCase = (null != $shipmentItem->getUnitCase()) ? ($shipmentItem->getUnitCase()) : 'EA';
            if ($orderItem->getProductType() == 'bundle') {
                $bundleFlag = '1';
            } else {
                $bundleFlag = '';
            }
            $attachedProductType = (int)($shipmentItem->getData('free_of_charge'));
            $space = ' ';
            $bucketId = $shipment->getData('stock_point_delivery_bucket_id');
            $qty = $this->dataExporterHelper->getQtyBucketShipment($bucketId, $orderItem->getSku());
            $assortmentNumber = (int)$orderItem->getTaxPercent() == (int) $this->taxHelper->getCompareTaxPercent()
                ? 1 : 0;
            $shipmentDetail =
                [
                    '',
                    $this->dataExporterHelper->addFullLengthChar('0', 4, $rowDetailFinal),
                    $this->dataExporterHelper->addFullLengthChar($space, 16, $bucketId, false),
                    $this->dataExporterHelper->addFullLengthChar($space, 16, $indexer, false),
                    $this->dataExporterHelper->addFullLengthChar($space, 24, $orderItem->getSku(), false),
                    $this->dataExporterHelper->addFullLengthChar('0', 8),
                    $this->dataExporterHelper->addFullLengthChar('0', 2, (int)$orderItem->getTaxPercent()),
                    $this->dataExporterHelper->addFullLengthChar('0', 8, $qty, true),
                    $this->dataExporterHelper->addFullLengthChar('0', 8),
                    $this->dataExporterHelper->addFullLengthChar($space, 100, $productName, false),
                    $this->dataExporterHelper->addFullLengthChar('0', 15),
                    $this->dataExporterHelper->addFullLengthChar('0', 8),
                    $this->dataExporterHelper->addFullLengthChar('0', 15),
                    $this->dataExporterHelper->addFullLengthChar($space, 16, '', false),
                    $this->dataExporterHelper->addFullLengthChar($space, 16, $wrappingCode, false),
                    $this->dataExporterHelper->addFullLengthChar($space, 80, $wrappingName, false),
                    $this->dataExporterHelper->addFullLengthChar('0', 16, $totalAmountGiftWrap, true),
                    $this->dataExporterHelper->addFullLengthChar('0', 8, $totalAmountGiftWrapTax, true),
                    $this->dataExporterHelper->addFullLengthChar($space,1, $assortmentNumber),
                    $this->dataExporterHelper->addFullLengthChar($space, 16),
                    $this->dataExporterHelper->addFullLengthChar($space, 80),
                    $this->dataExporterHelper->addFullLengthChar($space, 400),
                    $this->dataExporterHelper->addFullLengthChar($space, 80),
                    $this->dataExporterHelper->addFullLengthChar($space, 80, '', false),
                    $this->dataExporterHelper->addFullLengthChar('0', 8, '0', true),
                    $this->dataExporterHelper->addFullLengthChar('0', 8, '0', true),
                    $this->dataExporterHelper->addFullLengthChar('0', 1, $attachedProductType, false),
                    $this->dataExporterHelper->addFullLengthChar('0', 1),
                    $this->dataExporterHelper->addFullLengthChar('0', 8, '0', true),
                    $this->dataExporterHelper->addFullLengthChar('0', 1, '0', false),//Assortment instruction data
                    $this->dataExporterHelper->addFullLengthChar('0', 1, '0', false),
                    $this->dataExporterHelper->addFullLengthChar($space, 2, $productUnitCase, false), //Unit
                    $this->dataExporterHelper->addFullLengthChar($space, 24, $orderItem->getSku(), false),
                    $this->dataExporterHelper->addFullLengthChar($space, 1),
                    $this->dataExporterHelper->addFullLengthChar($space, 16, $orderItemData['bundleSkuCode'], false),
                    $this->dataExporterHelper->addFullLengthChar($space, 1, $bundleFlag, false),
                    $this->dataExporterHelper->addFullLengthChar($space, 959)
                ];
            $rowDetailFinal++;
            $fileDetail->write($this->dataExporterHelper->writeFileTxtSAP($shipmentDetail));
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param $rowExport
     * @param $filesExport
     *
     * @throws LocalizedException
     */
    public function exportShipmentDetailBucket(
        \Magento\Sales\Model\Order\Shipment $shipment,
        & $rowExport,
        $filesExport
    ) {
        switch ($shipment->getWarehouse()) {
            case \Riki\ShipmentExporter\Helper\DataExporter::WH_BIZEX:
                $fileDetail = $filesExport['fileDetailBizex'];
                $wareHouseIndex = 'rowDetailBizex';
                break;
            case \Riki\ShipmentExporter\Helper\DataExporter::WH_HITACHI:
                $fileDetail = $filesExport['fileDetailHitachi'];
                $wareHouseIndex = 'rowDetailHitachi';
                break;
            case \Riki\ShipmentExporter\Helper\DataExporter::WH_TOYO:
                $fileDetail = $filesExport['fileDetailToyo'];
                $wareHouseIndex = 'rowDetailToyo';
                break;
            default:
                throw new LocalizedException(
                    __('Shipment exporter doesn\'t support warehouse %1', $shipment->getWarehouse())
                );
        }
        $orderItems = $this->buildOrderItemData($shipment);
        foreach ($orderItems as $orderItemData) {
            $this->putShipmentDetailData(
                $shipment,
                $orderItemData,
                $fileDetail,
                $rowExport[$wareHouseIndex]
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function exportShipment(
        \Magento\Sales\Model\Order\Shipment $shipment,
        & $rowExport,
        $filesExport,
        $extraData
    ) {
        $bucketId = $shipment->getData('stock_point_delivery_bucket_id');
        $shipment->setIsExported(1);
        $shipment->setShipmentDate($this->dateTime->gmtDate('Y-m-d H:i:s'));
        $shipment->setShipmentStatus(ShipmentStatus::SHIPMENT_STATUS_EXPORTED);
        if ($bucketId) {
            if ($bucketId != $this->currentBucket) {
                $this->dataExporterHelper->writeToLog(sprintf(__('Export bucket number: %s'), $bucketId));
                $this->dataExporterHelper->writeToLog(sprintf(
                    __(
                        'Export stock point shipment number: %s'
                    ),
                    $shipment->getIncrementId()
                ));
                $this->exportShipmentHeader(
                    $shipment,
                    $rowExport,
                    $filesExport,
                    $extraData
                );
                $this->currentBucket = $bucketId;
                $this->itemExported[$bucketId] = [];
            }
            $this->exportShipmentDetailBucket(
                $shipment,
                $rowExport,
                $filesExport
            );
        }
    }
}
