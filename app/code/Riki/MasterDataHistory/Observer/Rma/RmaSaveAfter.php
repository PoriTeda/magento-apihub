<?php
namespace Riki\MasterDataHistory\Observer\Rma;

use Magento\Framework\Event\ObserverInterface;
use Riki\MasterDataHistory\Observer\MasterDataHistoryObserver;
class RmaSaveAfter extends MasterDataHistoryObserver implements ObserverInterface
{
    const DEFAULT_PATH_FOLDER = 'var/history-return';
    const CONFIG_PATH_FOLDER = 'masterdatahistory/common/datahistoryrma';
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_rma;
    /**
     * @var \Magento\Rma\Model\Item
     */
    protected $_itemrma;
    /**
     * @var \Magento\Rma\Model\ResourceModel\Item\CollectionFactory
     */
    protected $_collectionItemFactory;
    /**
     * @var \Magento\Rma\Model\ResourceModel\Rma\CollectionFactory
     */
    protected $_collectionRmaFactory;

    protected $headFileNameMassAction;

    protected $csvHeader;

    protected $csvHeaderItem;

    /**
     * RmaSaveAfter constructor.
     * @param \Magento\Rma\Model\RmaFactory $rma
     * @param \Magento\Rma\Model\ResourceModel\Rma\CollectionFactory $rmaCollectionFactory
     * @param \Magento\Rma\Model\Item $item
     * @param \Magento\Rma\Model\ResourceModel\Item\CollectionFactory $collectionFactory
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\File\Csv $csv
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Magento\Rma\Model\RmaFactory $rma,
        \Magento\Rma\Model\ResourceModel\Rma\CollectionFactory $rmaCollectionFactory,
        \Magento\Rma\Model\Item $item,
        \Magento\Rma\Model\ResourceModel\Item\CollectionFactory $collectionFactory,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\File\Csv $csv,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request
    )
    {
        $this->_rma = $rma;
        $this->_itemrma = $item;
        $this->_collectionItemFactory = $collectionFactory;
        $this->_collectionRmaFactory = $rmaCollectionFactory;
        parent::__construct($directoryList, $file, $csv,$timezone,$authSession,$scopeConfig,$request);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //if this is action mass action change status
        if($configFolder = $this->getConfig(self::CONFIG_PATH_FOLDER)){
            $dirFile = $configFolder;
        }else{
            $dirFile = self::DEFAULT_PATH_FOLDER;
        }
        $dirLocal = $this->createFileLocal($dirFile);
        if($dirLocal){
            /**
             * @var \Magento\Rma\Model\Rma $rma
             */
            $rma = $observer->getRma();

            $this->saveHeader($rma, $dirLocal);

            /**
             * create list item Rma
             */
            $timeStamp = $this->_datetime->date()->getTimestamp();
            $this->createCsvRmaItem($rma, $dirLocal, $timeStamp ,$this->getActionNameRma() );
        }

    }

    /**
     * @param $rma
     * @param $dirLocal
     * @return $this
     */
    protected function saveHeader($rma, $dirLocal)
    {
        $timeStamp = $this->_datetime->date()->getTimestamp();

        $headerRma = $this->_prepareHeader();
        $addMoreData = $this->_prepareAddMoreColumn();

        $dataRma = [];

        foreach($headerRma as $column){
            $dataRma[] = $rma->getData($column);
        }

        $dataExport = array_merge($dataRma, array_values($addMoreData));

        $entityIds = $this->_request->getParam('entity_ids');

        if (is_array($entityIds) && !is_null($entityIds)) { //mass action

            $savedData = null;

            if (is_null($this->headFileNameMassAction)) {
                $this->headFileNameMassAction = $timeStamp . '-return-header.csv';

                $savedData = array_merge($headerRma, array_keys($addMoreData));
            }

            $fp = fopen($dirLocal . DS . $this->headFileNameMassAction, 'a');

            if ($savedData) {
                fputcsv($fp, $savedData);
            }

            fputcsv($fp, $dataExport);

            fclose($fp);

        } else {
            $nameCsv = $timeStamp . '-return-header.csv';

            $prepareData = [array_merge($headerRma , array_keys($addMoreData)), $dataExport];

            $this->_csv->saveData($dirLocal.DS.$nameCsv, $prepareData);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function _prepareHeader(){

        if (is_null($this->csvHeader)) {
            $resource = $this->_rma->create()->getResource();
            $connection = $resource->getConnection();
            $describle = $connection->describeTable($resource->getMainTable());
            $this->csvHeader = array_keys($describle);
        }

        return $this->csvHeader;
    }

    public function getActionNameRma(){
        if($this->_request->getParam('rma_id')){
            return 'Update';
        }else{
            return 'Add';
        }
    }

    /**
     * @return array
     */
    public function _prepareHeaderItemRma(){

        if (is_null($this->csvHeaderItem)) {
            $itemRmaAttribute = [];
            foreach ($this->_itemrma->getAttributes() as $attribute) {
                $itemRmaAttribute[] = $attribute->getAttributeCode();
            }
            $this->csvHeaderItem = $itemRmaAttribute;
        }

        return $this->csvHeaderItem;
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @param $dirLocal
     */
    public function createCsvRmaItem(\Magento\Rma\Model\Rma $rma, $dirLocal){

        $addMoreData = $this->_prepareAddMoreColumn('Update');
        $collection = $this->_collectionItemFactory->create();
        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter('rma_entity_id',$rma->getId());
        if($collection->getSize()){
            $headerItem = $this->_prepareHeaderItemRma();
            foreach ($collection->getItems() as $item){
                $itemData = [];
                foreach ($headerItem as $attributeCode){
                    if (!is_array($item->getData($attributeCode))) {
                        $itemData[] = $item->getData($attributeCode);
                    } else {
                        $itemData[] = '';
                    }
                }
                $dataExport[] = array_merge($itemData,array_values($addMoreData));
            }
            $header[] = array_merge($headerItem, array_keys($addMoreData));
            $prepareData = array_merge($header,$dataExport);
            $nameCsv = $this->_datetime->date()->getTimestamp() . '-return-detail-'.$rma->getId().'.csv';
            $this->_csv->saveData($dirLocal.DS.$nameCsv,$prepareData);
        }
    }

    public function createMultiRmaCsv($rmaIds = [],$dirLocal){
        $collectionRma = $this->_collectionRmaFactory->create();
        $collectionRma->addFieldToFilter('entity_id',['in' => $rmaIds]);
        if($collectionRma->getSize()){
            $headerRma = $this->_prepareHeader();
            $addMoreData = $this->_prepareAddMoreColumn();
            $timeStamp = $this->_datetime->date()->getTimestamp();
            foreach($collectionRma->getItems() as $rma){
                $dataRma = [];
                foreach($headerRma as $column){
                    $dataRma[] = $rma->getData($column);
                }
                $dataExport[] = array_merge($dataRma,array_values($addMoreData));
                /**
                 * create detail item rma
                 */
                $this->createCsvRmaItem($rma, $dirLocal, $timeStamp , 'Update');
            }

            $header[] = array_merge($headerRma , array_keys($addMoreData));

            $prepareData = array_merge($header,$dataExport);



            $nameCsv = $timeStamp . '-return-header.csv';

            $this->_csv->saveData($dirLocal.DS.$nameCsv,$prepareData);
        }
        return;
    }

    private function _prepareAddMoreColumn($action = ''){
        //add more column Data
        return [
            'user' => $this->getCurrentUserAdmin(),
            'time' => $this->_datetime->date()->format('Y-m-d H:i:s'),
            'action' => (($action) ? $action : $this->getActionNameRma())
        ];
    }
}