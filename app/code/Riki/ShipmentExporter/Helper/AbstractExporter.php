<?php
namespace Riki\ShipmentExporter\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\LocalizedException;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;

abstract class AbstractExporter extends AbstractHelper
{
    /**
     * @var DataExporter
     */
    protected $dataExporterHelper;

    /**
     * @var \Riki\Customer\Helper\ConverKana
     */
    protected $convertKanaHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $orderHelper;

    /**
     * @var \Riki\Tax\Helper\Data
     */
    protected $taxHelper;

    /**
     * AbstractExporter constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param DataExporter $dataExporterHelper
     * @param \Riki\Customer\Helper\ConverKana $convertKana
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Riki\Sales\Helper\Order $orderHelper
     * @param \Riki\Tax\Helper\Data $taxHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Riki\ShipmentExporter\Helper\DataExporter $dataExporterHelper,
        \Riki\Customer\Helper\ConverKana $convertKana,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\Sales\Helper\Order $orderHelper,
        \Riki\Tax\Helper\Data $taxHelper
    ) {
        $this->dataExporterHelper = $dataExporterHelper;
        $this->convertKanaHelper = $convertKana;
        $this->dateTime = $dateTime;
        $this->orderHelper = $orderHelper;
        $this->taxHelper = $taxHelper;
        parent::__construct($context);
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param $rowExport
     * @param $filesExport
     * @param $extraData
     *
     * @throws LocalizedException
     */
    public function exportShipment(
        \Magento\Sales\Model\Order\Shipment $shipment,
        &$rowExport,
        $filesExport,
        $extraData
    ) {
        $shipment->setIsExported(1);
        $shipment->setShipmentDate($this->dateTime->gmtDate('Y-m-d H:i:s'));
        $shipment->setShipmentStatus(ShipmentStatus::SHIPMENT_STATUS_EXPORTED);
        $this->dataExporterHelper->writeToLog(sprintf(
            __("Export normal shipment number: %s"),
            $shipment->getIncrementId()
        ));
        $this->exportShipmentHeader(
            $shipment,
            $rowExport,
            $filesExport,
            $extraData
        );
        $this->exportShipmentDetail(
            $shipment,
            $rowExport,
            $filesExport,
            $extraData
        );
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param $rowExport
     * @param $filesExport
     * @param $extraData
     *
     * @throws LocalizedException
     */
    public function exportShipmentHeader(
        \Magento\Sales\Model\Order\Shipment & $shipment,
        & $rowExport,
        $filesExport,
        $extraData
    ) {
        switch ($shipment->getWarehouse()) {
            case \Riki\ShipmentExporter\Helper\DataExporter::WH_BIZEX:
                $fileHeader = $filesExport['fileHeaderBizex'];
                $rowHeaderFinal = $rowExport['rowHeaderBizex'];
                $rowExport['rowHeaderBizex']++;
                break;
            case \Riki\ShipmentExporter\Helper\DataExporter::WH_HITACHI:
                $fileHeader = $filesExport['fileHeaderHitachi'];
                $rowHeaderFinal = $rowExport['rowHeaderHitachi'];
                $rowExport['rowHeaderHitachi']++;
                break;
            case \Riki\ShipmentExporter\Helper\DataExporter::WH_TOYO:
                $fileHeader = $filesExport['fileHeaderToyo'];
                $rowHeaderFinal = $rowExport['rowHeaderToyo'];
                $rowExport['rowHeaderToyo']++;
                break;
            default:
                throw new LocalizedException(
                    __('Shipment exporter doesn\'t support warehouse %1', $shipment->getWarehouse())
                );
        }

        $shippingAddressNewId = $shipment->getData('shipping_address_newid');
        if ($shippingAddressNewId) {
            $shippingAddress = $this->dataExporterHelper->getOrderAddressById($shippingAddressNewId);
        } else {
            $shippingAddress = $shipment->getShippingAddress();
        }
        $b2bFlag = $shipment->getData('customer_b2b_flag') ?
                    $shipment->getData('customer_b2b_flag') :
                    0;

        $noShipment = false;
        $shipmentItems = $shipment->getAllItems();
        foreach ($shipmentItems as $shipItem) {
            $orderItemSingle = $this->dataExporterHelper->getOrderItemDataByItemId($shipItem->getOrderItemId());
            if ($orderItemSingle instanceof \Magento\Sales\Model\Order\Item) {
                $isAllowedToExportedToWh = $this->dataExporterHelper->isAllowedToExportedItem($orderItemSingle);
                if ($isAllowedToExportedToWh) {
                    $noShipment = false;
                    break;
                } else {
                    $noShipment = true;
                }
            }
        }
        if (!$noShipment) {
            $dataHeader = $this->getInfoHeader(
                $shipment,
                $shippingAddress,
                $rowHeaderFinal,
                $b2bFlag,
                $extraData
            );
            $fileHeader->write($this->dataExporterHelper->writeFileTxtSAP($dataHeader));
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return array
     */
    public function buildOrderItemData(
        \Magento\Sales\Model\Order\Shipment $shipment
    ) {
        $chirashiCase = $this->dataExporterHelper->isChirashiShipmentProductCase($shipment);
        $shipmentItems = $shipment->getAllItems();
        $orderItems = [];
        $parentSKUs = [];
        foreach ($shipmentItems as $shipItem) {
            $orderItemSingle = $this->dataExporterHelper->getOrderItemDataByItemId($shipItem->getOrderItemId());
            if ($orderItemSingle) {
                //Chirashi item, does not export
                if ($chirashiCase && $orderItemSingle->getData('chirashi')) {
                    continue;
                }
                $isAllowedToExportedToWh = $this->dataExporterHelper->isAllowedToExportedItem($orderItemSingle);
                /*product is not allowed to exported to WH*/
                if (!$isAllowedToExportedToWh) {
                    continue;
                }
            } else {
                continue;
            }
            $qtyOrderedShipment = (int)$shipItem->getQty();
            $qtyOrderedOrderItem = $orderItemSingle->getQtyOrdered();
            $parentSKUs[$orderItemSingle->getItemId()] = $orderItemSingle->getSku();
            $discountAmount = (int)$orderItemSingle->getDiscountAmount();
            $rowTotalInclTax = (int)$orderItemSingle->getRowTotalInclTax();
            $price = $purchaseAmount = ($rowTotalInclTax - $discountAmount);
            $itemKey = $orderItemSingle->getItemId();
            $vat = (int)$orderItemSingle->getTaxRiki();
            $qtyFinal = $qtyOrderedOrderItem;
            $qtyFinalShipment = $qtyOrderedShipment;
            if ($orderItemSingle->getData('unit_case') ==
                \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE
            ) {
                $qtyUnit = $orderItemSingle->getData('unit_qty');
                if ($qtyUnit) {
                    $qtyFinal = (int)$qtyOrderedOrderItem/$qtyUnit;
                    $qtyFinalShipment = (int)$qtyFinalShipment/$qtyUnit;
                }
            }
            if ($qtyFinal) {
                $price = round($purchaseAmount/$qtyFinal);
            }
            if ($qtyFinal>0) {
                $detailVat = round($orderItemSingle->getTaxRiki()/$qtyFinal);
            } else {
                $detailVat = round($orderItemSingle->getTaxRiki()/$qtyOrderedShipment);
            }
            $totalAmountGiftWrapTax = (int)($orderItemSingle->getGwTaxAmount() * $qtyFinal);
            $totalAmountGiftWrap = (int)($orderItemSingle->getGwPrice() +
                    $orderItemSingle->getGwTaxAmount()) * $qtyFinal;
            $detailTotalAmount = $purchaseAmount;
            $bundleSkuCode = '';
            if ($orderItemSingle->getParentItemId() &&
                array_key_exists($orderItemSingle->getParentItemId(), $parentSKUs)
            ) {
                $bundleSkuCode = $parentSKUs[$orderItemSingle->getParentItemId()];
            }
            $orderItems[$itemKey] = [
                'shipmentItem'=> $shipItem,
                'orderItem'=> $orderItemSingle,
                'qty' => (int)$qtyFinalShipment,
                'price' => $price,
                'detailVat' => $detailVat,
                'detailTotalAmount' => $detailTotalAmount,
                'vat' => $vat,
                'totalAmountGiftWrap' =>$totalAmountGiftWrap,
                'totalAmountGiftWrapTax'=>$totalAmountGiftWrapTax,
                'bundleSkuCode' => $bundleSkuCode
            ];
        }// end of data processing and begin to export
        return $orderItems;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param $rowExport
     * @param $filesExport
     * @param $extraData
     *
     * @throws LocalizedException
     */
    public function exportShipmentDetail(
        \Magento\Sales\Model\Order\Shipment $shipment,
        &$rowExport,
        $filesExport,
        $extraData
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
}
