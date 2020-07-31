<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */



namespace Riki\Checkout\Block\Product\ProductList;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\View\Element\AbstractBlock;

/**
 * Catalog product related items block
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Related extends \Magento\Catalog\Block\Product\AbstractProduct implements \Magento\Framework\DataObject\IdentityInterface
{
    /**
     * @var Collection
     */
    protected $_itemCollection;

    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * Catalog product visibility
     *
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $_catalogProductVisibility;

    /**
     * Checkout cart
     *
     * @var \Magento\Checkout\Model\ResourceModel\Cart
     */
    protected $_checkoutCart;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $_dataHelperSubscriptionPage;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */

    protected $_storeManager;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;
    /**
     * @var \Magento\Framework\Api\Filter
     */
    protected $_filter;
    /**
     * @var \Magento\Framework\Api\Search\FilterGroup
     */
    protected $_filterGroup;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaInterface
     */
    protected $_searchCriteriaInterface;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * Related constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Checkout\Model\ResourceModel\Cart $checkoutCart
     * @param \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Api\Filter $filter
     * @param \Magento\Framework\Api\Search\FilterGroup $filerGroup
     * @param \Riki\SubscriptionPage\Helper\Data $dataHelperSubscriptionPage
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Checkout\Model\ResourceModel\Cart $checkoutCart,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\Filter $filter,
        \Magento\Framework\Api\Search\FilterGroup $filerGroup,
        \Riki\SubscriptionPage\Helper\Data $dataHelperSubscriptionPage,
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteriaInterface,
        \Magento\Framework\Module\Manager $moduleManager,
        array $data = []
    ) {
        $this->_checkoutCart    = $checkoutCart;
        $this->_catalogProductVisibility = $catalogProductVisibility;
        $this->_checkoutSession = $checkoutSession;
        $this->moduleManager    = $moduleManager;
        $this->_dataHelperSubscriptionPage = $dataHelperSubscriptionPage;
        $this->_storeManager    = $context->getStoreManager();
        $this->_productRepository = $productRepository;
        $this->_filter      = $filter;
        $this->_filterGroup = $filerGroup;
        $this->_searchCriteriaInterface = $searchCriteriaInterface;
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * @return $this
     */
    protected function _prepareData()
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->_prepareData();
        return parent::_beforeToHtml();
    }

    /**
     * @return $this
     */
    public function setTemplate($template)
    {
        $configEnable = $this->getConfig('cartrecomendationtemporarily/recommendation_product_setting/enable');
        if (!$configEnable) {
            $template = null;
        }        
        return parent::setTemplate($template);
    }

    /**
     * Get product by array product id
     *
     * @param $arrProductId
     * @return Collection
     */
    public function getProductItemCollection($arrProductId)
    {
        $filters[] = $this->_filter
                          ->setField('entity_id')
                          ->setConditionType('in')
                          ->setValue($arrProductId);
        $filterGroup[]  = $this->_filterGroup->setFilters($filters);
        $searchCriteria = $this->_searchCriteriaInterface->setFilterGroups($filterGroup);
        $collection     = $this->_productRepository->getList($searchCriteria);
        return $collection->getItems();
    }

    /**
     * get list product
     *
     * @return array
     */
    public function getListProductItem(){
        $cart = $this->_checkoutSession->getQuote();
        $arrCartItem = $cart->getAllItems();

        $arrProductItems = [];
        if(is_array($arrCartItem)&&count($arrCartItem)>0){
            $arrId = [];
            foreach($arrCartItem as $item){
                $relatedId = $item->getProduct()->getRelatedProductIds();
                if(is_array($relatedId) && count($relatedId)>0){
                    $arrId = array_merge($relatedId,$arrId);
                }
            }

            //filter product in stock,allow spot order = yes
            if(count($arrId)>0){
                $currentWebsiteId = $this->_storeManager->getStore()->getWebsiteId();
                $listItems = $this->getProductItemCollection($arrId);
                if( is_array($listItems) && count($listItems)>0 ){
                    foreach ($listItems as $item){
                        if($item->getIsSalable() && $item->getAllowSpotOrder() ){
                            $arrWebsiteId = $item->getWebsiteIds();
                            if(is_array($arrWebsiteId) && count($arrWebsiteId)>0){
                                if(in_array($currentWebsiteId,$arrWebsiteId)){
                                    $arrProductItems[$item->getId()] = $item;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $arrProductItems;
    }

    /**
     * @return Collection
     */
    public function getItems()
    {
        return $this->_itemCollection;
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
        $identities = [];
        foreach ($this->getItems() as $item) {
            $identities = array_merge($identities, $item->getIdentities());
        }
        return $identities;
    }

    /**
     * Find out if some products can be easy added to cart
     *
     * @return bool
     */
    public function canItemsAddToCart()
    {
        foreach ($this->getItems() as $item) {
            if (!$item->isComposite() && $item->isSaleable() && !$item->getRequiredOptions()) {
                return true;
            }
        }
        return false;
    }

    /**
     * if riki_course_id is not null .show related prouct on cart page
     *
     * @return bool
     */
    public function checkShowRelatedProduct(){
        $quote = $this->_checkoutSession->getQuote();
        if ($quote->getData('riki_course_id') != null) {
            return true;
        }
        return false;
    }

    /**
     * get Config by path
     *
     * @param $path
     * @return mixed
     */
    public function getConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue($path,$storeScope);
    }

}
