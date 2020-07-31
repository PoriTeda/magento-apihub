<?php
// @codingStandardsIgnoreFile
namespace Riki\GiftWrapping\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Catalog\Setup\CategorySetupFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    private $eavSetupFactory;
    private $eavSetup;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $_eavAttribute;
    /**
     * @var QuoteSetupFactory
     */
    protected $quoteSetupFactory;

    /**
     * @var SalesSetupFactory
     */
    protected $salesSetupFactory;



    /**
     * @var ConfigInterface
     */
    protected $productTypeConfig;

    /**
     * @var CategorySetupFactory
     */
    protected $categorySetupFactory;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        SalesSetupFactory $salesSetupFactory,
        QuoteSetupFactory $quoteSetupFactory,
        ConfigInterface $productTypeConfig,
        CategorySetupFactory $categorySetupFactory,
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $eavSetup,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->quoteSetupFactory = $quoteSetupFactory;
        $this->productTypeConfig = $productTypeConfig;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavSetup = $eavSetup;
        $this->_eavAttribute = $eavAttribute;
        $this->_storeManager = $storeManager;
    }
    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        //$installer = $this->categorySetupFactory->create(['resourceName' => 'catalog_setup', 'setup' => $setup]);

        $quoteInstaller = $this->quoteSetupFactory->create(['resourceName' => 'quote_setup', 'setup' => $setup]);
        $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
        /**
         * Add gift wrapping attributes for catalog product entity
         */
        $applyTo = join(',', $this->productTypeConfig->filter('is_real_product'));
        $setup->startSetup();
        if (version_compare($context->getVersion(), '2.0.0', '<')){
            // Section Information

            $field = 'gift_wrapping';
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'type' => 'varchar',
                    'input' => 'multiselect',
                    'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                    'label' => 'Gift Wrapping',
                    'source' => 'Riki\GiftWrapping\Model\Config\Giftwrapping',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid' => false,
                    'unique' => false,
                    'frontend_class' => 'hidden-for-virtual',
                    'apply_to' => $applyTo
                ]
            );

            $groupName = 'Autosettings';
            $entityTypeId = $salesInstaller->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
            $attributeSetId = $salesInstaller->getAttributeSetId($entityTypeId, 'Default');

            $attributesOrder = ['gift_wrapping' => 71];

            foreach ($attributesOrder as $key => $value) {
                $attribute = $salesInstaller->getAttribute($entityTypeId, $key);
                if ($attribute) {
                    $salesInstaller->addAttributeToGroup(
                        $entityTypeId,
                        $attributeSetId,
                        $groupName,
                        $attribute['attribute_id'],
                        $value
                    );
                }
            }


            $entityAttributesCodes = [
                'gift_wrapping' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'gift_code' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'sap_code' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            ];
            foreach ($entityAttributesCodes as $code => $type) {
                $quoteInstaller->addAttribute('quote', $code, ['type' => $type, 'visible' => false]);
                $quoteInstaller->addAttribute('quote_item', $code, ['type' => $type, 'visible' => false]);
                $salesInstaller->addAttribute('order', $code, ['type' => $type, 'visible' => false]);
                $salesInstaller->addAttribute('order_item', $code, ['type' => $type, 'visible' => false]);
            }

        }
        if (version_compare($context->getVersion(), '2.0.2', '<')){

            $quoteInstaller->addAttribute('quote_address', 'apartment', ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 'visible' => false]);
            $salesInstaller->addAttribute('order_address', 'apartment', ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 'visible' => false]);
            
        }
        if (version_compare($context->getVersion(), '2.0.3') < 0) {
            $installer = $this->categorySetupFactory->create([ 'setup' => $setup]);
            $installer->updateAttribute(\Magento\Catalog\Model\Product::ENTITY, 'gift_wrapping_available', 'is_visible', '0');
            $installer->updateAttribute(\Magento\Catalog\Model\Product::ENTITY, 'gift_wrapping_price', 'is_visible', '0');
        }

        if (version_compare($context->getVersion(), '2.0.4') < 0)
        {
            $data = [
                ['BOXC001', '包装（のし無し）', '195'],
                ['BOXC004', '包装+内のし（御祝）', '195'],
                ['BOXC005', '包装+内のし（無地）', '195'],
                ['BOXC006', '包装+内のし（内祝）', '195'],
                ['BOXC007', '包装（弔事用）＋内のし（志）', '195'],
                ['BOXC008', '包装（弔事用）＋内のし（短冊）', '195'],
                ['BOXC009', '包装+内のし（御礼）	', '195'],
                ['BOXC054', '包装+外のし（御祝）', '195'],
                ['BOXC055', '包装+外のし（無地）', '195'],
                ['BOXC056', '包装+外のし（内祝）', '195'],
                ['BOXC057', '包装（弔事用）＋外のし（志）', '195'],
                ['BOXC058', '包装（弔事用）＋外のし（短冊）', '195'],
                ['BOXC059', '包装+外のし（御礼）', '195'],
                ['BOXF007', '包装（弔事用）＋内のし（志）', '0'],
                ['BOXF008', '包装（弔事用）＋内のし（短冊）', '0'],
                ['BOXF010', '包装（弔事用）のし無し', '0'],
                ['BOXF057', '包装（弔事用）＋外のし（志）', '0'],
                ['BOXF058', '包装（弔事用）＋外のし（短冊）', '195'],
                ['CAS1001', '包装（のし無し）', '973'],
                ['CAS1005', '包装+内のし（無地）', '973'],
                ['CAS1055', '包装+外のし（無地）', '973'],
                ['NHCF001', '定期お届け便案内チラシ', '0'],
                ['NHCF002', '商品案内＋定期お届け便案内チラシ', '0'],
                ['NHCF003', 'キャンペーン景品', '0'],
                ['NHCF004', 'アルジネードキャンペーン景品', '0'],
                ['NHCF005', 'キャンペーン景品（スリムウォーク）', '0']
            ];
            foreach ($data as $row) {
                $bind = [
                    'sap_code' =>'',
                    'gift_code' => $row[0],
                    'gift_name' => $row[1],
                    'status' => 1,
                    'base_price' => $row[2],
                    'image'=> "",
                    'flag_export_bi'=> 0
                ];
                $setup->getConnection()->insert($setup->getTable('magento_giftwrapping'), $bind);

                $wrappingId = $setup->getConnection()->lastInsertId($setup->getTable('magento_giftwrapping'));

                $bind = ['wrapping_id' => $wrappingId, 'store_id' => 0, 'design' => $row[1]];
                $setup->getConnection()->insert($setup->getTable('magento_giftwrapping_store_attributes'), $bind);

                $website = $this->_storeManager->getWebsites();
                foreach ($website as $site) {
                    $bind = ['wrapping_id' => $wrappingId, 'website_id' => $site->getId()];
                    $setup->getConnection()->insert($setup->getTable('magento_giftwrapping_website'), $bind);
                }
            }
        }

        $setup->endSetup();
    }
}
