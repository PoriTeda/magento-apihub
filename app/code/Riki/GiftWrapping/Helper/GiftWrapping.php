<?php
namespace Riki\GiftWrapping\Helper;

use Riki\MasterDataHistory\Observer\MasterDataHistoryObserver;

/**
 * Class GiftWrapping
 * @package Riki\GiftWrapping\Helper
 */
class GiftWrapping extends MasterDataHistoryObserver
{
    const DEFAULT_PATH_FOLDER = 'var/gift';
    const CONFIG_PATH_FOLDER = 'masterdatahistory/common/datahistorygiftwrapping';
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_wrapping;
    /**
     * @var \Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection
     */
    protected $_wrappingCollection;

    /**
     * GiftWrapping constructor.
     * @param \Magento\GiftWrapping\Model\Wrapping $wrapping
     * @param \Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection $wrappingCollection
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\File\Csv $csv
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Magento\GiftWrapping\Model\Wrapping $wrapping,
        \Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection $wrappingCollection,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\File\Csv $csv,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request
    )
    {
        $this->_wrapping = $wrapping;
        $this->_wrappingCollection = $wrappingCollection;
        parent::__construct($directoryList, $file, $csv,$timezone,$authSession,$scopeConfig,$request);
    }

    /**
     * @param \Magento\GiftWrapping\Model\Wrapping $giftWrapping
     */
    public function exportCsv($giftWrapping)
    {
        if($giftWrapping instanceof \Magento\GiftWrapping\Model\Wrapping){
            if($configFolder = $this->getConfig(self::CONFIG_PATH_FOLDER)){
                $dirFile = $configFolder;
            }else{
                $dirFile = self::DEFAULT_PATH_FOLDER;
            }

            $dirLocal = $this->createFileLocal($dirFile);

            if($dirLocal){
                //add more column Data
                $addMoreData = [
                    'user' => $this->getCurrentUserAdmin(),
                    'time' => $this->_datetime->date()->format('Y-m-d H:i:s'),
                    'action' => $this->getActionNameGift()
                ];

                $headerColumns = $this->_prepareHeader($giftWrapping);

                foreach($headerColumns as $column){
                    $giftWrappingData[] =  $giftWrapping->getData($column);
                }

                $header[] = array_merge($headerColumns, array_keys($addMoreData));

                $dataExport[] = array_merge($giftWrappingData,array_values($addMoreData));

                $prepareData = array_merge($header,$dataExport);

                $nameCsv = $this->_datetime->date()->getTimestamp() . '-gift.csv';

                $this->_csv->saveData($dirLocal.DS.$nameCsv,$prepareData);
            }
        }

    }

    /**
     * get all column table magento_giftwrapping
     * @param \Magento\GiftWrapping\Model\Wrapping $giftWrapping
     */
    protected function _prepareHeader(\Magento\GiftWrapping\Model\Wrapping $giftWrapping){
        $resource = $giftWrapping->getResource();
        $connection = $resource->getConnection();
        $columnTables = $connection->describeTable($resource->getMainTable());
        return array_keys($columnTables);
    }

    protected function getActionNameGift(){
        if($this->_request->getParam('id')){
            return 'Update';
        }else{
            return 'Add';
        }
    }

    public function createCsvDeleteAction(){

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

    public function createCsvChangeStatusAction($wrappingIds = []){

        if($configFolder = $this->getConfig(self::CONFIG_PATH_FOLDER)){
            $dirFile = $configFolder;
        }else{
            $dirFile = self::DEFAULT_PATH_FOLDER;
        }
        $dirLocal = $this->createFileLocal($dirFile);
        if($dirLocal){
            $prepareData = [];

            //add more column Data
            $addMoreData = [
                'user' => $this->getCurrentUserAdmin(),
                'time' => $this->_datetime->date()->format('Y-m-d H:i:s'),
                'action' => 'Update'
            ];

            $headerTable = $this->_prepareHeader($this->_wrapping);
            $wrappingCollection = $this->_wrappingCollection;
            $wrappingCollection->addFieldToFilter('wrapping_id',['in' => $wrappingIds]);
            foreach($wrappingCollection->getItems() as $wrapping){
                $wrappingData = [];
                foreach ($headerTable as $attribute) {
                    if (!is_array($wrapping->getData($attribute))) {
                        $wrappingData[] = $wrapping->getData($attribute);
                    } else {
                        $wrappingData[] = '';
                    }
                }
                $dataExport[] = array_merge($wrappingData,array_values($addMoreData));
            }

            $header[] = array_merge($headerTable, array_keys($addMoreData));

            $prepareData = array_merge($header,$dataExport);

            $nameCsv = $this->_datetime->date()->getTimestamp() . '-gift.csv';

            $this->_csv->saveData($dirLocal.DS.$nameCsv,$prepareData);
        }
    }

    /**
     * Check giftcode exist
     *
     * @param $giftCode
     * @return bool
     */
    public function checkGiftCode($giftCode){
        $wrappingCollection = $this->_wrappingCollection;
        $wrappingCollection->addFieldToFilter('gift_code',['eq' => $giftCode]);
        if($wrappingCollection->getSize()){
            return true;
        }else{
            return false;
        }
    }

}