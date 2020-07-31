<?php
/**
 * Riki Basic Setup
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\BasicSetup\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\BasicSetup\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Class WebsiteSetup
 *
 * @category  RIKI
 * @package   Riki\BasicSetup\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class WebsiteSetup
{
    /**
     * @var \Magento\Store\Model\Store
     */
    protected $_storeManager;
    /**
     * @var \Magento\Store\Model\Website
     */
    protected $_websiteManager;
    /**
     * @var \Magento\Store\Model\Group
     */
    protected $_storeGroupManager;
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $_configManager;
    /**
     * @var \Magento\Store\Model\ResourceModel\Website\Collection
     */
    protected $_websiteCollection ;
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Type\CollectionFactory
     */
    protected $_eavEntityType;
    /**
     * @var \Magento\SalesSequence\Model\ResourceModel\Profile
     */
    protected $_sequenceProfile;
    /**
     * @var \Magento\SalesSequence\Model\ResourceModel\Meta
     */
    protected $_sequenceMeta;

    protected $_configWriter;
    /**
     * WebsiteSetup constructor.
     * @param \Magento\Store\Model\Store $storeManager
     * @param \Magento\Store\Model\Website $webSiteManager
     * @param \Magento\Store\Model\Group $groupManager
     * @param \Magento\Config\Model\ResourceModel\Config $configManager
     * @param \Magento\Store\Model\ResourceModel\Website\Collection $websiteCollection
     * @param \Magento\SalesSequence\Model\ResourceModel\Profile $sequenceProfile
     * @param \Magento\SalesSequence\Model\ResourceModel\Meta $sequenceMeta
     * @param \Magento\Eav\Model\ResourceModel\Entity\Type\CollectionFactory $eavEntityTypeCollection
     */
    public function __construct(
        \Magento\Store\Model\Store $storeManager,
        \Magento\Store\Model\Website $webSiteManager,
        \Magento\Store\Model\Group $groupManager,
        \Magento\Config\Model\ResourceModel\Config $configManager,
        \Magento\Store\Model\ResourceModel\Website\Collection $websiteCollection,
        \Magento\SalesSequence\Model\ResourceModel\Profile $sequenceProfile,
        \Magento\SalesSequence\Model\ResourceModel\Meta $sequenceMeta,
        \Magento\Eav\Model\ResourceModel\Entity\Type\CollectionFactory $eavEntityTypeCollection,
        WriterInterface $writer
    ) {
    
        $this->_storeManager = $storeManager;
        $this->_storeGroupManager = $groupManager;
        $this->_websiteManager = $webSiteManager;
        $this->_configManager = $configManager;
        $this->_websiteCollection = $websiteCollection;
        $this->_eavEntityType = $eavEntityTypeCollection;
        $this->_sequenceMeta = $sequenceMeta;
        $this->_sequenceProfile = $sequenceProfile;
        $this->_configWriter = $writer;
    }


    /**
     *  Check exist websites
     *
     * @param $websiteData
     * @return array
     * @throws \Exception
     */

    public function checkExistsWebsite($websiteData)
    {
        $rootCategoryId = $this->_storeGroupManager->getRootCategoryId();
        $checkedWebsites = [];
        //remove unnecessary website and store
        $websiteCollection = $this->_websiteCollection
            ->addFieldToFilter('website_id', ['neq'=> 0])
            ->load();
        if ($websiteCollection->getSize()) {
            foreach ($websiteCollection as $_website) {
                $existWebsite = $this->compareWebsites($websiteData, $_website->getName(), $_website->getCode());
                if ($existWebsite) {
                    $checkedWebsites[] = $_website->getCode();

                    //update website info
                    $_website->setName($existWebsite['name']);
                    $_website->setCode($existWebsite['code']);
                    try {
                        $_website->save();
                    } catch (\Exception $e) {
                        throw $e;
                    }
                    //store configuration for website

                    $scope = 'websites';
                    $scopeId = $_website->getWebsiteId();
                    if ($scopeId > 0) {
                        $wkey = 'currency/options/base';
                        $this->_configManager->saveConfig($wkey, $existWebsite[$wkey], $scope, $scopeId);

                        $wkey = 'web/unsecure/base_url';
                        if ($existWebsite[$wkey]) {
                            $this->_configManager->saveConfig($wkey, $existWebsite[$wkey], $scope, $scopeId);
                        }
                        $wkey = 'shipping/origin/country_id';
                        $this->_configManager->saveConfig($wkey, $existWebsite[$wkey], $scope, $scopeId);
                    }
                    //update store info
                    $_stores = $_website->getGroups();
                    if ($_stores) {
                        $this->_processStores($_stores, $existWebsite);
                    } else { //create store

                        $this->_storeGroupManager->setWebsiteId($scopeId);
                        $this->_storeGroupManager->setName($existWebsite['store']);
                        $this->_storeGroupManager->setRootCategoryId($rootCategoryId);
                        $this->_storeGroupManager->save();
                        if ($this->_storeGroupManager->getGroupId()) {
                        //store view
                            $this->_storeManager->setName($existWebsite['store_view_name']);
                            $this->_storeManager->setCode($existWebsite['store_view_code']);
                            $this->_storeManager->setGroupId($this->_storeGroupManager->getGroupId());
                            $this->_storeManager->setIsActive(1);
                            $this->_storeManager->save();
                        }
                    }
                } else {
                    try {
                        if (!$_website->getIsDefault()) {
                            $_website->delete();
                        }
                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
            }
        }
        return $checkedWebsites;
    }

    /**
     * Process Store
     *
     * @param   $_stores
     * @param   $existWebsite
     * @throws  \Exception
     */
    private function _processStores($_stores, $existWebsite)
    {
        foreach ($_stores as $_store) {
            if (strtolower($_store->getName()) == strtolower($existWebsite['store'])) {
                try {
                    $_store->setName($existWebsite['store'])->save();
                } catch (\Exception $e) {
                    throw $e;
                }
                //check store view
                $_storesView = $_store->getStores();
                if ($_storesView) {
                    foreach ($_storesView as $_storeView) {
                        if (strtolower($_storeView->getName()) == strtolower($existWebsite['store_view_name'])
                            || strtolower($_storeView->getCode()) == strtolower($existWebsite['store_view_code'])
                        ) {
                            //update info
                            if ($_storeView) {
                                try {
                                    $_storeView->setName($existWebsite['store_view_name']);
                                    $_storeView->setCode($existWebsite['store_view_code']);
                                    $_storeView->setIsActive(1);
                                    $_storeView->save();
                                } catch (\Exception $e) {
                                    throw $e;
                                }
                            }
                        } else {
                            try {
                                if ($_storeView->getStoreId()) {
                                    $_storeView->delete();
                                }
                            } catch (\Exception $e) {
                                throw $e;
                            }
                        }
                    }
                } else {
                    //store view
                    $this->_storeManager->setName($existWebsite['store_view_name']);
                    $this->_storeManager->setCode($existWebsite['store_view_code']);
                    $this->_storeManager->setGroupId($_store->getGroupId());
                    $this->_storeManager->setIsActive(1);
                    $this->_storeManager->save();
                }
            } else {
                try {
                    if ($_store->getGroupId()>0) {
                        $_store->delete();
                    }
                } catch (\Exception $e) {
                    throw $e;
                }
            }
        }
    }
    /**
     * @throws \Exception
     */
    public function setupWebsites()
    {
        $websiteData = [
            'ec' => [
                'name' => 'EC Site',
                'code' => 'ec',
                'currency/options/base'=>'JPY',
                //'web/unsecure/base_url'=> 'http://shop.nestle.jp/ec/',
                'web/unsecure/base_url'=> '',
                'shipping/origin/country_id' => 'JP',
                'store'=>'EC Store',
                'store_view_code' => 'ec',
                'store_view_name' => 'EC View'
            ],
            'employee' => [
                'name' => 'Employee Site',
                'code' => 'employee',
                'currency/options/base'=>'JPY',
                'web/unsecure/base_url'=> 'http://shop.nestle.jp/employee/',
                'shipping/origin/country_id' => 'JP',
                'store' => 'Employee Store',
                'store_view_code' => 'employee',
                'store_view_name' => 'Employee View'
            ],
            'cnc'=> [
                'name' => 'CNC Site',
                'code' => 'cnc',
                'currency/options/base'=>'JPY',
                'web/unsecure/base_url'=> 'http://shop.nestle.jp/cnc/',
                'shipping/origin/country_id' => 'JP',
                'store' => 'CNC Store',
                'store_view_code' => 'cnc',
                'store_view_name' => 'CNC View'
            ],
            'cis' => [
                'name' => 'CIS Site',
                'code' => 'cis',
                'currency/options/base'=>'JPY',
                'web/unsecure/base_url'=> 'http://shop.nestle.jp/cis/',
                'shipping/origin/country_id' => 'JP',
                'store' => 'CIS Store',
                'store_view_code' => 'cis',
                'store_view_name' => 'CIS View'
            ],
            'milan' => [
                'name' => 'Milano Site',
                'code' => 'milan',
                'currency/options/base'=>'JPY',
                'web/unsecure/base_url'=> 'http://shop.nestle.jp/milan/',
                'shipping/origin/country_id' => 'JP',
                'store' => 'Milano Store',
                'store_view_code' => 'milan',
                'store_view_name' => 'Milano View'
            ],
            'alegria' => [
                'name' => 'Alegria Site',
                'code' => 'alegria',
                'currency/options/base'=>'JPY',
                'web/unsecure/base_url'=> 'http://shop.nestle.jp/alegria/',
                'shipping/origin/country_id' => 'JP',
                'store' => 'Alegria Store',
                'store_view_code' => 'alegria',
                'store_view_name' => 'Alegria View'
            ],
        ];

        //update website
        $remainWebsites = $this->checkExistsWebsite($websiteData);
        foreach ($websiteData as $_webcode => $_webdata) {
            if (!in_array($_webcode, $remainWebsites)) {
                try {
                    //create Website
                    $website = $this->_websiteManager->load($_webdata['code'], 'code');
                    if (!$website->getWebsiteId()) {
                        $this->_websiteManager->setName($_webdata['name']);
                        $this->_websiteManager->setCode($_webdata['code']);
                        $this->_websiteManager->save();
                        $website = $this->_websiteManager->load($_webdata['code'], 'code');
                    }
                    $rootCategoryId = $this->_storeGroupManager->getRootCategoryId();
                    if ($website->getWebsiteId()) {
                        //store configuration for website
                        $scope = 'websites';
                        $scopeId = $website->getWebsiteId();
                        if ($scopeId > 0) {
                            $wkey = 'currency/options/base';
                            $this->_configManager->saveConfig($wkey, $_webdata[$wkey], $scope, $scopeId);

                            $wkey = 'web/unsecure/base_url';
                            if ($_webdata[$wkey]) {
                                $this->_configManager->saveConfig($wkey, $_webdata[$wkey], $scope, $scopeId);
                            }
                            $wkey = 'shipping/origin/country_id';
                            $this->_configManager->saveConfig($wkey, $_webdata[$wkey], $scope, $scopeId);
                        }
                        // Create store group
                        $storeGroup = $this->_storeGroupManager->load($_webdata['store'], 'name');
                        $this->_storeGroupManager->setId($storeGroup->getGroupId());
                        $this->_storeGroupManager->setWebsiteId($scopeId);
                        $this->_storeGroupManager->setName($_webdata['store']);
                        $this->_storeGroupManager->setRootCategoryId($rootCategoryId);
                        $this->_storeGroupManager->save();
                        if ($storeGroup->getGroupId()) {
                        //store view
                            $storeView = $this->_storeManager->load($_webdata['store_view_code'], 'code');
                            if ($storeView->getStoreId()) {
                                $this->_storeManager->setStoreId($storeView->getStoreId());
                            }
                            $this->_storeManager->setName($_webdata['store_view_name']);
                            $this->_storeManager->setCode($_webdata['store_view_code']);
                            $this->_storeManager->setGroupId($storeGroup->getGroupId());
                            $this->_storeManager->setIsActive(1);
                            $this->_storeManager->save();
                        }
                    }
                } catch (\Exception $e) {
                    throw $e;
                }
            }
        }
        //basic configurations setup
        $globalData = [
            'general/country/default' => 'JP',
            'general/locale/timezone' => 'Asia/Tokyo',
            'general/locale/code' => 'ja_JP',
            'currency/options/base' => 'JPY',
            'currency/options/default' => 'JPY',
            'currency/options/allow' => 'JPY',
            'trans_email/ident_general/name' => 'Support',
            'trans_email/ident_general/email' => 'support@nestle.co.jp',
            'trans_email/ident_sales/name' => 'Support',
            'trans_email/ident_sales/email' => 'support@nestle.co.jp',
            'trans_email/ident_support/name' => 'Suport',
            'trans_email/ident_support/email' => 'support@nestle.co.jp',
            'general/store_information/name'=>'Nestlé Online Shop',
            'general/store_information/phone'=>'Not used',
            'general/store_information/hours'=>'Not used',
            'general/store_information/country_id'=>'JP',
            'general/store_information/region_id'=>'672',
            'general/store_information/postcode'=>'651-0087',
            'general/store_information/city'=>'Kobe 神戸市',
            'general/store_information/street_line1'=>'Chuo-ku, Goko-dori 7-1-15',
            'general/store_information/street_line2'=>'Not used',
            'general/store_information/merchant_vat_number'=>'Not used',
        ];
        $globalScope = 'default';
        $globalScopeId = 0;

        foreach ($globalData as $gkey => $gvalue) {
            $this->_configManager->saveConfig($gkey, $gvalue, $globalScope, $globalScopeId);
        }
    }

    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @throws \Exception
     */
    public function adjustIncrementId(\Magento\Framework\Setup\SchemaSetupInterface $setup)
    {
        //update length of order increment;
        $eavEntityTypeCollection = $this->_eavEntityType->create();
        //$eavEntityTypeCollection->addFieldToFilter('entity_type_code', 'order');
        //$eavEntityTypeCollection->addFieldToFilter('entity_table', 'sales_order')->load();
        if ($eavEntityTypeCollection->getSize()) {
            foreach ($eavEntityTypeCollection as $_entity) {
                try {
                    $_entity->setIncrementPadLength(7)->save();
                } catch (\Exception $e) {
                    throw $e;
                }
            }
        }
        //update prefix of increment Id of order
        $metaTable = $setup->getTable('sales_sequence_meta');
        $profileTable = $setup->getTable('sales_sequence_profile');
        $sql = "UPDATE  $profileTable AS `a` INNER JOIN $metaTable AS `b` ON a.meta_id = b.meta_id
                SET `prefix` = CONCAT('5',b.store_id) WHERE b.entity_type = 'order'";
        $setup->run($sql);
        //for remains ones
        $sql = "UPDATE  $profileTable AS `a` INNER JOIN $metaTable AS `b` ON a.meta_id = b.meta_id
                SET `prefix` = '0' WHERE prefix is null OR prefix = '' ";
        $setup->run($sql);
    }
    /**
     * @param $data
     * @param $websiteName
     * @param $websiteCode
     * @return mixed
     */
    public function compareWebsites($data, $websiteName, $websiteCode)
    {
        foreach ($data as $_row) {
            if (strtolower($_row['name']) == strtolower($websiteName) || strtolower($_row['code']) ==strtolower($websiteCode)) {
                return $_row;
            }
        }
    }

    /**
     * Remove config in other websites
     */
    public function removeBIPWebsiteConfig()
    {
        $path1 = 'payment/invoicedbasedpayment/customergroup';
        $path2 = 'payment/invoicedbasedpayment/active';
        $scope = 'websites';
        $websiteCollection = $this->_websiteCollection
            ->addFieldToFilter('website_id', ['neq'=> 0])
            ->load();
        if ($websiteCollection->getSize()) {
            foreach ($websiteCollection as $_website) {
                $websiteId = $_website->getWebsiteId();
                if ($websiteId > 0) {
                    $this->_configWriter->delete($path1, $scope, $websiteId);
                    $this->_configWriter->delete($path2, $scope, $websiteId);
                }
            }
        }
    }
}
