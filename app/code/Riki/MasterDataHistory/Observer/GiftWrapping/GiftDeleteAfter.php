<?php
namespace Riki\MasterDataHistory\Observer\GiftWrapping;
use Magento\Framework\Event\ObserverInterface;
use Riki\MasterDataHistory\Observer\MasterDataHistoryObserver;
class GiftDeleteAfter extends MasterDataHistoryObserver implements ObserverInterface
{
    const DEFAULT_PATH_FOLDER = 'var/gift';
    const CONFIG_PATH_FOLDER = 'masterdatahistory/common/datahistorygiftwrapping';
    /**
     * CategoryDeleteAfter constructor.
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\File\Csv $csv
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\File\Csv $csv,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request
    )
    {
        parent::__construct($directoryList, $file, $csv,$timezone,$authSession,$scopeConfig,$request);
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!$this->_request->getParam('wrapping_ids')){
            if($configFolder = $this->getConfig(self::CONFIG_PATH_FOLDER)){
                $dirFile = $configFolder;
            }else{
                $dirFile = self::DEFAULT_PATH_FOLDER;
            }
            $dirLocal = $this->createFileLocal($dirFile);
            if($dirLocal){
                $data = [
                    'user' => $this->getCurrentUserAdmin(),
                    'time' => $this->_datetime->date()->format('Y-m-d H:i:s'),
                    'action' => 'Delete'
                ];
                $header[] =  array_keys($data);
                $dataExport[] = array_values($data);
                $prepareData = array_merge($header,$dataExport);
                $nameCsv = $this->_datetime->date()->getTimestamp() . '-gift.csv';
                $this->_csv->saveData($dirLocal.DS.$nameCsv,$prepareData);
            }
        }

    }
}