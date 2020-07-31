<?php
namespace Riki\PurchaseRestriction\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
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

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'limit_user_unit');

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, 'limit_user_unit', [
                'group' => 'Basic',
                'type' => 'varchar',
                'input' => 'select',
                'label' => 'Purchase Limit - Per customer: Duration unit',
                'source' => 'Riki\PurchaseRestriction\Model\Config\Source\Product\DurationUnit',
                'visible' => true,
                'required' => false,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'is_used_in_grid' => false,
                'unique' => false,
                'apply_to' => '',
                'sort_order' => 2001,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE
            ]
        );


        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'limit_user_duration');

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, 'limit_user_duration', [
                'group' => 'Basic',
                'type' => 'int',
                'input' => 'text',
                'label' => 'Purchase Limit - Per customer: Duration',
                'visible' => true,
                'required' => false,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'is_used_in_grid' => false,
                'unique' => false,
                'apply_to' => '',
                'sort_order' => 2002,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE
            ]
        );


        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'limit_user_qty');

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, 'limit_user_qty', [
                'group' => 'Basic',
                'type' => 'int',
                'input' => 'text',
                'label' => 'Purchase limit - Per customer: Qty',
                'visible' => true,
                'required' => false,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'is_used_in_grid' => false,
                'unique' => false,
                'apply_to' => '',
                'sort_order' => 2003,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE
            ]
        );
    }
}
