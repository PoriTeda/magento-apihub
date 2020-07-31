<?php
// @codingStandardsIgnoreFile
namespace Riki\Loyalty\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Customer\Model\Customer;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var \Magento\Customer\Setup\CustomerSetupFactory
     */
    protected $customerSetupFactory;

    /**
     * @var \Magento\Cms\Model\PageFactory
     */
    protected $pageFactory;

    /**
     * @var \Magento\Framework\Setup\SampleData\FixtureManager
     */
    protected $fixtureManager;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Sales\Setup\SalesSetupFactory
     */
    protected $salesSetupFactory;

    /**
     * @var \Magento\Quote\Setup\QuoteSetupFactory
     */
    protected $quoteSetupFactory;

    /**
     * @var \Magento\Cms\Api\PageRepositoryInterface
     */
    protected $pageRepository;

    /**
     * @var \Magento\Indexer\Model\IndexerFactory
     */
    protected $indexerFactory;

    /**
     * UpgradeData constructor.
     *
     * @param \Magento\Cms\Model\PageFactory $pageFactory
     * @param \Magento\Cms\Api\PageRepositoryInterface $pageRepository
     * @param \Magento\Framework\Setup\SampleData\Context $context
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Setup\SalesSetupFactory $salesSetupFactory
     * @param \Magento\Quote\Setup\QuoteSetupFactory $quoteSetupFactory
     * @param \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory
     * @param \Magento\Indexer\Model\IndexerFactory $indexerFactory
     */
    public function __construct(
        \Magento\Cms\Model\PageFactory $pageFactory,
        \Magento\Cms\Api\PageRepositoryInterface $pageRepository,
        \Magento\Framework\Setup\SampleData\Context $context,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Setup\SalesSetupFactory $salesSetupFactory,
        \Magento\Quote\Setup\QuoteSetupFactory $quoteSetupFactory,
        \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory,
        \Magento\Indexer\Model\IndexerFactory $indexerFactory
    ) {
        $this->pageFactory = $pageFactory;
        $this->fixtureManager = $context->getFixtureManager();
        $this->resourceConfig = $resourceConfig;
        $this->logger = $logger;
        $this->salesSetupFactory = $salesSetupFactory;
        $this->quoteSetupFactory = $quoteSetupFactory;
        $this->pageRepository = $pageRepository;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->indexerFactory = $indexerFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $this->upgradeToVersion102();
        }
        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $this->upgradeToVersion103($setup);
        }
        if (version_compare($context->getVersion(), '1.0.5', '<')) {
            $this->upgradeToVersion105($setup);
        }
        if (version_compare($context->getVersion(), '1.0.6', '<')) {
            $this->upgradeToVersion106($setup);
        }
        $setup->endSetup();
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @return void
     */
    private function upgradeToVersion106($setup)
    {
        /** @var \Magento\Customer\Setup\CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
        $customerSetup->addAttribute(Customer::ENTITY, 'approval_needed', [
            'type' => 'int',
            'input' => 'select',
            'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
            'visible' => true,
            'required' => false,
            'system' => 0,
            'sort_order' => 255,
            'position' => 255,
            'label' => 'Approval needed',
            'default' => '0',
        ]);
        $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'approval_needed');
        $attribute->setData('used_in_forms', ['adminhtml_customer']);
        $attribute->save();
        $customerSetup->updateAttribute(
            Customer::ENTITY,
            'approval_needed',
            [
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => true
            ]
        );
        $indexer = $this->indexerFactory->create();
        $indexer->load('customer_grid');
        $indexer->reindexAll();
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @return void
     */
    private function upgradeToVersion105($setup)
    {
        /** @var  \Magento\Quote\Setup\QuoteSetup $quoteInstaller */
        $quoteInstaller = $this->quoteSetupFactory->create(['resourceName' => 'quote_setup', 'setup' => $setup]);
        $quoteInstaller->addAttribute(
            'quote',
            'bonus_point_amount',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER]
        );
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @return void
     */
    private function upgradeToVersion103($setup)
    {
        /** @var  \Magento\Quote\Setup\QuoteSetup $quoteInstaller */
        $quoteInstaller = $this->quoteSetupFactory->create(['resourceName' => 'quote_setup', 'setup' => $setup]);
        /** @var  \Magento\Sales\Setup\SalesSetup $salesInstaller */
        $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);

        $quoteInstaller->addAttribute(
            'quote',
            'base_used_point_amount',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );
        $quoteInstaller->addAttribute(
            'quote',
            'used_point_amount',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );
        $quoteInstaller->addAttribute(
            'quote',
            'used_point',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER]
        );

        $quoteInstaller->addAttribute(
            'quote_address',
            'base_used_point_amount',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );
        $quoteInstaller->addAttribute(
            'quote_address',
            'used_point_amount',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );
        $quoteInstaller->addAttribute(
            'quote_address',
            'used_point',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER]
        );

        $salesInstaller->addAttribute(
            'order',
            'base_used_point_amount',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );
        $salesInstaller->addAttribute(
            'order',
            'used_point_amount',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
        );
    }
    /**
     * Add cms page for reward point module
     * @return void
     */
    private function upgradeToVersion102()
    {
        try {
            $pageSetting = $this->fixtureManager->getFixture('Riki_Loyalty::fixtures/cms_page/point-settings.html');
            $cmsPageData = [
                'title' => '自動使用ポイントの設定',
                'page_layout' => 'empty',
                'identifier' => 'point-settings',
                'content_heading' => '',
                'is_active' => 1,
                'stores' => [0],
                'content' => file_get_contents($pageSetting)
            ];
            /** @var \Magento\Cms\Model\Page $cmsPageSetting */
            $cmsPageSetting = $this->pageFactory->create()->setData($cmsPageData);
            $this->pageRepository->save($cmsPageSetting);
            $this->resourceConfig->saveConfig(
                \Riki\Loyalty\Block\Reward::XPATH_POINT_SETTING_CMS_PAGE,
                $cmsPageSetting->getIdentifier(),
                'default',
                0
            );

            $pageHistory = $this->fixtureManager->getFixture('Riki_Loyalty::fixtures/cms_page/point-history.html');
            $cmsPageData = [
                'title' => 'ネスレショッピングポイントとは',
                'page_layout' => 'empty',
                'identifier' => 'point-history',
                'content_heading' => '',
                'is_active' => 1,
                'stores' => [0],
                'content' => file_get_contents($pageHistory)
            ];
            $cmsPageHistory = $this->pageFactory->create()->setData($cmsPageData);
            $this->pageRepository->save($cmsPageHistory);
            $this->resourceConfig->saveConfig(
                \Riki\Loyalty\Block\Reward::XPATH_POINT_HISTORY_CMS_PAGE,
                $cmsPageHistory->getIdentifier(),
                'default',
                0
            );
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
