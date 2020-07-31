<?php
namespace Riki\MasterDataHistory\Observer\Shosha;

use Magento\Framework\Event\ObserverInterface;
use Riki\MasterDataHistory\Observer\MasterDataHistoryObserver;
class ShoshaSaveAfter extends MasterDataHistoryObserver implements ObserverInterface
{
    const DEFAULT_PATH_FOLDER = 'var/history-shosha';
    const CONFIG_PATH_FOLDER = 'masterdatahistory/common/datahistoryshosha';
    /**
     * @var \Riki\Customer\Model\Shosha
     */
    protected $_shosha;

    /**
     * ShoshaSaveAfter constructor.
     * @param \Riki\Customer\Model\Shosha $shosha
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\File\Csv $csv
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
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
        \Magento\Framework\App\Request\Http $request
    )
    {
        $this->_shosha = $shosha;
        parent::__construct($directoryList, $file, $csv,$timezone,$authSession,$scopeConfig,$request);
    }

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
            $prepareData = $productData = [];
            $shosha = $observer->getShosha();
            //add more column Data
            $addMoreData = [
                'user' => $this->getCurrentUserAdmin(),
                'time' => $this->_datetime->date()->format('Y-m-d H:i:s'),
                'action' => $this->getActionName()
            ];

            $headerShosha = $this->_prepareHeader();

            foreach ($headerShosha as $attribute) {
                if (!is_array($shosha->getData($attribute))) {
                    $shoshaData[] = $shosha->getData($attribute);
                } else {
                    $shoshaData[] = '';
                }
            }
            $header[] = array_merge($headerShosha, array_keys($addMoreData));
            $dataExport[] = array_merge($shoshaData,array_values($addMoreData));
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