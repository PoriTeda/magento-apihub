<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\ThirdPartyImportExport\Cron\ExportToBi;

class PushBiExportedFile
{
    protected $configExport = [];
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * PushBiExportedFile constructor.
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\MessageQueue\ConfigInterface $messageQueueConfig
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\File\Csv $csv
     * @param \Magento\Framework\Filesystem $fileSystem
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper $globalHelper
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\SubProfileNextDeliveryOrderHelper $subProfileOrderHelper
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\SubProfileNextDeliveryShipmentHelper $subProfileShipmentHelper
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerCSV $loggerSubprofile
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerSubProfileCart $loggerSubOrder
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerShipmentCSV $loggerSubShipment
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerSubShipmentProfileCart $loggerSubShipmentDetail
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\MessageQueue\ConfigInterface $messageQueueConfig,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\File\Csv $csv,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\Shell $shell,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper $globalHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\SubProfileNextDeliveryOrderHelper $subProfileOrderHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\SubProfileNextDeliveryShipmentHelper $subProfileShipmentHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerCSV $loggerSubprofile,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerSubProfileCart $loggerSubOrder,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerShipmentCSV $loggerSubShipment,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerSubShipmentProfileCart $loggerSubShipmentDetail,
        \Riki\Subscription\Logger\LoggerOrder $loggerCreateOrder,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->connection = $resourceConnection->getConnection();
        $this->messageQueueConfig = $messageQueueConfig;
        $this->directoryList = $directoryList;
        $this->fileSystem = $fileSystem;
        $this->file = $file;
        $this->csv = $csv;
        $this->globalHelper = $globalHelper;

        $this->subProfileOrderHelper = $subProfileOrderHelper;
        $this->subProfileShipmentHelper = $subProfileShipmentHelper;

