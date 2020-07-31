<?php
namespace Riki\MasterDataHistory\Observer\Shosha;

use Magento\Framework\Event\ObserverInterface;
use Riki\MasterDataHistory\Observer\MasterDataHistoryObserver;
class ShoshaImportAfter extends MasterDataHistoryObserver implements ObserverInterface
{
    const DEFAULT_PATH_FOLDER = 'var/history-shosha';
    const CONFIG_PATH_FOLDER = 'masterdatahistory/common/datahistoryshosha';
    protected $_header = [];
    /**
     * @var \Riki\Customer\Model\Shosha
     */
    protected $_shosha;
    /**
     * @var \Riki\Customer\Model\ResourceModel\Shosha\CollectionFactory
     */
    protected $_shoshaCollectionFactory;

    /**
     * ShoshaImportAfter constructor.
     * @param \Riki\Customer\Model\Shosha $shosha
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\File\Csv $csv
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Riki\Customer\Model\ResourceModel\Shosha\CollectionFactory $shoshaCollectionFactory
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Riki\Customer\Model\Shosha $shosha,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\File\Csv $csv,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Customer\Model\ResourceModel\Shosha\CollectionFactory $shoshaCollectionFactory,
        \Magento\Framework\App\Request\Http $request
    )
    {
        $this->_shosha = $shosha;
        $this->_shoshaCollectionFactory = $shoshaCollectionFactory;
        parent::__construct($directoryList, $file, $csv,$timezone,$authSession,$scopeConfig,$request);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $shoshaIds = $observer->getIdshosha();

        if($configFolder = $this->getConfig(self::CONFIG_PATH_FOLDER)){
            $dirFile = $configFolder;
        }else{
            $dirFile = self::DEFAULT_PATH_FOLDER;
        }
        $dirLocal = $this->createFileLocal($dirFile);
        if($dirLocal && $shoshaIds){
            $headerShosha = $this->_prepareHeader();
            $shoshaCollect = $this->_shoshaCollectionFactory->create();
            $shoshaCollect->addFieldToFilter('id',['in' => array_keys($shoshaIds)]);
            foreach($shoshaCollect->getItems() as $shosha){
                $shoshaData = $addMoreData = [];
                foreach ($headerShosha as $attribute) {
                    if (!is_array($shosha->getData($attribute))) {
                        $shoshaData[] = $shosha->getData($attribute);
                    } else {
                        $shoshaData[] = '';
                    }
                }
                //add more column Data
                $addMoreData = [
                    'user' => $this->getCurrentUserAdmin(),
                    'time' => $this->_datetime->date()->format('Y-m-d H:i:s'),
                    'action' => $shoshaIds[$shosha->getId()]
                ];
                $dataExport[] = array_merge($shoshaData,array_values($addMoreData));
            }
            $header[] = array_merge($headerShosha, array_keys($addMoreData));
            $prepareData = array_merge($header,$dataExport);
            $nameCsv = $this->_datetime->date()->getTimestamp() . '-shosha.csv';
            $this->_csv->saveData($dirLocal.DS.$nameCsv,$prepareData);
        }
    }
    private function _prepareHeader(){
        $resource = $this->_shosha->getResource();
        $connection = $resource->getConnection();
        $describle = $connection->describeTable($resource->getMainTable());
        return array_keys($describle);
    }
}