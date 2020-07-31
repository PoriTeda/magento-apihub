<?php

namespace Riki\Catalog\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Riki\DeliveryType\Model\Delitype;

/**
 * Class Data
 * @package Riki\Catalog\Helper
 */
class Data extends AbstractHelper
{
    const NO_PRODUCT_ATTRIBUTE_SET_NAME = 'No product';

    protected $_attributeSetRepository;

    protected $_wrappingRepository;

    /**
     * Filter manager
     *
     * @var \Magento\Framework\Filter\FilterManager
     */
    protected $_filterManager;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $_filterBuilder;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;
    /**
     * @var \Magento\Framework\Api\Search\FilterGroupBuilder
     */
    protected $_filterGroupBuilder;

    protected $_productsGiftWrapping = [];

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var \Riki\CatalogFreeShipping\Model\ResourceModel\Rule\CollectionFactory
     */
    protected $ruleCollection;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Catalog\Helper\Data
     */
    protected $_catalogDataHelper;

    protected $_bundleProductType;

    protected $_productCollection;

    protected $_wrappingList;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Filter\FilterManager $filterManager
     * @param \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepositoryInterface
     * @param \Magento\Eav\Api\AttributeSetRepositoryInterface $attributeSetRepositoryInterface
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Riki\CatalogFreeShipping\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Bundle\Model\Product\Type $bundleType
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection
     * @param \Magento\Catalog\Helper\Data $catalogDataHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepositoryInterface,
        \Magento\Eav\Api\AttributeSetRepositoryInterface  $attributeSetRepositoryInterface,
        \Magento\Catalog\Api\ProductRepositoryInterface  $productRepository,
        \Riki\CatalogFreeShipping\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Bundle\Model\Product\Type $bundleType,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
        \Magento\Catalog\Helper\Data $catalogDataHelper
    ){
        $this->_attributeSetRepository = $attributeSetRepositoryInterface;
        $this->_filterManager = $filterManager;
        $this->_wrappingRepository = $wrappingRepositoryInterface;
        $this->_filterBuilder = $filterBuilder;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_filterGroupBuilder = $filterGroupBuilder;
        $this->_productRepository = $productRepository;
        $this->ruleCollection = $ruleCollectionFactory;
        $this->storeManager   = $storeManager;
        $this->customerSession  = $customerSession;
        $this->_catalogDataHelper = $catalogDataHelper;
        $this->_bundleProductType = $bundleType;
        $this->_productCollection = $productCollection;

        parent::__construct($context);
    }

    /**
     * Sub string html
     *
     * @param $str
     * @param $start
     * @param $len
     *
     * @return string
     */
    public function subStrHtml($str, $start, $len)
    {
        $strClean = substr(strip_tags($str), $start, $len);
        $pos = strrpos($strClean, " ");
        if ($pos === false)
            $strClean = substr(strip_tags($str), $start, $len);
        else
            $strClean = substr(strip_tags($str), $start, $pos);
        if (preg_match_all('/\<[^>]+>/is', $str, $matches, PREG_OFFSET_CAPTURE)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i++) {
                if ($matches[0][$i][1] < $len) {
                    $strClean = substr($strClean, 0, $matches[0][$i][1]) . $matches[0][$i][0] . substr($strClean, $matches[0][$i][1]);
                } else if (preg_match('/\<[^>]+>$/is', $matches[0][$i][0])) {
                    $strClean = substr($strClean, 0, $matches[0][$i][1]) . $matches[0][$i][0] . substr($strClean, $matches[0][$i][1]);
                    break;
                }
            }
            return $strClean;
        } else {
            $string = substr($str, $start, $len);
            $pos = strrpos($string, " ");
            if ($pos === false) {
                return substr($str, $start, $len);
            }
            return substr($str, $start, $pos);
        }
    }

    /**
     * @param $str
     * @param $len
     * @return string
     */
    public function subStr($str, $len)
    {
        $remainder = '';

        $filter = new \Magento\Framework\Filter\Truncate(
            new \Magento\Framework\Stdlib\StringUtils(),
            $len,
            '',
            $remainder,
            true
        );

        return $filter->filter($str);
    }

    /**
     * @param $string
     * @param $length
     * @param string $postfix
     * @param bool|true $isHtml
     * @return string
     */
    public function truncateHtml($string, $length, $postfix = '...', $isHtml = true){
        $string = trim($string);
        $postfix = (mb_strlen(strip_tags($string), 'utf-8') > $length) ? $postfix : '';
        $i = 0;
        $tags = []; // change to array() if php version < 5.4

        if($isHtml) {
            preg_match_all('/<[^>]+>([^<]*)/', $string, $tagMatches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
            foreach($tagMatches as $tagMatch) {
                if ($tagMatch[0][1] - $i >= $length) {
                    break;
                }

                $tag = substr(strtok($tagMatch[0][0], " \t\n\r\0\x0B>"), 1);
                if ($tag[0] != '/') {
                    $tags[] = $tag;
                }
                elseif (end($tags) == substr($tag, 1)) {
                    array_pop($tags);
                }

                $i += $tagMatch[1][1] - $tagMatch[0][1];
            }
        }

        $result = mb_substr($string, 0, $length = min(strlen($string), $length + $i));

        $result .= $postfix;

        $result .= count($tags = array_reverse($tags)) ? '</' . implode('></', $tags) . '>' : '';

        return $result;
    }

    /**
     * @param $deliveryType
     * @return string
     */
    public function getLabelHtmlClassOfDeliveryType($deliveryType){
        switch($deliveryType){
            case Delitype::COOL:
                $result = 'cool';
                break;
            case Delitype::DM:
                $result = 'direct_mail';
                break;
            case Delitype::COLD:
                $result = 'cold';
                break;
            case Delitype::CHILLED:
                $result = 'chilled';
                break;
            case Delitype::COSMETIC:
                $result = 'cosmetic';
                break;
            default:
                $result = '';
                break;
        }

        return $result;
    }

    /**
     * @param $deliveryType
     * @return string
     */
    public function getLabelHtmlOfDeliveryType($deliveryType){
        switch($deliveryType){
            case Delitype::COOL:
                $result = __('Cool type') . '<br/>' . __('(Refrigerated)');
                break;
            case Delitype::DM:
                $result = __('DM type');
                break;
            case Delitype::COLD:
                $result = '<span>' . __('Separate delivery') . '</span><span>' . __('Refrigeration only') . '</span>';
                break;
            case Delitype::CHILLED:
                $result = '<span>' . __('Separate delivery') . '</span><span>' . __('Freezing only') . '</span>';
                break;
            case Delitype::COSMETIC:
                $result = '<span>' . __('Separate delivery') . '</span><span>' . __('Cosmetic only') . '</span>';
                break;
            default:
                $result = '';
                break;
        }

        return $result;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function isNoProductType(\Magento\Catalog\Model\Product $product){

        $attributeSetName = $product->getAttributeSetName();

        if(is_null($attributeSetName)){
            $attributeSetId = $product->getAttributeSetId();

            try{
                $attributeSet = $this->_attributeSetRepository->get($attributeSetId);

                $attributeSetName = $attributeSet->getAttributeSetName();

            }catch (\Exception $e){
                $this->_logger->critical($e);
            }
        }

        return $attributeSetName == self::NO_PRODUCT_ATTRIBUTE_SET_NAME;
    }

    /**
     * @return array
     */
    public function getWrappingList(){
        if(is_null($this->_wrappingList)){

            $this->_wrappingList = [];

            try{

                $filters = [];

                $filters[] = $this->_filterBuilder
                    ->setField('website_ids')
                    ->setConditionType('FIND_IN_SET')
                    ->setValue($this->storeManager->getWebsite()->getId())
                    ->create();

                $filterGroup = $this->_filterGroupBuilder
                    ->setFilters($filters)
                    ->create();

                $searchCriteria = $this->_searchCriteriaBuilder
                    ->setFilterGroups([$filterGroup])
                    ->create();

                $wrappingList = $this->_wrappingRepository->getList($searchCriteria)->getItems();

                foreach($wrappingList as $wrapping){
                    $this->_wrappingList[$wrapping->getWrappingId()] = $wrapping;
                }

            }catch (\Exception $e){
                $this->_logger->critical($e->getMessage());
            }
        }

        return $this->_wrappingList;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function hasFreeGiftWrapping(\Magento\Catalog\Model\Product $product){
        $giftWrappings = $this->getGiftWrappingByProduct($product);

        if($giftWrappings){
            foreach($giftWrappings as $giftWrapping){
                if($giftWrapping->getBasePrice() == 0)
                    return true;
            }
        }

        return false;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return bool|int
     */
    public function hasGiftWrapping(\Magento\Catalog\Model\Product $product){
        $giftWrappings = $this->getGiftWrappingByProduct($product);

        return count($giftWrappings);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return mixed
     */
    public function getGiftWrappingByProduct(\Magento\Catalog\Model\Product $product){

        if(!isset($this->_productsGiftWrapping[$product->getId()])){

            $this->_productsGiftWrapping[$product->getId()] = [];

            $selectedIds = $product->getGiftWrapping();

            if(!empty($selectedIds)){

                $selectedIds = explode(',', $selectedIds);

                $availableGws = $this->getWrappingList();
                $availableGwIds = array_keys($availableGws);

                foreach($selectedIds as $selectedId){
                    if(in_array($selectedId, $availableGwIds)){
                        $this->_productsGiftWrapping[$product->getId()][] = $availableGws[$selectedId];
                    }
                }
            }
        }

        return $this->_productsGiftWrapping[$product->getId()];
    }

    /**
     * @param $productObject
     * @param bool $isObject
     * @param bool $finalPrice
     * @return array
     */
    public function getProductUnitInfo($productObject , $isObject = false, $finalPrice = false){
        $unitQty = 1;
        $unitCase = \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_PIECE;
        $product = null;
        if($isObject)
        {
            if($productObject->getCaseDisplay()){
                $product = $productObject;
            }
            else{
                $productId = $productObject->getId();
            }
        }
        else
        {
            $productId = $productObject;
        }
        if(!$product){
            try{
                $product = $this->_productRepository->getById($productId);
            }catch (\Exception $e){
                $this->_logger->critical($e->getMessage());
            }
        }
        $tierCasePrice = 0;
        if($product){
            if($product->getCaseDisplay() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY){
                $unitQty = ((int)$product->getUnitQty() != 0)?$product->getUnitQty():1;
                $unitCase = \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE;
            }

            $tierPrices = $product->getTierPrices();

            if($tierPrices){
                foreach ($tierPrices as $tierPrice) {
                    if ($tierPrice->getQty()/$unitQty == 1 && $product->getCaseDisplay() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY) {
                        $tierCasePrice = $this->_catalogDataHelper->getTaxPrice($product,$tierPrice->getValue());
                        $tierCasePrice = $tierCasePrice * $unitQty;
                        break;
                    }
                }
            }

            if($finalPrice === false){
                $finalPrice = $product->getFinalPrice();
            }

            if($tierCasePrice && $finalPrice * $unitQty < $tierCasePrice){
                $tierCasePrice = 0;
            }
        }


        return array($unitQty,$unitCase,$tierCasePrice);
    }

    /**
     * Get product detail and indexing by id
     *
     * @param array $productIds
     * @return array
     */
    public function getProductByIds($productIds)
    {
        $filter = $this->_searchCriteriaBuilder->addFilter('entity_id', $productIds, 'in');
        $products = $this->_productRepository->getList($filter->create());
        $result = [];
        if ($products->getTotalCount()) {
            foreach ($products->getItems() as $item) {
                $result[$item->getId()] = $item;
            }
        }
        return $result;
    }

    /**
     * Get product detail and indexing by SKU
     *
     * @param array $arrSKUs
     * @return array
     */
    public function getProductBySKUs($arrSKUs)
    {
        $filter = $this->_searchCriteriaBuilder->addFilter('sku', $arrSKUs, 'in');
        $products = $this->_productRepository->getList($filter->create());
        $result = [];
        if ($products->getTotalCount()) {
            foreach ($products->getItems() as $item) {
                $result[$item->getSku()] = $item;
            }
        }
        return $result;
    }

    /**
     * @param $bundleProductId
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection|null
     */
    public function getProductSelectionOfBundle($bundleProductId){
        $childrenIds = $this->_bundleProductType->getChildrenIds($bundleProductId);

        if(count($childrenIds)){
            /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
            $productCollection = $this->_productCollection->create();
            $productCollection->addFieldToFilter('entity_id', ['in' =>  $childrenIds]);

            return $productCollection;
        }

        return null;
    }

    /**
     * @param $configName
     * @return mixed
     */
    public function getStockConfigByPath($configName){
        return $this->scopeConfig->getValue('cataloginventory/item_options/' . $configName);
    }

    public function getIsFreeShippingByProductId($productId){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->create('Magento\Catalog\Model\Product')->load($productId);
        return $product->getIsFreeShipping();
    }
}