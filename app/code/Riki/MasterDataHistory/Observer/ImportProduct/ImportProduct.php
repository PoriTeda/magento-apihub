<?php
namespace Riki\MasterDataHistory\Observer\ImportProduct;

use Magento\Framework\Event\ObserverInterface;
use Riki\MasterDataHistory\Observer\MasterDataHistoryObserver;
class ImportProduct extends MasterDataHistoryObserver implements ObserverInterface
{
    const DEFAULT_PATH_FOLDER = 'var/history-product';
    const CONFIG_PATH_FOLDER = 'masterdatahistory/common/datahistoryproduct';
    protected $_header = [];
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_product;

    /**
     * ImportProduct constructor.
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\File\Csv $csv
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Magento\Catalog\Model\Product $product,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\File\Csv $csv,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request
    )
    {
        $this->_product = $product;
        parent::__construct($directoryList, $file, $csv,$timezone,$authSession,$scopeConfig,$request);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $adapter = $observer->getAdapter();
        if($adapter->getBehavior() == \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE  ||
            $adapter->getFlagReplace()
        ){
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
                $nameCsv = $this->_datetime->date()->getTimestamp() + 1 . '-product.csv';
                $this->_csv->saveData($dirLocal.DS.$nameCsv,$prepareData);
            }
        }
    }
}