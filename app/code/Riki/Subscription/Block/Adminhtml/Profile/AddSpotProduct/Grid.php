<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Block\Adminhtml\Profile\AddSpotProduct;

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
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * Filter builder
     *
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;
    /**
     * @var \Riki\Subscription\Helper\Profile\AddSpotHelper
     */
    protected $addSpotHelper;
    /**
     * @var \Magento\CatalogInventory\Helper\Stock
     */
    protected $stockHelper;

    /**
     * Grid constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\Config $catalogConfig
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\Sales\Model\Config $salesConfig
     * @param \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper
     * @param \Riki\SubscriptionCourse\Model\ResourceModel\Course $subscriptionModel
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Riki\Subscription\Helper\Profile\AddSpotHelper $addSpotHelper
     * @param \Magento\CatalogInventory\Helper\Stock $helperStock
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
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Riki\Subscription\Helper\Profile\AddSpotHelper $addSpotHelper,
        \Magento\CatalogInventory\Helper\Stock $helperStock,
        array $data = []
    ) {
        $this->_productFactory = $productFactory;
        $this->_catalogConfig = $catalogConfig;
        $this->_sessionQuote = $sessionQuote;
        $this->_salesConfig = $salesConfig;
        $this->subscriptionPageHelper = $subscriptionPageHelper;
        $this->subscriptionModel = $subscriptionModel;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder  = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->addSpotHelper = $addSpotHelper;
        $this->stockHelper = $helperStock;
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
        $this->setId('add_spot_product');
        $this->setFilterVisibility(true);
        $this->setDefaultSort('entity_id');
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
     * Prepare collection to be displayed in the grid
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $profileId = $this->getRequest()->getParam('id');
        $deliveryTypeOfProfile = null;
        if($profileId){
            $deliveryTypeOfProfile = $this->addSpotHelper->getDeliveryTypeOfProfile($profileId);
        }
        $productCollection = $this->_productFactory->create()->getCollection();
        $productCollection->addAttributeToSelect(['name','price']);
        $productCollection->addAttributeToFilter('spot_allow_subscription',true);
        $productCollection->addAttributeToFilter('status',true);
        if($deliveryTypeOfProfile) {
            $productCollection->addAttributeToFilter('delivery_type',$deliveryTypeOfProfile);
        }
        $this->stockHelper->addInStockFilterToCollection($productCollection);
        $this->setCollection($productCollection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'ids',
            [
                'header' => "<div style='width:100%;text-align: center;'><input class='checkAllItem' type='checkbox'></div>",
                'sortable' => false,
                'width'     => '10px',
                'filter' => false,
                'header_css_class' => 'col-id',
                'column_css_class' => 'spot-input-check',
                'index' => 'entity_id',
                'type' => 'checkbox',
                'renderer'  => 'Magento\Backend\Block\Widget\Grid\Column\Renderer\Checkbox',
            ]
        );
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
                'index' => 'name'
            ]
        );
        $this->addColumn('sku', ['header' => __('SKU'), 'index' => 'sku']);
        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'column_css_class' => 'price',
                'type' => 'currency',
                'currency_code' => $this->getStore()->getCurrentCurrencyCode(),
                'rate' => $this->getStore()->getBaseCurrency()->getRate($this->getStore()->getCurrentCurrencyCode()),
                'index' => 'price',
                'renderer' => 'Riki\CatalogRule\Block\Adminhtml\Subscription\Order\Create\Search\Grid\Renderer\Price'
            ]
        );
        $this->addColumn(
            'qty',
            [
                'filter' => false,
                'sortable' => false,
                'header' => __('Quantity'),
                'renderer' => 'Riki\Subscription\Block\Adminhtml\Profile\AddSpotProduct\Grid\Column\Renderer\Qty',
                'name' => 'qty',
                'inline_css' => 'qty',
                'type' => 'input',
                'header_css_class' => 'col-qty add-spot-qty',
                'validate_class' => 'validate-number',
                'index' => 'qty'
            ]
        );
        $this->addColumn(
            'Action',
            [
                'header'    => __('Add Spot'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => [
                    [
                        'caption' => __('Add Spot'),
                        'url'     => 'javascript:void(0)',
                        'field'   => 'id',
                        'class' => 'add-spot'
                    ]
                ],
                'filter'    => false,
                'sortable'  => false,
                'is_system' => true,
            ]
        );
        return parent::_prepareColumns();
    }
    public function getRowClickCallback()
    {
        $profileId = $this->getRequest()->getParam('id');
        $urlConfirm = $this->getUrl('profile/profile/confirmSpotProduct',['id'=>$profileId]);
        $script = <<<JQUERY
        function (grid, event) {
            var trElement = Event.findElement(event, 'tr');
            var add_spot_link = Event.findElement(event, '.add-spot')
            if (typeof(add_spot_link) != 'undefined') {
                var productId = parseInt(jQuery(trElement).find('.col-id').html());
                var qty = jQuery(trElement).find('input[name=qty]').val();
                if(!qty){
                    alert('Please input qty');
                }else{
                    if(!(!isNaN(parseFloat(qty)) && isFinite(qty)) || parseInt(qty)<=0){
                       alert('The value is greater than 0')
                    } else{
                        qty = parseInt(qty);
                        var caseDisplay = jQuery(trElement).find('select[name=case_display]').val();
                        var unitQty = parseInt(jQuery(trElement).find('input[name=unit_qty]').val());
                        window.location = '$urlConfirm'+'?productId='+productId+'&qty='+qty+'&case='+caseDisplay+'&unit='+unitQty;                   
                    }
                }
            };
        }
JQUERY;
        return $script;
    }

}
