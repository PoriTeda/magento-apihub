<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Block\Adminhtml\Profile\Edit\MainProduct;

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;

/**
 * Adminhtml enquiry create search customer block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_collectionProductFactory;


    protected $_productFactory;
    /**
     * @var \Riki\Customer\Helper\Config
     */
    protected $_configHelper;

    /**
     * @var \Magento\Catalog\Model\Product\Type
     */
    protected $_type;
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory
     */
    protected $_setsFactory;

    protected $_status;

    protected $_visibility;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfile;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Riki\Subscription\Helper\Data
     */
    protected $subscriptionHelper;

    protected $catalogRuleHelper;

    /**
     * Grid constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionProductFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Riki\Customer\Helper\Config $configHelper
     * @param \Magento\Catalog\Model\Product\Type $type
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory
     * @param \Magento\Catalog\Model\Product\Attribute\Source\Status $status
     * @param \Magento\Catalog\Model\Product\Visibility $visibility
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Subscription\Helper\Data $subscriptionHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionProductFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Riki\Customer\Helper\Config $configHelper,
        \Magento\Catalog\Model\Product\Type $type,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $status,
        \Magento\Catalog\Model\Product\Visibility $visibility,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Magento\Framework\Registry $registry,
        \Riki\Subscription\Helper\Data $subscriptionHelper,
        \Riki\CatalogRule\Helper\Data $catalogRuleHelper,
        array $data = []
    ) {
        $this->_collectionProductFactory = $collectionProductFactory;
        $this->_productFactory = $productFactory;
        $this->_configHelper = $configHelper;
        $this->_type = $type;
        $this->_setsFactory = $setsFactory;
        $this->_status = $status;
        $this->_visibility = $visibility;
        $this->helperProfile = $helperProfile;
        $this->_registry = $registry;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->catalogRuleHelper = $catalogRuleHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('main_product_grid');
        $this->setUseAjax(true);
        $this->setRowClickCallback('profileProductAdd.productGridRowClick.bind(profileProductAdd)');
        $this->setCheckboxCheckCallback('profileProductAdd.productGridCheckboxCheck.bind(profileProductAdd)');
        $this->setRowInitCallback('profileProductAdd.productGridRowInit.bind(profileProductAdd)');
        $this->setDefaultSort('entity_id');
        if ($this->getRequest()->getParam('collapse')) {
            $this->setIsCollapsed(true);
        }
    }

    /**
     * Prepare collection to be displayed in the grid
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $profileId = $this->getRequest()->getParam('id');
        if($profileId) {
            $subscriptionCourseResourceModel = $this->helperProfile->getSubscriptionCourseResourceModel();
            $profileData = $this->helperProfile->load($profileId);

            if(!$this->_registry->registry(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID)){
                $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $profileData->getData("course_id"));
            }

            $iFrequencyId = $this->subscriptionHelper->getFrequencyIdByUnitAndInterval($profileData->getData("frequency_unit"),$profileData->getData("frequency_interval"));

            if($iFrequencyId && !$this->_registry->registry(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID)){
                $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $iFrequencyId);
            }

            if(!$this->_registry->registry('subscription_profile_obj')){
                $this->_registry->register('subscription_profile_obj',$profileData );
            }
            if($profileData->getId()) {
                $products = $subscriptionCourseResourceModel->getAllProductByCourse(
                    $profileData->getData("course_id"), $profileData->getData("store_id"));
                if($products instanceof  \Magento\Catalog\Model\ResourceModel\Product\Collection) {
                    $productIds = $products->getAllIds();
                    if ($productIds) { // improve performance by decrease load catalog rule
                        $this->catalogRuleHelper->registerPreLoadedProductIds($productIds);
                    }
                }
            }
            else{
                $products = null;
            }
        }
        else{
            $products =  null;
        }
        $this->setCollection($products);
        return parent::_prepareCollection();
    }

    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_products',
            [
                'type' => 'checkbox',
                'name' => 'in_products',
                'values' => $this->_getSelectedProducts(),
                'index' => 'entity_id',
                'is_system' => true,
                'sortable' => false
            ]
        );

        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'index' => 'entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'img',
            [
                'header' => __('Product Image'),
                'sortable' => true,
                'index' => 'img',
                'renderer' => 'Riki\Subscription\Block\Adminhtml\Profile\Edit\MainProduct\Grid\Column\Renderer\Thumbnail',
                'header_css_class' => 'col-img',
                'column_css_class' => 'col-img'
            ]
        );

        $this->addColumn(
            'name',
            [
                'header' => __('Name'),
                'index' => 'name',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );

        $this->addColumn(
            'type',
            [
                'header' => __('Type'),
                'index' => 'type_id',
                'type' => 'options',
                'options' => $this->_type->getOptionArray(),
                'header_css_class' => 'col-type',
                'column_css_class' => 'col-type'
            ]
        );

        $this->addColumn(
            'product_sku',
            [
                'header' => __('SKU'),
                'index' => 'sku',
                'header_css_class' => 'col-sku',
                'column_css_class' => 'col-sku'
            ]
        );

        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'type' => 'currency',
                'currency_code' => (string)$this->_scopeConfig->getValue(
                    \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_BASE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ),
                'index' => 'price',
                'header_css_class' => 'col-price',
                'column_css_class' => 'col-price',
                'renderer' => 'Riki\CatalogRule\Block\Adminhtml\Subscription\Order\Create\Search\Grid\Renderer\Price'
            ]
        );
        $this->addColumn(
            'qty',
            [
                'header' => __('Quantity'),
                'renderer' => 'Riki\Subscription\Block\Adminhtml\Profile\Edit\MainProduct\Grid\Column\Renderer\Qty',
                'name' => 'qty',
                'inline_css' => 'qty',
                'type' => 'input',
                'validate_class' => 'validate-number',
                'index' => 'qty',
                'filter'    => false
            ]
        );
        $this->addColumn(
            'case_display',
            [
                'header'  => __('Unit'),
                'align'   => 'left',
                'width'   => '80px',
                'renderer' => 'Riki\Subscription\Block\Adminhtml\Profile\Edit\MainProduct\Grid\Column\Renderer\Unit',
                'index'   => 'case_display',
                'type'    => 'options',
                'options' => [
                    1 => __('EA'),
                    2 => __('CS'),
                    3 => __('EA/CS')
            ],
        ]);

        return parent::_prepareColumns();
    }

    /**
     * Get selected products
     *
     * @return mixed
     */
    protected function _getSelectedProducts()
    {
        $products = $this->getRequest()->getPost('products', []);

        return $products;
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl(
            'profile/profile_edit/LoadBlock',
            ['block' => 'mainproduct_grid', '_current' => true, 'collapse' => null]
        );
    }

    /**
     * @return array
     */
    public function getRequireJsDependencies()
    {
        return ['Riki_Subscription/js/action/init-product-add'];
    }
}
