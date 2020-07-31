<?php
// @codingStandardsIgnoreFile
namespace Riki\Preorder\Setup;;

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

        if (version_compare($context->getVersion(), '1.0.2') < 0) {

            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'riki_preorder_is_confirmed');
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'riki_preorder_is_confirmed',
                [
                    'type'              => 'int',
                    'backend'           => '',
                    'frontend'          => '',
                    'label'             => __('Is Confirmed'),
                    'input'             => 'select',
                    'class'             => '',
                    'source'            => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'global'            => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'           => false,
                    'required'          => false,
                    'user_defined'      => false,
                    'default'           => 0,
                    'searchable'        => false,
                    'filterable'        => false,
                    'comparable'        => false,
                    'visible_on_front'  => false,
                    'unique'            => false,
                    'apply_to'          => '',
                    'is_configurable'   => false
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.3') < 0) {

            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'riki_preorder_is_confirmed');
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'riki_preorder_is_confirmed',
                [
                    'type'              => 'int',
                    'backend'           => '',
                    'frontend'          => '',
                    'label'             => __('Is Confirmed'),
                    'input'             => 'select',
                    'class'             => '',
                    'source'            => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'global'            => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'           => false,
                    'required'          => false,
                    'user_defined'      => false,
                    'default'           => 0,
                    'searchable'        => false,
                    'filterable'        => false,
                    'comparable'        => false,
                    'visible_on_front'  => false,
                    'unique'            => false,
                    'apply_to'          => '',
                    'is_configurable'   => false
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.4') < 0) {
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'riki_preorder_is_confirmed');

            $attribute = 'cumulative_per_customer';
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $attribute);
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                $attribute,
                [
                    'type'              => 'int',
                    'backend'           => '',
                    'frontend'          => '',
                    'label'             => __('Cumulative per customer'),
                    'input'             => 'text',
                    'class'             => '',
                    'source'            => '',
                    'global'            => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
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
        }

        if (version_compare($context->getVersion(), '1.0.5') < 0) {
            $attribute = 'cumulative_per_customer';
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $attribute);
        }

        $setup->endSetup();
    }
}