        $this->_timezone = $timezone;
        $this->loggerSubprofile = $loggerSubprofile;
        $this->loggerSubprofile->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));

        $this->loggerSubOrder = $loggerSubOrder;
        $this->loggerSubOrder->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));

        $this->loggerSubShipment = $loggerSubShipment;
        $this->loggerSubShipment->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));

        $this->loggerSubShipmentDetail = $loggerSubShipmentDetail;
        $this->loggerSubShipmentDetail->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));

        $this->shell = $shell;
        $this->scopeConfig = $scopeConfig;

        $this->loggerCreateOrder = $loggerCreateOrder;

        $this->_log = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {

        $this->initConfig();

        $aListQueue = $this->messageQueueConfig->getQueuesByTopic('thirdparty.export.nextdelivery');

        $maxProcess = $this->getMaxConsumer();
        foreach ($aListQueue as $queueId => $queueName) {
            $aExportEntity = $this->configExport[$queueName];
            foreach ($aExportEntity as $configQueue){

                    // always run since we don't have any consumer in topic "thirdparty.export.nextdelivery"
                    \Magento\Framework\App\ObjectManager::getInstance()->get("Nestle\Debugging\Helper\DebuggingHelper")
                    ->inClass($this)
                    ->logServerIp()
                    ->log("publish to queue: " . $queueName)
                    ->logBacktrace()
                    ->save("run_merge_file_generate_order");
                    try {
                        $pathLocalTmp = $configQueue['path_local'] . '_tmp';
                        $logger = $configQueue['logger'];

                        if ($this->file->isExists($pathLocalTmp)) {
                            //put collect flag
                            $checkFlag = rtrim($pathLocalTmp) . '/' . '.collectflag';

                            $readFile = $this->fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
                            $pathLocalRelateTmp = str_replace(BP,"",$pathLocalTmp);
                            $listFileExported = $readFile->read($pathLocalRelateTmp);

                            if(!count($listFileExported)){
                                continue;
                            }

                            if($this->file->isExists($checkFlag)){
                                if(count($listFileExported) == 1 || $this->isQueueFinished($configQueue)){
                                    $this->removeCheckFlag($checkFlag,$configQueue);
                                }
                                continue;
                            }

                            $this->file->filePutContents($checkFlag, 1);

                            $flagTime = microtime(true);

                            $logger->info('Running export file with '.$maxProcess.' process...');
                            $logger->info('Collecting log export file from processes..');

                            try {
                                $this->collectLogFile($configQueue);
                            }catch (\Exception $e){
                                $logger->error($e->getMessage());
                            }


                            $logger->info('Start merging separated file of processes...');
                            $aListFileMerged = $this->mergeFileExport($configQueue);

                            $spendTime = microtime(true) - $flagTime;
                            $flagTime = microtime(true);

                            $logger->info('Spend time for merged file :'.$spendTime."s");

                            if(!empty($aListFileMerged)){
                                $logger->info('Ready push file to SFTP...');
                            }
                            else{
                                $logger->info('There are no files to be merged');
                                $this->removeCheckFlag($checkFlag,$configQueue);
                                continue;
                            }

                            $this->pushFileToSftp($configQueue, $aListFileMerged);

                            $spendTime = microtime(true) - $flagTime;
                            $logger->info('Spend time for push file to SFTP:'.$spendTime."s");

                            $this->removeCheckFlag($checkFlag,$configQueue);
                        }
                    }catch (\Exception $e){
                        $logger->info($e->getMessage());
                    }
            }
        }

        /*Collect for subscription order*/
        $configQueue['base_consumer_name'] = 'GenerateOrderSubscription';
        $configQueue['logger'] = $this->loggerCreateOrder;

        if ($this->isQueueFinished($configQueue)) {
            try {
                $this->collectLogFile($configQueue);
            }catch (\Exception $e){
                $this->loggerCreateOrder->error($e->getMessage());
            }

        }

    }


    /**
     * @param $checkFlag
     * @param $configQueue
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function removeCheckFlag($checkFlag,$configQueue){
        if($this->file->isExists($checkFlag) || $this->isQueueFinished($configQueue)){
            $this->file->deleteFile($checkFlag);
        }
    }
    /**
     * @return array
     */
    public function initConfig()
    {
        return $this->configExport = [
            'sender_queue_next_order_subscription_profile' => [
                'profile' => [
                    'base_consumer_name' => 'NextOrderSubscriptionProfileHeader',
                    'path_local' => BP.'/'.$this->subProfileOrderHelper->getPathLocalExportProfile(),
                    'path_sftp' => [
                        'main' => $this->subProfileOrderHelper->getSFTPPathExportProfile(),
                        'version' => $this->subProfileOrderHelper->getSFTPPathExportProfileVersion(),
                    ],
                    'path_sftp_report' => [
                        'main' => $this->subProfileOrderHelper->getSFTPPathProfileReportExport(),
                        'version' => $this->subProfileOrderHelper->getSFTPPathProfileVersionReportExport(),
                    ],
                    'logger' => $this->loggerSubprofile
                ]
            ],
            'sender_queue_next_order_subscription_profile_simulate' => [
                'profile_cart' => [
                    'base_consumer_name' => 'NextOrderSubscriptionProfileSimulate',
                    'path_local' => BP.'/'.$this->subProfileOrderHelper->getPathLocalExportProfileCart(),
                    'path_sftp' => $this->subProfileOrderHelper->getSFTPPathExportProfileCart(),
                    'path_sftp_report' => $this->subProfileOrderHelper->getSFTPPathProfileCartReportExport(),
                    'logger' => $this->loggerSubOrder
                ],
                'profile_shipment' => [
                    'base_consumer_name' => 'NextOrderSubscriptionProfileSimulate',
                    'path_local' => BP.'/'.$this->subProfileShipmentHelper->getPathLocalSubShipmentExport(),
                    'path_sftp' => $this->subProfileShipmentHelper->getSFTPPathExport(),
                    'path_sftp_report' => $this->subProfileShipmentHelper->getSFTPPathReportExport(),
                    'logger' => $this->loggerSubShipment
                ],
                'profile_shipment_detail' => [
                    'base_consumer_name' => 'NextOrderSubscriptionProfileSimulate',
                    'path_local' => BP.'/'.$this->subProfileShipmentHelper->getPathLocalSubShipmentDetailExport(),
                    'path_sftp' => $this->subProfileShipmentHelper->getSFTPPathExport(),
                    'path_sftp_report' => $this->subProfileShipmentHelper->getSFTPPathReportExport(),
                    'logger' => $this->loggerSubShipmentDetail
                ]
            ]
        ];
    }

    /**
     * @param $queueId
     * @param $aListNewMessage
     * @return bool
     */
    public function isQueueFinished($configQueue)
    {
        $baseConsumerName = $configQueue['base_consumer_name'];

        $sPathBinMagento = BP.'/bin/magento';

        $basicCommand    = $sPathBinMagento.' queue:consumers:start --max-messages=';
        $aResultCommand  = $this->shell->execute("ps aux | grep -i " . $baseConsumerName);

        if (strpos($aResultCommand, $basicCommand) !== false && strpos($aResultCommand, $baseConsumerName) !== false) {
            return false;
        }
        return true;
    }

    /**
     * @param $configQueue
     * @return array
     * @throws \Exception
     */
    public function mergeFileExport($configQueue)
    {

        $pathLocalTmp = $configQueue['path_local'] . '_tmp';
        $baseConsumerName = $configQueue['base_consumer_name'];
        $logger = $configQueue['logger'];

        $readFile = $this->fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $pathLocalRelateTmp = str_replace(BP,"",$pathLocalTmp);
        $listFileExported = $readFile->read($pathLocalRelateTmp);

        $listFileMerged = [];

        if (count($listFileExported)) {
            foreach ($listFileExported as $fileExported) {
                $iPos = strpos($fileExported, $baseConsumerName);
                if ($iPos !== false) {
                    $fileExportMerged = str_replace($baseConsumerName, '', $fileExported);
                    $fileExportMerged = substr_replace($fileExportMerged, '', $iPos - 2, 2);
                    $listFileMerged[$fileExportMerged][] = $fileExported;
                }
            }
        }

        if(!empty($listFileMerged)){
            $logger->info('Validating merged file...');
        }

        $listFileMergedSuccess = [];
        foreach ($listFileMerged as $filMerged => $listFileSeparated) {
            $dataMerged = [];

            $headerMergedFile = [];
            foreach ($listFileSeparated as $keyFile => $fileSeparated) {
                try {

                    $fileSeparated = BP.'/'.$fileSeparated;
                    $dataSeparated = $this->csv->getData($fileSeparated);

                    if($this->file->isExists($fileSeparated)){
                        $this->file->deleteFile($fileSeparated);
                    }

                    if (count($dataSeparated)) {
                        $headerMergedFile = array_shift($dataSeparated);
                    }
                    //remove duplicated profile origin
                    $dataMergedWithHeader = array_merge([$headerMergedFile],$dataMerged);
                    $dataMergedWithHeader = $this->globalHelper->removeSubDuplicatedInfo($dataMergedWithHeader, $dataSeparated);
                    array_shift($dataMergedWithHeader);

                    $dataMerged = $dataMergedWithHeader;
                    $dataMerged = array_merge($dataMerged, $dataSeparated);

                } catch (\Exception $e) {
                    $logger->error('Some problem while merge file local '.$filMerged);
                    $logger->error($e->getMessage());
                }
            }

            try{

                $dataMerged = array_merge([$headerMergedFile], $dataMerged);
                $this->validateFileCsv($filMerged, $dataMerged, $logger);

                //check file exist on sftp.
                $filMerged = BP.'/'.$filMerged;
                /*$sftpInfo = $this->getSftpInfo($filMerged,$configQueue);
                $nameFile = str_replace($pathLocalTmp,"",$filMerged);
                $nameFile = trim($nameFile,"/");
                $downloadFileSftp  = $this->globalHelper->downloadFileFromFtp($pathLocalRelateTmp,$nameFile,$sftpInfo['sftp'],$logger);
                if($downloadFileSftp){
                    $logger->info(sprintf('Checking file %s exist on SFTP',$filMerged));
                    $dataMerged = $this->checkMergeFileDownload($filMerged,$dataMerged);
                }*/

                $this->csv->saveData($filMerged, $dataMerged);

             }catch (\Exception $e){
                $logger->error(sprintf('Some problem while validate file ',$filMerged));
                $logger->error($e->getMessage());
             }

            $listFileMergedSuccess[] = $filMerged;
            $logger->info($filMerged.' is OK.');
        }

        return $listFileMergedSuccess;
    }

    /**
     * @param $dataOrigin
     * @param $dataUpdate
     * @return array
     */
    public function checkMergeFileDownload($filMerged,$dataUpdate){

        if($this->file->isExists($filMerged)){

            $dataOrigin = $this->csv->getData($filMerged);

            if(empty($dataOrigin)){
                return $dataUpdate;
            }

            if(empty($dataUpdate)){
                return $dataOrigin;
            }

            if(count($dataOrigin[0]) != count($dataUpdate[0])){
                return $dataUpdate;
            }

            if(count($dataOrigin) > 1 && count($dataUpdate) > 1){
                array_shift($dataUpdate);
                //in here we need to remove duplicate information related to profile_id
                $dataOrigin = $this->globalHelper->removeSubDuplicatedInfo($dataOrigin,$dataUpdate);

                return array_merge($dataOrigin,$dataUpdate);
            }
        }
        return $dataUpdate;
    }

    /**
     * @param $configQueue
     */
    public function collectLogFile($configQueue){
        $baseConsumerName = $configQueue['base_consumer_name'];
        $logger = $configQueue['logger'];

        $readFile         = $this->fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $listFileLog      = $readFile->read('var/log');

        $totalTime = 0;
        $minStartTime = 9999999999;
        $maxStartTime = 0;
        foreach($listFileLog as $fileLog){
            $fileLog = BP.'/'.$fileLog;
            $iPos = strpos($fileLog,$baseConsumerName);
            if($iPos !== false){
                $consumerChildName = substr($fileLog,$iPos-1,strlen($baseConsumerName)+1);
                $fileMergedLog = str_replace($baseConsumerName,"",$fileLog);
                $fileMergedLog = substr_replace($fileMergedLog, '', $iPos - 1, 2);
                $logger->info('Log of process '.$consumerChildName);
                $fileContentLog = $this->file->fileGetContents($fileLog);

                //get time log for each log file.
                if(strpos($fileContentLog,"\n") !== false){
                    $aFileContentLog = explode("\n",$fileContentLog);
                    $iCountLine = count($aFileContentLog);
                    if($iCountLine){
                        $firstLine = $aFileContentLog[0];
                        $lastLine = ($aFileContentLog[$iCountLine - 1] != '')?$aFileContentLog[$iCountLine - 1]:$aFileContentLog[$iCountLine - 2];

                        preg_match("/\[(.*?)\]/s", $firstLine, $matchFirstLine);
                        if(isset($matchFirstLine[1])){
                            $matchFirstLine[1] = str_replace(' JST','',$matchFirstLine[1]);
                            $timeStart = strtotime($matchFirstLine[1]);
                            if($minStartTime > $timeStart){
                                $minStartTime = $timeStart;
                            }
                        }

                        preg_match("/\[(.*?)\]/s", $lastLine, $matchLastLine);
                        if(isset($matchLastLine[1])){
                            $matchLastLine[1] = str_replace('JST','',$matchLastLine[1]);
                            $timeEnd= strtotime($matchLastLine[1]);
                            if($maxStartTime < $timeEnd){
                                $maxStartTime = $timeEnd;
                            }
                        }
                    }
                }

                try{
                    $this->file->filePutContents($fileMergedLog,$fileContentLog,FILE_APPEND);
                }catch(\Exception $e){
                    $logger->info($e->getMessage());
                }

                $this->file->deleteFile($fileLog);

            }
        }


        $spendTime = (int)$maxStartTime - (int)$minStartTime;
        $spendTime = ($spendTime > 0)?$spendTime:0;
        $totalTime += $spendTime;

        $logger->info('Spend time for generate of all process about '.$totalTime.'s');
    }

    /**
     * @param $filMerged
     * @param $dataMerged
     * @param $logger
     * @return bool
     */
    public function validateFileCsv($filMerged, $dataMerged, $logger)
    {
        if (empty($dataMerged)) {
            return false;
        }
        $iValid = true;

        $iColumn = count($dataMerged[0]);
        foreach ($dataMerged as $dataRecord) {
            if (count($dataRecord) != $iColumn) {
                $logger->error($filMerged . ' is invalid csv file');
                $iValid = false;
                break;
            }
        }
        return $iValid;
    }

    /**
     * @param $configQueue
     * @param $aListFileMerged
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function pushFileToSftp($configQueue, $aListFileMerged)
    {
        $pathLocal          = str_replace(BP.'/',"",$configQueue['path_local']);
        $pathLocalTmp       = $pathLocal . '_tmp';
        $logger = $configQueue['logger'];

        foreach ($aListFileMerged as $fileMerged) {
            if ($this->file->isExists($fileMerged)) {
                $fileMerged = str_replace(BP.'/',"",$fileMerged);
                $fileName = str_replace($pathLocalTmp,'',$fileMerged);
                $fileName = ltrim($fileName,'/');

                $sftpInfo = $this->getSftpInfo($fileName,$configQueue);

                //change file name.
                $oldFile = BP.'/'.$pathLocalTmp.'/'.$fileName;
                $timeStamp = $this->_timezone->date()->format('His');
                $newFile = str_replace(".csv",$timeStamp.".csv",$oldFile);

                if ($this->file->isExists($oldFile)) {
                    $this->file->rename($oldFile, $newFile);
                    $fileName = str_replace(".csv",$timeStamp.".csv",$fileName);
                }

                $this->globalHelper->MoveOneFileToFtp($pathLocalTmp, $pathLocal, $fileName,$sftpInfo['sftp'],$logger,$sftpInfo['sftp_report']);
            }
        }
    }

    /**
     * @param $fileName
     * @param $configQueue
     * @return array
     */
    public function getSftpInfo($fileName,$configQueue){

        $baseConsumerName = $configQueue['base_consumer_name'];
        $pathSftp           = $configQueue['path_sftp'];
        $pathSftpReport     = $configQueue['path_sftp_report'];

        $pathSftpReturn = '';
        $pathSftpReportReturn = '';
        if($baseConsumerName == 'NextOrderSubscriptionProfileHeader'){
            if(strpos($fileName,'subscription_profile_version') !== false){
                $pathSftpReturn = $pathSftp['version'];
                $pathSftpReportReturn  = $pathSftpReport['version'];
            }
            else{
                $pathSftpReturn       = $pathSftp['main'];
                $pathSftpReportReturn = $pathSftpReport['main'];
            }
        }
        else{
            $pathSftpReturn       = $pathSftp;
            $pathSftpReportReturn = $pathSftpReport;
        }

        return [
            'sftp' => $pathSftpReturn,
            'sftp_report' => $pathSftpReportReturn
        ];
    }

    /**
     * GetPathPhp
     *
     *
     * @return string
     */
    public function getPathPhp(){

        $sPhpPath = '';

        $aCommands = [
            'whereis php',
            'which php'
        ];
        try{
            foreach($aCommands as $sCommand){
                $sPhpPath = $this->shell->execute($sCommand);

                $aPhpPath = explode(" ",$sPhpPath);
                foreach($aPhpPath as $sPhpPathLine){
                    if(strpos($sPhpPathLine,"/bin/php") !== false){
                        $sPhpPath = $sPhpPathLine;
                        break;
                    }
                }

                return $sPhpPath;
            }
        }catch (\Exception $e){
            $this->_log->critical($e->getMessage());
        }

    }

    /**
     * @return mixed
     */
    public function getMaxConsumer(){

        return $this->scopeConfig->getValue('di_data_export_setup/data_cron_subscription_next_delivery/number_consumer_exported',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

}
