<?php

namespace Riki\Sales\Model;
use \Magento\Framework\Model\AbstractModel;

class ProductMachineOwner extends AbstractModel
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
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_collectionProductFactory;

    /**
     * @var \Zend\Soap\Client
     */
    protected $_soapClient;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $_imageHelper;

    /**
     * @var \Riki\Customer\Model\ConsumerLogFileFactory
     */
    protected $_consumerLogFactory;

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
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Link\CollectionFactory
     */
    protected $_linkCollectionFactory;

    /**
     * @var \Riki\SubscriptionMachine\Model\ResourceModel\MachineSkus\CollectionFactory
     */
    protected $_collectionMachineSkusFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var
     */
    protected $_currentWebsiteID;

    /**
     * @var \Riki\Customer\Helper\ConsumerDb\Soap
     */
    protected $soapHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Riki\Customer\Model\CustomerRepository
     */
    protected $consumerCustomerRepository;

    /**
     * ProductMachineOwner constructor.
     *
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Zend\Soap\Client $soapClient
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Magento\Framework\Api\Filter $filter
     * @param \Magento\Framework\Api\Search\FilterGroup $filerGroup
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteriaInterface
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Riki\Customer\Model\ConsumerLogFileFactory $consumerLogFactory
     * @param \Riki\SubscriptionMachine\Model\ResourceModel\MachineSkus\CollectionFactory $collectionMachineSkuFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionProductFactory
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Customer\Model\CustomerRepository $consumerCustomerRepository
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Zend\Soap\Client $soapClient,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Framework\Api\Filter $filter,
        \Magento\Framework\Api\Search\FilterGroup $filerGroup,
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteriaInterface,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\Customer\Model\ConsumerLogFileFactory $consumerLogFactory,
        \Riki\SubscriptionMachine\Model\ResourceModel\MachineSkus\CollectionFactory $collectionMachineSkuFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionProductFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Link\CollectionFactory $linkCollectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Action\Context $context,
        \Riki\Customer\Helper\ConsumerDb\Soap $soapHelper,
        \Magento\Framework\Registry $registry,
        \Riki\Customer\Model\CustomerRepository $consumerCustomerRepository
    ){
        $this->_customerSession = $customerSession;
        $this->_storeManager    = $storeManager;
        $this->_soapClient      = $soapClient;
        $this->_logger          = $logger;
        $this->_dateTime        = $dateTime;
        $this->_imageHelper     = $imageHelper;
        $this->_filter          = $filter;
        $this->_filterGroup     = $filerGroup;
        $this->_scopeConfig     =$scopeConfig;
        $this->_consumerLogFactory = $consumerLogFactory;
        $this->_searchCriteriaInterface      = $searchCriteriaInterface;
        $this->_productRepository            = $productRepository;
        $this->_collectionMachineSkusFactory = $collectionMachineSkuFactory;
        $this->_collectionProductFactory     = $collectionProductFactory;
        $this->_linkCollectionFactory        = $linkCollectionFactory;
        $this->soapHelper = $soapHelper;
        $this->_registry = $registry;
        $this->consumerCustomerRepository = $consumerCustomerRepository;
    }

    /**
     * get machine no
     *
     * @return array
     */
    public function getMachineNo()
    {
        $customer = $this->_customerSession->getCustomer();

        if (!$customer->getId() || !$customer->getData('consumer_db_id')) {
            return [];
        }

        try {
            $customerMachineInfo = $this->consumerCustomerRepository->prepareInfoMachineCustomer($customer->getData('consumer_db_id'));
        } catch (\Exception $e) {
            $customerMachineInfo = null;
        }

        if (isset($customerMachineInfo[0]) && is_array($customerMachineInfo[0]) && $customerMachineInfo[0]) {
            return array_keys($customerMachineInfo[0]);
        }

        return [];
    }

    /**
     * check website id
     *
     * @param $product
     * @return bool
     */
    public function checkWebsiteId($product){
        $arrWebsiteId = $product->getWebsiteIds();
        if( is_array($arrWebsiteId) && count($arrWebsiteId) >0 ){
            if(in_array($this->_currentWebsiteID,$arrWebsiteId)){
                return true;
            }
        }
        return false;
    }

    /**
     * get product related by array sku
     *
     * @param $arrSku
     * @return array
     */
    public function getListProductRelation($arrSku){
        $arrProductRelationId = [];
        $productLink = $this->_linkCollectionFactory->create()
            ->join( [
                'p' =>'catalog_product_entity'],
                'p.entity_id = main_table.product_id'
            )
            ->addFieldToFilter('main_table.link_type_id',\Magento\Catalog\Model\Product\Link::LINK_TYPE_RELATED)
            ->addFieldToFilter('p.sku',$arrSku);
        if($productLink->getSize()>0){
            $arrProductRelationId = $productLink->getColumnValues('linked_product_id');
        }
        return $arrProductRelationId;
    }

    /**
     * get product id in stock
     *
     * @param $arrProductIds
     * @return array
     */
    public function filterProductInStock($arrProductIds){
        $arrIds = [];
        if(is_array($arrProductIds) && count($arrProductIds)>0){
            $filters[] = $this->_filter->setField('entity_id')->setConditionType('in')->setValue($arrProductIds);
            $filterGroup[]  = $this->_filterGroup->setFilters($filters);
            $searchCriteria = $this->_searchCriteriaInterface->setFilterGroups($filterGroup);
            $searchResults  = $this->_productRepository->getList($searchCriteria);
            if($searchResults->getTotalCount()){
                $listProductItems = $searchResults->getItems();
                foreach ($listProductItems as $product){
                    if($product->getIsSalable()){
                        $arrIds[$product->getId()] = $product->getId();
                    }
                }
            }
        }
        return $arrIds;
    }

    /**
     * get product by machine no
     *
     * @param $machineNo
     * @return array|null
     */
    public function getProductByMachineNo($machineNo){
        $productMachine = $this->_collectionMachineSkusFactory->create()
                               ->addFieldToFilter('machine_type_code',['in'=> $machineNo]);
        $arrSku = null;
        if($productMachine->getSize()>0){
            $arrSku = $productMachine->getColumnValues('sku');
        }
        return $arrSku;
    }

    /**
     * get product machine owner
     *
     * @param $page
     * @return array
     */
    public function getListProductMachineOwner($page){
        //get current website id
        $this->_currentWebsiteID = $this->_storeManager->getStore()->getWebsiteId();

        $machineNo = $this->getMachineNo();

        $arrProduct    = [];
        if($machineNo){
            //get product by machine no
            $arrSku = $this->getProductByMachineNo($machineNo);
            if(is_array($arrSku) && count($arrSku)>0){

                //get product machine by sku
                $arrProductRelatedId = $this->getListProductRelation($arrSku);

                //filter product in stock
                $arrProductId = $this->filterProductInStock($arrProductRelatedId);

                if(is_array($arrProductId) && count($arrProductId)>0){
                    $filters[]      = $this->_filter->setField('entity_id')->setConditionType('in')->setValue($arrProductId);
                    $filterGroup[]  = $this->_filterGroup->setFilters($filters);
                    $searchCriteria = $this->_searchCriteriaInterface->setFilterGroups($filterGroup)->setPageSize(7)->setCurrentPage($page);
                    $searchResults  = $this->_productRepository->getList($searchCriteria);

                    if($searchResults->getTotalCount()){
                        return $listProductItems = $searchResults->getItems();
                    }
                }
            }
        }
        return $arrProduct;
    }
}
