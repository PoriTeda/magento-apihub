<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Block\Adminhtml\Order\Create\Product;

use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;
/**
 * Adminhtml sales order create search products block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Sales config
     *
     * @var \Magento\Sales\Model\Config
     */
    protected $_salesConfig;

    /**
     * Session quote
     *
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_sessionQuote;

    /**
     * Catalog config
     *
     * @var \Magento\Catalog\Model\Config
     */
    protected $_catalogConfig;

    /**
     * Product factory
     *
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    protected $_isHanpukai;

    protected $subscriptionPageHelper;

    protected $subscriptionModel;

    protected $categoryFactory;


    /**
     * Grid constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\Config $catalogConfig
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\Sales\Model\Config $salesConfig
     * @param \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\Config $salesConfig,
        \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course $subscriptionModel,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        array $data = []
    ) {
        $this->_productFactory = $productFactory;
        $this->_catalogConfig = $catalogConfig;
        $this->_sessionQuote = $sessionQuote;
        $this->_salesConfig = $salesConfig;
        $this->subscriptionPageHelper = $subscriptionPageHelper;
        $this->subscriptionModel = $subscriptionModel;
        $this->categoryFactory = $categoryFactory;
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
        $this->setId('sales_order_create_product_course_grid');
        $this->setRowClickCallback('order.productGridRowClick.bind(order)');
        $this->setCheckboxCheckCallback('order.productGridCheckboxCheck.bind(order)');
        $this->setRowInitCallback('order.productGridRowInit.bind(order)');
        $this->setDefaultSort('entity_id');
//        $this->setFilterVisibility(false);
        $this->setUseAjax(true);
        if ($this->getRequest()->getParam('collapse')) {
            $this->setIsCollapsed(true);
        }
    }

    /**
     * Retrieve quote store object
     *
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        return $this->_sessionQuote->getStore();
    }

    /**
     * Retrieve quote object
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->_sessionQuote->getQuote();
    }

    /**
     * Add column filter to collection
     *
     * @param \Magento\Backend\Block\Widget\Grid\Column $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in product flag
        if ($column->getId() == 'category_name') {
            $filter = $column->getFilter()->getCondition();
            $courseId = $this->getRequest()->getParam('id');
            $categoryIdsInCource = $this->subscriptionModel->getCategoryIds($courseId);
            $categoryModel = $this->categoryFactory->create()->getCollection()
                                ->addFieldToSelect('entity_id')
                                ->addAttributeToFilter('name',['like' => $filter])
                                ->addAttributeToFilter('entity_id',['in' => $categoryIdsInCource]);
            $categoryIds = [];
            if(sizeof($categoryModel->getItems()) > 0){
                foreach ($categoryModel as $item) {
                    $categoryIds[] =  $item->getId();
                }
            }
            $productIds = $this->subscriptionModel->getProductByCategoryIds($categoryIds);

            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', ['in' => $productIds]);
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    /**
     * Prepare collection to be displayed in the grid
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $courseId = $this->getRequest()->getParam('id');
        $storeId = $this->_sessionQuote->getQuote()->getStore()->getId();
        $collection = $this->subscriptionModel->getAllProductByCoursePieceCase($courseId,$storeId);

        $collection->joinField(
            'stock_qty',
            'cataloginventory_stock_item',
            'qty',
            'product_id=entity_id',
            '{{table}}.stock_id=1',
            'left'
        );

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $courseId = $this->getRequest()->getParam('id');
        $subscriptionType = $this->subscriptionPageHelper->getSubscriptionType($courseId);
        $this->_isHanpukai = ($subscriptionType=='hanpukai')?true:false;
        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'index' => 'entity_id'
            ]
        );
        $this->addColumn(
            'name',
            [
                'header' => __('Product'),
                'renderer' => 'Magento\Sales\Block\Adminhtml\Order\Create\Search\Grid\Renderer\Product',
                'index' => 'name'
            ]
        );
        $this->addColumn(
            'sku',
            [
                'header' => __('SKU'),
                'index' => 'sku'
            ]
        );

        $this->addColumn(
            'category_name',
            [
                'sortable' => false,
                'header' => __('Category Name'),
                'renderer' => 'Riki\Subscription\Block\Adminhtml\Order\Create\Product\Grid\Column\Renderer\Category',
                'index' => 'category_name'
            ]
        );
        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'column_css_class' => 'price',
                'type' => 'currency',
                'currency_code' => $this->getStore()->getCurrentCurrencyCode(),
                'rate' => $this->getStore()->getBaseCurrency()->getRate($this->getStore()->getCurrentCurrencyCode()),
                'index' => 'price',
                'renderer' => 'Magento\Sales\Block\Adminhtml\Order\Create\Search\Grid\Renderer\Price'
            ]
        );

        $this->addColumn(
            'stock_qty',
            [
                'header' => __('Stock'),
                'index' => 'stock_qty',
                'type' => 'text',
                'header_css_class' => 'col-period',
                'column_css_class' => 'col-period'
            ]
        );

        $inProduct = [
            'filter' => false,
            'header' => __('Select'),
            'type' => 'checkbox',
            'name' => 'in_products',
            'values' => $this->_getSelectedProducts(),
            'index' => 'entity_id',
            'sortable' => false,
        ];
        if($this->_isHanpukai){
            $inProduct['renderer'] = 'Riki\Subscription\Block\Adminhtml\Order\Create\Product\Grid\Column\Renderer\Checked';
        }

        $this->addColumn(
            'in_products',$inProduct);

        $this->addColumn(
            'qty',
            [
                'filter' => false,
                'sortable' => false,
                'header' => __('Quantity'),
                'renderer' => 'Riki\Subscription\Block\Adminhtml\Order\Create\Product\Grid\Column\Renderer\Qty',
                'name' => 'qty',
                'inline_css' => 'qty',
                'type' => 'input',
                'validate_class' => 'validate-number',
                'index' => 'qty'
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('sales/order_create/loadBlock',['block' => 'product_course_grid', '_current' => true, 'collapse' => null]);
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
     * Add custom options to product collection
     *
     * @return $this
     */
    protected function _afterLoadCollection()
    {
        $courseId = $this->getRequest()->getParam('id');
        $courseFactory = $this->subscriptionModel->getCourseFactory();
        $courseModel = $courseFactory->create()->load($courseId);
        $this->getCollection()->addOptionsToResult();
        if ($courseModel->getData('hanpukai_type') == SubscriptionType::TYPE_HANPUKAI_SEQUENCE) {
            $arrProductHanpukaiSequenceFirstDelivery = $this->subscriptionModel->getHanpukaiSequenceFirstDelivery($courseModel);
            foreach ($this->getCollection()->getItems() as $item) {
                $dataExtra = $arrProductHanpukaiSequenceFirstDelivery[$item->getId()];
                $item->addData(array('fix_qty' => $dataExtra['qty']));
                $item->addData(array('unit_case' => $dataExtra['unit_case']));
                $item->addData(array('unit_qty' => $dataExtra['unit_qty']));
            }
        }

        if ($courseModel->getData('hanpukai_type') == SubscriptionType::TYPE_HANPUKAI_FIXED) {
            $arrProductHanpukaiFixedConfig = $this->subscriptionModel->getHanpukaiFixedProductsDataPieCase($courseModel);
            foreach ($this->getCollection()->getItems() as $item) {
                $dataExtra = $arrProductHanpukaiFixedConfig[$item->getId()];
                $item->addData(array('fix_qty' => $dataExtra['qty']));
                $item->addData(array('unit_case' => $dataExtra['unit_case']));
                $item->addData(array('unit_qty' => $dataExtra['unit_qty']));
            }
        }
        foreach ($this->getCollection()->getItems() as $item) {
            $item->addData(array('course_id' => $courseId));
        }
        return parent::_afterLoadCollection();
    }
}
