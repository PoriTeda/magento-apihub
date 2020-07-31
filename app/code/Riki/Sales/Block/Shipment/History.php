<?php
namespace Riki\Sales\Block\Shipment;

/**
 * Sales order delivery block
 */
class History extends \Magento\Framework\View\Element\Template
{
    const MAGENTO_DATE_FROM = '2016-03-15';
    const LEGACY_DATE_TO = '2016-03-15';

    protected $_shipmentCollectionFactory;

    protected $_legacyShipmentCollectionFactory;

    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $_currentCustomer;

    protected $_rikiSalesAddressHelper;

    protected $_coreRegistry;

    protected $_shipments;

    protected $_legacyShipmentToProductsName;

    protected $_productCollection;

    protected $_productSkuToName;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory,
        \Riki\ThirdPartyImportExport\Model\ResourceModel\Shipping\CollectionFactory $legacyShipmentCollectionFactory,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Riki\Sales\Helper\Address $addressHelper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->_legacyShipmentCollectionFactory = $legacyShipmentCollectionFactory;
        $this->_currentCustomer = $currentCustomer;
        $this->_rikiSalesAddressHelper = $addressHelper;
        $this->_productCollection = $productCollectionFactory;

        parent::__construct($context, $data);
    }

    /**
     * @return mixed
     */
    public function getAddressId(){
        return $this->_coreRegistry->registry('address_id');
    }

    /**
     * @return mixed
     */
    public function getAddress(){
        return $this->_coreRegistry->registry('current_address');
    }

    /**
     * @return bool
     */
    public function isLegacyRequest(){
        $requestType = $this->_coreRegistry->registry('is_legacy');
        return boolval($requestType);
    }

    /**
     * @return string
     */
    public function getLegacyShipmentUrl(){
        return $this->getUrl('sales/shipment/history', ['id'    =>  $this->getAddressId(), 'legacy' =>  1]);
    }

    /**
     * @return string
     */
    public function getMagentoShipmentUrl(){
        return $this->getUrl('sales/shipment/history', ['id'    =>  $this->getAddressId()]);
    }

    /**
     * @return string
     */
    public function getBackUrl(){
        return $this->getUrl('customer/address');
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getShipments()) {
            $pager = $this->getLayout()->createBlock(
                'Magento\Theme\Block\Html\Pager',
                'sales.shipment.history.pager'
            )
                ->setTemplate('Riki_Customer::address/html/pager.phtml')
                ->setCollection(
                $this->getShipments()
            );
            $this->setChild('pager', $pager);
            $this->getShipments()->load();
        }
        return $this;
    }

    /**
     * @return bool|\Magento\Sales\Model\ResourceModel\Order\Shipment\Collection
     */
    public function getShipments(){
        if (!($customerId = $this->_currentCustomer->getCustomerId())) {
            return false;
        }
        if (!$this->_shipments) {
            if($this->isLegacyRequest()){
                $this->_shipments = $this->_getLegacyShipments();
            }else{
                $this->_shipments = $this->_getMagentoShipments();
            }
        }
        return $this->_shipments;
    }

    /**
     * @return bool
     */
    protected function _getMagentoShipments(){
        $orderAddressIds = $this->_rikiSalesAddressHelper->getOrderAddressIdsByCustomerAddressId($this->getAddressId());

        if(count($orderAddressIds)){
            $collection = $this->_shipmentCollectionFactory->create()->addFieldToSelect(
                '*'
            )->addFieldToFilter(
                'main_table.customer_id',
                $this->_currentCustomer->getCustomerId()
            )->addFieldToFilter(
                'main_table.shipping_address_id',
                ['in'   =>  $orderAddressIds]
            )->setOrder(
                'main_table.delivery_complete_date',
                'desc'
            );

            $collection->getSelect()->join(
                ['item_table'   =>  'sales_shipment_item'],
                'main_table.entity_id=item_table.parent_id',
                ['item_table.parent_id', 'products_name' =>  'GROUP_CONCAT(item_table.name SEPARATOR \'</br>\')']
            )->group('item_table.parent_id');

            //only show parent bundle
            $collection->getSelect()->join(
                ['item_order_table'   =>  'sales_order_item'],
                'item_table.order_item_id=item_order_table.item_id'
            )->where('item_order_table.parent_item_id IS NULL');

            $collection->getSelect()->join(
                ['order_table' =>  'sales_order'],
                'order_table.entity_id=main_table.order_id',
                ['order_table.created_at', 'order_table.riki_type', 'order_table.grand_total']
            );

            return $collection;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function _getLegacyShipments(){

        $legacyAddressId = $this->getAddress()->getConsumerDbAddressId();

        if(!empty($legacyAddressId)){

            /** @var \Magento\Customer\Api\Data\CustomerInterface $customerData */
            $customerData = $this->_currentCustomer->getCustomer();

            $shoshaBusinessAttr = $customerData->getCustomAttribute('shosha_business_code');

            if($shoshaBusinessAttr){
                $shoshaBusinessCode = $shoshaBusinessAttr->getValue();

                $collection = $this->_legacyShipmentCollectionFactory->create()->addFieldToSelect(
                    '*'
                )
                    ->addFieldToFilter(
                        'main_table.customer_code',
                        $shoshaBusinessCode
                    )
                    ->addFieldToFilter(
                        'main_table.address_no',
                        $legacyAddressId
                    )
                    ->setOrder(
                        'main_table.created_datetime',
                        'desc'
                    );

                $collection->getSelect()->join(
                    ['item_table'   =>  'riki_shipping_detail'],
                    'main_table.shipping_no=item_table.shipping_no',
                    ['products_sku' =>  'GROUP_CONCAT(item_table.sku_code SEPARATOR \',\')']
                )->group('item_table.shipping_no');

                $collection->getSelect()->join(
                    ['order_table' =>  'riki_order'],
                    'order_table.order_no=main_table.order_no',
                    ['created_at' =>  'order_table.order_datetime', 'riki_type'  =>  'order_table.plan_type', 'grand_total'    =>  'order_table.grand_total']
                );

                return $collection;
            }
        }

        return false;
    }

    /**
     * @param $type
     * @return \Magento\Framework\Phrase
     */
    public function preparedOrderType($type){
        if(is_numeric($type)){
            switch($type){
                case 0:
                    $type = __('SUBSCRIPTION');
                    break;
                case 1:
                    $type = __('HANPUKAI');
                    break;
                default:
                    $type = '';
            }
        }else{
            if($type == 'SPOT')
                $type = '';
        }

        return $type;
    }

    /**
     * get product name by sku list
     *
     * @param string $skuListString
     * @return string
     */
        public function getProductsNameHtmlByShipmentId($skuListString){

        $result = '';

        if(!empty($skuListString)){

            if(is_null($this->_productSkuToName)){

                $this->_productSkuToName = [];

                $skus = [];

                foreach($this->getShipments() as $shipment){
                    $shipmentSkus = explode(',', $shipment->getProductsSku());
                    foreach($shipmentSkus as $shipmentSku){
                        $skus[] = $shipmentSku;
                    }
                }

                if(count($skus)){
                    $productCollection = $this->_productCollection->create();
                    $productCollection->addAttributeToFilter('sku', ['in'   =>  $skus]);

                    foreach($productCollection as $product){
                        $this->_productSkuToName[$product->getSku()] = $product->getName();
                    }
                }
            }

            $skuList = explode(',', $skuListString);

            foreach($skuList as $sku){
                $result .= isset($this->_productSkuToName[$sku])? $this->_productSkuToName[$sku] . '</br>' : '';
            }
        }

        return $result;
    }


    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }
}
