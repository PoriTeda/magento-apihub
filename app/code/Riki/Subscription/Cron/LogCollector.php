<?php


namespace Riki\Subscription\Cron;


class LogCollector
{
    /**
     * @var \Riki\Subscription\Logger\LoggerOrder
     */
    private $loggerCreateOrder;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    private $file;
    /**
     * @var \Magento\Framework\Filesystem
     */
    private $fileSystem;

    /**
     * CollectLog constructor.
     * @param \Riki\Subscription\Logger\LoggerOrder $loggerCreateOrder
     */
    public function __construct(
        \Riki\Subscription\Logger\LoggerOrder $loggerCreateOrder,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\Filesystem $fileSystem
    ) {
        $this->loggerCreateOrder = $loggerCreateOrder;
        $this->file = $file;
        $this->fileSystem = $fileSystem;
    }

    public function execute() {
        /*Collect for subscription order*/
        $configQueue['base_consumer_name'] = 'GenerateOrderSubscription';
        $configQueue['logger'] = $this->loggerCreateOrder;

        try {
            $this->collectLogFile($configQueue);
        }catch (\Exception $e){
            $this->loggerCreateOrder->error($e->getMessage());
        }
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
                /*
                 * Short term: disable function deleteFile
                 * After go-live: will enable this function
                 */
//                $this->file->deleteFile($fileLog);

            }
        }


        $spendTime = (int)$maxStartTime - (int)$minStartTime;
        $spendTime = ($spendTime > 0)?$spendTime:0;
        $totalTime += $spendTime;

        $logger->info('Spend time for generate of all process about '.$totalTime.'s');
    }

}