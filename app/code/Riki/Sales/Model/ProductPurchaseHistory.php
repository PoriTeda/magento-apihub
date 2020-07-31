<?php

namespace Riki\Sales\Model;
use Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Framework\Model\AbstractModel;
use \Magento\Framework\Api\SortOrder;
class ProductPurchaseHistory extends AbstractModel
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */

    protected $_storeManager;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory
     */
    protected $_itemcollectionFactory;


    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $_imageHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Catalog product visibility
     *
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $_catalogProductVisibility;

    /**
     * @var Collection
     */
    protected $_itemCollection;

    /**
     * @var \Magento\Framework\Api\Filter
     */
    protected $filter;
    /**
     * @var \Magento\Framework\Api\Search\FilterGroup
     */
    protected $filterGroup;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaInterface
     */
    protected $searchCriteriaInterface;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resourceConnection;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Link\CollectionFactory
     */
    protected $_linkCollectionFactory;
    /**
     * @var \Riki\ThirdPartyImportExport\Model\ResourceModel\Order\Detail\CollectionFactory
     */
    protected $_collectionLegacyOrderDetailFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timeZone;
    /**
     * @var SortOrder
     */
    protected $sortOrder;

    protected $_customerId;

    protected $_currentWebsiteID;

    protected $_filterSaleProductId = [];

    protected $_searchCriterialBuilder;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $collectionFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Framework\Api\Filter $filter,
        \Magento\Framework\Api\Search\FilterGroup $filerGroup,
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteriaInterface,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Catalog\Model\ResourceModel\Product\Link\CollectionFactory $linkCollectionFactory,
        \Riki\ThirdPartyImportExport\Model\ResourceModel\Order\Detail\CollectionFactory $collectionLegacyOrderDetailFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        SortOrder $sortOrder,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ){
        $this->_customerSession = $customerSession;
        $this->_storeManager    = $storeManager;
        $this->_itemcollectionFactory    = $collectionFactory;
        $this->_productRepository        = $productRepository;
        $this->_imageHelper              = $imageHelper;
        $this->_catalogProductVisibility = $catalogProductVisibility;
        $this->searchCriteriaInterface   = $searchCriteriaInterface;
        $this->_resourceConnection       = $resourceConnection;
        $this->_linkCollectionFactory    = $linkCollectionFactory;
        $this->_collectionLegacyOrderDetailFactory = $collectionLegacyOrderDetailFactory;
        $this->filter      = $filter;
        $this->filterGroup = $filerGroup;
        $this->_dateTime   = $dateTime;
        $this->_timeZone   = $timezoneInterface;
        $this->sortOrder = $sortOrder;
        $this->_searchCriterialBuilder = $searchCriteriaBuilder;
        $this->productFactory = $productFactory;
    }

    /**
     * Check website id
     *
     * @param $product
     * @return bool
     */
    public function checkWebsiteId($product){
        $arrWebsiteId = $product->getWebsiteIds();
        if( is_array($arrWebsiteId) &&count($arrWebsiteId) >0 ){
            if(in_array($this->_currentWebsiteID,$arrWebsiteId)){
                return true;
            }
        }
        return false;
    }

    /**
     * Check product not exit on table sales order item
     *
     * @param $arrProductIds
     * @return array
     */
    public function checkProductNotExitOnSaleOrder($arrProductIds){
        $arrIds = [];
        if(count($arrProductIds) > 0) {
            foreach ($arrProductIds as $val) {
                if(!in_array($val,$this->_filterSaleProductId)) {
                    $arrIds[] = $val;
                }
            }
        }
        return $arrIds;
    }

    /**
     * Get product id from legacy order
     */
    public function getProductIdFromLegacyOrder(){
        $arrProductId = [];
        $consumerDbID = $this->_customerSession->getCustomer()->getData('consumer_db_id');
        $collection = $this->_collectionLegacyOrderDetailFactory->create();
        $collection->addFieldToFilter('s.customer_code',$consumerDbID);
        $collection->addFieldToFilter('s.order_datetime', array('gteq' => $this->getTimeLastYear()));
        $collection->getSelect()->join(
                ['s' =>'riki_order'],
                'main_table.order_no = s.order_no',
                ['s.customer_code']
        );
        $collection->getSelect()->columns('sku_code');
        $collection->getSelect()->group('sku_code');
        if($collection->getSize()>0){
            $arrSku =  $collection->getColumnValues('sku_code');
            if(is_array($arrSku) && count($arrSku)>0){
                //get list product by sku
                $filters[] = $this->filter
                                  ->setField('sku')
                                  ->setConditionType('in')
                                  ->setValue($arrSku);

                $filterGroup[]  = $this->filterGroup->setFilters($filters);
                $searchCriteria = $this->searchCriteriaInterface->setFilterGroups($filterGroup);
                $searchResults  = $this->_productRepository->getList($searchCriteria);
                if($searchResults->getTotalCount()>0){
                    $listItems = $searchResults->getItems();
                    foreach($listItems as $product){
                        if($product->getIsSalable() && $this->checkWebsiteId($product)){
                            $arrProductId[$product->getId()] = $product->getId();
                        }

                        //add value product sales
                        $this->_filterSaleProductId [$product->getId()] = $product->getId();
                    }
                }
            }
        }

        return $arrProductId;
    }

    /**
     * get product id from sales orders
     *
     * @return array
     */
    public function getProductIdFromSalesOrder()
    {
        $productId = [];
        $collection = $this->_itemcollectionFactory->create();
        $collection->addAttributeToSelect('product_id');
        $collection->addFieldToFilter('s.customer_id', $this->_customerId);
        $collection->addFieldToFilter('s.created_at', array('gteq' => $this->getTimeLastYear()));
        $collection->getSelect()->join(
            ['s' =>'sales_order'],
            'main_table.order_id = s.entity_id',
            []
        );
        $collection->getSelect()->group('product_id');
        $productIds = $collection->getColumnValues('product_id');

        if (!empty($productIds)) {
            // Get product collection with specific attribute.
            $productCollection = $this->productFactory->create()->getCollection();
            $productCollection->addAttributeToSelect(
                ['status', 'allow_spot_order']
            )->addIdFilter(
                $productIds
            );

            if ($productCollection->getSize() > 0) {
                foreach ($productCollection->getItems() as $product) {
                    if ($product && $product->getId() != null) {
                        if ($product->getIsSalable() && $this->checkWebsiteId($product)) {
                            $productId[$product->getId()] = $product->getId();
                        }
                        //add value product sales
                        $this->_filterSaleProductId[$product->getId()] = $product->getId();
                    }
                }
            }
        }
        return $productId;
    }

    /**
     * get product id in stock
     *
     * @param $arrProductIds
     * @return array
     */
    public function filterProductInstock($arrProductIds){
        $arrIds = [];
        $filters[] = $this->filter->setField('entity_id')->setConditionType('in')->setValue($arrProductIds);
        $searchCriteria = $this->_searchCriterialBuilder->addFilters($filters)->create();
        $searchResults  = $this->_productRepository->getList($searchCriteria);
        if($searchResults->getTotalCount()){
            $listProductItems = $searchResults->getItems();
            foreach ($listProductItems as $product){
                if($product->getIsSalable()) {
                    $arrIds[$product->getId()] = $product->getId();
                }
            }
        }
        return $arrIds;
    }

    /**
     * get product relation
     *
     * @param $arrProductIds
     * @return array
     */
    public function getListProductRelation($arrProductIds){
        $arrProductRelationId = [];
        $productLink = $this->_linkCollectionFactory->create()
            ->addFieldToFilter('link_type_id',\Magento\Catalog\Model\Product\Link::LINK_TYPE_RELATED)
            ->addFieldToFilter('product_id',$arrProductIds);
        if($productLink->getSize() > 0 ){
            $arrProductRelationId = $productLink->getColumnValues('linked_product_id');
        }
        return $arrProductRelationId;
    }

    /**
     * get time last year
     *
     * @return string
     */
    public function getTimeLastYear(){
        $now = $this->_dateTime->date('Y-m-d H:m:i');
        $lastYear = strtotime ( '-2 year' , strtotime ( $now ) ) ;
        return $this->_timeZone->date($lastYear)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    /**
     * @param $page
     * @param bool $nextStep
     * @return array|null
     */
    public function getListProductPurchaseHistory($page, $nextStep = false){

        $arrLegacyOrderProductIds = [];
        $arrSalesOrderProductIds = [];
        //check customer exit
        $this->_customerId = $this->_customerSession->getCustomerId();
        if($this->_customerId==null) {
            return null;
        }

        //get current website id
        $this->_currentWebsiteID = $this->_storeManager->getStore()->getWebsiteId();

        //get product id from table sales order items
        $arrSalesOrderProductIds = $this->getProductIdFromSalesOrder();

        //check exit product id
        if( count($arrSalesOrderProductIds) == 0 ) {
            //get product legacy order
            $arrLegacyOrderProductIds = $this->getProductIdFromLegacyOrder();

        }

        //check exit product id
        if( count($arrSalesOrderProductIds) == 0 && count($arrLegacyOrderProductIds) == 0 ){
            return null;
        }

        //get product id relation
        $arrIds = array_merge($arrSalesOrderProductIds, $arrLegacyOrderProductIds);
        $idProductRelations = $this->getListProductRelation($arrIds);

        $productNotSales = $this->checkProductNotExitOnSaleOrder($idProductRelations);

        if(is_array($productNotSales) && count($productNotSales) <= 0) {
            return null;
        }

        //filter product in stock
        $arrProductIds = $this->filterProductInstock($idProductRelations);

        //sort
        $order[] = $this->sortOrder->setField('position')->setDirection(SortOrder::SORT_ASC);
        $order[] = $this->sortOrder->setField('price')->setDirection(SortOrder::SORT_ASC);

        //get product in stock
        $arrProduct    = [];
        if(count($arrProductIds)>0){
            $filters[]      = $this->filter->setField('entity_id')->setConditionType('in')->setValue($arrProductIds);
            $filterGroup[]  = $this->filterGroup->setFilters($filters);
            $searchCriteria = $this->searchCriteriaInterface->setFilterGroups($filterGroup)
                                   ->setSortOrders($order)
                                   ->setPageSize(7)
                                   ->setCurrentPage($page);

            $searchResults  = $this->_productRepository->getList($searchCriteria);
            if($searchResults->getTotalCount() > 0 ){
                $listProductItems = $searchResults->getItems();
                if($nextStep)
                {
                    return $listProductItems;
                }
                else
                {
                    foreach ($listProductItems as $product){
                        $arrProduct[$product->getId()] = array(
                            'name'        => $product->getName(),
                            'url'         => $product->getProductUrl(),
                            'id'         => $product->getId(),
                            'sku'         => $product->getSku(),
                            'imageUrl'    => $this->_imageHelper->init($product, 'category_page_grid')->getUrl(),
                            'stock_status'=> (!$product->getIsSalable()) ? __('Out of stock')  : ""
                        );
                    }
                }

            }
        }
        return $arrProduct;
    }
}