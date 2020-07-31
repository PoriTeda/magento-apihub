<?php
// @codingStandardsIgnoreFile
namespace Riki\CreateProductAttributes\Setup;

use Magento\Eav\Setup\EavSetupFactory;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private $eavSetupFactory;
    private $eavSetup;

    public function __construct(EavSetupFactory $eavSetupFactory, ModuleDataSetupInterface $eavSetup )
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavSetup = $eavSetup;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);

        $field = 'sku_sap_code';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'SKU Sap code',
                'frontend_class' => 'validate-length maximum-length-64',
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
                'apply_to' => ''
            ]
        );

        $field = 'sku_3pl_code';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'SKU 3pl Code',
                'frontend_class' => 'validate-length maximum-length-64',
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
                'apply_to' => ''
            ]
        );

        $field = 'jan_code';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'Jan code',
                'frontend_class' => 'validate-length maximum-length-64',
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
                'apply_to' => ''
            ]
        );

        $field = 'description_invoice';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'text',
                'input' => 'textarea',
                'label' => 'Product Description for Invoiced Customer',
                'visible' => true,
                'required' => false,
                'wysiwyg_enabled' => true,
                'is_html_allowed_on_front' => true,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => false,
                'is_used_in_grid' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );

        $field = 'backfront_visibility';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'int',
                'input' => 'select',
                'label' => 'By where order can be received ',
                'source' => 'Riki\CreateProductAttributes\Model\Product\Visibility',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
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
                'apply_to' => ''
            ]
        );

        $field = 'priority';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'int',
                'input' => 'text',
                'label' => 'Priority Number (Product Recommend Function)',
                'frontend_class' => 'validate-number',
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
                'apply_to' => ''
            ]
        );

        $field = 'unit_sap';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'int',
                'input' => 'select',
                'label' => 'Unit (EA/CS) in SAP',
                'source' => 'Riki\CreateProductAttributes\Model\Product\UnitSap',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
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
                'apply_to' => ''
            ]
        );

        $field = 'unit_qty';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'int',
                'input' => 'text',
                'label' => 'Converstion Number (Number of EACH in SAP)',
                'frontend_class' => 'validate-number',
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
                'apply_to' => ''
            ]
        );

        $field = 'unit_ec';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'int',
                'input' => 'select',
                'label' => 'Unit (EA/CS) for Sales in Magento',
                'source' => 'Riki\CreateProductAttributes\Model\Product\UnitEc',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
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
                'apply_to' => ''
            ]
        );

        $field = 'master_reference';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'Master reference',
                'frontend_class' => 'validate-number maximum-length-255',
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
                'apply_to' => ''
            ]
        );

        $field = 'point_currency';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'int',
                'input' => 'text',
                'label' => 'Shopping Point % ',
                'frontend_class' => 'validate-number',
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
                'apply_to' => ''
            ]
        );

        $field = 'cod_applicable';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'int',
                'input' => 'boolean',
                'label' => 'Classification for Cash on Deliver Fee or not',
                'backend' => 'Magento\Customer\Model\Attribute\Backend\Data\Boolean',
                'visible' => true,
                'required' => false,
                'default' => 0,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => false,
                'is_used_in_grid' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );

        // Section Information

        $field = 'ph_code';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'varchar',
                'input' => 'multiselect',
                'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                'label' => 'PH (x digits) (need for Invoice pepartion for Invoiced customer)',
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
                'apply_to' => ''
            ]
        );

        $field = 'ph1_description';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);

        $field = 'ph2_description';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);

        $field = 'ph3_description';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);

        $field = 'ph4_description';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);

        $field = 'ph5_description';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'varchar',
                'input' => 'multiselect',
                'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                'label' => 'PH5 Description',
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
                'apply_to' => ''
            ]
        );

        $field = 'brand';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'varchar',
                'input' => 'multiselect',
                'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                'label' => 'BH2',
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
                'apply_to' => ''
            ]
        );

        $field = 'shelf_life_period';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'decimal',
                'input' => 'text',
                'label' => 'Product Shelf Life period (in days)',
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'frontend_class' => 'validate-zero-or-greater',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => false,
                'is_used_in_grid' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );

        $field = 'depth';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'decimal',
                'input' => 'text',
                'label' => 'Dimention / Depth(cm) per each',
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'frontend_class' => 'validate-zero-or-greater',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => false,
                'is_used_in_grid' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );

        $field = 'width';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'decimal',
                'input' => 'text',
                'label' => 'Dimention / Wide(cm) per Each',
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'frontend_class' => 'validate-zero-or-greater',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => false,
                'is_used_in_grid' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );

        $field = 'height';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'decimal',
                'input' => 'text',
                'label' => 'Dimention / Height(cm) per Each',
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'frontend_class' => 'validate-zero-or-greater',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => false,
                'is_used_in_grid' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );


        $field = 'desc_explanation';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'text',
                'input' => 'textarea',
                'label' => 'Product Description: Explanation',
                'visible' => true,
                'required' => false,
                'wysiwyg_enabled' => true,
                'is_html_allowed_on_front' => true,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => false,
                'is_used_in_grid' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );

        $field = 'desc_allergen_mandatory';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'text',
                'input' => 'textarea',
                'label' => 'Product Description: Allergen(Mandatory)',
                'visible' => true,
                'required' => false,
                'wysiwyg_enabled' => true,
                'is_html_allowed_on_front' => true,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => false,
                'is_used_in_grid' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );

        $field = 'desc_explanation_recom';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'text',
                'input' => 'textarea',
                'label' => 'Product Description: Allergen(Recommendation)',
                'visible' => true,
                'required' => false,
                'wysiwyg_enabled' => true,
                'is_html_allowed_on_front' => true,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => false,
                'is_used_in_grid' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );

        $field = 'desc_ingredient';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'text',
                'input' => 'textarea',
                'label' => 'Product Description: Ingrediencts',
                'visible' => true,
                'required' => false,
                'wysiwyg_enabled' => true,
                'is_html_allowed_on_front' => true,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => false,
                'is_used_in_grid' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );

        $field = 'desc_nutrition';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'text',
                'input' => 'textarea',
                'label' => 'Product Description: Nutrition Compass',
                'visible' => true,
                'required' => false,
                'wysiwyg_enabled' => true,
                'is_html_allowed_on_front' => true,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => false,
                'is_used_in_grid' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );

        $field = 'desc_supplemental_info';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'text',
                'input' => 'textarea',
                'label' => 'Product Description: Nutrition Compass',
                'visible' => true,
                'required' => false,
                'wysiwyg_enabled' => true,
                'is_html_allowed_on_front' => true,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => false,
                'is_used_in_grid' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );

        $field = 'delivery_type';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'int',
                'input' => 'select',
                'label' => 'Availability for Subscription Order by item',
                'source' => 'Riki\CreateProductAttributes\Model\Product\Delivery',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
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
                'apply_to' => ''
            ]
        );

        $field = 'available_subscription';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'int',
                'input' => 'select',
                'label' => 'Availability for Subscription Order by item',
                'source' => 'Riki\CreateProductAttributes\Model\Product\Available',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
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
                'apply_to' => ''
            ]
        );

        $field = 'future_price';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'decimal',
                'input' => 'price',
                'backend' => 'Magento\Catalog\Model\Product\Attribute\Backend\Price',
                'label' => 'Future Basic Selling Price (excl. Consumption Tax)',
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
                'apply_to' => ''
            ]
        );

        $field = 'future_price_from';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'datetime',
                'input' => 'date',
                'label' => 'Future Basic Selling Price (excl. Consumption Tax) effective date & time',
                'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\Datetime',
                'frontend' => 'Magento\Eav\Model\Entity\Attribute\Frontend\Datetime',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'is_used_in_grid' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );


        $field = 'GPS_price';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'decimal',
                'input' => 'price',
                'backend' => 'Magento\Catalog\Model\Product\Attribute\Backend\Price',
                'label' => 'GPS',
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
                'apply_to' => ''
            ]
        );

        $field = 'future_gps_price';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'decimal',
                'input' => 'price',
                'backend' => 'Magento\Catalog\Model\Product\Attribute\Backend\Price',
                'label' => 'Effective Date of GPS application (From)',
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
                'apply_to' => ''
            ]
        );

        $field = 'future_gps_price_from';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'datetime',
                'input' => 'date',
                'label' => 'Effective Date of GPS application (End)',
                'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\Datetime',
                'frontend' => 'Magento\Eav\Model\Entity\Attribute\Frontend\Datetime',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'is_used_in_grid' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );

        // Section Booking

        $field = 'material_type';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'int',
                'input' => 'select',
                'label' => 'Material Type',
                'source' => 'Riki\CreateProductAttributes\Model\Product\Material',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
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
                'apply_to' => ''
            ]
        );

        $field = 'sales_organization';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'varchar',
                'input' => 'multiselect',
                'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                'label' => 'Sales organization',
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
                'apply_to' => '',
                'option' => [
                    'values' => ['JP30', 'JP36']
                ]
            ]
        );


        $field = 'booking_item_wbs';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'WBS no. (For Sales)',
                'frontend_class' => 'validate-length maximum-length-255',
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
                'apply_to' => ''
            ]
        );

        $field = 'booking_item_account';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'Account No. (For Sales)',
                'frontend_class' => 'validate-length maximum-length-255',
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
                'apply_to' => ''
            ]
        );


        $field = 'booking_profit_center';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'Profit Center. (For Sales)',
                'frontend_class' => 'validate-length maximum-length-255',
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
                'apply_to' => ''
            ]
        );

        $field = 'booking_point_wbs';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'WBS no. (Shopping Point Expenses)',
                'frontend_class' => 'validate-length maximum-length-255',
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
                'apply_to' => ''
            ]
        );

        $field = 'booking_point_account';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'Account no. (Shopping Point Expenses)',
                'frontend_class' => 'validate-length maximum-length-255',
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
                'apply_to' => ''
            ]
        );


        $field = 'booking_free_wbs';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'WBS no. (Dealing Consumer Complaint)',
                'frontend_class' => 'validate-length maximum-length-255',
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
                'apply_to' => ''
            ]
        );

        $field = 'booking_free_account';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'Account no. (Dealing Consumer Complaint)',
                'frontend_class' => 'validate-length maximum-length-255',
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
                'apply_to' => ''
            ]
        );

        $field = 'booking_machine_mt_wbs';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'Accoung no. (Machine Service)',
                'frontend_class' => 'validate-length maximum-length-255',
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
                'apply_to' => ''
            ]
        );

        $field = 'booking_machine_mt_account';
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY, $field, [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'Cost Center no. (Machine Service)',
                'frontend_class' => 'validate-length maximum-length-255',
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
                'apply_to' => ''
            ]
        );


        $installer->endSetup();

    }
}
