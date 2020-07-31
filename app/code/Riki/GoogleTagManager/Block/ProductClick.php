<?php
namespace Riki\GoogleTagManager\Block;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Riki\GoogleTagManager\Helper\Data as GoogleTagManagerHelper;
/**
 * Class DataLayer
 * @package Riki\GoogleTagManager\Block
 */
class ProductClick extends Template {
    /**
     * @var Context
     */
    protected $_context;
    /**
     * @var GoogleTagManagerHelper
     */
    protected $_helper;

    CONST EXCEPT_ROUTE = 'catalogsearch';
    /**
     * ProductClick constructor.
     * @param Context $context
     * @param HttpContext $httpContext
     * @param GoogleTagManagerHelper $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        HttpContext $httpContext,
        GoogleTagManagerHelper $helper,
        array $data = []
    ){
        parent::__construct($context, $data);
        $this->httpContext = $httpContext;
        $this->_context = $context;
        $this->_helper = $helper;
        $this->_context->getEscaper();
    }

    /**
     * @return array
     */
    public function getRenderData()
    {
        $route      = $this->getRequest()->getRouteName();
        $blocks = $this->_layout->getAllBlocks();
        $productData = array();
        $actionField = $this->getActionField();
        foreach($blocks as $blockName => $block)
        {
            $blockType = $block->getType();
            switch($blockName)
            {
                /* Account Index page */
                case 'purchase_history':
                    //Riki\Sales\Block\Dashboard\PurchaseHistory
                    $productCollection = $block->getListProductPurchaseHistory(true);
                    $this->_helper->renderDataProducts($productCollection, $actionField, $productData);
                    break;
                    //Riki\Sales\Block\Dashboard\MachineOwned
                    //Magento_Sales::product/list/customer_machine_owned.phtml
//NED-4419
//                case 'machine_owned':
//                    $productCollection  = $block->getListProductItem(1);
//                    $this->_helper->renderDataProducts($productCollection, $actionField, $productData);
//                    break;
                case 'search_result_list':
                    //Riki\Catalog\Block\Product\ListProduct
                    $productCollection = $block->getLoadedProductCollection();
                    $this->_helper->renderDataProducts($productCollection, $actionField, $productData);
                    break;
                case 'catalog.product.related':
                    //Magento\Catalog\Block\Product\ProductList\Related
                    break;
                case 'product.info.upsell':
                    //Magento\Catalog\Block\Product\ProductList\Upsell
                    $productCollection = $block->getItemCollection()->getItems();
                    $this->_helper->renderDataProducts($productCollection, $actionField, $productData);
                    break;
                case 'product.info.additional':
                case 'product.related.top':
                case 'product.related.bottom':
                case 'product.info.additional':
                case 'catalog.product.related':
                case 'product.info.upsell':
                    $productCollection = $block->getLoadedProductCollection();
                    $this->_helper->renderDataProducts($productCollection, $actionField, $productData);
                    break;
                case 'sales.order.view':
                    $orderObject = $block->getOrder();
                    $this->_helper->renderDataProductsFromOrder($orderObject,$actionField, $productData);
                    break;
                case 'checkout.cart':
                    $quoteItems = $block->getItems();
                    $subscriptionCode = $block->getData('subscription_name');
                    $this->_helper->renderDataProductsFromQuote($quoteItems,$actionField, $productData, $subscriptionCode);
                    break;
            }
            switch($block->getType())
            {
                case 'Magento\CatalogWidget\Block\Product\ProductsList\Interceptor':
                    $productCollection = $block->createCollection()->getItems();
                    $this->_helper->renderDataProducts($productCollection, $actionField, $productData, true);
                    break;
                case 'related-rule':
                    $productCollection = $block->getAllItems();
                    $this->_helper->renderDataProducts($productCollection, $actionField, $productData, true);
                    break;
                case 'related':
                    break;
                case 'upsell-rule':
                    $productCollection = $block->getAllItems();
                    $this->_helper->renderDataProducts($productCollection, $actionField, $productData);
                    break;
                case 'upsell':
                    $productCollection = $block->getItemCollection()->getItems();
                    $this->_helper->renderDataProducts($productCollection, $actionField, $productData);
                    break;
                case 'crosssell-rule':
                    $productCollection = $block->getItemCollection();
                    $this->_helper->renderDataProducts($productCollection, $actionField, $productData);
                    break;
                case 'crosssell':
                    $productCollection = $block->getItems();
                    $this->_helper->renderDataProducts($productCollection, $actionField, $productData);
                    break;
                case 'new':
                    $productCollection->getProductCollection();
                    $this->_helper->renderDataProducts($productCollection, $actionField, $productData);
                    break;
            }
        }

        return $productData;
    }
    /**
     * @param $renderData
     * @return array
     */
    public function getProductClickRenderData($renderData)
    {
        $variant = $this->getVariant();
        return $this->_helper->getProductClickData($renderData,$variant);
    }
    /**
     * @param $renderData
     * @return array
     */
    public function getProductScrollRenderData($renderData)
    {
        $variant = $this->getVariant();
        return $this->_helper->getProductScrollData($renderData,$variant);
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getActionField()
    {
        $route      = $this->getRequest()->getRouteName();
        $keyword = $this->_context->getRequest()->getParam('q');
        $actionField = '';
        switch(strtolower($route))
        {
            case 'customer':
                $actionField = __('Customer Account Page Product List');
                break;
            case 'catalogsearch':
                $actionField = __('Search Results Page - '). $keyword;
                break;
            case 'catalog':
                $actionField = __('Recommendation List on Product Detail Page');
                break;
            case 'sales':
                $actionField = __('Order Detail Page Product List');
                break;
            case 'checkout':
                $actionField = __('Checkout Page Product List');
                break;
        }
        return $actionField;
    }

    /**
     * @return bool
     */
    public function isCheckoutPage()
    {
        $route      = $this->getRequest()->getRouteName();
        if($route=='checkout'){
            return true;
        }
        return false;

    }

    /**
     * Get variant data
     *
     * @return string
     */
    public function getVariant() {
        $variant = '';
        if($this->isCheckoutPage()) {
            $quote = $this->_helper->getQuoteData();
            if(!empty($quote)) {
                //getCourseCodeByQuote
                $variant = $this->_helper->getCodeProfileSubscription($quote);
            }
        }
        return $variant;
    }

}