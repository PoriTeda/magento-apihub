<?php
namespace Riki\SapIntegration\Cron;

use Riki\Framework\Helper\Logger\LoggerBuilder;
use Riki\Rma\Validator\BeforeSaveRmaReturnPoint;
use Riki\SapIntegration\Api\ConfigInterface;

class RmaV2
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
     * @var \Riki\SapIntegration\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Riki\Framework\Helper\Datetime
     */
    protected $datetimeHelper;

    /**
     * @var \Riki\Rma\Api\RmaRepositoryInterface
     */
    protected $rmaRepository;

    /**
     * @var \Riki\SapIntegration\Cron\Exporter\Returns
     */
    protected $returnsExporter;


    /***
     * RmaV2 constructor.
     *
     * @param Exporter\Returns $returnsExporter
     * @param \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository
     * @param \Riki\Framework\Helper\Datetime $datetimeHelper
     * @param \Riki\SapIntegration\Helper\Data $dataHelper
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Framework\Helper\Logger\LoggerBuilderFactory $loggerBuilder
     * @param \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
     */
    public function __construct(
        \Riki\SapIntegration\Cron\Exporter\Returns $returnsExporter,
        \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Riki\Framework\Helper\Datetime $datetimeHelper,
        \Riki\SapIntegration\Helper\Data $dataHelper,
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Framework\Helper\Logger\LoggerBuilderFactory $loggerBuilder,
        \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
    ) {
        $this->returnsExporter = $returnsExporter;
        $this->rmaRepository = $rmaRepository;
        $this->datetimeHelper = $datetimeHelper;
        $this->dataHelper = $dataHelper;
        $this->searchHelper = $searchHelper;
        $this->logger = $loggerBuilder->create()
            ->setName('CronRmaSapExport')
            ->setFileName('report')
            ->pushHandlerByAlias(LoggerBuilder::ALIAS_DATE_HANDLER)
            ->create();

        /*set logger for return exporter*/
        $this->returnsExporter->setLogger($this->logger);

        $this->scopeConfigHelper = $scopeConfigHelper;

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
            ->exportRma()
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
                $rma = $this->searchHelper
                    ->getByEntityId($id)
                    ->getOne()
                    ->execute($this->rmaRepository);
                if ($rma) {
                    $rma->setData('is_exported_sap', \Riki\SapIntegration\Model\Api\Shipment::FAILED_TO_EXPORT);
                    $rma->setData('export_sap_date', $this->datetimeHelper->toDb());
                    /*pass some unnecessary validation*/
                    $rma->setData(BeforeSaveRmaReturnPoint::IGNORE_VALIDATE, true);
                    $this->rmaRepository->save($rma);
                    $this->logger->info(__('Rma #%1 has exported to SAP failed', $rma->getIncrementId()));
                }
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }

        foreach ($this->getSuccess() as $id => $flag) {
            try {
                $rma = $this->searchHelper
                    ->getByEntityId($id)
                    ->getOne()
                    ->execute($this->rmaRepository);
                if ($rma) {
                    $rma->setData('is_exported_sap', \Riki\SapIntegration\Model\Api\Shipment::EXPORTED_TO_SAP);

                    if ($rma->getData('is_without_goods') == \Riki\Rma\Model\Rma::TYPE_WITHOUT_GOODS) {
                        $rma->setData('export_sap_date', null);
                    } else {
                        $rma->setData('export_sap_date', $this->datetimeHelper->toDb());
                    }

                    /*pass some unnecessary validation*/
                    $rma->setData('validate', true);

                    $this->rmaRepository->save($rma);
                    $this->logger->info(__('Rma #%1 has already exported to SAP.', $rma->getIncrementId()));
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
            ->exportRma()
            ->limit();
        $limit = $limit ?: 100;
        $this->logger->info(sprintf('Config export limit: %s', $limit));

        $batchLimit = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->sapIntegration()
            ->exportRma()
            ->batchLimit();
        $batchLimit = $batchLimit ?: 10;
        $this->logger->info(sprintf('Config batch limit: %s', $batchLimit));

        $allowedFlags = [
            \Riki\SapIntegration\Model\Api\Shipment::WAITING_FOR_EXPORT,
            \Riki\SapIntegration\Model\Api\Shipment::FAILED_TO_EXPORT
        ];

        $count = 0;
        $rmaIds = [0];
        while ($count < $limit) {
            $count++;
            /** @var \Magento\Rma\Model\Rma $rma */
            $rma = $this->searchHelper
                ->getByIsExportedSap($allowedFlags)
                ->getByEntityId($rmaIds, 'nin')
                ->getOne()
                ->execute($this->rmaRepository);
            if (!$rma) {
                break;
            }

            $rmaIds[] = $rma->getId();

            try {
                if ($this->returnsExporter->export($rma)) {
                    $this->returnsExporter->clean();
                }

                $this->trackSuccess($rma->getId());
                $this->logger->info(__('Rma #%1 has ready exported to SAP', $rma->getIncrementId()));
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->trackFailed($rma->getId());
            }
        }
        $this->returnsExporter->clean();
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
            ->exportRma()
            ->debug();
    }
}