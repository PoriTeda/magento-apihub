<?php
namespace Riki\ThirdPartyImportExport\Cron\Order;

use Riki\Framework\Helper\Logger\LoggerBuilder;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\DriverPool;

class Import1
{
    /**
     * @var bool
     */
    protected $initialized;

    /**
     * @var mixed[]
     */
    protected $files;

    /**
     * @var \Riki\Framework\Helper\Importer\Csv[]
     */
    protected $importers;

    /**
     * @var string
     */
    protected $tmpStorageDir;

    /**
     * @var \Riki\Framework\Helper\ScopeConfig
     */
    protected $scopeConfigHelper;

    /**
     * @var \Riki\Framework\Helper\Sftp
     */
    protected $sftpHelper;

    /**
     * @var \Riki\Framework\Helper\Logger
     */
    protected $loggerHelper;

    /**
     * @var \Riki\Framework\Helper\Logger\Monolog
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\Filesystem\File\ReadFactory
     */
    protected $fileReadFactory;

    /**
     * Import1 constructor.
     *
     * @param Import\ShippingDetailImporter $shippingDetailImporter
     * @param Import\ShippingImporter $shippingImporter
     * @param Import\OrderDetailImporter $orderDetailImporter
     * @param Import\OrderImporter $orderImporter
     * @param \Magento\Framework\Filesystem\File\ReadFactory $fileReadFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param LoggerBuilder $loggerHelper
     * @param \Riki\Framework\Helper\Sftp $sftpHelper
     * @param \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Cron\Order\Import\ShippingDetailImporter $shippingDetailImporter,
        \Riki\ThirdPartyImportExport\Cron\Order\Import\ShippingImporter $shippingImporter,
        \Riki\ThirdPartyImportExport\Cron\Order\Import\OrderDetailImporter $orderDetailImporter,
        \Riki\ThirdPartyImportExport\Cron\Order\Import\OrderImporter $orderImporter,
        \Magento\Framework\Filesystem\File\ReadFactory $fileReadFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Riki\Framework\Helper\Logger\LoggerBuilder $loggerHelper,
        \Riki\Framework\Helper\Sftp $sftpHelper,
        \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
    ){
        $this->fileReadFactory = $fileReadFactory;
        $this->filesystem = $filesystem;
        $this->loggerHelper = $loggerHelper;
        $this->logger = $this->loggerHelper
            ->setName('CronOrderImport')
            ->setFileName('report')
            ->pushHandlerByAlias(LoggerBuilder::ALIAS_DATE_HANDLER)
            ->create();
        $this->sftpHelper = $sftpHelper;
        $this->scopeConfigHelper = $scopeConfigHelper;
        $this->pushImporter('order', $orderImporter);
        $this->pushImporter('order_detail', $orderDetailImporter);
        $this->pushImporter('shipping', $shippingImporter);
        $this->pushImporter('shipping_detail', $shippingDetailImporter);
        $this->init();

    }

    /**
     * Initialize
     *
     * @return void
     */
    public function init()
    {
        $this->tmpStorageDir = $this->filesystem->getDirectoryRead(DirectoryList::SYS_TMP);
        $this->initialized = true;
        $this->files = [];
    }

    /**
     * Get initialized
     *
     * @return bool
     */
    public function getInitialized()
    {
        return $this->initialized;
    }

    /**
     * Set initialize
     *
     * @param $initialized
     *
     * @return $this
     */
    public function setInitialized($initialized)
    {
        $this->initialized = $initialized;
        return $this;
    }

    /**
     * @param $identifier
     *
     * @param \Riki\Framework\Helper\Importer\Csv $importer
     *
     * @return $this
     */
    public function pushImporter($identifier, \Riki\Framework\Helper\Importer\Csv $importer)
    {
        $this->importers[$identifier] = $importer;
        return $this;
    }


    /**
     * Get info logger
     *
     * @return \Riki\Framework\Helper\Logger\Monolog
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Get tmp storage dir
     *
     * @return string
     */
    public function getTmpStorageDir()
    {
        return $this->tmpStorageDir;
    }

