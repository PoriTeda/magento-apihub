<?php
namespace Riki\SapIntegration\Cron;

use Riki\SapIntegration\Api\ConfigInterface;
use Riki\Framework\Helper\Logger\LoggerBuilder;

class ShipmentV2
{
    /**
     * @var array
     */
    protected $trackData;

    /**
     * @var \Riki\Framework\Helper\ScopeConfig
     */
    protected $scopeConfigHelper;

    /**
     * @var \Riki\Framework\Helper\Logger\Monolog
     */
    protected $logger;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var \Riki\SapIntegration\Cron\Exporter\Orders
     */
    protected $ordersExporter;

    /**
     * @var \Riki\SapIntegration\Cron\Exporter\OrdersReturns
     */
    protected $ordersReturnsExporter;

    /**
     * @var \Riki\SapIntegration\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Riki\Framework\Helper\Datetime
     */
    protected $datetimeHelper;

    /**
     * @var \Riki\SapIntegration\Api\ShipmentSapExportedRepositoryInterface
     */
    protected $shipmentSapExportedRepository;

    /**
     * ShipmentV2 constructor.
     *
     * @param \Riki\Framework\Helper\Datetime $datetimeHelper
     * @param \Riki\SapIntegration\Api\ShipmentSapExportedRepositoryInterface $shipmentSapExportedRepository
     * @param \Riki\SapIntegration\Helper\Data $dataHelper
     * @param Exporter\OrdersReturns $ordersReturnsExporter
     * @param Exporter\Orders $ordersExporter
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Framework\Helper\Logger\LoggerBuilderFactory $loggerBuilder
     * @param \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
     */
    public function __construct(
        \Riki\Framework\Helper\Datetime $datetimeHelper,
        \Riki\SapIntegration\Api\ShipmentSapExportedRepositoryInterface $shipmentSapExportedRepository,
        \Riki\SapIntegration\Helper\Data $dataHelper,
        \Riki\SapIntegration\Cron\Exporter\OrdersReturns $ordersReturnsExporter,
        \Riki\SapIntegration\Cron\Exporter\Orders $ordersExporter,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Framework\Helper\Logger\LoggerBuilderFactory $loggerBuilder,
        \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
    ) {
        $this->datetimeHelper = $datetimeHelper;
        $this->shipmentSapExportedRepository = $shipmentSapExportedRepository;
        $this->dataHelper = $dataHelper;
        $this->ordersReturnsExporter = $ordersReturnsExporter;
        $this->ordersExporter = $ordersExporter;
        $this->shipmentRepository = $shipmentRepository;
        $this->searchHelper = $searchHelper;
        $this->logger = $loggerBuilder->create()
            ->setName('CronShipmentSapExport')
            ->setFileName('report')
            ->pushHandlerByAlias(LoggerBuilder::ALIAS_DATE_HANDLER)
            ->create();
        $this->scopeConfigHelper = $scopeConfigHelper;

        /*set logger for orders exporter*/
        $this->ordersExporter->setLogger($this->logger);
        /*set logger for orders return  exporter*/
        $this->ordersReturnsExporter->setLogger($this->logger);

        $this->init();
    }

    public function init()
    {
        $this->trackData = [
            'success' => [],
            'failed' => []
        ];
    }

    /**
     * Get logger
     *
     * @return \Riki\Framework\Helper\Logger\Monolog
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Enable flag
     *
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->scopeConfigHelper->read(ConfigInterface::class)
            ->sapIntegration()
            ->exportShipment()
            ->enable();
    }

    /**
     * Track a success record
     *
     * @param $id
     *
     * @return $this
     */
    public function trackSuccess($id)
    {
        $this->trackData['success'][$id] = 1;
        if (isset($this->trackData['failed'][$id])) {
            unset($this->trackData['failed'][$id]);
        }

        return $this;
    }

    /**
     * Track a failed record
     *
     * @param $id
     *
     * @return $this
     */
    public function trackFailed($id)
    {
        $this->trackData['failed'][$id] = 1;
        if (isset($this->trackData['success'][$id])) {
            unset($this->trackData['success'][$id]);
        }

        return $this;
    }

    /**
     * Get success records
     *
     * @return array
     */
    public function getSuccess()
    {
        return $this->trackData['success'];
    }

