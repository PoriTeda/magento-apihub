<?php
// @codingStandardsIgnoreFile
namespace Riki\CreateProductAttributes\Setup;

use Magento\Eav\Setup\EavSetupFactory;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Validator\Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;

class UpgradeSchema implements UpgradeSchemaInterface {

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private $eavSetupFactory;
    private $eavSetup;
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $_eavAttribute;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected $_productCollection;
    /**
     * @var \Magento\Catalog\Model\Product\Action
     */
    protected $_productAction;
    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $_product;

    protected $resourceConnection;

    protected $_storeManager;

    protected $_attributeFactory;

    protected $_dataSetup;

    protected $_productAttributeRepository;

    protected $_productRepository;

    protected $productResource;
    /**
     * UpgradeSchema constructor.
     * @param EavSetupFactory $eavSetupFactory
     * @param ModuleDataSetupInterface $eavSetup
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection
     * @param \Magento\Catalog\Model\Product\Action $productAction
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Catalog\Model\Product $product
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $eavSetup,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection,
        \Magento\Catalog\Model\Product\Action $productAction,
        \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Catalog\Model\Product $product,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeFactory,
        \Riki\CreateProductAttributes\Model\InitialData $initialData,
        \Magento\Catalog\Model\Product\Attribute\Repository $productAttributeRepository,
        ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ResourceModel\ProductFactory $productResource
    )
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavSetup = $eavSetup;
        $this->_eavAttribute = $eavAttribute;
        $this->_productCollection = $productCollection;
        $this->_productAction = $productAction;
        $this->attributeRepository = $attributeRepository;
        $this->logger = $logger;
        $this->_product = $product;
        $this->resourceConnection = $resourceConnection;
        $this->_storeManager = $storeManager;
        $this->_attributeFactory = $attributeFactory;
        $this->_dataSetup = $initialData;
        $this->_productAttributeRepository = $productAttributeRepository;
        $this->_productRepository = $productRepository;
        $this->productResource = $productResource;
    }

    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context )
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {

            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);

            $field = 'jan_code';
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);

        }
        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'sku_sap_code';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'type'                    => 'varchar',
                    'input'                   => 'text',
                    'label'                   => 'SKU Sap code',
                    'frontend_class'          => 'validate-length maximum-length-64',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'sku_3pl_code';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'type'                    => 'varchar',
                    'input'                   => 'text',
                    'label'                   => 'SKU 3pl Code',
                    'frontend_class'          => 'validate-length maximum-length-64',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );


            $field = 'description_invoice';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                    => 'Basic',
                    'type'                     => 'text',
                    'input'                    => 'textarea',
                    'label'                    => 'Product Description for Invoiced Customer',
                    'visible'                  => true,
                    'required'                 => false,
                    'wysiwyg_enabled'          => true,
                    'is_html_allowed_on_front' => true,
                    'user_defined'             => true,
                    'searchable'               => false,
                    'filterable'               => false,
                    'comparable'               => false,
                    'visible_on_front'         => true,
                    'used_in_product_listing'  => false,
                    'is_used_in_grid'          => false,
                    'unique'                   => false,
                    'apply_to'                 => ''
                ]
            );

            $field = 'backfront_visibility';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'int',
                    'input'                   => 'select',
                    'label'                   => 'By where order can be received ',
                    'source'                  => 'Riki\CreateProductAttributes\Model\Product\Visibility',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'priority';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'int',
                    'input'                   => 'text',
                    'label'                   => 'Priority Number (Product Recommend Function)',
                    'frontend_class'          => 'validate-number',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'unit_sap';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'int',
                    'input'                   => 'select',
                    'label'                   => 'Unit (EA/CS) in SAP',
                    'source'                  => 'Riki\CreateProductAttributes\Model\Product\UnitSap',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'unit_qty';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'int',
                    'input'                   => 'text',
                    'label'                   => 'Converstion Number (Number of EACH in SAP)',
                    'frontend_class'          => 'validate-number',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'unit_ec';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'int',
                    'input'                   => 'select',
                    'label'                   => 'Unit (EA/CS) for Sales in Magento',
                    'source'                  => 'Riki\CreateProductAttributes\Model\Product\UnitEc',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'master_reference';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'varchar',
                    'input'                   => 'text',
                    'label'                   => 'Master reference',
                    'frontend_class'          => 'validate-number maximum-length-255',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'point_currency';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'int',
                    'input'                   => 'text',
                    'label'                   => 'Shopping Point % ',
                    'frontend_class'          => 'validate-number',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'cod_applicable';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group' => 'Basic',
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
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            // Section Information

            $field = 'ph_code';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'varchar',
                    'input'                   => 'multiselect',
                    'backend'                 => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                    'label'                   => 'PH (x digits) (need for Invoice pepartion for Invoiced customer)',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'ph5_description';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'varchar',
                    'input'                   => 'multiselect',
                    'backend'                 => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                    'label'                   => 'PH5 Description',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'brand';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'varchar',
                    'input'                   => 'multiselect',
                    'backend'                 => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                    'label'                   => 'BH2',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'shelf_life_period';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'decimal',
                    'input'                   => 'text',
                    'label'                   => 'Product Shelf Life period (in days)',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'frontend_class'          => 'validate-zero-or-greater',
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'depth';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'decimal',
                    'input'                   => 'text',
                    'label'                   => 'Dimention / Depth(cm) per each',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'frontend_class'          => 'validate-zero-or-greater',
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'width';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'decimal',
                    'input'                   => 'text',
                    'label'                   => 'Dimention / Wide(cm) per Each',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'frontend_class'          => 'validate-zero-or-greater',
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'height';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'decimal',
                    'input'                   => 'text',
                    'label'                   => 'Dimention / Height(cm) per Each',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'frontend_class'          => 'validate-zero-or-greater',
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );


            $field = 'desc_explanation';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                    => 'Information',
                    'type'                     => 'text',
                    'input'                    => 'textarea',
                    'label'                    => 'Product Description: Explanation',
                    'visible'                  => true,
                    'required'                 => false,
                    'wysiwyg_enabled'          => true,
                    'is_html_allowed_on_front' => true,
                    'user_defined'             => true,
                    'searchable'               => false,
                    'filterable'               => false,
                    'comparable'               => false,
                    'visible_on_front'         => true,
                    'used_in_product_listing'  => false,
                    'is_used_in_grid'          => false,
                    'unique'                   => false,
                    'apply_to'                 => ''
                ]
            );

            $field = 'desc_allergen_mandatory';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                    => 'Information',
                    'type'                     => 'text',
                    'input'                    => 'textarea',
                    'label'                    => 'Product Description: Allergen(Mandatory)',
                    'visible'                  => true,
                    'required'                 => false,
                    'wysiwyg_enabled'          => true,
                    'is_html_allowed_on_front' => true,
                    'user_defined'             => true,
                    'searchable'               => false,
                    'filterable'               => false,
                    'comparable'               => false,
                    'visible_on_front'         => true,
                    'used_in_product_listing'  => false,
                    'is_used_in_grid'          => false,
                    'unique'                   => false,
                    'apply_to'                 => ''
                ]
            );

            $field = 'desc_explanation_recom';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                    => 'Information',
                    'type'                     => 'text',
                    'input'                    => 'textarea',
                    'label'                    => 'Product Description: Allergen(Recommendation)',
                    'visible'                  => true,
                    'required'                 => false,
                    'wysiwyg_enabled'          => true,
                    'is_html_allowed_on_front' => true,
                    'user_defined'             => true,
                    'searchable'               => false,
                    'filterable'               => false,
                    'comparable'               => false,
                    'visible_on_front'         => true,
                    'used_in_product_listing'  => false,
                    'is_used_in_grid'          => false,
                    'unique'                   => false,
                    'apply_to'                 => ''
                ]
            );

            $field = 'desc_ingredient';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                    => 'Information',
                    'type'                     => 'text',
                    'input'                    => 'textarea',
                    'label'                    => 'Product Description: Ingrediencts',
                    'visible'                  => true,
                    'required'                 => false,
                    'wysiwyg_enabled'          => true,
                    'is_html_allowed_on_front' => true,
                    'user_defined'             => true,
                    'searchable'               => false,
                    'filterable'               => false,
                    'comparable'               => false,
                    'visible_on_front'         => true,
                    'used_in_product_listing'  => false,
                    'is_used_in_grid'          => false,
                    'unique'                   => false,
                    'apply_to'                 => ''
                ]
            );

            $field = 'desc_nutrition';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                    => 'Information',
                    'type'                     => 'text',
                    'input'                    => 'textarea',
                    'label'                    => 'Product Description: Nutrition Compass',
                    'visible'                  => true,
                    'required'                 => false,
                    'wysiwyg_enabled'          => true,
                    'is_html_allowed_on_front' => true,
                    'user_defined'             => true,
                    'searchable'               => false,
                    'filterable'               => false,
                    'comparable'               => false,
                    'visible_on_front'         => true,
                    'used_in_product_listing'  => false,
                    'is_used_in_grid'          => false,
                    'unique'                   => false,
                    'apply_to'                 => ''
                ]
            );

            $field = 'desc_supplemental_info';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                    => 'Information',
                    'type'                     => 'text',
                    'input'                    => 'textarea',
                    'label'                    => 'Product Description: Nutrition Compass',
                    'visible'                  => true,
                    'required'                 => false,
                    'wysiwyg_enabled'          => true,
                    'is_html_allowed_on_front' => true,
                    'user_defined'             => true,
                    'searchable'               => false,
                    'filterable'               => false,
                    'comparable'               => false,
                    'visible_on_front'         => true,
                    'used_in_product_listing'  => false,
                    'is_used_in_grid'          => false,
                    'unique'                   => false,
                    'apply_to'                 => ''
                ]
            );

            $field = 'delivery_type';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'int',
                    'input'                   => 'select',
                    'label'                   => 'Availability for Subscription Order by item',
                    'source'                  => 'Riki\CreateProductAttributes\Model\Product\Delivery',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'available_subscription';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'int',
                    'input'                   => 'select',
                    'label'                   => 'Availability for Subscription Order by item',
                    'source'                  => 'Riki\CreateProductAttributes\Model\Product\Available',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'future_price';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Price',
                    'type'                    => 'decimal',
                    'input'                   => 'price',
                    'backend'                 => 'Magento\Catalog\Model\Product\Attribute\Backend\Price',
                    'label'                   => 'Future Basic Selling Price (excl. Consumption Tax)',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'future_price_from';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Price',
                    'type'                    => 'datetime',
                    'input'                   => 'date',
                    'label'                   => 'Future Basic Selling Price (excl. Consumption Tax) effective date & time',
                    'backend'                 => 'Magento\Eav\Model\Entity\Attribute\Backend\Datetime',
                    'frontend'                => 'Magento\Eav\Model\Entity\Attribute\Frontend\Datetime',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => false,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );


            $field = 'GPS_price';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Price',
                    'type'                    => 'decimal',
                    'input'                   => 'price',
                    'backend'                 => 'Magento\Catalog\Model\Product\Attribute\Backend\Price',
                    'label'                   => 'GPS',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'future_gps_price';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Price',
                    'type'                    => 'decimal',
                    'input'                   => 'price',
                    'backend'                 => 'Magento\Catalog\Model\Product\Attribute\Backend\Price',
                    'label'                   => 'Effective Date of GPS application (From)',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'future_gps_price_from';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Price',
                    'type'                    => 'datetime',
                    'input'                   => 'date',
                    'label'                   => 'Effective Date of GPS application (End)',
                    'backend'                 => 'Magento\Eav\Model\Entity\Attribute\Backend\Datetime',
                    'frontend'                => 'Magento\Eav\Model\Entity\Attribute\Frontend\Datetime',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => false,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            // Section Booking

            $field = 'material_type';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Booking',
                    'type'                    => 'int',
                    'input'                   => 'select',
                    'label'                   => 'Material Type',
                    'source'                  => 'Riki\CreateProductAttributes\Model\Product\Material',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'sales_organization';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Booking',
                    'type'                    => 'varchar',
                    'input'                   => 'multiselect',
                    'backend'                 => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                    'label'                   => 'Sales organization',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => '',
                    'option'                  => [
                        'values' => ['JP30', 'JP36']
                    ]
                ]
            );


            $field = 'booking_item_wbs';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Booking',
                    'type'                    => 'varchar',
                    'input'                   => 'text',
                    'label'                   => 'WBS no. (For Sales)',
                    'frontend_class'          => 'validate-length maximum-length-255',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'booking_item_account';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Booking',
                    'type'                    => 'varchar',
                    'input'                   => 'text',
                    'label'                   => 'Account No. (For Sales)',
                    'frontend_class'          => 'validate-length maximum-length-255',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );


            $field = 'booking_profit_center';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Booking',
                    'type'                    => 'varchar',
                    'input'                   => 'text',
                    'label'                   => 'Profit Center. (For Sales)',
                    'frontend_class'          => 'validate-length maximum-length-255',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'booking_point_wbs';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Booking',
                    'type'                    => 'varchar',
                    'input'                   => 'text',
                    'label'                   => 'WBS no. (Shopping Point Expenses)',
                    'frontend_class'          => 'validate-length maximum-length-255',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'booking_point_account';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Booking',
                    'type'                    => 'varchar',
                    'input'                   => 'text',
                    'label'                   => 'Account no. (Shopping Point Expenses)',
                    'frontend_class'          => 'validate-length maximum-length-255',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );


            $field = 'booking_free_wbs';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Booking',
                    'type'                    => 'varchar',
                    'input'                   => 'text',
                    'label'                   => 'WBS no. (Dealing Consumer Complaint)',
                    'frontend_class'          => 'validate-length maximum-length-255',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'booking_free_account';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Booking',
                    'type'                    => 'varchar',
                    'input'                   => 'text',
                    'label'                   => 'Account no. (Dealing Consumer Complaint)',
                    'frontend_class'          => 'validate-length maximum-length-255',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'booking_machine_mt_wbs';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Booking',
                    'type'                    => 'varchar',
                    'input'                   => 'text',
                    'label'                   => 'Accoung no. (Machine Service)',
                    'frontend_class'          => 'validate-length maximum-length-255',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

            $field = 'booking_machine_mt_account';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Booking',
                    'type'                    => 'varchar',
                    'input'                   => 'text',
                    'label'                   => 'Cost Center no. (Machine Service)',
                    'frontend_class'          => 'validate-length maximum-length-255',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''

                ]
            );

        }
        if (version_compare($context->getVersion(), '1.0.4', '<')) {

            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'launch_from';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'datetime',
                    'input'                   => 'date',
                    'label'                   => 'Launch from',
                    'backend'                 => 'Magento\Eav\Model\Entity\Attribute\Backend\Datetime',
                    'frontend'                => 'Magento\Eav\Model\Entity\Attribute\Frontend\Datetime',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => false,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );
            $field = 'launch_to';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'datetime',
                    'input'                   => 'date',
                    'label'                   => 'Launch to',
                    'backend'                 => 'Magento\Eav\Model\Entity\Attribute\Backend\Datetime',
                    'frontend'                => 'Magento\Eav\Model\Entity\Attribute\Frontend\Datetime',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => false,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.6', '<')) {

            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);

            $field = 'delivery_type';


            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'varchar',
                    'input'                   => 'select',
                    'label'                   => 'Delivery Type',
                    'source'                  => 'Riki\DeliveryType\Model\Product\Deliverytype',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

        }
        if (version_compare($context->getVersion(), '1.0.7', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);

            $field = 'price';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                      => 'Price',
                    'type'                       => 'decimal',
                    'label'                      => 'Price',
                    'input'                      => 'price',
                    'backend'                    => 'Magento\Catalog\Model\Product\Attribute\Backend\Price',
                    'sort_order'                 => 1,
                    'global'                     => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'searchable'                 => true,
                    'filterable'                 => true,
                    'visible_in_advanced_search' => true,
                    'used_in_product_listing'    => true,
                    'used_for_sort_by'           => true,
                    'apply_to'                   => 'simple,virtual'
                ]
            );
            $field = 'weight';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                 => 'Information',
                    'type'                  => 'decimal',
                    'label'                 => 'Weight',
                    'input'                 => 'weight',
                    'backend'               => 'Magento\Catalog\Model\Product\Attribute\Backend\Weight',
                    'input_renderer'        => 'Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Weight',
                    'sort_order'            => 5,
                    'apply_to'              => 'simple,virtual',
                    'is_used_in_grid'       => true,
                    'is_visible_in_grid'    => false,
                    'is_filterable_in_grid' => true,
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.0.8', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);

            $field = 'price';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                      => 'Price',
                    'type'                       => 'decimal',
                    'label'                      => 'Price',
                    'input'                      => 'price',
                    'backend'                    => 'Magento\Catalog\Model\Product\Attribute\Backend\Price',
                    'sort_order'                 => 1,
                    'global'                     => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'searchable'                 => true,
                    'filterable'                 => true,
                    'visible_in_advanced_search' => true,
                    'used_in_product_listing'    => true,
                    'used_for_sort_by'           => true,
                    'apply_to'                   => ''
                ]
            );

            $field = 'description';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                      => 'Basic',
                    'type'                       => 'text',
                    'label'                      => 'Description',
                    'input'                      => 'textarea',
                    'sort_order'                 => 3,
                    'global'                     => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'searchable'                 => true,
                    'comparable'                 => true,
                    'wysiwyg_enabled'            => true,
                    'is_html_allowed_on_front'   => true,
                    'visible_in_advanced_search' => true,
                ]
            );

            $field = 'name';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'varchar',
                    'label'                   => 'Name',
                    'input'                   => 'text',
                    'sort_order'              => 1,
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'used_in_product_listing' => true,
                ]

            );

            $field = 'status';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'int',
                    'label'                   => 'Status',
                    'input'                   => 'select',
                    'source'                  => 'Magento\Catalog\Model\Product\Attribute\Source\Status',
                    'sort_order'              => 9,
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'searchable'              => true,
                    'used_in_product_listing' => true,
                ]

            );

            $field = 'sku';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                      => 'Basic',
                    'type'                       => 'static',
                    'label'                      => 'SKU',
                    'input'                      => 'text',
                    'frontend_class'             => 'validate-length maximum-length-64',
                    'backend'                    => 'Magento\Catalog\Model\Product\Attribute\Backend\Sku',
                    'unique'                     => true,
                    'sort_order'                 => 2,
                    'searchable'                 => true,
                    'comparable'                 => true,
                    'visible_in_advanced_search' => true,
                ]
            );

            $field = 'weight';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                 => 'Information',
                    'type'                  => 'decimal',
                    'label'                 => 'Weight',
                    'input'                 => 'weight',
                    'backend'               => 'Magento\Catalog\Model\Product\Attribute\Backend\Weight',
                    'input_renderer'        => 'Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Weight',
                    'sort_order'            => 5,
                    'apply_to'              => '',
                    'is_used_in_grid'       => true,
                    'is_visible_in_grid'    => false,
                    'is_filterable_in_grid' => true,
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.0.9', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);

            $field = 'business_group_code';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field,
                [
                    'group'                    => 'Basic',
                    'type'                     => 'varchar',
                    'input'                    => 'text',
                    'label'                    => 'Business group code',
                    'visible'                  => true,
                    'required'                 => false,
                    'is_html_allowed_on_front' => true,
                    'user_defined'             => true,
                    'searchable'               => false,
                    'filterable'               => false,
                    'comparable'               => false,
                    'visible_on_front'         => true,
                    'used_in_product_listing'  => false,
                    'is_used_in_grid'          => false,
                    'unique'                   => false,
                    'apply_to'                 => ''
                ]
            );

            $field = 'stock_display_type';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field,
                [
                    'group'                    => 'Basic',
                    'type'                     => 'varchar',
                    'input'                    => 'select',
                    'label'                    => 'Stock display type',
                    'visible'                  => true,
                    'required'                 => false,
                    'source'                   => 'Riki\CreateProductAttributes\Model\Product\StockDisplayType',
                    'is_html_allowed_on_front' => true,
                    'user_defined'             => true,
                    'searchable'               => false,
                    'filterable'               => false,
                    'comparable'               => false,
                    'visible_on_front'         => true,
                    'used_in_product_listing'  => false,
                    'is_used_in_grid'          => false,
                    'unique'                   => false,
                    'apply_to'                 => ''
                ]
            );

            $field = 'delivery_type';
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'int',
                    'input'                   => 'select',
                    'label'                   => 'Delivery Type',
                    'source'                  => 'Riki\CreateProductAttributes\Model\Product\Delivery',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );
            $field = 'unit_ec';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'int',
                    'input'                   => 'select',
                    'label'                   => 'Unit (EA/CS) for Sales in Magento',
                    'source'                  => 'Riki\CreateProductAttributes\Model\Product\UnitEc',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.1.0', '<')) {

            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);

            $field = 'delivery_type';


            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'varchar',
                    'input'                   => 'select',
                    'label'                   => 'Delivery Type',
                    'source'                  => 'Riki\DeliveryType\Model\Product\Deliverytype',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

        }
        if (version_compare($context->getVersion(), '1.1.1', '<')) {

            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
//            $field = 'is_free_shipping';
//
//            $eavSetup->addAttribute(
//                \Magento\Catalog\Model\Product::ENTITY, $field, [
//                    'group' => 'Information',
//                    'type' => 'int',
//                    'input' => 'boolean',
//                    'label' => 'Free shipping',
//                    'backend' => 'Magento\Customer\Model\Attribute\Backend\Data\Boolean',
//                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
//                    'visible' => true,
//                    'required' => false,
//                    'default' => 0,
//                    'user_defined' => true,
//                    'searchable' => false,
//                    'filterable' => false,
//                    'comparable' => false,
//                    'visible_on_front' => true,
//                    'used_in_product_listing' => false,
//                    'is_used_in_grid' => false,
//                    'unique' => false,
//                    'apply_to' => ''
//                ]
//            );
        }
        if (version_compare($context->getVersion(), '1.1.2', '<')) {
            $attributeId = $this->_eavAttribute->getIdByCode('catalog_product', 'delivery_type');
            if ($attributeId) {
                $table = $setup->getTable('catalog_product_entity_int');
                if ($setup->getConnection()->isTableExists($table) == true) {
                    $setup->run("DELETE FROM {$table} WHERE attribute_id = {$attributeId} ");
                }
            }
        }
        if (version_compare($context->getVersion(), '1.1.3', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'gps_price';
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'GPS_price');
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Price',
                    'type'                    => 'decimal',
                    'input'                   => 'price',
                    'backend'                 => 'Magento\Catalog\Model\Product\Attribute\Backend\Price',
                    'label'                   => 'GPS',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.1.4', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);

            $field = 'case_display';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'int',
                    'input'                   => 'select',
                    'label'                   => 'Case Display',
                    'source'                  => 'Riki\CreateProductAttributes\Model\Product\CaseDisplay',
                    'sort_order'              => 10,
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );

        }

        if (version_compare($context->getVersion(), '1.1.5', '<')) {

            $table = $setup->getTable('quote_item');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'unit_case',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'Unit Case']
                );
                $setup->getConnection()->addColumn(
                    $table, 'unit_qty',
                    ['type' => Table::TYPE_INTEGER, 10, 'default' => 1, 'comment' => 'Unit Quantity']
                );
            }

        }

        if (version_compare($context->getVersion(), '1.1.6', '<')) {

            $table = $setup->getTable('sales_order_item');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'unit_case',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'Unit Case']
                );
                $setup->getConnection()->addColumn(
                    $table, 'unit_qty',
                    ['type' => Table::TYPE_INTEGER, 10, 'default' => 1, 'comment' => 'Unit Quantity']
                );
            }
        }
        if (version_compare($context->getVersion(), '1.1.7', '<')) {
            $_attributeIgnoreOnImports = array(
                'created_at',
                'giftcard_type',
                'links_purchased_separately',
                'links_title',
                'name',
                'pcs',
                'price',
                'price_type',
                'price_view',
                'samples_title',
                'shipment_type',
                'status',
                'weight',
            );
            $eavSetup                  = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            foreach ($_attributeIgnoreOnImports as $field) {
                $eavSetup->updateAttribute(
                    \Magento\Catalog\Model\Product::ENTITY, $field, [
                        'is_required' => false,
                    ]
                );
            }


        }

        if (version_compare($context->getVersion(), '1.1.8', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);

            $field = 'credit_card_only';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'int',
                    'input'                   => 'boolean',
                    'label'                   => 'Credit Card Only',
                    'visible'                 => true,
                    'required'                => false,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => false,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => '',
                    'sort_order'              => 24,
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.1.9', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'booking_machine_mt_center';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Booking',
                    'type'                    => 'varchar',
                    'input'                   => 'text',
                    'label'                   => 'Booking Machine Mt Center',
                    'frontend_class'          => 'validate-length maximum-length-255',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''

                ]
            );
        }

        if (version_compare($context->getVersion(), '1.1.10', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'weight_unit';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'varchar',
                    'input'                   => 'select',
                    'label'                   => 'Weight Unit',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                ]
            );

            $field = 'dimension_unit';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'varchar',
                    'input'                   => 'select',
                    'label'                   => 'Dimension Unit',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                ]
            );

            $field = 'ph1_description';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'varchar',
                    'input'                   => 'select',
                    'label'                   => 'Ph1 Description',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                ]
            );

            $field = 'ph2_description';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'varchar',
                    'input'                   => 'select',
                    'label'                   => 'Ph2 Description',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                ]
            );

            $field = 'ph3_description';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'varchar',
                    'input'                   => 'select',
                    'label'                   => 'Ph3 Description',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                ]
            );

            $field = 'ph4_description';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'varchar',
                    'input'                   => 'select',
                    'label'                   => 'Ph4 Description',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                ]
            );

            $field = 'ph5_description';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'varchar',
                    'input'                   => 'select',
                    'label'                   => 'Ph5 Description',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                ]
            );

            $field = 'bh_sap';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'varchar',
                    'input'                   => 'select',
                    'label'                   => 'BH Sap',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                ]
            );

            $field = 'gps_price_ec';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Price',
                    'type'                    => 'decimal',
                    'input'                   => 'price',
                    'label'                   => 'GPS Price Ec',
                    'backend'                 => 'Magento\Catalog\Model\Product\Attribute\Backend\Price',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                ]
            );

            $field = 'future_gps_price_ec';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Price',
                    'type'                    => 'decimal',
                    'input'                   => 'price',
                    'label'                   => 'Future GPS Price Ec',
                    'backend'                 => 'Magento\Catalog\Model\Product\Attribute\Backend\Price',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                ]
            );

        }

        if (version_compare($context->getVersion(), '1.1.11', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'desc_supplemental_info';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'varchar',
                    'input'                   => 'text',
                    'label'                   => 'Product Description: EC supplemental info',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => true,
                    'filterable'              => true,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false
                ]
            );

            $field = 'allow_spot_order';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'int',
                    'input'                   => 'boolean',
                    'label'                   => 'Allow Spot Order',
                    'backend'                 => 'Magento\Customer\Model\Attribute\Backend\Data\Boolean',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => true,
                    'filterable'              => true,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false
                ]
            );


        }

        if (version_compare($context->getVersion(), '1.1.12', '<')) {

            /// https://rikibusiness.atlassian.net/wiki/display/MS/Data+model+-+Product  VER 24
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);

            $field = 'bh_sap';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'frontend_label' => 'BH2 SAP',
                    'label'          => 'BH2 SAP',
                    'global'         => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE
                ]
            );

            $field = 'booking_free_account';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'frontend_label' => 'Account no. (Replacement)',
                    'label'          => 'Account no. (Replacement)',
                    'global'         => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                ]
            );

            $field = 'booking_free_wbs';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'label'          => 'WBS no. (Replacement)',
                    'frontend_label' => 'WBS no. (Replacement)',
                    'global'         => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL
                ]
            );

            $field = 'booking_machine_mt_account';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'frontend_label' => 'Account no. (Machine Related)',
                    'label'          => 'Account no. (Machine Related)',
                    'validate_rules' => 'a:1:{s:15:"max_text_length";i:30;}',
                    'global'         => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL
                ]
            );

            $field = 'booking_machine_mt_center';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'frontend_label' => 'Cost Center no. (Machine Related)',
                    'label'          => 'Cost Center no. (Machine Related)',
                    'validate_rules' => 'a:1:{s:15:"max_text_length";i:30;}',
                ]
            );

            $field = 'dimension_unit';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'frontend_label' => 'Unit of dimension',
                    'label'          => 'Unit of dimension',
                    'global'         => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE
                ]
            );


            $field = 'future_gps_price';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'frontend_label' => 'Future Effective Date of GPS',
                    'label'          => 'Future Effective Date of GPS'
                ]
            );

            $field = 'future_gps_price_from';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'frontend_label' => 'Effective Date of GPS application (From)',
                    'label'          => 'Effective Date of GPS application (From)'
                ]
            );


            $field = 'gps_price_ec';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'frontend_label' => 'GPS price for EA',
                    'label'          => 'GPS price for EA'
                ]
            );

            $field = 'master_reference';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'frontend_label' => 'Master reference (Variant parent product)',
                    'label'          => 'Master reference (Variant parent product)',
                    'global'         => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL
                ]
            );
            $field = 'name';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'frontend_label' => 'Product name',
                    'label'          => 'Product name'
                ]
            );

            $field = 'price';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'frontend_label' => 'Basic Selling Price',
                    'label'          => 'Basic Selling Price'
                ]
            );

            $field = 'weight';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'frontend_label' => 'Dimention / Weight(g) per Each',
                    'label'          => 'Dimention / Weight(g) per Each'
                ]
            );

        }

        if (version_compare($context->getVersion(), '1.1.13', '<')) {

            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);

            $attribute = 'pcss';

            $attributeId = $this->_eavAttribute->getIdByCode('catalog_product', $attribute);

            if ($attributeId) {
                $table = $setup->getTable('catalog_product_super_attribute');
                if ($setup->getConnection()->isTableExists($table) == true) {
                    $setup->run("DELETE FROM {$table} WHERE attribute_id = {$attributeId} ");
                }
            }

            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $attribute);

            $attribute = 'asdfsdf';

            $attributeId = $this->_eavAttribute->getIdByCode('catalog_product', $attribute);

            if ($attributeId) {
                $table = $setup->getTable('catalog_product_super_attribute');
                if ($setup->getConnection()->isTableExists($table) == true) {
                    $setup->run("DELETE FROM {$table} WHERE attribute_id = {$attributeId} ");
                }
            }

            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $attribute);

        }

        if (version_compare($context->getVersion(), '1.1.14', '<')) {

            /// https://rikibusiness.atlassian.net/wiki/display/MS/Data+model+-+Product  VER 24
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);

            $field = 'case_display';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'frontend_label' => 'Piece Case display',
                    'label'          => 'Piece Case display',
                    'global'         => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'is_required'    => true,
                ]
            );

            $field = 'weight_unit';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'frontend_label' => 'Unit of weight',
                    'label'          => 'Unit of weight'
                ]
            );

            $field = 'price';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'is_required' => true,
                ]
            );
            $field = 'material_type';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'is_required' => true,
                ]
            );

            $field = 'sales_organization';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'is_required' => true,
                ]
            );


            $field = 'stock_display_type';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'is_required' => true,
                ]
            );

            $field = 'name';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'is_required' => true,
                ]
            );

            $field = 'status';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'is_required' => true,
                ]
            );


            $attribute = 'ph_3_description';

            $attributeId = $this->_eavAttribute->getIdByCode('catalog_product', $attribute);

            if ($attributeId) {
                $table = $setup->getTable('catalog_product_super_attribute');
                if ($setup->getConnection()->isTableExists($table) == true) {
                    $setup->run("DELETE FROM {$table} WHERE attribute_id = {$attributeId} ");
                }
            }

            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $attribute);

        }

        if (version_compare($context->getVersion(), '1.1.15', '<')) {

            $table = $setup->getTable('sales_shipment_item');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'unit_case',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 'length' => 255, 'default' => 'EA', 'comment' => 'Unit Case']
                );
                $setup->getConnection()->addColumn(
                    $table, 'unit_qty',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL, '12,4', 'default' => 1.0, 'comment' => 'Unit Quantity']
                );
            }

            $table = $setup->getTable('sales_invoice_item');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'unit_case',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 'length' => 255, 'default' => 'EA', 'comment' => 'Unit Case']
                );
                $setup->getConnection()->addColumn(
                    $table, 'unit_qty',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL, '12,4', 'default' => 1.0, 'comment' => 'Unit Quantity']
                );
            }

            $table = $setup->getTable('sales_creditmemo_item');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'unit_case',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 'length' => 255, 'default' => 'EA', 'comment' => 'Unit Case']
                );
                $setup->getConnection()->addColumn(
                    $table, 'unit_qty',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL, '12,4', 'default' => 1.0, 'comment' => 'Unit Quantity']
                );
            }

            $table = $setup->getTable('sales_order_item');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'unit_case',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 'length' => 255, 'default' => 'EA', 'comment' => 'Unit Case']
                );
                $setup->getConnection()->addColumn(
                    $table, 'unit_qty',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL, '12,4', 'default' => 1.0, 'comment' => 'Unit Quantity']
                );
            }

        }

        if (version_compare($context->getVersion(), '1.1.16', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'allow_spot_order';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'used_in_product_listing' => true
                ]
            );
        }

        //update data default allow_spot_order = true;
        if (version_compare($context->getVersion(), '1.1.17', '<')) {
            $attributeId = $this->_eavAttribute->getIdByCode('catalog_product', 'allow_spot_order');
            $table       = $setup->getTable('catalog_product_entity_int');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->run("UPDATE  {$table} SET value = 1  WHERE attribute_id = {$attributeId} ");
            }
        }
        if (version_compare($context->getVersion(), '1.1.18', '<')) {

            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);

            $field = 'case_display';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'is_required' => true,
                ]
            );


            $field = 'price';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'is_required' => true,
                ]
            );
            $field = 'material_type';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'is_required' => true,
                ]
            );

            $field = 'sales_organization';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'is_required' => true,
                ]
            );


            $field = 'stock_display_type';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'is_required' => true,
                ]
            );

            $field = 'name';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'is_required' => true,
                ]
            );

            $field = 'delivery_type';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'is_required' => true,
                ]
            );
            $field = 'status';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'is_required' => true,
                ]
            );

        }

        if (version_compare($context->getVersion(), '1.1.19', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'allow_spot_order';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'default_value' => '1'
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.1.20', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'case_display';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'used_in_product_listing' => true
                ]
            );

            $field = 'unit_qty';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'used_in_product_listing' => true
                ]
            );

        }

        if (version_compare($context->getVersion(), '2.0.0', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'old_product_id';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'text',
                    'input'                   => 'text',
                    'label'                   => 'Old Product ID',
                    'visible'                 => false,
                    'required'                => false,
                    'user_defined'            => false,
                    'frontend_class'          => '',
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => false,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );
        }
        if (version_compare($context->getVersion(), '2.0.1', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'allow_seasonal_skip';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'int',
                    'input'                   => 'select',
                    'source'                  => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'label'                   => 'Allow seasonal skip',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => false,
                    'frontend_class'          => '',
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => false,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );
            $field = 'seasonal_skip_optional';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'int',
                    'input'                   => 'select',
                    'source'                  => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'label'                   => 'Seasonal skip optional',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => false,
                    'frontend_class'          => '',
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => false,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );
            $field = 'allow_skip_from';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'datetime',
                    'input'                   => 'date',
                    'label'                   => 'Allow skip from',
                    'backend'                 => 'Magento\Eav\Model\Entity\Attribute\Backend\Datetime',
                    'frontend'                => 'Magento\Eav\Model\Entity\Attribute\Frontend\Datetime',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => false,
                    'frontend_class'          => '',
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => false,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );
            $field = 'allow_skip_to';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'datetime',
                    'input'                   => 'date',
                    'label'                   => 'Allow skip to',
                    'backend'                 => 'Magento\Eav\Model\Entity\Attribute\Backend\Datetime',
                    'frontend'                => 'Magento\Eav\Model\Entity\Attribute\Frontend\Datetime',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => false,
                    'frontend_class'          => '',
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => false,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );
            $field = 'desc_content';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                    => 'Information',
                    'type'                     => 'text',
                    'input'                    => 'textarea',
                    'label'                    => 'Product Description: Content',
                    'sort_order'               => 12,
                    'visible'                  => true,
                    'required'                 => false,
                    'wysiwyg_enabled'          => true,
                    'is_html_allowed_on_front' => true,
                    'user_defined'             => true,
                    'searchable'               => false,
                    'filterable'               => false,
                    'comparable'               => false,
                    'visible_on_front'         => true,
                    'used_in_product_listing'  => false,
                    'is_used_in_grid'          => false,
                    'unique'                   => false,
                    'apply_to'                 => ''
                ]
            );

            $field = 'desc_supplemental_info';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'varchar',
                    'label'                   => 'Product Description: EC supplemental info',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => true,
                    'filterable'              => true,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'input'                   => 'textarea',
                    'wysiwyg_enabled'         => true,
                ]
            );

            $field = 'ph5_description';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'varchar',
                    'input'                   => 'select',
                    'label'                   => 'Ph5 Description',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'sort_order'              => 24,
                ]
            );

            $field = 'ph_code';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'sort_order' => 25,
                ]
            );

            $field = 'brand';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'varchar',
                    'input'                   => 'multiselect',
                    'backend'                 => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                    'label'                   => 'PH (x digits) (need for Invoice pepartion for Invoiced customer)',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => '',
                    'sort_order'              => 26,
                ]
            );
        }
        if (version_compare($context->getVersion(), '2.0.2', '<')) {

            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);

            $field = 'brand';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'varchar',
                    'input'                   => 'multiselect',
                    'backend'                 => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                    'label'                   => 'BH2',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => '',
                    'sort_order'              => 27,
                ]
            );

            $field = 'ph_code';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'varchar',
                    'input'                   => 'multiselect',
                    'backend'                 => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                    'label'                   => 'PH (x digits) (need for Invoice pepartion for Invoiced customer)',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'sort_order'              => 29,
                    'apply_to'                => ''
                ]
            );

            $field = 'booking_machine_mt_account';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Booking',
                    'type'                    => 'varchar',
                    'input'                   => 'text',
                    'frontend_class'          => 'validate-length maximum-length-255',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => '',
                    'frontend_label'          => 'Account no. (Machine Related)',
                    'label'                   => 'Account no. (Machine Related)',
                    'validate_rules'          => 'a:1:{s:15:"max_text_length";i:255;}',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL
                ]
            );

            $field = 'allow_spot_order';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Booking',
                    'type'                    => 'int',
                    'input'                   => 'boolean',
                    'label'                   => 'Allow Spot Order',
                    'backend'                 => 'Magento\Customer\Model\Attribute\Backend\Data\Boolean',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => true,
                    'filterable'              => true,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'default_value'           => 1,
                    'used_in_product_listing' => false
                ]
            );
        }
        if (version_compare($context->getVersion(), '2.0.3', '<')) {
            $eavSetup    = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $attributeId = $this->_eavAttribute->getIdByCode('catalog_product', 'allow_spot_order');
            $table       = $setup->getTable('catalog_product_entity_int');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->run("UPDATE  {$table} SET value = 1  WHERE attribute_id = {$attributeId} ");
            }
        }
        if (version_compare($context->getVersion(), '2.0.4', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'allow_spot_order';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Booking',
                    'type'                    => 'int',
                    'input'                   => 'boolean',
                    'label'                   => 'Allow Spot Order',
                    'backend'                 => 'Magento\Customer\Model\Attribute\Backend\Data\Boolean',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => true,
                    'filterable'              => true,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'default_value'           => '1',
                    'used_in_product_listing' => true
                ]
            );
            $attributeId = $this->_eavAttribute->getIdByCode('catalog_product', 'allow_spot_order');
            $table       = $setup->getTable('catalog_product_entity_int');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->run("UPDATE  {$table} SET value = 1  WHERE attribute_id = {$attributeId} ");
            }
        }

        if (version_compare($context->getVersion(), '2.0.5', '<')) {

            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'desc_supplemental_info';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                    => 'Information',
                    'type'                     => 'text',
                    'label'                    => 'Product Description: EC supplemental info',
                    'global'                   => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                  => true,
                    'required'                 => false,
                    'user_defined'             => true,
                    'searchable'               => true,
                    'filterable'               => true,
                    'comparable'               => false,
                    'visible_on_front'         => true,
                    'used_in_product_listing'  => false,
                    'input'                    => 'textarea',
                    'wysiwyg_enabled'          => true,
                    'is_html_allowed_on_front' => true
                ]
            );

            $field = 'desc_explanation';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                    => 'Information',
                    'type'                     => 'text',
                    'input'                    => 'textarea',
                    'label'                    => 'Product Description: Explanation',
                    'visible'                  => true,
                    'required'                 => false,
                    'wysiwyg_enabled'          => true,
                    'is_html_allowed_on_front' => true,
                    'user_defined'             => true,
                    'searchable'               => false,
                    'filterable'               => false,
                    'comparable'               => false,
                    'visible_on_front'         => true,
                    'used_in_product_listing'  => false,
                    'is_used_in_grid'          => false,
                    'unique'                   => false,
                    'apply_to'                 => '',
                    'is_html_allowed_on_front' => true
                ]
            );

            $field = 'desc_allergen_mandatory';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                    => 'Information',
                    'type'                     => 'text',
                    'input'                    => 'textarea',
                    'label'                    => 'Product Description: Allergen(Mandatory)',
                    'visible'                  => true,
                    'required'                 => false,
                    'wysiwyg_enabled'          => true,
                    'is_html_allowed_on_front' => true,
                    'user_defined'             => true,
                    'searchable'               => false,
                    'filterable'               => false,
                    'comparable'               => false,
                    'visible_on_front'         => true,
                    'used_in_product_listing'  => false,
                    'is_used_in_grid'          => false,
                    'unique'                   => false,
                    'apply_to'                 => '',
                    'is_html_allowed_on_front' => true
                ]
            );

            $field = 'desc_explanation_recom';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                    => 'Information',
                    'type'                     => 'text',
                    'input'                    => 'textarea',
                    'label'                    => 'Product Description: Allergen(Recommendation)',
                    'visible'                  => true,
                    'required'                 => false,
                    'wysiwyg_enabled'          => true,
                    'is_html_allowed_on_front' => true,
                    'user_defined'             => true,
                    'searchable'               => false,
                    'filterable'               => false,
                    'comparable'               => false,
                    'visible_on_front'         => true,
                    'used_in_product_listing'  => false,
                    'is_used_in_grid'          => false,
                    'unique'                   => false,
                    'apply_to'                 => '',
                    'is_html_allowed_on_front' => true
                ]
            );

            $field = 'desc_ingredient';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                    => 'Information',
                    'type'                     => 'text',
                    'input'                    => 'textarea',
                    'label'                    => 'Product Description: Ingrediencts',
                    'visible'                  => true,
                    'required'                 => false,
                    'wysiwyg_enabled'          => true,
                    'is_html_allowed_on_front' => true,
                    'user_defined'             => true,
                    'searchable'               => false,
                    'filterable'               => false,
                    'comparable'               => false,
                    'visible_on_front'         => true,
                    'used_in_product_listing'  => false,
                    'is_used_in_grid'          => false,
                    'unique'                   => false,
                    'apply_to'                 => '',
                    'is_html_allowed_on_front' => true
                ]
            );

            $field = 'desc_nutrition';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                    => 'Information',
                    'type'                     => 'text',
                    'input'                    => 'textarea',
                    'label'                    => 'Product Description: Nutrition Compass',
                    'visible'                  => true,
                    'required'                 => false,
                    'wysiwyg_enabled'          => true,
                    'is_html_allowed_on_front' => true,
                    'user_defined'             => true,
                    'searchable'               => false,
                    'filterable'               => false,
                    'comparable'               => false,
                    'visible_on_front'         => true,
                    'used_in_product_listing'  => false,
                    'is_used_in_grid'          => false,
                    'unique'                   => false,
                    'apply_to'                 => '',
                    'is_html_allowed_on_front' => true
                ]
            );

            $field = 'desc_content';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                    => 'Information',
                    'type'                     => 'text',
                    'input'                    => 'textarea',
                    'label'                    => 'Product Description: Content',
                    'sort_order'               => 12,
                    'visible'                  => true,
                    'required'                 => false,
                    'wysiwyg_enabled'          => true,
                    'is_html_allowed_on_front' => true,
                    'user_defined'             => true,
                    'searchable'               => false,
                    'filterable'               => false,
                    'comparable'               => false,
                    'visible_on_front'         => true,
                    'used_in_product_listing'  => false,
                    'is_used_in_grid'          => false,
                    'unique'                   => false,
                    'apply_to'                 => '',
                    'is_html_allowed_on_front' => true
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.0.5.1', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'allow_seasonal_skip';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'int',
                    'input'                   => 'select',
                    'source'                  => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'label'                   => 'Allow seasonal skip',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => false,
                    'frontend_class'          => '',
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => false,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );
            $field = 'seasonal_skip_optional';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'int',
                    'input'                   => 'select',
                    'source'                  => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'label'                   => 'Seasonal skip optional',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => false,
                    'frontend_class'          => '',
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => false,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );
            $field = 'allow_skip_from';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'datetime',
                    'input'                   => 'date',
                    'label'                   => 'Allow skip from',
                    'backend'                 => 'Magento\Eav\Model\Entity\Attribute\Backend\Datetime',
                    'frontend'                => 'Magento\Eav\Model\Entity\Attribute\Frontend\Datetime',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => false,
                    'frontend_class'          => '',
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => false,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );
            $field = 'allow_skip_to';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Basic',
                    'type'                    => 'datetime',
                    'input'                   => 'date',
                    'label'                   => 'Allow skip to',
                    'backend'                 => 'Magento\Eav\Model\Entity\Attribute\Backend\Datetime',
                    'frontend'                => 'Magento\Eav\Model\Entity\Attribute\Frontend\Datetime',
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => false,
                    'frontend_class'          => '',
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => false,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.0.6', '<')) {
            $field    = 'spot_allow_subscription';
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Booking',
                    'type'                    => 'int',
                    'input'                   => 'boolean',
                    'label'                   => 'Allow Spot Subscription',
                    'backend'                 => 'Magento\Customer\Model\Attribute\Backend\Data\Boolean',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => true,
                    'filterable'              => true,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'default_value'           => 0,
                    'used_in_product_listing' => false
                ]
            );
        }
        if (version_compare($context->getVersion(), '2.0.7') < 0) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, 'sap_interface_excluded', [
                    'group'                   => 'Basic',
                    'type'                    => 'int',
                    'input'                   => 'boolean',
                    'label'                   => 'SAP interface excluded',
                    'backend'                 => 'Magento\Customer\Model\Attribute\Backend\Data\Boolean',
                    'visible'                 => true,
                    'required'                => false,
                    'default'                 => 0,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => false,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => true,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );
        }
        if (version_compare($context->getVersion(), '2.0.8', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'allow_spot_order';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'default_value' => '1'
                ]
            );
        }
        if (version_compare($context->getVersion(), '2.0.9', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'unit_ec';
            $eavSetup->removeAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field
            );
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'asdads';
            $eavSetup->removeAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field
            );
        }

        if (version_compare($context->getVersion(), '2.0.10', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'spot_allow_subscription';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'is_filterable_in_search' => '1',
                    'frontend_input'          => 'select',
                    'source_model'            => '\Magento\Eav\Model\Entity\Attribute\Source\Boolean'
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.0.11') < 0) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'sap_interface_excluded';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'frontend_label' => 'SAP interface excluded flag'
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.0.12') < 0) {
            // attributes used for Document No 3.1.1
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'filter_part_applicable';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'int',
                    'input'                   => 'boolean',
                    'label'                   => 'Filter part applicable',
                    'backend'                 => 'Magento\Customer\Model\Attribute\Backend\Data\Boolean',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => true,
                    'filterable'              => true,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'default_value'           => 0,
                    'used_in_product_listing' => false,
                    'position'                => 10
                ]
            );
            $field = 'filter_part_number';
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'int',
                    'input'                   => 'text',
                    'label'                   => 'Filter part number',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => true,
                    'filterable'              => true,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'default_value'           => 0,
                    'used_in_product_listing' => false,
                    'position'                => 11,
                    'frontend_class'          => 'validate-number'
                ]
            );

            $field      = 'is_free_shipping';
            $entityType = \Magento\Catalog\Model\Product::ENTITY;

            try {

                $attribute = $this->attributeRepository->get($entityType, $field);
                if ($attribute->getAttributeId()) {
                    $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $field);
                }

            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                //  attribute is not exists
                $this->logger->info('Free Shipping attribute was removed');
            }

        }

        if (version_compare($context->getVersion(), '2.0.13') < 0) {

            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);

            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, 'booking_item_wbs', [
                    'used_in_product_listing' => true
                ]
            );

            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, 'booking_free_wbs', [
                    'used_in_product_listing' => true
                ]
            );

            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, 'booking_machine_mt_account', [
                    'used_in_product_listing' => true
                ]
            );

            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, 'booking_machine_mt_center', [
                    'used_in_product_listing' => true
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.0.14', '<')) {

            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'is_free_shipping';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Information',
                    'type'                    => 'int',
                    'input'                   => 'boolean',
                    'label'                   => 'Free shipping',
                    'backend'                 => 'Magento\Customer\Model\Attribute\Backend\Data\Boolean',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'default'                 => 0,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );
        }
        if (version_compare($context->getVersion(), '2.0.15', '<')) {
            $attribute = $this->_product->getResource()->getAttribute('priority');
            /**
             * @var \Magento\Eav\Setup\EavSetup $eavSetup
             */
            if ($attribute != false) {
                try {
                    $catalogSetup   = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
                    $entityTypeId   = $catalogSetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
                    $attributeSetId = $catalogSetup->getAttributeSetId($entityTypeId, 'Default');
                    $catalogSetup->addAttributeToGroup(
                        $entityTypeId,
                        $attributeSetId,
                        'Basic',
                        $attribute->getAttributeId(),
                        60
                    );
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->logger->debug($e->getMessage());
                }
            }
        }
        if (version_compare($context->getVersion(), '2.0.16', '<')) {
            $attribute = $this->_product->getResource()->getAttribute('brand');
            /**
             * @var \Magento\Eav\Setup\EavSetup $eavSetup
             */
            if ($attribute != false) {
                try {
                    $catalogSetup   = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
                    $entityTypeId   = $catalogSetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
                    $attributeSetId = $catalogSetup->getAttributeSetId($entityTypeId, 'Default');
                    $catalogSetup->addAttributeToGroup(
                        $entityTypeId,
                        $attributeSetId,
                        'Information',
                        $attribute->getAttributeId(),
                        27
                    );
                    $catalogSetup->cleanCache();
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->logger->debug($e->getMessage());
                }
            }
        }
        if (version_compare($context->getVersion(), '2.0.17', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'sales_organization';
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'input'          => 'select',
                    'frontend_input' => 'select',
                ]
            );
            $eavSetup->cleanCache();
        }

        if (version_compare($context->getVersion(), '2.0.18', '<')) {
            //update unit sap

            $connection       = $this->resourceConnection->getConnection();
            $productEntity    = $connection->getTableName('catalog_product_entity');
            $productEntityInt = $connection->getTableName('catalog_product_entity_int');
            $attributeId      = $this->_eavAttribute->getIdByCode('catalog_product', 'unit_sap');

            if ($attributeId) {
                $sqlCustom
                                  = "SELECT p.entity_id FROM $productEntity AS p 
                              WHERE p.entity_id NOT IN (
                                    SELECT p1.entity_id FROM $productEntity p1 ,$productEntityInt c 
                                    WHERE p1.entity_id = c.entity_id AND c.attribute_id = 244
                              )";
                $productIdNotExit = $connection->fetchAll($sqlCustom);
                if (is_array($productIdNotExit) && count($productIdNotExit) > 0) {
                    $arrData = [];
                    foreach ($productIdNotExit as $productId) {
                        $arrData[] = [
                            'attribute_id' => $attributeId,
                            'store_id'     => 1,
                            'entity_id'    => $productId['entity_id'],
                            'value'        => 1
                        ];
                    }
                    if (is_array($arrData) && count($arrData) > 0) {
                        $connection->insertMultiple($productEntityInt, $arrData);
                    }
                }
            }
        }
        if (version_compare($context->getVersion(), '2.0.20', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $attriName = 'product_group';
            if(!$eavSetup->getAttribute(\Magento\Catalog\Model\Product::ENTITY,$attriName)){
                try{
                $eavSetup->addAttribute(
                    \Magento\Catalog\Model\Product::ENTITY, $attriName, [
                        'group' => 'Information',
                        'type' => 'varchar',
                        'input' => 'select',
                        'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                        'label' => 'Product Group',
                        'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                        'visible' => true,
                        'required' => false,
                        'user_defined' => true,
                        'searchable' => false,
                        'filterable' => false,
                        'comparable' => false,
                        'visible_on_front' => true,
                        'used_in_product_listing' => true,
                        'is_used_in_grid' => false,
                        'unique' => false,
                        'sort_order' => 26,
                        'apply_to' => ''
                    ]
                );
                }catch(\Exception $e){
                    echo $e->getMessage();
                }
            }
            //get old options
            $groupOptions = $this->_productAttributeRepository->get($attriName)->getOptions();
            $oldData = array();
            foreach ($groupOptions as $gOption) {
                if( $gOption->getLabel()){
                    $oldData[] = $gOption->getLabel();  // Label
                }
            }
            //add options to product_group attribute
            $atrData= $eavSetup->getAttribute(\Magento\Catalog\Model\Product::ENTITY,$attriName);
            $allStores = $this->_storeManager->getStores();
            $option=array();
            $option['attribute_id'] = $atrData['attribute_id'];
            $groupData = $this->_dataSetup->getProductGroups(2, $oldData);
            if($groupData){
                foreach($groupData as $groupName){
                    $option['value'][$groupName][0]=$groupName;
                    foreach($allStores as $store){
                        $option['value'][$groupName][$store->getId()] = $groupName;
                    }
                }
                $eavSetup->addAttributeOption($option);
            }
            //add option for ph code;
            $attributeName = 'ph_code';
            $phOptions = $this->_productAttributeRepository->get($attributeName)->getOptions();
            $oldPhData = array();
            foreach ($phOptions as $phOption) {
                if($phOption->getLabel()){
                    $oldPhData[] = $phOption->getLabel();  // Label
                }
            }
            $phAttriData = $eavSetup->getAttribute(\Magento\Catalog\Model\Product::ENTITY,$attributeName);
            $option=array();
            $option['attribute_id'] = $phAttriData['attribute_id'];
            $phData = $this->_dataSetup->getProductGroups(3, $oldPhData);
            if($phData){
                foreach($phData as $phName){
                    $option['value'][$phName][0]=(string)$phName;;
                    foreach($allStores as $store){
                        if($phName){
                            $option['value'][$phName][$store->getId()] = (string)$phName;
                        }
                    }
                }
                try{
                $eavSetup->addAttributeOption($option);
                }catch (\Exception $e){
                    $this->logger->critical($e);
                }
            }
            // update product
            $dataProducts = $this->_dataSetup->getCsvContent();
            $groupOptions = $this->_productAttributeRepository->get($attriName)->getOptions();
            foreach($dataProducts as $_data) {
                $sku = $_data[0];
                $attributeId = $this->getAttributeId($_data[2], $groupOptions);
                if ($attributeId) {
                    try {
                        $product = $this->_productRepository->get($sku);
                        if ($product->getId()) {
                            $product->setData($attriName, $attributeId);
                            $productResource = $this->productResource->create();
                            $productResource->saveAttribute($product, $attriName);
                        }
                    } catch (\Exception $e) {
                        $this->logger->info('product sku does not exit : ' . $sku);
                    }
                }
            }
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $field    = 'meta_keyword_internal';

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                    => 'Search Engine Optimization',
                    'type'                     => 'text',
                    'input'                    => 'textarea',
                    'label'                    => 'meta keyword internal',
                    'visible'                  => true,
                    'required'                 => false,
                    'default'                  => '',
                    'user_defined'             => true,
                    'used_in_product_listing'  => false,
                    'is_used_in_grid'          => false,
                    'unique'                   => false,
                    'global'                   => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'apply_to'                 => '',
                    'wysiwyg_enabled'          => false,
                    'is_html_allowed_on_front' => false,
                    'searchable'               => true,
                    'filterable'               => false,
                    'comparable'               => false,
                    'visible_on_front'         => true,
                ]
            );

            $connection             = $this->resourceConnection->getConnection();
            $productEntity          = $connection->getTableName('catalog_product_entity');
            $productEntityText      = $connection->getTableName('catalog_product_entity_text');
            $attributeId            = $this->_eavAttribute->getIdByCode('catalog_product', 'meta_keyword_internal');
            $attributeMetaKeywordId = $this->_eavAttribute->getIdByCode('catalog_product', 'meta_keyword');

            if ($attributeId && $attributeMetaKeywordId) {
                $sqlCustom
                                  = "SELECT p.entity_id FROM $productEntity AS p 
                              WHERE p.entity_id NOT IN (
                                    SELECT p1.entity_id FROM $productEntity p1 ,$productEntityText c 
                                    WHERE p1.entity_id = c.entity_id AND c.attribute_id = $attributeId
                              )";
                $productIdNotExit = $connection->fetchAll($sqlCustom);
                if (is_array($productIdNotExit) && count($productIdNotExit) > 0) {
                    $arrData = [];
                    foreach ($productIdNotExit as $productId) {
                        $arrData[] = [
                            'attribute_id' => $attributeId,
                            'store_id'     => 0,
                            'entity_id'    => $productId['entity_id'],
                            'value'        => ''
                        ];
                    }
                    if (is_array($arrData) && count($arrData) > 0) {
                        $connection->insertMultiple($productEntityText, $arrData);
                    }
                }

                $sqlCustom
                    = "UPDATE $productEntityText AS a, 
                              (SELECT b.* from $productEntityText as b 
                              WHERE b.attribute_id = $attributeMetaKeywordId) AS c
                              SET a.value = c.value 
                              WHERE a.entity_id = c.entity_id and a.attribute_id = $attributeId";
                $connection->query($sqlCustom);

            }
        }
        if (version_compare($context->getVersion(), '2.0.21', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'allow_stock_point',
                [
                    'group'                   => 'Basic',
                    'type'                    => 'int',
                    'input'                   => 'boolean',
                    'label'                   => 'Stock Point Allow',
                    'backend'                 => 'Magento\Customer\Model\Attribute\Backend\Data\Boolean',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'default'                 => 0,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => true,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'apply_to'                => ''
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.0.22', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\product::ENTITY,
                'allow_stock_point',
                'is_global',
                \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL
            );
        }

        if (version_compare($context->getVersion(), '2.0.23', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\product::ENTITY,
                'allow_stock_point',
                [
                    'frontend_label' => 'Allow Stock Point',
                    'frontend_input' => 'select',
                    'backend_model' => new \Zend_Db_Expr('NULL'),
                    'source_model' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'is_user_defined' => false,
                ]
            );
        }
        if (version_compare($context->getVersion(), '2.1.1', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\product::ENTITY,
                'product_group',
                [
                    'is_user_defined' => false,
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'backend_type' => 'varchar'
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.1.2', '<')) {
            $field = "machine_categories";
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Product Details',
                    'type'                    => 'varchar',
                    'input'                   => 'multiselect',
                    'backend'                 => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                    'label'                   => 'Machine Categories',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'source'                  => \Riki\MachineApi\Model\B2CMachineSkus\Config\MachineTypeList::class,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'sort_order'              => 29,
                    'apply_to'                => ''
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.1.2', '<')) {
            $field = "machine_categories";
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, $field, [
                    'group'                   => 'Product Details',
                    'type'                    => 'varchar',
                    'input'                   => 'multiselect',
                    'backend'                 => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                    'label'                   => 'Machine Types',
                    'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                    'source'                  => \Riki\MachineApi\Model\B2CMachineSkus\Config\MachineTypeList::class,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => true,
                    'searchable'              => false,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => false,
                    'unique'                  => false,
                    'sort_order'              => 29,
                    'apply_to'                => ''
                ]
            );
        }

        $installer->endSetup();
    }

    /**
     * @param $attrLabel
     * @param $options
     * @return bool
     */
    public function getAttributeId($attrLabel, $options){
        foreach($options as $option){
            if($option->getLabel() == $attrLabel){
                return $option->getValue();
            }
        }
        return false;
    }
}
