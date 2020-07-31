<?php
// @codingStandardsIgnoreFile
namespace Riki\Preorder\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
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
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'riki_preorder_note',
            [
                'type'              => 'varchar',
                'backend'           => '',
                'frontend'          => '',
                'label'             => __('Pre-Order Note'),
                'input'             => 'hidden',
                'class'             => '',
                'source'            => '',
                'global'            => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'visible'           => false,
                'required'          => false,
                'user_defined'      => false,
                'default'           => '',
                'searchable'        => false,
                'filterable'        => false,
                'comparable'        => false,
                'visible_on_front'  => false,
                'unique'            => false,
                'apply_to'          => '',
                'is_configurable'   => false
            ]
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'riki_preorder_cart_label',
            [
                'type'              => 'varchar',
                'backend'           => '',
                'frontend'          => '',
                'label'             => __('Custom Pre-Order Cart Note'),
                'input'             => 'hidden',
                'class'             => '',
                'source'            => '',
                'global'            => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'visible'           => false,
                'required'          => false,
                'user_defined'      => false,
                'default'           => '',
                'searchable'        => false,
                'filterable'        => false,
                'comparable'        => false,
                'visible_on_front'  => false,
                'unique'            => false,
                'apply_to'          => '',
                'is_configurable'   => false
            ]
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'preorder_from',
            [
                'type'              => 'datetime',
                'backend'           => 'Magento\Eav\Model\Entity\Attribute\Backend\Datetime',
                'frontend'          => 'Magento\Eav\Model\Entity\Attribute\Frontend\Datetime',
                'label'             => __('Pre Order Start Date'),
                'input'             => 'hidden',
                'class'             => '',
                'source'            => '',
                'global'            => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'visible'           => false,
                'required'          => false,
                'user_defined'      => false,
                'default'           => '',
                'searchable'        => false,
                'filterable'        => false,
                'comparable'        => false,
                'visible_on_front'  => false,
                'used_in_product_listing' => true,
                'unique'            => false,
                'apply_to'          => '',
                'is_configurable'   => false
            ]
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'fulfilment_date',
            [
                'type'              => 'datetime',
                'backend'           => 'Magento\Eav\Model\Entity\Attribute\Backend\Datetime',
                'frontend'          => 'Magento\Eav\Model\Entity\Attribute\Frontend\Datetime',
                'label'             => __('Fulfillment Date'),
                'input'             => 'hidden',
                'class'             => '',
                'source'            => '',
                'global'            => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'visible'           => false,
                'required'          => false,
                'user_defined'      => false,
                'default'           => '',
                'searchable'        => false,
                'filterable'        => false,
                'comparable'        => false,
                'visible_on_front'  => false,
                'used_in_product_listing' => true,
                'unique'            => false,
                'apply_to'          => '',
                'is_configurable'   => false
            ]
        );


    }
}