    /**
     * Set tmp storage dir
     *
     * @param $dir
     * @return $this
     */
    public function setTmpStorageDir($dir)
    {
        $this->tmpStorageDir = $dir;
        return $this;
    }

    /**
     * Get files
     *
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Set files
     *
     * @param array $files
     *
     * @return $this
     */
    public function setFiles(array $files)
    {
        $this->files = $files;
        return $this;
    }


    /**
     * Execute cron
     *
     * @return bool
     */
    public function execute()
    {
        $this->logger->info('Starting ...');
        if (!$this->initialized) {
            return false;
        }
        $this->logger->info(sprintf('Total files: %s', count($this->files)));
        foreach ($this->files as $i => $fileData) {
            $this->logger->info(sprintf('Starting import file: %s', $fileData['remote_file']));
            $successCount = 0;
            $errorCount = 0;
            $importer = isset($this->importers[$fileData['importer']])
                ? $this->importers[$fileData['importer']]
                : null;
            if (!$importer) {
                $this->logger->critical('Invalid importer handle');
                continue;
            }

            try {
                $file = $this->fileReadFactory->create($fileData['local_file'], DriverPool::FILE);

            } catch (\Exception $e) {
                $this->logger->critical(sprintf('The file %s can not be read', $fileData['local_file']));
                continue;
            }

            $batch = [];
            $count = 0;
            while ($dataRow = $file->readCsv()) {
                $count++;
                try {
                    $result = $importer->isValid($dataRow);
                    if (!$result) {
                        foreach ($importer->getMessages('error') as $message) {
                            if (is_array($message) && isset($message['message']) && isset($message['columns'])) {
                                $this->logger->critical(\Zend_Json::encode([
                                    "Invalidate message" => $message['message'],
                                    "Invalidate trace" => [
                                        'Row number' => $count,
                                        'Columns' => $message['columns']
                                    ]
                                ]));
                            } else {
                                $this->logger->critical(\Zend_Json::encode([
                                    'Invalidate message' => (string)$message,
                                    'Invalidate trace' => [
                                        'Row number' => $count
                                    ]
                                ]));
                            }
                        }
                        $importer->clearMessages();
                        $errorCount++;
                        continue;
                    }

                    $batch[] = $result;
                    if (count($batch) >= 50) {
                        try {
                            $importer->import($batch);
                            $successCount += 50;
                            $batch = [];
                        } catch (\Exception $e) {
                            $rows = array_values(array_unique(array_column($batch, key(end($batch)))));
                            $this->logger->critical(\Zend_Json::encode([
                                'Exception message' => $e->getMessage(),
                                'Exception trace' => [
                                    'Rows' => $rows
                                ],
                            ]));
                            $errorCount += count($batch);
                            $batch = [];
                        }

                    }
                } catch (\Exception $e) {
                    $this->logger->critical(\Zend_Json::encode([
                        'Exception message' => $e->getMessage(),
                        'Exception trace' => [
                            'DataRow' => $dataRow
                        ],
                    ]));
                    $errorCount++;
                }
            }

            if ($batch) {
                try {
                    $importer->import($batch);
                    $successCount += count($batch);
                } catch (\Exception $e) {
                    $rows = array_values(array_unique(array_column($batch, key(end($batch)))));
                    $this->logger->critical(\Zend_Json::encode([
                        'Exception message' => $e->getMessage(),
                        'Exception trace' => [
                            'Rows' => $rows
                        ],
                    ]));
                    $errorCount += count($batch);
                }
            }

            if ($errorCount) {
                $this->files[$i]['status'] = 'error';
            } else {
                $this->files[$i]['status'] = 'success';
            }

            $this->logger->info(sprintf('Finish import file %s: %s successes, %s errors', $fileData['remote_file'], $successCount, $errorCount));
        }

        $this->logger->info('Finished.');

        return true;
    }
}