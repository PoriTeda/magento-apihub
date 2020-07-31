<?php
namespace Riki\ShipmentExporter\Cron;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;

class ShipmentExporter
{
    /**
     * @var \Riki\ShipmentExporter\Helper\NormalExporter
     */
    protected $normalHelper;

    /**
     * @var \Riki\ShipmentExporter\Helper\BucketExporter
     */
    protected $bucketHelper;

    /**
     * @var \Riki\ShipmentExporter\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Riki\ShipmentExporter\Helper\DataExporter
     */
    protected $exportHelper;

    /**
     * @var bool
     */
    protected $sftp;

    /**
     * ShipmentExporter constructor.
     * @param \Riki\ShipmentExporter\Helper\NormalExporter $normalExporter
     * @param \Riki\ShipmentExporter\Helper\BucketExporter $bucketExporter
     * @param \Riki\ShipmentExporter\Helper\Data $dataHelper
     * @param \Riki\ShipmentExporter\Helper\DataExporter $exportHelper
     * @param \Magento\Framework\Filesystem\Io\Sftp $sftp
     */
    public function __construct(
        \Riki\ShipmentExporter\Helper\NormalExporter $normalExporter,
        \Riki\ShipmentExporter\Helper\BucketExporter $bucketExporter,
        \Riki\ShipmentExporter\Helper\Data $dataHelper,
        \Riki\ShipmentExporter\Helper\DataExporter $exportHelper,
        \Magento\Framework\Filesystem\Io\Sftp $sftp
    ) {
        $this->normalHelper = $normalExporter;
        $this->bucketHelper = $bucketExporter;
        $this->dataHelper = $dataHelper;
        $this->exportHelper = $exportHelper;
        $this->sftp = $sftp;
    }

    /**
     * first export shipment
     */
    public function exportShipmentFirst()
    {
        $limit = $this->dataHelper->getShipmentLimitation(1);
        $this->execute($limit);
    }

    /**
     * second export shipment
     */
    public function exportShipmentSecond()
    {
        $limit = $this->dataHelper->getShipmentLimitation(2);
        $this->execute($limit);
    }

    /**
     * third export shipment
     */
    public function exportShipmentThird()
    {
        $limit = $this->dataHelper->getShipmentLimitation(3);
        $this->execute($limit);
    }

    /**
     * @param int $limitation
     */
    public function execute($limitation = 500)
    {
        list($internalShosha, $internalShoshaCode) = $this->exportHelper->loadAllShoshaCodes();
        $area = $this->exportHelper->getAreaList();
        $area->load(\Magento\Framework\App\Area::PART_TRANSLATE);
        $this->exportHelper->getWarehouses();
        if (!$this->dataHelper->isEnable()) {
            //send notification email
            $this->dataHelper->sendMailShipmentExporting(
                ['logContent' =>__('Shipment exporter function has been disabled')]
            );
            return;
        }
        // check sftp connection and directory
        $checker = $this->dataHelper->checkSftpConnection($this->sftp);
        if (!$checker[0]) {
            $this->exportHelper->writeToLog(__('Could not connect to SFTP.'));
            $mess = $checker[1]. "\r\n". __("Shipment exporting has been stopped.");
            $this->dataHelper->sendMailShipmentExporting(['logContent' =>$mess]);
            return;
        }

        list($needDate, $compareDate, $currentExportDate)  = $this->exportHelper->getNeededDates();

        try {
            // backup old log file
            $this->exportHelper->backupLog($needDate);
        } catch (FileSystemException $e) {
            $this->exportHelper->writeToLog(
                sprintf('Cannot backup old log, error message: %s', $e->getMessage())
            );
        }

        $shipmentCollection = $this->exportHelper->getShipmentDataCollection(
            $compareDate,
            $limitation
        );
        $this->exportHelper->writeToLog(__('Query to get shipments'));
        $this->exportHelper->writeToLog($shipmentCollection->getSelect());
        $this->exportHelper->writeToLog(sprintf(
            __('Total shipments are available : %s'),
            $shipmentCollection->getSize()
        ));
        //initial export files
        $filesExport = $this->exportHelper->initialFilesExport($needDate);
        $rowExport = [
            'rowHeaderToyo' => 1,
            'rowHeaderBizex' => 1,
            'rowHeaderHitachi' => 1,
            'rowDetailToyo' => 1,
            'rowDetailBizex' => 1,
            'rowDetailHitachi' => 1
        ];
        $extraData = [];
        $extraData['currentWarehouse'] = $this->exportHelper->getWarehouses();
        $extraData['internalDeliveryCodes'] = $this->exportHelper->getDeliveryCodes();
        $extraData['internalShoshaCode'] = $internalShoshaCode;
        $extraData['internalShosha'] = $internalShosha;
        if ($shipmentCollection->getSize()) {
            foreach ($shipmentCollection as $shipment) {
                /** @var \Magento\Sales\Model\Order\Shipment $shipment */
                $orderDetail = $shipment->getOrder();
                $extraData['categoryPrinting'] = (int)($orderDetail->getData('customer_amb_type'));
                $extraData['category_code'] = $this->exportHelper->getOrderPartial($orderDetail->getId());
                $extraData['currentDate'] = $currentExportDate;
                $extraData['compareDate'] = $compareDate;
                $this->exportHelper->writeToLog("Prepare to export shipment: ".$shipment->getIncrementId());

                if ($this->exportHelper->canExportShipment($shipment)) {
                    try {
                        if ($this->exportHelper->isBucketShipment($shipment)) {
                            //export bucket shipment
                            $this->bucketHelper->exportShipment(
                                $shipment,
                                $rowExport,
                                $filesExport,
                                $extraData
                            );
                        } else {
                            // export normal shipment
                            $this->normalHelper->exportShipment($shipment, $rowExport, $filesExport, $extraData);
                        }
                    } catch (LocalizedException $e) {
                        $this->exportHelper->writeToLog(
                            __(
                                'Can not export Shipment #%1, error message: %2',
                                $shipment->getIncrementId(),
                                $e->getMessage()
                            )
                        );
                    }
                } else {
                    $this->exportHelper->writeToLog(__('Can not export Shipment #%1', $shipment->getIncrementId()));
                }

                $this->exportHelper->updateShipment($shipment);
            }
        }

        //copy and export file
        $this->exportHelper->copyAndExportData($filesExport);
    }
}
