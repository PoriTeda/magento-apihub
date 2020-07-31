<?php
namespace Riki\CsvOrderMultiple\Model;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregator;

class ImportHandler extends \Magento\ImportExport\Model\Import
{
    protected $_debugMode = false;

    const ALLOWED_ERRORS_COUNT = 100;

    /**
     * ImportHandler constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\ImportExport\Helper\Data $importExportData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $coreConfig
     * @param \Magento\ImportExport\Model\Import\ConfigInterface $importConfig
     * @param \Magento\ImportExport\Model\Import\Entity\Factory $entityFactory
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data $importData
     * @param \Magento\ImportExport\Model\Export\Adapter\CsvFactory $csvFactory
     * @param \Magento\Framework\HTTP\Adapter\FileTransferFactory $httpFactory
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory
     * @param \Magento\ImportExport\Model\Source\Import\Behavior\Factory $behaviorFactory
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param \Magento\ImportExport\Model\History $importHistoryModel
     * @param DateTime $localeDate
     * @param array $data
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\Framework\App\Config\ScopeConfigInterface $coreConfig,
        \Magento\ImportExport\Model\Import\ConfigInterface $importConfig,
        \Magento\ImportExport\Model\Import\Entity\Factory $entityFactory,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        \Magento\ImportExport\Model\Export\Adapter\CsvFactory $csvFactory,
        \Magento\Framework\HTTP\Adapter\FileTransferFactory $httpFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\ImportExport\Model\Source\Import\Behavior\Factory $behaviorFactory,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\ImportExport\Model\History $importHistoryModel,
        DateTime $localeDate,
        array $data = []
    ) {
        parent::__construct($logger, $filesystem, $importExportData, $coreConfig, $importConfig, $entityFactory,
            $importData, $csvFactory, $httpFactory, $uploaderFactory, $behaviorFactory, $indexerRegistry,
            $importHistoryModel, $localeDate, $data);
        $this->setData(\Magento\ImportExport\Model\Import::FIELD_NAME_VALIDATION_STRATEGY, ProcessingErrorAggregator::VALIDATION_STRATEGY_SKIP_ERRORS);
    }


    public function getEntity()
    {
        return 'order';
    }

    /**
     * Retrieve processed reports entity types
     *
     * @param string|null $entity
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isReportEntityType($entity = null)
    {
        return false;
    }

    /**
     * @return bool
     */
    protected function processImport()
    {

        $this->_getEntityAdapter()->setUsername($this->getData(\Riki\CsvOrderMultiple\Model\ImportHandler\Order::UPLOADED_BY));

        parent::processImport();
    }

    /**
     * @return \Riki\CsvOrderMultiple\Model\ImportHandler\Order
     */
    protected function _getEntityAdapter()
    {
        if (!$this->_entityAdapter) {
            $this->_entityAdapter = $this->_entityFactory->create(\Riki\CsvOrderMultiple\Model\ImportHandler\Order::class);;
        }

        return $this->_entityAdapter;
    }
}