<?php
/**
 * Implement cron import payment using csv. Most primary functions are reused from Save controller.
 */

namespace Riki\ArReconciliation\Cron;

use \Magento\Framework\App\Filesystem\DirectoryList;
use Riki\ArReconciliation\Controller\Adminhtml\Import\Validate;
use \Riki\ArReconciliation\Model\OrderLog;
use Riki\ArReconciliation\Model\ShipmentLog;
use Riki\ArReconciliation\Model\ReturnLog;

class ImportCsvPayment
{
    const AR_PAYMENT_DONE_FOLDER_PATH = 'ar_payment_csv/done/';
    const STATUS_COLLECT = 40;
    const STATUS_RETURN = 60;
    const INSERT_MULTIPLE_LIMIT = 100;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $varDirectory;

    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $csvReader;

    /**
     * @var \Riki\ArReconciliation\Logger\LoggerImportCsv
     */

    protected $logger;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $salesConnection;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $defaultConnection;


    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timeZone;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Rma\Api\RmaRepositoryInterface
     */
    protected $rmaRepository;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory
     */
    protected $shipmentCollectionFactory;

    protected $paymentTypes = [
        \Riki\ArReconciliation\Model\Import::WELL_NET,
        \Riki\ArReconciliation\Model\Import::YAMATO,
        \Riki\ArReconciliation\Model\Import::ASKUL,
        \Riki\ArReconciliation\Model\Import::CREDIT_CARD
    ];

    protected $returnTransaction = [];

    protected $collectTransaction = [];

    protected $collectedLogData = [];

    protected $returnedLogData = [];

    protected $importLogData = [];

    protected $paymentMethod;

    protected $userId;

    protected $userName;

    protected $processingFile;

    protected $currentRow;

    /**
     * ImportCsvPayment constructor.
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\File\Csv $csvReader
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Rma\Api\RmaRepositoryInterface $rmaRepository
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory
     * @param \Riki\Sales\Helper\ConnectionHelper $connectionHelper
     * @param \Riki\ArReconciliation\Logger\LoggerImportCsv $logger
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\File\Csv $csvReader,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper,
        \Riki\ArReconciliation\Logger\LoggerImportCsv $logger
    ) {
        $this->varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->csvReader = $csvReader;
        $this->dateTime = $dateTime;
        $this->timeZone = $timezone;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->rmaRepository = $rmaRepository;
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->logger = $logger;
        $this->logger->setTimezone(
            new \DateTimeZone($this->timeZone->getConfigTimezone())
        );
        $this->salesConnection = $connectionHelper->getSalesConnection();
        $this->defaultConnection = $connectionHelper->getDefaultConnection();
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function execute()
    {
        /**
         * Check cron state in order to avoid overlap.
         */
        $this->checkCronRun();

        // Start import process
        $this->logger->info(__('START IMPORT PROCESS'));
        $this->importProcess();
        $this->logger->info(__('IMPORT DONE'));

