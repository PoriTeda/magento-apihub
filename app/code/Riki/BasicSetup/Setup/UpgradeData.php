<?php
/**
 * Module.
 *
 * PHP version 7
 *
 * @category  RIKI
 *
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

// @codingStandardsIgnoreFile
namespace Riki\BasicSetup\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Riki\BasicSetup\Model\CategorySetup;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    CONST DISABLED = 1;
    CONST ENABLED = 0;
    const WEBSITE_ID = 7;
    const STORE_GROUP = 'NescafeStand Store';
    const STORE_VIEW = 'NescafeStand View';
    /**
     * @var ResourceConfig
     */
    protected $config;

    /**
     * @var array
     */
    protected $modules = [
        'Magento_Authorizenet',
        'Magento_Braintree',
        'Magento_Cybersource',
        'Magento_Dhl',
        'Magento_Downloadable',
        'Magento_DownloadableImportExport',
        'Magento_Eway',
        'Magento_Fedex',
        'Magento_GiftCard',
        'Magento_GiftCardAccount',
        'Magento_GiftCardImportExport',

        'Magento_GiftRegistry',
        'Magento_GoogleAdwords',
        'Magento_GroupedImportExport',
        'Magento_GroupedProduct',
        'Magento_Invitation',
        'Magento_MultipleWishlist',
        'Magento_Newsletter',
        'Magento_Paypal',
        'Magento_Review',
        'Magento_Rss',
        'Magento_Ups',
        'Magento_Wishlist',
        'Magento_Worldpay',
    ];

    protected $modulesTobeConfirmed = [
        'Magento_Banner',
        'Magento_BannerCustomerSegment',
        'Magento_Captcha',
        'Magento_CatalogEvent',
        'Magento_Marketplace',
        'Magento_Persistent',
        'Magento_PersistentHistory',
        'Magento_SampleData',
        'Magento_ScheduledImportExport',
        'Magento_Solr',
        'Magento_Usps',
        'Magento_Weee',
    ];

    protected $modulesReEnabled = [
        'Magento_Contact',
        'Magento_Captcha',
        'Magento_GiftMessage'
    ];
    /**
     * @var string
     */
    protected $path = 'advanced/modules_disable_output/';

    protected $categorySetup;

    protected $setupHelper;
    protected $moduleStatus;

    /**
     * @var \Magento\Sales\Model\Order\StatusFactory
     */
    protected $statusModelFactory;

    /**
     * UpgradeData constructor.
     * @param ResourceConfig $resourceConfig
     * @param CategorySetup $categorySetup
     * @param \Riki\ArReconciliation\Setup\SetupHelper $setupHelper
     * @param \Magento\Framework\Module\Status $moduleStatus
     * @param \Magento\Sales\Model\Order\StatusFactory $statusModelFactory
     */
    public function __construct(
        ResourceConfig $resourceConfig,
        CategorySetup $categorySetup,
        \Riki\ArReconciliation\Setup\SetupHelper $setupHelper,
        \Magento\Framework\Module\Status $moduleStatus,
        \Magento\Sales\Model\Order\StatusFactory $statusModelFactory
    ) {
        $this->setupHelper = $setupHelper;
        $this->config = $resourceConfig;
        $this->categorySetup = $categorySetup;
        $this->moduleStatus = $moduleStatus;
        $this->statusModelFactory = $statusModelFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '0.1.4') < 0) {
            foreach ($this->modules as $module) {
                $this->config->saveConfig($this->path . $module, self::DISABLED, 'default', 0);
            }
        }

        if (version_compare($context->getVersion(), '0.1.5') < 0) {
            foreach ($this->modulesTobeConfirmed as $module) {
                $this->config->saveConfig($this->path . $module, self::DISABLED, 'default', 0);
            }
        }

        if (version_compare($context->getVersion(), '0.1.6') < 0) {
            $this->config->saveConfig('design/header/welcome', 'ようこそ<strong>ゲスト</strong>さん', 'default', 0);
        }

        // '0.1.6.1' will be replaced by '0.1.6.2'
        if (version_compare($context->getVersion(), '0.1.6.2') < 0) {
            foreach ($this->modulesReEnabled as $module) {
                $this->config->saveConfig($this->path . $module, self::ENABLED, 'default', 0);
            }
        }
        // Update new category data
        if (version_compare($context->getVersion(), '0.1.8') < 0) {
            $this->categorySetup->categorySetup('0.1.0');
        }
        // Update new category data
        if (version_compare($context->getVersion(), '0.1.9') < 0) {
            $this->categorySetup->categorySetup('0.1.0');
        }
        // Update new category data
        if (version_compare($context->getVersion(), '0.2.1') < 0) {
            $this->categorySetup->categorySetup('0.1.0');
        }

        if (version_compare($context->getVersion(), '0.2.3') < 0) {
            $salesConnection= $this->setupHelper->getSalesConnection();
            $salesConnection->update(
                $salesConnection->getTableName('sales_order_status_state'),
                [
                    'is_default' => 1,
                    'visible_on_front' => 1
                ],
                sprintf('status = \'%s\'', \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_PENDING_CC)
            );
        }
        // Update new category data
        if (version_compare($context->getVersion(), '0.2.5') < 0) {
            $this->categorySetup->removeDuplicateCategories();
        }
        // Update new category data
        if (version_compare($context->getVersion(), '0.2.6') < 0) {
            $this->categorySetup->categorySetup('0.1.0');
        }
        if (version_compare($context->getVersion(), '0.3.2') < 0) {
            $this->moduleStatus->setIsEnabled(false, ['Magento_SendFriend']);
        }
        if (version_compare($context->getVersion(), '0.3.3') < 0) { // Enable SOLR as request from Nestle
            $this->moduleStatus->setIsEnabled(true, ['Magento_Solr']);
        }

        if (version_compare($context->getVersion(), '0.5.5') < 0) {
            $configDataTable = $setup->getTable('core_config_data');
            $connection = $setup->getConnection();
            $connection->update(
                $configDataTable,
                ['value' => '1'], // 1 : Disable
                ['path = ?' => 'advanced/modules_disable_output/Magento_Review']
            );
            $connection->update(
                $configDataTable,
                ['value' => '1'],
                ['path = ?' => 'advanced/modules_disable_output/Magento_Newsletter']
            );
        }

        //remove setting data of module mass stock update

        if (version_compare($context->getVersion(), '0.5.6') < 0) {
            $connection = $setup->getConnection();
            $connection->query("DELETE FROM core_config_data where `path` like '%massstockupdate%'");
            $connection->query("DELETE FROM setup_module where `module` like '%Wyomind_MassStockUpdate%'");
        }

        if (version_compare($context->getVersion(), '0.5.7') < 0) {

            $connection = $this->setupHelper->getDefaultConnection();
            $select = $connection->select()->from(
                $connection->getTableName('store_website')
            )->where('website_id = ' . self::WEBSITE_ID);
            $result = $connection->fetchOne($select);

            if (empty($result)) {
                /**
                 * Create website
                 */

                $connection->insertForce(
                    $connection->getTableName('store_website'),
                    [
                        'website_id' => self::WEBSITE_ID,
                        'code' => 'ncs',
                        'name' => 'NescafeStand site',
                        'sort_order' => 7,
                        'default_group_id' => 0,
                        'is_default' => 0
                    ]
                );

                /**
                 * Create store group
                 */
                $connection->insert(
                    $connection->getTableName('store_group'),
                    [
                        'website_id' => self::WEBSITE_ID,
                        'name' => self::STORE_GROUP,
                        'root_category_id' => CategorySetup::DEFAULT_CATEGORY_ID,
                        'default_store_id' => self::WEBSITE_ID
                    ]
                );
                $groupId = $connection->lastInsertId($setup->getTable('store_group'));

                if ($groupId) {

                    /**
                     * Update group_id to website
                     */
                    $connection->update(
                        $connection->getTableName('store_website'),
                        [
                            'default_group_id' => $groupId
                        ],
                        "website_id = " . self::WEBSITE_ID
                    );

                    /**
                     * Create store
                     */
                    $connection->insert(
                        $connection->getTableName('store'),
                        [
                            'code' => 'ncs',
                            'website_id' => self::WEBSITE_ID,
                            'group_id' => $groupId,
                            'name' => self::STORE_VIEW,
                            'sort_order' => 0,
                            'is_active' => 1
                        ]
                    );
                    $storeId = $connection->lastInsertId($setup->getTable('store'));

                    if ($storeId) {
                        /**
                         * Update group_id to website
                         */
                        $connection->update(
                            $connection->getTableName('store_group'),
                            [
                                'default_store_id' => $storeId
                            ],
                            "group_id = $groupId "
                        );
                    }
                }
            }
        }

        if (version_compare($context->getVersion(), '0.5.8') < 0) {
            $statusModel = $this->statusModelFactory->create();
            $statusModel->setStatus('pending_for_machine');
            $statusModel->setLabel('PENDING_FOR_MACHINE');
            $statusModel->save();
            $statusModel->assignState(\Magento\Sales\Model\Order::STATE_PROCESSING, 0, 1);
        }

        $setup->endSetup();
    }
}
