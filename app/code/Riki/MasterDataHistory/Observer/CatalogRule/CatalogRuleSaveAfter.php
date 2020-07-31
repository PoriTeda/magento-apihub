<?php
namespace Riki\MasterDataHistory\Observer\CatalogRule;

use Magento\Framework\Event\ObserverInterface;
use Riki\MasterDataHistory\Observer\MasterDataHistoryObserver;
class CatalogRuleSaveAfter extends MasterDataHistoryObserver implements ObserverInterface
{
    const DEFAULT_PATH_FOLDER = 'var//history-promotions';
    const CONFIG_PATH_FOLDER = 'masterdatahistory/common/datahistorycatalogpricerules';
    /**
     * @var \Magento\CatalogRule\Model\Rule
     */
    protected $_catalogRule;

    /**
     * CatalogRuleSaveAfter constructor.
     * @param \Magento\CatalogRule\Model\Rule $rule
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\File\Csv $csv
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Magento\CatalogRule\Model\Rule $rule,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\File\Csv $csv,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request
    )
    {
        $this->_catalogRule = $rule;
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
            $prepareData = $productData = [];
            $rule = $observer->getRule();
            //add more column Data
            $addMoreData = [
                'user' => $this->getCurrentUserAdmin(),
                'time' => $this->_datetime->date()->format('Y-m-d H:i:s'),
                'action' => $this->getActionNameRule()
            ];
            $headerRule = $this->_prepareHeader();
            foreach ($headerRule as $attribute) {
                if (!is_array($rule->getData($attribute))) {
                    $ruleData[] = $rule->getData($attribute);
                } else {
                    $ruleData[] = '';
                }
            }
            $header[] = array_merge($headerRule, array_keys($addMoreData));
            $dataExport[] = array_merge($ruleData,array_values($addMoreData));
            $prepareData = array_merge($header,$dataExport);
            $nameCsv = $this->_datetime->date()->getTimestamp() . '-catalog-rules.csv';
            $this->_csv->saveData($dirLocal.DS.$nameCsv,$prepareData);
        }
    }
    /**
     * @return array
     */
    private function _prepareHeader(){
        $resource = $this->_catalogRule->getResource();
        $connection = $resource->getConnection();
        $describle = $connection->describeTable($resource->getMainTable());
        return array_keys($describle);
    }

    /**
     * @return string
     */
    private function getActionNameRule(){
        if($this->_request->getParam('rule_id')){
            return 'Update';
        }else{
            return 'Add';
        }
    }
}