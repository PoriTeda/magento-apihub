<?php
// @codingStandardsIgnoreFile
namespace Riki\Chirashi\Setup;;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{

    /**
     * EAV setup factory
     *
     * @var \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory
     */
    private $eavSetupFactory;

    public function __construct(\Magento\Eav\Setup\EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        if (version_compare($context->getVersion(), '1.0.1') < 0) {

            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'chirashi');

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, 'chirashi', [
                    'group' => 'Basic',
                    'type' => 'int',
                    'backend' => 'Magento\Catalog\Model\Product\Attribute\Backend\Boolean',
                    'input' => 'boolean',
                    'label' => 'Chirashi',
                    'visible' => true,
                    'required' => false,
                    'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true,
                    'is_used_in_grid' => false,
                    'unique' => false,
                    'is_used_for_promo_rules' => true,
                    'apply_to' => '',
                    'default' => 0,
                    'sort_order' => 1000,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.2') < 0) {

            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'chirashi');

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, 'chirashi', [
                    'group' => 'Basic',
                    'type' => 'int',
                    'backend' => 'Magento\Catalog\Model\Product\Attribute\Backend\Boolean',
                    'input' => 'boolean',
                    'label' => 'Chirashi',
                    'visible' => true,
                    'required' => false,
                    'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true,
                    'is_used_in_grid' => false,
                    'unique' => false,
                    'is_used_for_promo_rules' => true,
                    'apply_to' => '',
                    'default' => 0,
                    'sort_order' => 1000,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL
                ]
            );
        }

        $setup->endSetup();
    }
}
