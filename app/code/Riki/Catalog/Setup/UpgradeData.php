<?php
// @codingStandardsIgnoreFile
namespace Riki\Catalog\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    protected $_eavSetupFactory;

    protected $_attributeSetInterface;

    protected $_attributeSetManagementInterface;

    protected $_categorySetupFactory;

    public function __construct(
        \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory,
        \Magento\Eav\Api\AttributeSetManagementInterface $attributeSetManagementInterface,
        \Magento\Eav\Api\Data\AttributeSetInterface $attributeSetInterface,
        \Magento\Catalog\Setup\CategorySetupFactory $categorySetupFactory
    ) {
        $this->_eavSetupFactory = $eavSetupFactory;
        $this->_attributeSetInterface = $attributeSetInterface;
        $this->_attributeSetManagementInterface = $attributeSetManagementInterface;
        $this->_categorySetupFactory = $categorySetupFactory;
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

        if (version_compare($context->getVersion(), '0.1.5') < 0) {
            $eavSetup = $this->_eavSetupFactory->create([
                'setup' => $setup,
            ]);

            // add attributes to product listing
            $eavSetup->updateAttribute(\Magento\Catalog\Model\Product::ENTITY, 'delivery_type', [
                'is_filterable' => 1,
                'used_in_product_listing' => true,
            ]);
//            $eavSetup->updateAttribute(\Magento\Catalog\Model\Product::ENTITY, 'is_free_shipping', [
//                'is_filterable' => 1,
//                'used_in_product_listing' => true,
//            ]);
            $eavSetup->updateAttribute(\Magento\Catalog\Model\Product::ENTITY, 'gift_wrapping', [
                'is_filterable' => 1,
                'used_in_product_listing' => true,
            ]);

            // add new attribute
            $entityTypeId = $eavSetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);

            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'description_campaign');
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, 'description_campaign', [
                    'type' => 'text',
                    'label' => 'Description Campaign',
                    'input' => 'textarea',
                    'required' => false,
                    'sort_order' => 15,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'searchable' => true,
                    'comparable' => true,
                    'wysiwyg_enabled' => true,
                    'is_html_allowed_on_front' => true,
                    'visible_in_advanced_search' => true,
                    'used_in_product_listing' => true,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                ]
            );

            $attribute = $eavSetup->getAttribute($entityTypeId, 'description_campaign');
            if ($attribute) {
                $eavSetup->addAttributeToGroup(
                    $entityTypeId,
                    $eavSetup->getAttributeSetId($entityTypeId, 'Default'),
                    'Basic',
                    $attribute['attribute_id'],
                    15
                );
            }

            ///

            $defaultId = $eavSetup->getDefaultAttributeSetId(\Magento\Catalog\Model\Product::ENTITY);

            $model = $this->_attributeSetInterface
                ->setId(null)
                ->setEntityTypeId(4)
                ->setAttributeSetName('No product');

            $this->_attributeSetManagementInterface
                ->create(\Magento\Catalog\Model\Product::ENTITY, $model, $defaultId)
                ->save();

            /////////////

            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'external_url');
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, 'external_url', [
                    'type' => 'text',
                    'input' => 'text',
                    'label' => 'External Url',
                    'visible' => true,
                    'required' => true,
                    'user_defined' => true,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => true,
                    'used_in_product_listing' => true,
                    'is_used_in_grid' => false,
                    'unique' => false,
                    'sort_order' => 100,
                    'apply_to' => '',
                ]
            );

            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'no_product_type');
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, 'no_product_type', [
                    'type' => 'int',
                    'input' => 'select',
                    'label' => 'No Product Type',
                    'source' => 'Riki\Catalog\Model\Config\Source\Product\NoProductType',
                    'visible' => true,
                    'required' => true,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true,
                    'is_used_in_grid' => false,
                    'unique' => false,
                    'apply_to' => '',
                    'sort_order' => 110,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                ]
            );

            $groupName = 'Basic';

            $attributeSetId = $eavSetup->getAttributeSetId($entityTypeId, 'No product');

            $attributesOrder = ['external_url' => 100, 'no_product_type' => 110];

            foreach ($attributesOrder as $key => $value) {
                $attribute = $eavSetup->getAttribute($entityTypeId, $key);
                if ($attribute) {
                    $eavSetup->addAttributeToGroup(
                        $entityTypeId,
                        $attributeSetId,
                        $groupName,
                        $attribute['attribute_id'],
                        $value
                    );
                }
            }
        }

        if (version_compare($context->getVersion(), '0.1.6') < 0) {
            /* @var $categorySetup \Magento\Catalog\Setup\CategorySetup */
            $categorySetup = $this->_categorySetupFactory->create();

            $attributeCode = 'navigation_path';

            $categorySetup->addAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                $attributeCode,
                [
                    'type' => 'text',
                    'label' => 'Navigation Path',
                    'input' => 'textarea',
                    'sort_order' => 120,
                    'required' => false,
                    'is_html_allowed_on_front' => true,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'General Information',
                ]
            );

            $categorySetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                $attributeCode,
                [
                    'type' => 'text',
                    'label' => 'Navigation Path',
                    'input' => 'textarea',
                    'sort_order' => 120,
                    'required' => false,
                    'is_html_allowed_on_front' => true,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'Product Details',
                ]
            );
        }

        // remove no_product_type attribute from default attribute set
        if (version_compare($context->getVersion(), '0.1.7') < 0) {

            $eavSetup = $this->_eavSetupFactory->create([
                'setup' => $setup,
            ]);

            $noProductAttributeId = $eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'no_product_type');

            $setup->deleteTableRow('eav_entity_attribute', 'attribute_id', $noProductAttributeId);

            $entityTypeId = $eavSetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
            $attributeSetId = $eavSetup->getAttributeSetId($entityTypeId, 'No product');
            $groupName = 'Basic';

            $eavSetup->addAttributeToGroup(
                $entityTypeId,
                $attributeSetId,
                $groupName,
                $noProductAttributeId,
                100
            );
        }

        $setup->endSetup();
    }
}