    /**
     * Get failed records
     *
     * @return array
     */
    public function getFailed()
    {
        return $this->trackData['failed'];
    }

    /**
     * Execute cron export shipment
     *
     * @return bool
     */
    public function execute()
    {
        if (!$this->isEnabled()) {
            return true;
        }

        if ($this->isEnableProfiler()) {
            $time = microtime(true);
            $memory = memory_get_usage();
        }

        $this->logger->info('Starting ...');

        $this->export();

        foreach ($this->getFailed() as $id => $flag) {
            try {
                $shipmentSapExported = $this->searchHelper
                    ->getByShipmentEntityId($id)
                    ->getOne()
                    ->execute($this->shipmentSapExportedRepository);
                if ($shipmentSapExported) {
                    $shipmentSapExported->setData(
                        'is_exported_sap',
                        \Riki\SapIntegration\Model\Api\Shipment::FAILED_TO_EXPORT
                    );
                    $shipmentSapExported->setData('export_sap_date', $this->datetimeHelper->toDb());
                    $shipmentSapExported->setData('backup_file', $this->getBackupFile());
                    $this->shipmentSapExportedRepository->save($shipmentSapExported);//@codingStandardsIgnoreLine
                    $this->logger->info(
                        __('Shipment #%1 has exported to SAP failed.', $shipmentSapExported->getShipmentIncrementId())
                    );
                }
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }

        foreach ($this->getSuccess() as $id => $flag) {
            try {
                $shipmentSapExported = $this->searchHelper
                    ->getByShipmentEntityId($id)
                    ->getOne()
                    ->execute($this->shipmentSapExportedRepository);
                if ($shipmentSapExported) {
                    $shipmentSapExported->setData(
                        'is_exported_sap',
                        \Riki\SapIntegration\Model\Api\Shipment::EXPORTED_TO_SAP
                    );
                    $shipmentSapExported->setData('export_sap_date', $this->datetimeHelper->toDb());
                    $shipmentSapExported->setData('backup_file', $this->getBackupFile());
                    $this->shipmentSapExportedRepository->save($shipmentSapExported);//@codingStandardsIgnoreLine
                    $this->logger->info(
                        __('Shipment #%1 has already exported to SAP.', $shipmentSapExported->getShipmentIncrementId())
                    );
                }
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }

        $successCount = count($this->getSuccess());
        $failedCount = count($this->getFailed());
        $this->logger->info(sprintf('Finish. %s total(s). %s success, %s failed', $successCount + $failedCount, $successCount, $failedCount));

        if (isset($time) && isset($memory)) {
            $this->logger->info(sprintf(
                'Time: %s. Memory: %s',
                array_reduce([microtime(true) - $time], function ($k, $v) {
                    $units = ['seconds', 'minutes', 'hours'];
                    $power = $v >= 1 ? floor(log($v, 60)) : 0;
                    return number_format($v / pow(60, $power), 2, '.', ',') . ' ' . $units[$power];
                }),
                array_reduce([memory_get_usage() - $memory], function ($k, $v) {
                    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
                    $power = $v > 0 ? floor(log($v, 1024)) : 0;
                    return number_format($v / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
                })
            ));
        }

        return true;
    }

    /**
     * Export data
     *
     * @return void
     */
    public function export()
    {
        $limit = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->sapIntegration()
            ->exportShipment()
            ->limit();
        $limit = $limit ?: 100;
        $this->logger->info(sprintf('Config export limit: %s', $limit));

        $batchLimit = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->sapIntegration()
            ->exportShipment()
            ->batchLimit();
        $batchLimit = $batchLimit ?: 10;
        $this->logger->info(sprintf('Config batch limit: %s', $batchLimit));

        $allowedFlags = [
            \Riki\SapIntegration\Model\Api\Shipment::WAITING_FOR_EXPORT,
            \Riki\SapIntegration\Model\Api\Shipment::FAILED_TO_EXPORT
        ];

        $failedIds = [];
        $successIds = [];

        /*get list of shipment need to be exported*/
        $shipmentSapExportedList = $this->searchHelper
            ->getByIsExportedSap($allowedFlags)
            ->limit($limit)
            ->execute($this->shipmentSapExportedRepository);

        if ($shipmentSapExportedList) {
            foreach ($shipmentSapExportedList as $shipmentSapExported) {
                /*flag to check data need to export, is correct (shipment is exists, order is exists)*/
                $isCorrectData = true;

                try {
                    $shipment = $this->shipmentRepository->get($shipmentSapExported->getId());
                } catch (\Exception $e) {
                    $isCorrectData = false;
                    $this->logger->info($e);
                }

                if ($shipment) {
                    $order = $this->dataHelper->getOrder($shipment);
                    if (!$order) {
                        $isCorrectData = false;
                    }
                }

                if (!$isCorrectData) {
                    $this->logger->info(
                        __('Original data for shipment #%1 is not exist.', $shipmentSapExported->getId())
                    );

                    $shipmentSapExported->setData(
                        'is_exported_sap',
                        \Riki\SapIntegration\Model\Api\Shipment::NO_NEED_TO_EXPORT
                    );
                    try {
                        $this->shipmentSapExportedRepository->save($shipmentSapExported);//@codingStandardsIgnoreLine
                    } catch (\Exception $e) {
                        $this->logger->critical($e);
                    }

                    continue;
                }

                /*export with type = return for order which substitution = 1*/
                $isReturn = $order->getData('substitution');

                try {
                    if ($isReturn) {
                        /*shipment type is RETURNS*/
                        $this->ordersReturnsExporter->prepareShipmentExportData($shipment);
                    } else {
                        /*shipment type is ORDERS*/
                        $this->ordersExporter->prepareShipmentExportData($shipment);
                    }

                    $this->logger->info(__('Shipment #%1 has ready exported to SAP', $shipment->getIncrementId()));
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                    $this->trackFailed($shipment->getId());
                    continue;
                }

                if ($isReturn) {
                    if ($this->ordersReturnsExporter->isExceeded()) {
                        list($successIds, $failedIds) = $this->exportByReturnExporter($successIds, $failedIds);
                    }
                } else {
                    if ($this->ordersExporter->isExceeded()) {
                        list($successIds, $failedIds) = $this->exportByOrderExporter($successIds, $failedIds);
                    }
                }
            }
        }

        list($successIds, $failedIds) = $this->exportByReturnExporter($successIds, $failedIds);
        list($successIds, $failedIds) = $this->exportByOrderExporter($successIds, $failedIds);

        foreach ($failedIds as $failedId) {
            $this->trackFailed($failedId);
        }

        foreach ($successIds as $successId) {
            $this->trackSuccess($successId);
        }

        return $this;
    }

    /**
     * Export shipment to SAP - type is Return
     *
     * @param $successIds
     * @param $failedIds
     * @return array
     */
    protected function exportByReturnExporter($successIds, $failedIds)
    {
        $processedIds = $this->ordersReturnsExporter->getBatchIds();

        $exportResult = $this->ordersReturnsExporter->exportShipmentToSap();

        if ($exportResult) {
            $this->ordersReturnsExporter->clean();
            $successIds = array_unique(array_merge($successIds, array_keys($processedIds)));
        } else {
            $failedIds = array_unique(array_merge($failedIds, array_keys($processedIds)));
        }

        return [$successIds, $failedIds];
    }

    /**
     * export shipment to SAP - type us Order
     *
     * @param $successIds
     * @param $failedIds
     * @return array
     */
    protected function exportByOrderExporter($successIds, $failedIds)
    {
        $processedIds = $this->ordersExporter->getBatchIds();

        $exportResult = $this->ordersExporter->exportShipmentToSap();

        if ($exportResult) {
            $this->ordersExporter->clean();
            $successIds = array_unique(array_merge($successIds, array_keys($processedIds)));
        } else {
            $failedIds = array_unique(array_merge($failedIds, array_keys($processedIds)));
        }

        return [$successIds, $failedIds];
    }

    /**
     * Enable profiler?
     *
     * @return bool
     */
    public function isEnableProfiler()
    {
        return (bool)$this->scopeConfigHelper->read(ConfigInterface::class)
            ->sapIntegration()
            ->exportShipment()
            ->debug();
    }

    /**
     * backup file after exported success
     *
     * @return string
     */
    protected function getBackupFile()
    {
        $backupList = array_merge(
            $this->ordersExporter->getBackupFile(),
            $this->ordersReturnsExporter->getBackupFile()
        );

        return implode(', ', $backupList);
    }
}