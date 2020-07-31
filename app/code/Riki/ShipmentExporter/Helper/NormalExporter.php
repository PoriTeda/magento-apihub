<?php
namespace Riki\ShipmentExporter\Helper;

use Magento\Framework\Exception\LocalizedException;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use \Riki\ShipmentExporter\Helper\DataExporter;

class NormalExporter extends \Riki\ShipmentExporter\Helper\AbstractExporter
{
    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param \Magento\Sales\Api\Data\OrderAddressInterface $shippingAddress
     * @param int $row
     * @param int $b2bflag
     * @param array $extraData
     * @return array
     */
    public function getInfoHeader(
        \Magento\Sales\Model\Order\Shipment $shipment,
        \Magento\Sales\Api\Data\OrderAddressInterface $shippingAddress,
        int $row,
        int $b2bflag,
        array $extraData
    ) {
        $order = $shipment->getOrder();
        $billingAddress = $shipment->getBillingAddress();
        $billingAddressType = strtolower($billingAddress->getData('riki_type_address'));
        $shippingAddressType = strtolower($shippingAddress->getData('riki_type_address'));
        $shippingPostCode = $this->dataExporterHelper->formatPostCode($shippingAddress->getPostcode());
        $orderType = $this->dataExporterHelper->getOrderType($order->getRikiType());
        $consumerDbID = $order->getData('customer_consumer_db_id');
        $isAmbassador = (bool) $order->getData('customer_amb_type');
        /* Payment Type */
        $paymentCode = $this->dataExporterHelper->getPaymentType(
            $order->getPayment(),
            $order->getUsedPoint()
        );
        $needToCheckPaymentCodes = [
            DataExporter::PAYMENT_CODE_CASHONDELIVERY,
            DataExporter::PAYMENT_CODE_NP_ATOBARAI
        ];
        if (in_array($paymentCode, $needToCheckPaymentCodes)) {
            if ((int) $shipment->getData('base_shopping_point_amount') > 0
                && $shipment->getData('grand_total') == $shipment->getData('base_shopping_point_amount')) {
                // NED-1300 : switch payment code to 01 if shipment grand_total is equal to shopping point allocation.
                $paymentCode = DataExporter::PAYMENT_CODE_NO_PAYMENT_REQUIRED_USE_POINT;
            } elseif ($shipment->getData('grand_total') == 0) {
                // NED-1300 : switch payment code to 00 if shipment grand_total is 0 without shopping point allocation.
                $paymentCode = DataExporter::PAYMENT_CODE_NO_PAYMENT_REQUIRED;
            }
        }

        $warehouseShipment = $this->dataExporterHelper->getShipmentWarehouse($shipment);
        $currentWh = $extraData['currentWarehouse'][$warehouseShipment];
        $shoshaCmpName = $order->getData('customer_company_name');
        $addressData = $this->dataExporterHelper->getAddressData(
            $shipment,
            $currentWh,
            $billingAddressType,
            $isAmbassador,
            $shoshaCmpName,
            $order->getData('is_gift_order')
        );

        $shipmentCollection = $order->getShipmentsCollection();

        if($paymentCode == '04' && !$order->getData('is_gift_order') && $shipmentCollection->getSize() == 1) {
            $addressData['receiptFlag'] = 1;
        } else {
            $addressData['receiptFlag'] = 0;
        }

        $customerPostcode = $this->dataExporterHelper->formatPostCode($addressData['customerPostcode']);
        if ($isAmbassador && $shippingAddressType == 'company') {
            $shippingName = $this->dataExporterHelper->formatCustomerName(
                $shippingAddress->getLastname(),
                $shippingAddress->getFirstname()
            );
        } else {
            $shippingName = $this->dataExporterHelper->convertEncode(
                sprintf(__('Shipping name 1'), $shippingAddress->getLastname(), $shippingAddress->getFirstname())
            );
        }
        $shippingNameKana = $shippingAddress->getLastnamekana().' '.$shippingAddress->getFirstnamekana();
        $createdDate = $this->dataExporterHelper->formatDateTime($order->getCreatedAt(), 2, 2);
        /* calculate delivery date */
        if ($shipment->getData('delivery_date')) {
            //compare delivery date
            $deliveryDate = $this->dateTime->gmtDate('Ymd', $shipment->getData('delivery_date'));
            if (strtotime($deliveryDate) < strtotime($extraData['compareDate'])) {
                $deliveryDate = '';
            }
        } else {
            $deliveryDate = '';
        }
        $timeslot = $shipment->getDeliveryTime();
        $timeInterval = 0;
        if ($timeslot) {
            $timeInterval = $this->dataExporterHelper->getDeliveryTimeSlot($timeslot);
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
        $orderChannel = $this->dataExporterHelper->getOrderChannel($order->getData('order_channel'));
        $shippingSystemType = $this->dataExporterHelper->getShipmentSystemType($order);
        $shoshaBusinessCode = $order->getData('shosha_business_code');
        if (array_key_exists($shoshaBusinessCode, $extraData['internalShoshaCode'])) {
            $shoshaCode = $extraData['internalShoshaCode'][$shoshaBusinessCode];
        } else {
            $shoshaCode = '';
        }
        $paymentDetailNumber = $this->dataExporterHelper->getPaymentDetailNumber($order, $shoshaCode);
        $space = ' ';
        list (
            $totalAmountTax8Percent,
            $totalAmountTax10Percent,
            $usedPointTax8Percent,
            $usedPointTax10Percent,
            $totalTaxAmountTax8percent,
            $totalTaxAmountTax10percent
        ) = $this->orderHelper->splitShipmentAmountsByTaxPercent($shipment);

        $companyNameUpperSection = $this->dataExporterHelper->addFullLengthChar(
            '0',
            8,
            $totalAmountTax8Percent,
            true
        );
        $companyNameUpperSection .= $this->dataExporterHelper->addFullLengthChar(
            '0',
            8,
            $totalAmountTax10Percent,
            true
        );
        $companyNameUpperSection .= $this->dataExporterHelper->addFullLengthChar(
            '0',
            8,
            $usedPointTax8Percent,
            true
        );
        $companyNameUpperSection .= $this->dataExporterHelper->addFullLengthChar(
            '0',
            8,
            $usedPointTax10Percent,
            true
        );
        $companyNameUpperSection .= $this->dataExporterHelper->addFullLengthChar(
            '0',
            8,
            $totalTaxAmountTax8percent,
            true
        );
        $companyNameLowerSection = $this->dataExporterHelper->addFullLengthChar(
            '0',
            8,
            $totalTaxAmountTax10percent,
            true
        );
        return  [
            '',
            $this->dataExporterHelper->addFullLengthChar('0', 4, $row),
            $this->dataExporterHelper->addFullLengthChar($space, 16, $shipment->getIncrementId(), false),
            $this->dataExporterHelper->addFullLengthChar($space, 1, $orderType), //Order type
            $this->dataExporterHelper->addFullLengthChar($space, 1), //Customer type
            $this->dataExporterHelper->addFullLengthChar($space, 16, $consumerDbID, false),
            $this->dataExporterHelper->addFullLengthChar(
                $space,
                102,
                $this->convertKanaHelper->convertKanaToOneByte($addressData['customerNameKana']),
                false
            ),
            $this->dataExporterHelper->addFullLengthChar($space, 102, $addressData['customerName'], false, false),
            $this->dataExporterHelper->addFullLengthChar($space, 7, str_replace('-', '', $customerPostcode), false),
            $this->dataExporterHelper->addFullLengthChar($space, 8, $addressData['customerAddress1'], false),
            $this->dataExporterHelper->addFullLengthChar($space, 200, $addressData['customerAddress2'], false),
            $this->dataExporterHelper->addFullLengthChar($space, 100, $addressData['customerAddress3'], false),
            $this->dataExporterHelper->addFullLengthChar($space, 136, '', false), //Customer address 4
            $this->dataExporterHelper->addFullLengthChar($space, 16, $addressData['customerPhone'], false),
            $this->dataExporterHelper->addFullLengthChar($space, 1, $addressData['shippingAddressType']),
            $this->dataExporterHelper->addFullLengthChar(
                $space,
                102,
                $this->convertKanaHelper->convertKanaToOneByte($shippingNameKana),
                false
            ),
            $this->dataExporterHelper->addFullLengthChar($space, 102, $shippingName, false, false),
            $this->dataExporterHelper->addFullLengthChar($space, 7, $shippingPostCode, false),
            $this->dataExporterHelper->addFullLengthChar($space, 8, $shippingAddress->getRegion(), false),
            $this->dataExporterHelper->addFullLengthChar(
                $space,
                200,
                $this->dataExporterHelper->getStreetFull($shippingAddress, $space),
                false
            ),
            $this->dataExporterHelper->addFullLengthChar($space, 100),
            $this->dataExporterHelper->addFullLengthChar($space, 136, '', false), //Delivery address 4
            $this->dataExporterHelper->addFullLengthChar(
                $space,
                16,
                $this->dataExporterHelper->formatPhone($shippingAddress->getTelephone()),
                false
            ),
            $this->dataExporterHelper->addFullLengthChar('0', 1, (int)($order->getData('customer_gender')), false),
            $this->dataExporterHelper->addFullLengthChar(
                $space,
                8,
                $this->dateTime->gmtDate('Ymd', $createdDate),
                false
            ),
            $this->dataExporterHelper->addFullLengthChar(
                $space,
                6,
                $this->dateTime->gmtDate('His', $createdDate),
                false
            ),
            $this->dataExporterHelper->addFullLengthChar('0', 8, (int)($shipment->getShipmentFee()), true),
            $this->dataExporterHelper->addFullLengthChar('0', 8, (int)($shipment->getPaymentFee()), true),
            $this->dataExporterHelper->addFullLengthChar(
                '0',
                15,
                (int)$this->dataExporterHelper->getFinalTotal($shipment),
                true
            ),
            $this->dataExporterHelper->addFullLengthChar($space, 8, $deliveryDate, false),
            $this->dataExporterHelper->addFullLengthChar($space, 2, '00', false),
            $this->dataExporterHelper->addFullLengthChar($space, 1, $timeInterval, false),
            $this->dataExporterHelper->addFullLengthChar($space, 2, $paymentCode, false),
            $this->dataExporterHelper->addFullLengthChar($space, 8, '00', false), //Delivery type
            $this->dataExporterHelper->addFullLengthChar($space, 2), //Packing slip code
            $this->dataExporterHelper->addFullLengthChar($space, 6), //Shop code
            $this->dataExporterHelper->addFullLengthChar($space, 4, $extraData['category_code'], false),
            $this->dataExporterHelper->addFullLengthChar($space, 20, $courseCode, false),
            $this->dataExporterHelper->addFullLengthChar($space, 40, $companyNameUpperSection),
            $this->dataExporterHelper->addFullLengthChar($space, 40, $companyNameLowerSection, false),
            $this->dataExporterHelper->addFullLengthChar(
                '0',
                15,
                (int)$this->dataExporterHelper->getTotalAmountVoucher($shipment),
                true
            ),
            $this->dataExporterHelper->addFullLengthChar($space, 16, $order->getIncrementId(), false),
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
            $this->dataExporterHelper->addFullLengthChar('0', 8, $order->getRewardPointsBalance(), true),
            $this->dataExporterHelper->addFullLengthChar('0', 8, $order->getData('bonus_point_amount'), true),
            $this->dataExporterHelper->addFullLengthChar('0', 8, (int) $shipment->getShoppingPointAmount(), true),
            $this->dataExporterHelper->addFullLengthChar($space, 80, $courseName, false), //Delivery course name
            $this->dataExporterHelper->addFullLengthChar('0', 3, $subscriptionOrderTime, true),
            $this->dataExporterHelper->addFullLengthChar($space, 14, $nextDeliveryDate, false),
            $this->dataExporterHelper->addFullLengthChar('0', 15, 0, true),
            $this->dataExporterHelper->addFullLengthChar($space, 4), //Apology letter type
            $this->dataExporterHelper->addFullLengthChar($space, 8, $extraData['currentDate'], false),
            $this->dataExporterHelper->addFullLengthChar($space, 1, $memberType2, false), //Member type
            $this->dataExporterHelper->addFullLengthChar($space, 1, $orderChannel, true), //Order format
            $this->dataExporterHelper->addFullLengthChar($space, 256, $shoshaCmpName, false), //Company name
            $this->dataExporterHelper->addFullLengthChar($space, 1, $shippingSystemType, false), //Shipping system type
            $this->dataExporterHelper->addFullLengthChar(
                $space,
                5,
                $mchDeliveryChoiceCode,
                false
            ), //MCH delivery choice code
            $this->dataExporterHelper->addFullLengthChar($space, 1, $paymentDetailNumber, false),
            $this->dataExporterHelper->addFullLengthChar($space, 1, $addressData['receiptFlag'], true), // receipt flag
            $this->dataExporterHelper->addFullLengthChar($space, 1, $b2bflag, true),
            $this->dataExporterHelper->addFullLengthChar($space, 1), //Eng flg
            $this->dataExporterHelper->addFullLengthChar($space, 1), // Magento flg
            $this->dataExporterHelper->addFullLengthChar($space, 5) // Filter
        ];
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param array $orderItemData
     * @param \Magento\Framework\Filesystem\File\Write $fileDetail
     * @param int $rowDetailFinal
     * @param int $productIndexer
     * @param int $detailsFlag
     * @param int $categoryPrinting
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function putShipmentDetailData(
        \Magento\Sales\Model\Order\Shipment $shipment,
        array $orderItemData,
        \Magento\Framework\Filesystem\File\Write $fileDetail,
        int &$rowDetailFinal,
        int &$productIndexer,
        int $detailsFlag,
        int $categoryPrinting
    ) {
        $shipmentItem = $orderItemData['shipmentItem'];
        $orderItem = $orderItemData['orderItem'];
        $productName = $this->dataExporterHelper->cleanProductName($orderItem->getName());
        $price = $orderItemData['price'];
        $qtyOrderedShipment = $orderItemData['qty'];
        $detailVat = $orderItemData['detailVat'];
        $detailTotalAmount = $orderItemData['detailTotalAmount'];
        $vat = $orderItemData['vat'];
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
        $assortmentNumber = $this->taxHelper->compareTaxRateChange($orderItem->getTaxPercent());
        $shipmentDetail =
            [
                '',
                $this->dataExporterHelper->addFullLengthChar('0', 4, $rowDetailFinal),
                $this->dataExporterHelper->addFullLengthChar($space, 16, $shipment->getIncrementId(), false),
                $this->dataExporterHelper->addFullLengthChar($space, 16, $productIndexer, false),
                $this->dataExporterHelper->addFullLengthChar($space, 24, $orderItem->getSku(), false),
                $this->dataExporterHelper->addFullLengthChar('0', 8, $price, true),
                $this->dataExporterHelper->addFullLengthChar('0', 2, (int)$orderItem->getTaxPercent()),
                $this->dataExporterHelper->addFullLengthChar('0', 8, $qtyOrderedShipment, true),
                $this->dataExporterHelper->addFullLengthChar('0', 8, $detailVat, true),
                $this->dataExporterHelper->addFullLengthChar($space, 100, $productName, false),
                $this->dataExporterHelper->addFullLengthChar('0', 15, $detailTotalAmount, true),
                $this->dataExporterHelper->addFullLengthChar('0', 8, $vat, true),
                $this->dataExporterHelper->addFullLengthChar('0', 15, $detailTotalAmount, true),
                $this->dataExporterHelper->addFullLengthChar($space, 16, '', false),
                $this->dataExporterHelper->addFullLengthChar($space, 16, $wrappingCode, false),
                $this->dataExporterHelper->addFullLengthChar($space, 80, $wrappingName, false),
                $this->dataExporterHelper->addFullLengthChar('0', 16, $totalAmountGiftWrap, true),
                $this->dataExporterHelper->addFullLengthChar('0', 8, $totalAmountGiftWrapTax, true),
                $this->dataExporterHelper->addFullLengthChar($space, 1, $assortmentNumber),
                $this->dataExporterHelper->addFullLengthChar($space, 16),
                $this->dataExporterHelper->addFullLengthChar($space, 80),
                $this->dataExporterHelper->addFullLengthChar($space, 400),
                $this->dataExporterHelper->addFullLengthChar($space, 80),
                $this->dataExporterHelper->addFullLengthChar($space, 80, '', false),
                $this->dataExporterHelper->addFullLengthChar('0', 8, '0', true),
                $this->dataExporterHelper->addFullLengthChar('0', 8, '0', true),
                $this->dataExporterHelper->addFullLengthChar('0', 1, $attachedProductType, false),
                $this->dataExporterHelper->addFullLengthChar('0', 1, $detailsFlag, false),
                $this->dataExporterHelper->addFullLengthChar('0', 8, '0', true),
                $this->dataExporterHelper->addFullLengthChar('0', 1, '0', false),//Assortment instruction data
                $this->dataExporterHelper->addFullLengthChar('0', 1, '0', false),
                $this->dataExporterHelper->addFullLengthChar($space, 2, $productUnitCase, false), //Unit
                $this->dataExporterHelper->addFullLengthChar($space, 24, $orderItem->getSku(), false),
                $this->dataExporterHelper->addFullLengthChar($space, 1, $categoryPrinting, false),
                $this->dataExporterHelper->addFullLengthChar($space, 16, $orderItemData['bundleSkuCode'], false),
                $this->dataExporterHelper->addFullLengthChar($space, 1, $bundleFlag, false),
                $this->dataExporterHelper->addFullLengthChar($space, 959)
            ];
        $fileDetail->write($this->dataExporterHelper->writeFileTxtSAP($shipmentDetail));
        $productIndexer++;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param array $rowExport
     * @param array $filesExport
     * @param array $extraData
     * @throws LocalizedException
     */
    public function exportShipmentDetailNormal(
        \Magento\Sales\Model\Order\Shipment $shipment,
        array &$rowExport,
        array $filesExport,
        array $extraData
    ) {
        $categoryPrinting = $extraData['categoryPrinting'];
        $detailsFlag = 0;
        $productIndexer = 1;
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
                $rowExport[$wareHouseIndex],
                $productIndexer,
                $detailsFlag,
                $categoryPrinting
            );
            $rowExport[$wareHouseIndex]++;
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
        $shipment->setIsExported(1);
        $shipment->setShipmentDate($this->dateTime->gmtDate('Y-m-d H:i:s'));
        $shipment->setShipmentStatus(ShipmentStatus::SHIPMENT_STATUS_EXPORTED);
        $this->dataExporterHelper->writeToLog(sprintf(
            __(
                'Export normal shipment number: %s'
            ),
            $shipment->getIncrementId()
        ));
        $this->exportShipmentHeader(
            $shipment,
            $rowExport,
            $filesExport,
            $extraData
        );
        $this->exportShipmentDetailNormal(
            $shipment,
            $rowExport,
            $filesExport,
            $extraData
        );
    }
}