        /**
         * Delete lock folder when import finish
         */
        $this->deleteLockFolder();
    }

    /**
     * Check if there is a running process
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function checkCronRun()
    {
        $lockFolder = $this->varDirectory->getRelativePath('lock/' . $this->getLockFolderName());
        if ($this->varDirectory->isExist($lockFolder)) {
            $message = __('Please wait, system have a same process is running and haven’t finish yet.');
            throw new \Magento\Framework\Exception\LocalizedException($message);
        }

        $this->varDirectory->create($lockFolder);
    }

    /**
     * get Lock folder name
     * @return string
     */
    protected function getLockFolderName()
    {
        return 'ar_payment';
    }

    /**
     * @throws \Exception
     */
    protected function importProcess()
    {
        foreach ($this->paymentTypes as $paymentType) {
            $this->paymentMethod = $paymentType;
            $this->logger->info(__('Payment Type #%1', $paymentType));
            $this->importArPayment();
            $this->logger->info(__('#%1 import DONE', $paymentType));
        }
    }

    /**
     * @throws \Exception
     */
    protected function importArPayment()
    {
        $files = $this->getFolderEntities();
        foreach ($files as $file) {
            $fileName = explode('/', $file);
            $fileName = end($fileName);
            $this->processingFile = $file;
            $filePath = $this->varDirectory->getAbsolutePath($file);
            if ($this->getUserData($fileName)) {
                try {
                    $csvData = $this->csvReader->getData($filePath);
                    $this->import($csvData);
                } catch (\Exception $exception) {
                    $this->logger->error($exception->getMessage());
                }
            }
        }
    }

    /**
     * Get csv files according to payment type
     * @return array
     */
    protected function getFolderEntities()
    {
        $folderPath = Validate::AR_PAYMENT_CSV_FOLDER . DIRECTORY_SEPARATOR . $this->paymentMethod;
        $files = [];

        if ($this->varDirectory->isDirectory($folderPath)) {
            try {
                $files = $this->varDirectory->read($folderPath);
            } catch (\Magento\Framework\Exception\FileSystemException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        return $files;
    }

    /**
     * Import
     * @param $paymentData
     */
    protected function import($paymentData)
    {
        try {
            $this->generateImportData($paymentData);
            $this->importCollectTransaction();
            $this->importReturnTransaction();
            $this->moveProcessedFileToDoneFolder();
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    /**
     * Delete lock folder
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function deleteLockFolder()
    {
        $lockFolder = $this->varDirectory->getRelativePath('lock/' . $this->getLockFolderName());

        $this->varDirectory->delete($lockFolder);
    }

    /**
     * Move to done folder after processing
     */
    protected function moveProcessedFileToDoneFolder()
    {
        try {
            $destination = self::AR_PAYMENT_DONE_FOLDER_PATH .
                $this->paymentMethod . '-' . date('YmdHis') . '.csv';
            $this->varDirectory->copyFile($this->processingFile, $destination);
            $this->varDirectory->delete($this->processingFile);
        } catch (\Magento\Framework\Exception\FileSystemException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    /**
     * @param $fileName
     * @return bool
     */
    protected function getUserData($fileName)
    {
        $data = explode('-', $fileName);
        if ($data[0] && $data[1]) {
            $this->userName = $data[0];
            $this->userId = $data[1];
            return true;
        } else {
            $this->logger->error(__(
                    'Can not get UserName and UserId from file #%1',
                    $fileName)
            );
            return false;
        }
    }

    /**
     * Generate import data
     *
     * @param $data
     */
    public function generateImportData($data)
    {
        if (!empty($data)) {
            foreach ($data as $row => $dt) {
                /*pass header column*/
                if ($row == 0) {
                    continue;
                }

                $this->currentRow = $row;
                $this->addTransactionData($dt);
            }
        }
    }

    /**
     * Add transaction to validate list
     *
     * @param $item
     */
    public function addTransactionData($item)
    {
        $transactionId = trim($item[1]);

        if ($item[0] == self::STATUS_RETURN) {
            $this->returnTransaction[$transactionId] = [
                'row' => $this->currentRow,
                'amount' => $item[2],
                'date' => $item[3],
                'transactionId' => $transactionId,
                'statusCode' => $item[0]
            ];
        } elseif ($item[0] == self::STATUS_COLLECT) {
            if ($this->paymentMethod == \Riki\ArReconciliation\Model\Import::WELL_NET) {
                $transactionId = substr($transactionId, 0, -1);
            }

            $this->collectTransaction[$transactionId] = [
                'row' => $this->currentRow,
                'amount' => $item[2],
                'date' => $item[3],
                'transactionId' => $transactionId,
                'statusCode' => $item[0]
            ];
        }
    }

    /**
     * import collect transaction
     */
    public function importCollectTransaction()
    {
        if (!empty($this->collectTransaction)) {

            $collectData = $this->getCollectData();

            if (!empty($collectData)) {
                $processList = [];

                $totalRecords = 0;

                foreach ($collectData as $cd) {
                    $transactionId = $cd->getIncrementId();
                    if ($this->paymentMethod == \Riki\ArReconciliation\Model\Import::ASKUL
                        || $this->paymentMethod == \Riki\ArReconciliation\Model\Import::YAMATO
                    ) {
                        $transactionId = $cd->getTrackNumber();
                    }

                    if (!in_array($transactionId, $processList)) {
                        array_push($processList, $transactionId);

                        $importData = [];

                        if (isset($this->collectTransaction[$transactionId])) {
                            $importData = $this->collectTransaction[$transactionId];
                        }

                        if (!empty($importData)) {
                            $this->importCollectData($cd, $importData);
                            $totalRecords++;
                        }

                        if ($totalRecords == self::INSERT_MULTIPLE_LIMIT) {
                            $this->generateCollectLog();
                            $this->generateImportLog();
                            $totalRecords = 0;
                        }
                    }
                }

                $this->generateCollectLog();
                $this->generateImportLog();
            }
        }
    }

    /**
     * import return transaction
     */
    public function importReturnTransaction()
    {
        if (!empty($this->returnTransaction)) {

            $returnData = $this->getReturnData();

            if (!empty($returnData)) {
                $totalRecords = 0;

                foreach ($returnData as $rd) {
                    $transactionId = $rd->getIncrementId();

                    $importData = [];

                    if (isset($this->returnTransaction[$transactionId])) {
                        $importData = $this->returnTransaction[$transactionId];
                    }

                    if (!empty($importData)) {
                        $this->importReturnData($rd, $importData);
                        $totalRecords++;
                    }

                    if ($totalRecords == self::INSERT_MULTIPLE_LIMIT) {
                        $this->generateRefundLog();
                        $this->generateImportLog();
                        $totalRecords = 0;
                    }
                }
            }

            $this->generateRefundLog();
            $this->generateImportLog();
        }
    }

    /**
     * @return bool|\Magento\Sales\Api\Data\OrderInterface[]|\Magento\Sales\Model\ResourceModel\Order\Shipment\Collection
     */
    public function getCollectData()
    {
        $transactionIds = array_keys($this->collectTransaction);
        switch ($this->paymentMethod) {
            case \Riki\ArReconciliation\Model\Import::CREDIT_CARD:
                return $this->getOrderDataByIncrementIds($transactionIds);
            case \Riki\ArReconciliation\Model\Import::WELL_NET:
                return $this->getOrderDataByIncrementIds($transactionIds);
            case \Riki\ArReconciliation\Model\Import::ASKUL:
                return $this->getShipmentDataByTrackingNumbers($transactionIds);
            case \Riki\ArReconciliation\Model\Import::YAMATO:
                return $this->getShipmentDataByTrackingNumbers($transactionIds);
            default:
                return false;
        }
    }

    /**
     * Get return data
     *
     * @return mixed
     */
    public function getReturnData()
    {
        $transactionIds = array_keys($this->returnTransaction);
        switch ($this->paymentMethod) {
            case \Riki\ArReconciliation\Model\Import::CREDIT_CARD:
                return $this->getOrderDataByIncrementIds($transactionIds);
            case \Riki\ArReconciliation\Model\Import::WELL_NET:
                return $this->getOrderDataByIncrementIds($transactionIds);
            case \Riki\ArReconciliation\Model\Import::ASKUL:
                return $this->getReturnDataByIncrementIds($transactionIds);
            case \Riki\ArReconciliation\Model\Import::YAMATO:
                return $this->getReturnDataByIncrementIds($transactionIds);
            default:
                return false;
        }
    }

    /**
     * Get order data by increment ids
     *
     * @param array $incrementIds
     * @return bool|\Magento\Sales\Api\Data\OrderInterface[]
     */
    public function getOrderDataByIncrementIds($incrementIds)
    {
        $criteria = $this->searchCriteriaBuilder->addFilter(
            'increment_id',
            $incrementIds,
            'in'
        )->create();

        $orderCollection = $this->orderRepository->getList($criteria);

        if ($orderCollection->getTotalCount()) {
            return $orderCollection->getItems();
        }

        return false;
    }

    /**
     * Get shipment by tracking number
     *
     * @param $trackingNumbers
     * @return bool|\Magento\Sales\Model\ResourceModel\Order\Shipment\Collection
     */
    public function getShipmentDataByTrackingNumbers($trackingNumbers)
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $shipmentCollection */
        $shipmentCollection = $this->shipmentCollectionFactory->create();

        $shipmentCollection->join(
            'sales_shipment_track',
            'main_table.entity_id = sales_shipment_track.parent_id',
            'track_number'
        );

        $shipmentCollection->addFieldToFilter(
            'track_number',
            ['in' => $trackingNumbers]
        );

        $shipmentCollection->setOrder('delivery_date', 'DESC');

        $shipmentCollection->getSelect()->group('main_table.entity_id');

        if ($shipmentCollection->getSize()) {
            return $shipmentCollection;
        }

        return false;
    }

    /**
     * Get rma data by increment Ids
     *
     * @param array $incrementIds
     * @return bool|\Magento\Rma\Api\Data\RmaInterface[]
     */
    public function getReturnDataByIncrementIds($incrementIds)
    {
        $criteria = $this->searchCriteriaBuilder->addFilter(
            'increment_id',
            $incrementIds,
            'in'
        )->create();

        $rmaCollection = $this->rmaRepository->getList($criteria);

        if ($rmaCollection->getTotalCount()) {
            return $rmaCollection->getItems();
        }

        return false;
    }

    /**
     * Import return data, generate transaction log, import log
     *
     * @param $returnObject
     * @param $importData
     */
    public function importReturnData($returnObject, $importData)
    {
        $this->logger->info('Start import return data for transaction: ' . $importData['transactionId']);

        $oldData = [
            'nestle_refund_amount' => $returnObject->getData('nestle_refund_amount'),
            'nestle_refund_date' => $returnObject->getData('nestle_refund_date')
        ];

        $returnObject->setData(
            'nestle_refund_amount',
            (int)preg_replace(
                '~[\\\\.,¥ ]~',
                '',
                trim($importData['amount'])
            )
        );

        $returnObject->setData(
            'nestle_refund_date',
            $this->dateTime->date('Y-m-d', strtotime($importData['date']))
        );

        try {
            $this->saveReturnData($returnObject);
            $this->generateReturnLogData($returnObject, $oldData);
            $this->generateImportLogData($importData);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        $this->logger->info('End import return data for transaction: ' . $importData['transactionId']);
    }

    /**
     * Import collect data, generate transaction log, import log
     *
     * @param $collectObject
     * @param $importData
     */
    public function importCollectData($collectObject, $importData)
    {
        $this->logger->info('Start import collect data for transaction: ' . $importData['transactionId']);

        $oldData = [
            'nestle_payment_amount' => $collectObject->getData('nestle_payment_amount'),
            'nestle_payment_date' => $collectObject->getData('nestle_payment_date')
        ];

        $collectObject->setData(
            'nestle_payment_amount',
            (int)preg_replace(
                '~[\\\\.,¥ ]~',
                '',
                trim($importData['amount'])
            )
        );

        $collectObject->setData('nestle_payment_date', $this->dateTime->date('Y-m-d', strtotime($importData['date'])));

        $collectObject->setData('nestle_payment_receive_date', $this->dateTime->date());

        try {
            $this->saveCollectData($collectObject);
            $this->generateCollectLogData($collectObject, $oldData);
            $this->generateImportLogData($importData);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        $this->logger->info('End import collect data for transaction: ' . $importData['transactionId']);
    }

    /**
     * save collect data
     *
     * @param $collectObject
     */
    public function saveCollectData($collectObject)
    {
        if ($this->paymentMethod == \Riki\ArReconciliation\Model\Import::CREDIT_CARD
            || $this->paymentMethod == \Riki\ArReconciliation\Model\Import::WELL_NET
        ) {
            $this->saveCollectDataForOrder($collectObject);
        } else {
            $this->saveCollectDataForShipment($collectObject);
        }
    }

    /**
     * Save collect data for order
     *
     * @param $collectObject
     */
    public function saveCollectDataForOrder($collectObject)
    {
        $orderTable = $this->salesConnection->getTableName('sales_order');
        $bind = [
            'nestle_payment_amount' => $collectObject->getData('nestle_payment_amount'),
            'nestle_payment_date' => $collectObject->getData('nestle_payment_date')
        ];

        $condition = 'entity_id = ' . $collectObject->getId();

        $this->salesConnection->update($orderTable, $bind, $condition);
    }

    /**
     * Save collect data for shipment
     *
     * @param $collectObject
     */
    public function saveCollectDataForShipment($collectObject)
    {
        $shipmentTable = $this->salesConnection->getTableName('sales_shipment');
        $bind = [
            'nestle_payment_amount' => $collectObject->getData('nestle_payment_amount'),
            'nestle_payment_date' => $collectObject->getData('nestle_payment_date'),
            'nestle_payment_receive_date' => $collectObject->getData('nestle_payment_receive_date')
        ];
        $condition = 'entity_id = ' . $collectObject->getId();
        $this->salesConnection->update($shipmentTable, $bind, $condition);
    }

    /**
     * save return data
     *
     * @param $returnObject
     */
    public function saveReturnData($returnObject)
    {
        if ($this->paymentMethod == \Riki\ArReconciliation\Model\Import::CREDIT_CARD
            || $this->paymentMethod == \Riki\ArReconciliation\Model\Import::WELL_NET
        ) {
            $this->saveReturnDataForOrder($returnObject);
        } else {
            $this->saveReturnDataForRma($returnObject);
        }
    }

    /**
     * Save return data for order
     *
     * @param $returnObject
     */
    public function saveReturnDataForOrder($returnObject)
    {
        $orderTable = $this->salesConnection->getTableName('sales_order');
        $bind = [
            'nestle_refund_amount' => $returnObject->getData('nestle_refund_amount'),
            'nestle_refund_date' => $returnObject->getData('nestle_refund_date')
        ];

        $condition = 'entity_id = ' . $returnObject->getId();

        $this->salesConnection->update($orderTable, $bind, $condition);
    }

    /**
     * Save return data for rma
     *
     * @param $returnObject
     */
    public function saveReturnDataForRma($returnObject)
    {
        $rmaTable = $this->defaultConnection->getTableName('magento_rma');

        $bind = [
            'nestle_refund_amount' => $returnObject->getData('nestle_refund_amount'),
            'nestle_refund_date' => $returnObject->getData('nestle_refund_date')
        ];

        $condition = 'entity_id = ' . $returnObject->getId();

        $this->defaultConnection->update($rmaTable, $bind, $condition);
    }

    /**
     * Generate refund log
     */
    public function generateRefundLog()
    {
        if (empty($this->returnedLogData)) {
            return;
        }

        $this->logger->info('Start generate returned Log:');

        if ($this->paymentMethod == \Riki\ArReconciliation\Model\Import::CREDIT_CARD
            || $this->paymentMethod == \Riki\ArReconciliation\Model\Import::WELL_NET) {
            $this->generateOrderRefundLog();
        } else {
            $this->generateReturnLog();
        }


        $this->logger->info('End generate return log');
    }

    /**
     * Generate collected log data - use to insert multiple
     *
     * @param $collectObject
     * @param $oldData
     */
    public function generateCollectLogData($collectObject, $oldData)
    {
        array_push(
            $this->collectedLogData,
            ['collectObject' => $collectObject, 'oldData' => $oldData]
        );
    }

    /**
     * Generate returned log data - use to insert multiple
     *
     * @param $returnObject
     * @param $oldData
     */
    public function generateReturnLogData($returnObject, $oldData)
    {
        array_push(
            $this->returnedLogData,
            ['returnObject' => $returnObject, 'oldData' => $oldData]
        );
    }

    /**
     * Generate import  log data - use to insert multiple
     *
     * @param $importData
     */
    public function generateImportLogData($importData)
    {
        array_push($this->importLogData, $importData);
    }

    /**
     * Generate collect log
     */
    public function generateCollectLog()
    {
        if (empty($this->collectedLogData)) {
            return;
        }

        $this->logger->info('Start generate collected Log:');

        if ($this->paymentMethod == \Riki\ArReconciliation\Model\Import::CREDIT_CARD
            || $this->paymentMethod == \Riki\ArReconciliation\Model\Import::WELL_NET) {
            $this->generateOrderLog();
        } else {
            $this->generateShipmentLog();
        }

        $this->logger->info('End generate collected log');
    }

    /**
     * Create import log for each transaction
     */
    public function generateImportLog()
    {
        if (empty($this->importLogData)) {
            return;
        }

        $this->logger->info('Start generate import log');

        $insertData = [];

        foreach ($this->importLogData as $importData) {
            $amount = (int)preg_replace('~[\\\\.,¥ ]~', '', trim($importData['amount']));
            array_push($insertData, [
                'transaction_id' => trim($importData['transactionId']),
                'amount' => $amount,
                'payment_date' => $this->dateTime->date('Y-m-d', strtotime($importData['date'])),
                'status_code' => trim($importData['statusCode']),
                'payment_from' => $this->paymentMethod
            ]);
        }

        $logTable = $this->salesConnection->getTableName('riki_payment_ar_list');

        try {
            $this->salesConnection->insertMultiple($logTable, $insertData);
            /*reset log data*/
            $this->importLogData = [];
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }

        $this->logger->info('End generate import log');
    }

    /**
     * Generate shipment collected log
     */
    public function generateShipmentLog()
    {
        if (empty($this->collectedLogData)) {
            return;
        }

        $insertData = [];

        foreach ($this->collectedLogData as $logData) {
            $shipment = $logData['collectObject'];
            $oldData = $logData['oldData'];

            array_push($insertData, [
                'user_id' => $this->userId,
                'user_name' => $this->userName,
                'shipment_id' => $shipment->getId(),
                'shipment_increment_id' => $shipment->getIncrementId(),
                'nestle_payment_amount' => $shipment->getData('nestle_payment_amount'),
                'nestle_payment_date' => $shipment->getData('nestle_payment_date'),
                'log' => json_encode($oldData),
                'type' => ShipmentLog::TYPE_IMPORT,
                'note' => __('Amount and date of money Received'),
                'created' => $this->dateTime->date()
            ]);
        }

        $logTable = $this->salesConnection->getTableName('riki_shipment_log');

        try {
            $this->salesConnection->insertMultiple($logTable, $insertData);
            /*reset log data*/
            $this->collectedLogData = [];
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    /**
     * Generate rma returned log
     */
    public function generateReturnLog()
    {
        if (empty($this->returnedLogData)) {
            return;
        }

        $insertData = [];

        foreach ($this->returnedLogData as $logData) {
            $rma = $logData['returnObject'];
            $oldData = $logData['oldData'];

            array_push($insertData, [
                'user_id' => $this->userId,
                'user_name' => $this->userName,
                'rma_id' => $rma->getId(),
                'rma_increment_id' => $rma->getIncrementId(),
                'nestle_refund_amount' => $rma->getData('nestle_refund_amount'),
                'nestle_refund_date' => $rma->getData('nestle_refund_date'),
                'log' => json_encode($oldData),
                'type' => ReturnLog::TYPE_IMPORT,
                'note' => __('Amount and date of money returned'),
                'created' => $this->dateTime->date()
            ]);
        }

        $logTable = $this->salesConnection->getTableName('riki_rma_refund_log');

        try {
            $this->salesConnection->insertMultiple($logTable, $insertData);
            /*reset log data*/
            $this->returnedLogData = [];
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    /**
     * generate order collected log
     */
    public function generateOrderLog()
    {
        if (empty($this->collectedLogData)) {
            return;
        }

        $insertData = [];

        foreach ($this->collectedLogData as $logData) {
            $order = $logData['collectObject'];
            $oldData = $logData['oldData'];

            array_push($insertData, [
                'user_id' => $this->userId,
                'user_name' => $this->userName,
                'order_id' => $order->getId(),
                'order_increment_id' => $order->getIncrementId(),
                'nestle_payment_amount' => $order->getData('nestle_payment_amount'),
                'nestle_payment_date' => $order->getData('nestle_payment_date'),
                'log' => json_encode($oldData),
                'type' => OrderLog::TYPE_IMPORT,
                'note' => __('Amount and date of money Received'),
                'created' => $this->dateTime->date()
            ]);
        }

        $logTable = $this->salesConnection->getTableName('riki_order_collected_log');

        try {
            $this->salesConnection->insertMultiple($logTable, $insertData);

            /*reset log data*/
            $this->collectedLogData = [];
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    /**
     * Generate order refunded log
     */
    public function generateOrderRefundLog()
    {
        if (empty($this->returnedLogData)) {
            return;
        }

        $insertData = [];

        foreach ($this->returnedLogData as $logData) {
            $order = $logData['returnObject'];
            $oldData = $logData['oldData'];

            array_push($insertData, [
                'user_id' => $this->userId,
                'user_name' => $this->userName,
                'order_id' => $order->getId(),
                'order_increment_id' => $order->getIncrementId(),
                'nestle_refund_amount' => $order->getData('nestle_refund_amount'),
                'nestle_refund_date' => $order->getData('nestle_refund_date'),
                'log' => json_encode($oldData),
                'type' => OrderLog::TYPE_IMPORT,
                'note' => __('Amount and date of money returned'),
                'created' => $this->dateTime->date()
            ]);
        }

        $logTable = $this->salesConnection->getTableName('riki_order_refund_log');

        try {
            $this->salesConnection->insertMultiple($logTable, $insertData);

            /*reset log data*/
            $this->returnedLogData = [];
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}
