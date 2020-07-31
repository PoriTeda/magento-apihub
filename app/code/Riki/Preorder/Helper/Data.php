<?php

namespace Riki\Preorder\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const BACKORDERS_PREORDER_OPTION = 101;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_sessionQuote;
    /**
     * @var \Magento\CatalogInventory\Api\StockStateInterface
     */
    protected $_stockInterface;
    /**
     * @var \Magento\CatalogInventory\Model\StockRegistry
     */
    protected $_stockRegistry;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_orderCollection;
    /**
     * @var ProductRepositoryInterface
     */
    protected $_productRepository;
    /*
     * @var  \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected $_productCollection;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;
    /**
     * @var \Riki\Preorder\Model\OrderPreorderFactory
     */
    protected $_preOrderFactory;
    /**
     * @var \Riki\Preorder\Model\OrderItemPreorderFactory
     */
    protected $_preOrderItemFactory;
    /**
     * @var \Riki\Preorder\Model\ResourceModel\OrderPreorder
     */
    protected $_preOrderResource;
    /**
     * @var \Riki\Preorder\Helper\Templater
     */
    protected $_helperTemplate;

    protected $_rikiCatalogHelper;

    protected $_cachedPreOrderList = [];
    protected $isOrderProcessing = false;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\CatalogInventory\Api\StockStateInterface $stockStateInterface
     * @param \Magento\CatalogInventory\Model\StockRegistry $stockRegistry
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\Preorder\Model\OrderPreorderFactory $orderPreorderFactory
     * @param \Riki\Preorder\Model\OrderItemPreorderFactory $orderItemPreorderFactory
     * @param \Riki\Preorder\Model\ResourceModel\OrderPreorder $preorderResource
     * @param \Riki\Catalog\Helper\Data $rikiCatalogHelper
     * @param Templater $helperTemplate
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\CatalogInventory\Api\StockStateInterface $stockStateInterface,
        \Magento\CatalogInventory\Model\StockRegistry $stockRegistry,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Preorder\Model\OrderPreorderFactory $orderPreorderFactory,
        \Riki\Preorder\Model\OrderItemPreorderFactory $orderItemPreorderFactory,
        \Riki\Preorder\Model\ResourceModel\OrderPreorder $preorderResource,
        \Riki\Catalog\Helper\Data $rikiCatalogHelper,
        \Riki\Preorder\Helper\Templater $helperTemplate
    ){
        parent::__construct($context);
        $this->_dateTime = $dateTime;
        $this->_timezone = $timezone;
        $this->_storeManager = $storeManager;
        $this->_customerSession = $customerSession;
        $this->_sessionQuote = $sessionQuote;
        $this->_stockInterface = $stockStateInterface;
        $this->_stockRegistry = $stockRegistry;
        $this->_orderRepository = $orderRepository;
        $this->_orderCollection = $orderCollection;
        $this->_productRepository = $productRepository;
        $this->_productCollection = $productCollection;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_preOrderFactory = $orderPreorderFactory;
        $this->_preOrderItemFactory = $orderItemPreorderFactory;
        $this->_preOrderResource = $preorderResource;
        $this->_helperTemplate = $helperTemplate;
        $this->_rikiCatalogHelper = $rikiCatalogHelper;
    }

    /**
     * @return \Magento\Backend\Model\Session\Quote
     */
    public function getSessionQuote(){
        return $this->_sessionQuote;
    }

    /**
     * @param $productId
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface
     */
    protected function _initProduct($productId)
    {
        if ($productId) {
            $storeId = $this->_storeManager->getStore()->getWebsiteId();
            try {
                return $this->_productRepository->getById($productId, false, $storeId);
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * @param $productId
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface
     */
    public function getProductById($productId){
        return $this->_initProduct($productId);
    }

    /***
     * Check and validate can add to cart "pre order" product
     *
     * @param $productId
     * @param null $qtyRequest
     * @return bool
     */
    public function checkCanPreOrder($productId)
    {
        $product = $this->_initProduct($productId);

        if (!$product) {
            return false;
        }

        $preordersEnabled = $this->preordersEnabled();

        $isPreorder = $product->getExtensionAttributes()->getStockItem()->getBackorders() == \Riki\Preorder\Helper\Data::BACKORDERS_PREORDER_OPTION;

        $result = $preordersEnabled && $isPreorder;

        return $result;
    }

    public function checkNewOrder(\Magento\Sales\Model\Order $order)
    {
        $orderPreorderResource = $this->_preOrderResource;

        $alreadyProcessed = $order->getId() && $orderPreorderResource->getIsOrderProcessed($order->getId());
        if (!$alreadyProcessed) {
            /*if (is_null($order->getId())) {
                //save order , prevent error "foreign key" when save preorder
                $order->save();
            }*/
            $this->processNewOrder($order);
        }

        // Will work for normal email flow only. Deprecated.
        if ($this->getOrderIsPreorderFlag($order)) {
            $order->setData('preorder_warning', $orderPreorderResource->getWarningByOrderId($order->getId()));
        }
    }

    protected function processNewOrder(\Magento\Sales\Model\Order $order)
    {
        $this->isOrderProcessing = true;
        /** @var \Magento\Sales\Model\ResourceModel\Order\Item\Collection $itemCollection */
        $itemCollection = $order->getItemsCollection();

        $orderIsPreorder = false;

        $fulfillmentDate = $this->_dateTime->gmtDate('Y-m-d');

        foreach ($itemCollection as $item) {
            /** @var \Magento\Sales\Model\Order\Item $item */
            $orderItemIsPreorder = $this->getOrderItemIsPreorder($item);

            // AdvanceInventory Extension disable can_subtract option , code here will auto decrease stock after place order
            if($orderItemIsPreorder) {
                $product = $item->getProduct();
                if($product){

                    $fulfillmentDate = $this->_dateTime->gmtDate('Y-m-d', $product->getFulfilmentDate());

                    $qtyInStock = $product->getExtensionAttributes()->getStockItem()->getQty();
                    $qtyAfterOrder = $qtyInStock - $item->getQtyOrdered();
                    $product->setQuantityAndStockStatus(['qty' => $qtyAfterOrder, 'is_in_stock' => $qtyAfterOrder > 0]);
                    try {
                        $product->save();
                    } catch (\Exception $e) {
                        $this->_logger->error($e->getMessage());
                    }
                }
            }

            $this->saveOrderItemPreorderFlag($item, $orderItemIsPreorder, $fulfillmentDate);

            $orderIsPreorder |= $orderItemIsPreorder;
        }

        $this->addPreorderFlagForOrder( $order->getId(), $orderIsPreorder );


    }

    protected function addPreorderFlagForOrder( $orderId, $orderIsPreorder )
    {
        /** @var \Riki\Preorder\Model\OrderPreorder $orderPreorder */
        $model = $this->_preOrderFactory->create();
        $collection = $model->getCollection();
        $collection->addFieldToFilter('order_id', $orderId);

        if( !$collection->getSize() )
        {
            $model->setOrderId($orderId);
            $model->setIsPreorder($orderIsPreorder);
            if ($orderIsPreorder) {
                $warningText = $this->getCurrentStoreConfig('rikipreorder/general/orderpreorderwarning');
                $model->setWarning($warningText);
            }
            $model->save();
        }
        return true;
    }

    /**
     * Order item is pre-order item( product)
     *
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return bool|mixed
     */
    protected function getOrderItemIsPreorder(\Magento\Sales\Model\Order\Item $orderItem)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $orderItem->getProduct();

        if (empty($product) || !$product instanceof \Magento\Catalog\Model\Product) {
            return false;
        }

        $result = $this->getIsProductPreorder($product);

        if (!$result) {
            foreach($orderItem->getChildrenItems() as $childItem) {
                $result = $this->getOrderItemIsPreorder($childItem);
                if ($result) {
                    break;
                }
            }
        }

        return $result;
    }

    protected function saveOrderItemPreorderFlag(\Magento\Sales\Model\Order\Item $orderItem, $isPreorder, $fulfillmentDate = false)
    {
        /** @var \Riki\Preorder\Model\OrderItemPreorder $orderItemPreorder */
        $orderItemPreorder = $this->_preOrderItemFactory->create();
        $orderItemPreorder->setOrderItemId($orderItem->getId());
        $orderItemPreorder->setIsPreorder($isPreorder);
        $orderItemPreorder->setFulfillmentDate($fulfillmentDate);
        try{
            $orderItemPreorder->save();
        }catch (\Exception $e){
            $this->_logger->error($e->getMessage());
        }
    }

    public function getQuoteItemIsPreorder(\Magento\Quote\Model\Quote\Item $item)
    {
        $product = $item->getProduct();

        if ($product->isComposite()) {
            $productTypeInstance = $product->getTypeInstance();

            if ($productTypeInstance instanceof \Magento\ConfigurableProduct\Model\Product\Type\Configurable) {
                /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $productTypeInstance */

                /** @var \Magento\Quote\Model\Quote\Item\Option $option */
                $option = $item->getOptionByCode('simple_product');
                $simpleProduct = $option->getProduct();
                if (!$simpleProduct instanceof \Magento\Catalog\Model\Product) {
                    return false;
                }
                return $this->getIsSimpleProductPreorder($simpleProduct);
            }

            if ($productTypeInstance instanceof \Magento\Bundle\Model\Product\Type) {
                /** @var \Magento\Bundle\Model\Product\Type $productTypeInstance */

                $isPreorder = false;
                foreach ($item->getChildren() as $childItem) {
                    if ($this->getQuoteItemIsPreorder($childItem)) {
                        $isPreorder = true;
                        break;
                    }
                }
                return $isPreorder;
            }
        } else {
            return $this->getIsSimpleProductPreorder($product);
        }

        return false;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return mixed
     */
    public function getIsProductPreorder(\Magento\Catalog\Model\Product $product)
    {
        if ($product->isComposite()) {
            $result = $this->getIsCompositeProductPreorder($product);
        } else {
            $result = $this->getIsSimpleProductPreorder($product);
        }
        $product->setIsPreorder($result);

        return $product->getIsPreorder();
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    protected function getIsCompositeProductPreorder(\Magento\Catalog\Model\Product $product)
    {
        if (!$this->getCurrentStoreConfig('rikipreorder/additional/discovercompositeoptions'))
        {
            // We never know what options customer will select
            return false;
        }

        $typeId = $product->getTypeId();
        $typeInstance = $product->getTypeInstance();

        switch ($typeId) {
            case 'grouped':
                $result = $this->getIsGroupedProductPreorder($typeInstance);
                break;

            case 'configurable':
                $result = $this->getIsConfigurableProductPreorder($typeInstance);
                break;

            case 'bundle':
                $result = $this->getIsBundleProductPreorder($typeInstance, $product);
                break;

            default:
                //Mage::log('Cannot determinate pre-order status of product of unknown product type: ' . $typeId, Zend_Log::WARN);
                $result = false;
        }

        // Still have no implementation for bundles
        return $result;
    }

    protected function getIsGroupedProductPreorder(\Magento\GroupedProduct\Model\Product\Type\Grouped $typeInstance)
    {
        $elementaryProducts = $typeInstance->getAssociatedProducts();

        if (count($elementaryProducts) == 0) {
            return false;
        }

        $result = true; // for a while
        foreach ($elementaryProducts as $elementary) {
            if (!$this->getIsSimpleProductPreorder($elementary)) {
                $result = false;
                break;
            }
        }

        return $result;
    }

    protected function getIsConfigurableProductPreorder(\Magento\ConfigurableProduct\Model\Product\Type\Configurable $typeInstance)
    {
        $elementaryProducts = $typeInstance->getUsedProducts();

        if (count($elementaryProducts) == 0) {
            return false;
        }

        $result = true; // for a while
        foreach ($elementaryProducts as $elementary) {
            /** @var \Magento\Catalog\Model\Product $elementary */
            if (!$this->getIsSimpleProductPreorder($elementary)) {
                $result = false;
                break;
            }
        }

        return $result;
    }

    protected function getIsBundleProductPreorder(\Magento\Bundle\Model\Product\Type $typeInstance, \Magento\Catalog\Model\Product $product)
    {
        $optionIds = array();
        $optionSelectionCounts = array();
        $optionPreorder = array();

        $options = $typeInstance->getOptionsCollection($product);
        foreach ($options as $option) {
            /** @var \Magento\Bundle\Model\Option $option */
            if (!$option->getRequired()) {
                continue;
            }

            $id = $option->getId();
            $optionIds[] = $id;
            $optionSelectionCounts[$id] = 0; // for a while
            $optionPreorder[$id] = true; // for a while
        }
        if (!$optionIds) {
            return false;
        }

        $selections = $typeInstance->getSelectionsCollection($optionIds, $product);
        $products = $this->getProductCollectionBySelectionsCollection($selections);
        foreach ($selections as $selection) {
            /** @var \Magento\Bundle\Model\Selection $selection */

            /** @var \Magento\Catalog\Model\Product $product */
            $product = $products->getItemById($selection->getProductId());

            $isPreorder = $this->getIsSimpleProductPreorder($product);
            $optionId = $selection->getOptionId();
            $optionSelectionCounts[$optionId]++;
            if (!$isPreorder) {
                $optionPreorder[$optionId] = false;
            }
        }

        $result = false; // for a while
        foreach ($optionPreorder as $id => $isPreorder) {
            if ($isPreorder && $optionSelectionCounts[$id] > 0) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    protected function getProductCollectionBySelectionsCollection($selections)
    {
        $productIds = array();
        foreach ($selections as $selection) {
            /** @var \Magento\Bundle\Model\Selection $selection */
            $productIds[] = $selection->getProductId();
        }

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->_productCollection->create();
        $collection->addFieldToFilter('entity_id', array('in', $productIds));

        return $collection;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    protected function getIsSimpleProductPreorder(\Magento\Catalog\Model\Product $product)
    {
        $backOrderValue = $product->getBackorders();

        if(is_null($backOrderValue)){
            /** @var \Magento\CatalogInventory\Model\StockRegistry $inventoryRegistry */
            $inventoryRegistry = $this->_stockRegistry;
            /** @var \Magento\CatalogInventory\Model\Stock\Item $inventory */
            $inventory = $inventoryRegistry->getStockItem($product->getId());

            $backOrderValue = $inventory->getBackorders();
        }

        $isPreorder = $backOrderValue == self::BACKORDERS_PREORDER_OPTION;
        return $isPreorder;
    }

    /**
     * @param $incrementId
     * @return bool
     */
    public function getOrderIsPreorderFlagByIncrementId($incrementId)
    {
        // finally convert back to string to optimize SQL query
        $incrementId = ''. (int)$incrementId;

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->_getOrderByInCrementId($incrementId);

        if (!$order->getId()) {
            $this->_logger->critical( 'Preorder: Cannot load order by incrementId = ' . $incrementId );
            return false;
        }

        return $this->getOrderIsPreorderFlag($order);
    }

    public function getOrderIsPreorderFlag(\Magento\Sales\Model\Order $order)
    {
        $orderId = $order->getId();

        if(!isset($this->_cachedPreOrderList[$orderId])){
            if (is_null($order)) {
                //Mage::log('Preorder: Cannot load preorder flag for null order. Processing as a regular order.', Zend_Log::ALERT);
                $this->_cachedPreOrderList[$orderId] = false;
            }
            /** @var \Riki\Preorder\Model\ResourceModel\OrderPreorder $orderPreorderResource */
            $orderPreorderResource = $this->_preOrderResource;
            $this->_cachedPreOrderList[$orderId] =  $orderPreorderResource->getOrderIsPreorderFlag($order->getId());
        }

        return $this->_cachedPreOrderList[$orderId];
    }

    public function getOrderPreorderWarning($orderId)
    {
        /** @var \Riki\Preorder\Model\ResourceModel\OrderPreorder $orderPreorderResource */
        $orderPreorderResource = $this->_preOrderResource;
        $warning = $orderPreorderResource->getWarningByOrderId($orderId);
        if (is_null($warning)) {
            $warning = $this->getCurrentStoreConfig('rikipreorder/general/orderpreorderwarning');
        }

        return $warning;
    }

    public function getOrderItemIsPreorderFlag($itemId)
    {
        $model = $this->_preOrderItemFactory->create();
        $orderItemPreorderCollection = $model->getCollection();
        $orderItemPreorderCollection->addFieldToFilter('order_item_id', $itemId);
        $orderItemPreorderCollection->addFieldToSelect('is_preorder');

        if($orderItemPreorderCollection->getSize()){
            return $orderItemPreorderCollection->setPageSize(1)->getFirstItem()->getIsPreorder();
        } else {
            return false;
        }
    }

    public function getQuoteItemPreorderNote(\Magento\Quote\Model\Quote\Item $quoteItem)
    {
        if ($quoteItem->getProductType() == 'configurable') {
            $option = $quoteItem->getOptionByCode('simple_product');
            $simpleProduct = $option->getProduct();
            return $this->getProductPreorderNote($simpleProduct);
        } else {
            return $this->getProductPreorderNote($quoteItem->getProduct());
        }
    }

    public function getProductPreorderNote(\Magento\Catalog\Model\Product $product)
    {
        $template = $product->getData('riki_preorder_note');
        if (is_null($template)) {
            $resource = $product->getResource();
            $template = $resource->getAttributeRawValue($product->getId(), 'riki_preorder_note', $product->getStoreId());
        }

        if (($template == "") || (is_array($template) && count($template) == 0)) {
            $template = $this->getCurrentStoreConfig('rikipreorder/general/defaultpreordernote');
        }

        /** @var \Riki\Preorder\Helper\Templater $templater */
        $note = $this->_helperTemplate->process($template, $product);
        if(is_array($note) && count($note) == 0){
            $note = "";
        }
        return $note;
    }

    public function getProductPreorderCartLabel(\Magento\Catalog\Model\Product $product)
    {
        $template = $product->getData('riki_preorder_cart_label');
        if (is_null($template)) {
            $resource = $product->getResource();
            $template = $resource->getAttributeRawValue($product->getId(), 'riki_preorder_cart_label', $product->getStoreId());
        }

        if (($template == "") || (is_array($template) && count($template) == 0)) {
            $template = $this->getCurrentStoreConfig('rikipreorder/general/addtocartbuttontext');
        }

        /** @var \Riki\Preorder\Helper\Templater $templater */
        $note = $this->_helperTemplate->process($template, $product);
        if(is_array($note) && count($note) == 0){
            $note = "";
        }
        return $note;
    }

    public function getDefaultPreorderCartLabel()
    {
        return $this->getCurrentStoreConfig('rikipreorder/general/addtocartbuttontext');
    }

    public function preordersEnabled()
    {
        return $this->getCurrentStoreConfig('rikipreorder/functional/enabled');
    }

    protected function getCurrentStoreConfig($path)
    {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    protected function _getOrderByInCrementId($incrementId)
    {
        $criteria = $this->_searchCriteriaBuilder->addFilter('increment_id', $incrementId )
            ->create();

        /** @var \Magento\Sales\Api\Data\OrderSearchResultInterface $orderCollection */
        $orderCollection = $this->_orderRepository->getList($criteria);

        if ($orderCollection->getTotalCount()) {
            return $orderCollection->getFirstItem();
        }

        return false;
    }

    /**
     * @param $product
     * @param bool $mobile
     * @return \Magento\Framework\Phrase|mixed
     */
    public function getAddToCartLabel( $product, $mobile = false )
    {
        if( $this->getIsProductPreorder($product) ) {
            $rs = $this->getProductPreorderCartLabel($product);
            if( !empty( $rs ) ){
                return $rs;
            }
        }
        if($mobile){
            return __('Buy');
        } else {
            return __('Add To Cart');
        }

    }

    /**
     * @param $product
     * @return string|void
     */
    public function getAddToCartClass( $product )
    {
        if( $this->getIsProductPreorder($product) ) {
            return 'preorder-button';
        } else {
            return;
        }
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function cartMultiTypeProductMessage(){
        return __('You can only buy a particular pre-order product at the same time. Please check your shopping cart !');
    }

    /**
     * @param $customerId
     * @param $productId
     * @return int
     */
    public function cumulativeProductPerCustomer( $customerId, $productId ){

        $order = $this->_orderCollection->create();

        $order->addFieldToFilter(
            'customer_id', $customerId
        )->addFieldToFilter(
            'state', ['neq' => \Magento\Sales\Model\Order::STATE_CANCELED]
        )->join(
            'sales_order_item', 'main_table.entity_id = sales_order_item.order_id', ['product_id', 'qty_ordered']
        )->addFieldToFilter(
            'sales_order_item.product_id', $productId
        )->getSelect()->columns('SUM( qty_ordered ) as total_qty')->limitPage(1,1);

        if($order->getSize()){
            return (int)$order->getFirstItem()->getData('total_qty');
        } else {
            return 0;
        }
    }

    /**
     * Check order is pre order
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function isPreOrder(\Magento\Sales\Model\Order $order)
    {
        if (!$order instanceof \Magento\Sales\Model\Order) {
            return false;
        }

        $orderItems = $order->getAllItems();

        $orderItem = end($orderItems);

        if (!$orderItem instanceof \Magento\Sales\Model\Order\Item || !$orderItem->getId()) {
            return false;
        }

        return $this->getOrderItemIsPreorder($orderItem);
    }
}
