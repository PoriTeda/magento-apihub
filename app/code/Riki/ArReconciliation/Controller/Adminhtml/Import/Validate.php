<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\ArReconciliation\Controller\Adminhtml\Import;

use Magento\ImportExport\Controller\Adminhtml\ImportResult as ImportResultController;
use Magento\ImportExport\Block\Adminhtml\Import\Frame\Result as ImportResultBlock;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Exception\LocalizedException;

class Validate extends ImportResultController
{
    const STATUS_COLLECT = 40;
    const STATUS_RETURN = 60;
    const AR_PAYMENT_CSV_FOLDER = 'ar_payment_csv';

    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $uploaderFactory;
    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $readerCSV;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
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
    /**
     * @var \Riki\ArReconciliation\Logger\LoggerImportCsv
     */
    protected $logger;

    protected $fileSystem;
    /**
     * @var string
     */
    protected $fileId = 'csv_file';
    /**
     * @var array
     */
    protected $allowedExtensions = ['csv'];

    protected $paymentMethod;

    protected $currentRow;

    protected $collectTransaction = [];

    protected $collectTransactionError = [];

    protected $returnTransaction = [];

    protected $returnTransactionError = [];

    protected $error = [];

    protected $statusCode = [];

    protected $filePath;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\ImportExport\Model\Report\ReportProcessorInterface $reportProcessor,
        \Magento\ImportExport\Model\History $historyModel,
        \Magento\ImportExport\Helper\Report $reportHelper,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Framework\File\Csv $reader,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory,
        \Riki\ArReconciliation\Logger\LoggerImportCsv $logger,
        File $fileSystem
    ) {
        parent::__construct($context, $reportProcessor, $historyModel, $reportHelper);
        $this->uploaderFactory = $uploaderFactory;
        $this->readerCSV = $reader;
        $this->directoryList = $directoryList;
        $this->timezone = $timezone;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->rmaRepository = $rmaRepository;
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->logger = $logger;
        $this->fileSystem = $fileSystem;
        $this->logger->setTimezone(
            new \DateTimeZone($this->timezone->getConfigTimezone())
        );

        $this->statusCode = [
            self::STATUS_COLLECT,
            self::STATUS_RETURN
        ];
    }

    /**
     * Validate uploaded files action
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {
        $dataPost = $this->getRequest()->getPostValue();

        /*@var \Magento\Framework\View\Result\Layout $resultLayout*/
        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);

        /*@var ImportResultBlock $resultBlock*/
        $resultBlock = $resultLayout->getLayout()->getBlock('import.frame.result');

        if ($dataPost) {
            /* common actions */
            $resultBlock->addAction('show', 'import_validation_container');

            try {
                $this->paymentMethod = $dataPost['payment_type'];
                /*get import file content, throw exception if failed */
                $data = $this->getImportFileContent();
            } catch (\Exception $e) {
                $resultBlock->addError($e->getMessage());
                return $resultLayout;
            }

            if ($data) {
                try {
                    $startTime = microtime(true);
                    $this->validate($data);
                    $endTime = microtime(true) - $startTime;
                    $this->logger->info('End validate data, total rows: '.$this->currentRow.', total time: '.$endTime);
                    $this->deleteUploadedFile();
                } catch (\Exception $e) {
                    $resultBlock->addError(__($e->getMessage()));
                    $this->deleteUploadedFile();
                    return $resultLayout;
                }
            }

            if (!empty($this->error)) {
                $resultBlock->addError($this->setErrorMessage());
            } else {
                $resultBlock->addSuccess(__('File is valid! To start import process press "Import" button'), true);
                $resultBlock->addNotice(__('Validate ok!'));
            }

            return $resultLayout;
        } elseif ($this->getRequest()->isPost()) {
            $resultBlock->addError(__('The file was not uploaded.'));
            return $resultLayout;
        }

        $this->messageManager->addError(__('Sorry, but the data is invalid or the file is not uploaded.'));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $resultRedirect->setPath('adminhtml/*/edit');

        return $resultRedirect;
    }


    /**
     * validate import data
     *
     * @param $data
     */
    public function validate($data)
    {
        foreach ($data as $row => $value) {
            $this->currentRow = $row;

            /*pass header column*/
            if ($row == 0) {
                continue;
            }

            if (is_array($value)) {
                $this->logger->info('Start validate data for row '.$this->currentRow);

                $startTime = microtime(true);

                /*check empty data for row column*/
                $emptyColumn = $this->hasEmptyColumn($value);
                /*check data type for row column*/
                $invalidDataType = $this->invalidDataTypeColumn($value);
                /* check transaction is exists or not, is duplicated or not*/
                if (!$emptyColumn && !$invalidDataType) {
                    $this->addTransactionData($value);
                }

                $endTime = microtime(true) - $startTime;

                $this->logger->info('End validate data for row '.$this->currentRow.', spend time: '.$endTime);
            }
        }

        /*validate collected data*/
        $this->validateCollectTransaction();

        /*validate returned data*/
        $this->validateReturnTransaction();

        /*get error after validate transaction*/
        $this->addTransactionError();
    }

    /**
     * Check empty column
     *
     * @param $item
     * @return bool
     */
    public function hasEmptyColumn($item)
    {
        $rs = false;

        for ($x = 0; $x < 4; $x++) {
            if (empty($item[$x]) && $item[$x] != 0) {
                $rs = true;

                $this->addValidateMessages(
                    $this->currentRow,
                    __("column %1 empty", $x + 1)
                );
            }
        }

        return $rs;
    }

    /**
     * Check data type
     *
     * @param $item
     * @return bool
     */
    public function invalidDataTypeColumn($item)
    {
        $rs = false;
        /*status code is only 40 or 60*/
        if (!empty($item[0])) {
            if (!in_array(trim($item[0]), $this->statusCode)) {
                $rs = true;
                $this->addValidateMessages(
                    $this->currentRow,
                    __("Status code is only 40 or 60")
                );
            }
        }

        /*transaction id must be a number*/
        if (!empty($item[ 1 ])) {
            if (!preg_match('/^[0-9]+$/', trim($item[1]))) {
                $rs = true;
                $this->addValidateMessages(
                    $this->currentRow,
                    __("Transaction Id must be number")
                );
            }
        }

        /*amount id must be a number*/
        if (!empty($item[2])) {
            $amount = preg_replace('~[\\\\., ]~', '', trim($item[2]));

            if ($this->paymentMethod == \Riki\ArReconciliation\Model\Import::ASKUL) {
                $amount = preg_replace('~[\\\\.,¥ ]~', '', trim($item[2]));
            }

            if (!preg_match('/^[0-9]+$/', $amount)) {
                $rs = true;
                $this->addValidateMessages(
                    $this->currentRow,
                    __("Amount must be number")
                );
            }
        }

        /*payment date must be date-time format*/
        if (!empty($item[ 3 ])) {
            /*some special character maybe convert to today time like { c , l.... }*/
            if (strlen($item[3]) < 5 || strtotime(trim($item[3])) === false) {
                $rs = true;
                $this->addValidateMessages(
                    $this->currentRow,
                    __("Payment date must be date-time(mm/dd/yyyy) format")
                );
            }
        }

        return $rs;
    }

    /**
     * Add validate error message
     *
     * @param $message
     */
    public function addValidateMessages($row, $message)
    {
        if (!isset($this->error[$row])) {
            $this->error[$row] = [];
        }

        array_push($this->error[$row], $message);
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
            array_push($this->returnTransaction, $transactionId);

            $this->returnTransactionError[$transactionId] = [
                'row' => $this->currentRow,
                'isExists' => false,
                'isDuplicated' => false,
            ];
        } elseif ($item[0] == self::STATUS_COLLECT) {
            if ($this->paymentMethod == \Riki\ArReconciliation\Model\Import::WELL_NET) {
                $transactionId = substr($transactionId, 0, -1);
            }

            array_push($this->collectTransaction, $transactionId);

            $this->collectTransactionError[$transactionId] = [
                'row' => $this->currentRow,
                'isExists' => false,
                'isDuplicated' => false,
            ];
        }
    }

    /**
     * validate return data: is exists, is duplicated
     */
    public function validateCollectTransaction()
    {
        if (empty($this->collectTransaction)) {
            return;
        }

        $this->logger->info('Start get collect data');

        $startTime = microtime(true);

        $data = $this->getCollectData();

        $endTime = microtime(true) - $startTime;

        $this->logger->info('End get collect data, spend time: '.$endTime);

        if ($data) {
            $this->logger->info('Start validate collect data');

            $startTime = microtime(true);

            foreach ($data as $dt) {
                $transactionId = $dt->getIncrementId();

                if ($this->paymentMethod == \Riki\ArReconciliation\Model\Import::ASKUL
                || $this->paymentMethod == \Riki\ArReconciliation\Model\Import::YAMATO
                ) {
                    $transactionId = $dt->getTrackNumber();
                }

                if (!isset($this->collectTransactionError[$transactionId]) ||
                    (isset($this->collectTransactionError[$transactionId]) &&
                    $this->collectTransactionError[$transactionId]['isExists'])
                ) {
                    continue;
                }

                $isDuplicated = false;

                if (!empty($dt->getData('nestle_payment_amount')) && $dt->getData('nestle_payment_amount') > 0) {
                    $isDuplicated = true;
                }

                $this->collectTransactionError[$transactionId]['isExists'] = true;
                $this->collectTransactionError[$transactionId]['isDuplicated'] = $isDuplicated;
            }

            $endTime = microtime(true) - $startTime;

            $this->logger->info('End validate collect data, spend time: '.$endTime);
        }
    }

    /**
     * validate return data: is exists, is duplicated
     */
    public function validateReturnTransaction()
    {
        if (empty($this->returnTransaction)) {
            return;
        }

        $this->logger->info('Start get return data');

        $startTime = microtime(true);

        $data = $this->getReturnData();

        $endTime = microtime(true) - $startTime;

        $this->logger->info('End get return data, spend time: '.$endTime);

        if ($data) {
            $this->logger->info('Start validate return data');

            $startTime = microtime(true);

            foreach ($data as $dt) {
                $transactionId = $dt->getIncrementId();

                $isDuplicated = false;

                if (!empty($dt->getData('nestle_refund_amount')) && $dt->getData('nestle_refund_amount') > 0) {
                    $isDuplicated = true;
                }

                $this->returnTransactionError[$transactionId]['isExists'] = true;
                $this->returnTransactionError[$transactionId]['isDuplicated'] = $isDuplicated;
            }

            $endTime = microtime(true) - $startTime;

            $this->logger->info('End validate return data, spend time: '.$endTime);
        }
    }

    /**
     * Get collect data
     *
     * @return mixed
     */
    public function getCollectData()
    {
        switch ($this->paymentMethod) {
            case \Riki\ArReconciliation\Model\Import::CREDIT_CARD:
                return $this->getOrderDataByIncrementIds($this->collectTransaction);
            case \Riki\ArReconciliation\Model\Import::WELL_NET:
                return $this->getOrderDataByIncrementIds($this->collectTransaction);
            case \Riki\ArReconciliation\Model\Import::ASKUL:
                return $this->getShipmentDataByTrackingNumbers($this->collectTransaction);
            case \Riki\ArReconciliation\Model\Import::YAMATO:
                return $this->getShipmentDataByTrackingNumbers($this->collectTransaction);
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
        switch ($this->paymentMethod) {
            case \Riki\ArReconciliation\Model\Import::CREDIT_CARD:
                return $this->getOrderDataByIncrementIds($this->returnTransaction);
            case \Riki\ArReconciliation\Model\Import::WELL_NET:
                return $this->getOrderDataByIncrementIds($this->returnTransaction);
            case \Riki\ArReconciliation\Model\Import::ASKUL:
                return $this->getReturnDataByIncrementIds($this->returnTransaction);
            case \Riki\ArReconciliation\Model\Import::YAMATO:
                return $this->getReturnDataByIncrementIds($this->returnTransaction);
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
            return $shipmentCollection->getItems();
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
     * add error after validate transaction data
     */
    public function addTransactionError()
    {
        if (!empty($this->collectTransactionError)) {
            foreach ($this->collectTransactionError as $key => $value) {
                $transactionId = $key;

                $row = $value['row'];

                if (!$value['isExists']) {
                    $this->addValidateMessages(
                        $row,
                        __("Transaction ID %1 doesn't exist", $transactionId)
                    );
                }

                if ($value['isDuplicated']) {
                    $this->addValidateMessages(
                        $row,
                        __("Transaction ID %1 is duplicated", $transactionId)
                    );
                }
            }
        }

        if (!empty($this->returnTransactionError)) {
            foreach ($this->returnTransactionError as $key => $value) {
                $transactionId = $key;

                $row = $value['row'];

                if (!$value['isExists']) {
                    $this->addValidateMessages(
                        $row,
                        __("Transaction ID %1 doesn't exist", $transactionId)
                    );
                }

                if ($value['isDuplicated']) {
                    $this->addValidateMessages(
                        $row,
                        __("Transaction ID %1 is duplicated", $transactionId)
                    );
                }
            }
        }
    }

    /*
     * get import file content
     */
    public function getImportFileContent()
    {
        $destinationPath = $this->getDestinationPath();

        $uploader = $this->uploaderFactory->create(['fileId' => $this->fileId])
            ->setAllowCreateFolders(true)
            ->setAllowedExtensions($this->allowedExtensions)
            ->setAllowRenameFiles(true)
            ->addValidateCallback('validate', $this, 'validateFile');
        if (!$uploader->save($destinationPath)) {
            throw new LocalizedException(
                __('File cannot be saved to path: $1', $destinationPath)
            );
        }

        $fileName = $uploader->getUploadedFileName();
        $this->logger->info('Start validate data: '.$fileName);
        $this->logger->info('Payment type: '.$this->paymentMethod);

        $this->filePath = $destinationPath . DIRECTORY_SEPARATOR . $uploader->getUploadedFileName();

        //success
        return $this->readerCSV->getData($destinationPath .'/'. $uploader->getUploadedFileName());
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getDestinationPath()
    {
        $varDirectory = $this->directoryList->getPath(
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );

        $path = $varDirectory. DIRECTORY_SEPARATOR . self::AR_PAYMENT_CSV_FOLDER . DIRECTORY_SEPARATOR
            . $this->paymentMethod;

        $fileObject = new File();

        if (!$fileObject->isDirectory($path)) {
            $fileObject->createDirectory($path, 0777);
        }

        return $path;
    }

    /**
     * Delete uploaded file
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function deleteUploadedFile()
    {
        $this->fileSystem->deleteFile($this->filePath);
    }

    /**
     * set error message for response layout
     *
     * @return array
     */
    public function setErrorMessage()
    {
        $errorMsg = [];

        foreach ($this->error as $row => $error) {
            if (is_array($error)) {
                $msg = __('Error at row %1: ', ($row + 1)) . implode(" , ", $error);
                array_push($errorMsg, $msg);
            }
        }

        return $errorMsg;
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_ArReconciliation::import_payment_csv_file');
    }
}
