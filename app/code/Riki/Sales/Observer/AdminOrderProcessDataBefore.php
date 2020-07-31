<?php
namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class AdminOrderProcessDataBefore implements ObserverInterface
{
    protected $_customerFactory;

    protected $_deliveryTypeAdminHelper;

    protected $_salesAdminHelper;

    protected $_scopeConfig;

    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    protected $_rikiQuoteHelper;

    /**
     * @var \Riki\AdvancedInventory\Helper\Inventory
     */
    protected $helperInventory;

    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Riki\DeliveryType\Helper\Admin $deliveryTypeAdminHelper,
        StockRegistryInterface $stockRegistry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Quote\Helper\Data $rikiQuoteHelper,
        \Riki\Sales\Helper\Admin $salesAdminHelper,
        \Riki\AdvancedInventory\Helper\Inventory $helperInventory
    ){
        $this->_customerFactory = $customerFactory;
        $this->_deliveryTypeAdminHelper = $deliveryTypeAdminHelper;
        $this->_salesAdminHelper = $salesAdminHelper;
        $this->stockRegistry = $stockRegistry;
        $this->_scopeConfig = $scopeConfig;
        $this->_rikiQuoteHelper = $rikiQuoteHelper;
        $this->helperInventory = $helperInventory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $session = $observer->getSession();
        $data = $observer->getRequestModel()->getPost();
        /** @var \Magento\Sales\Model\AdminOrder\Create $order */
        $order = $observer->getOrderCreateModel();

        /** @var \Magento\Framework\App\Request $request */
        $request = $observer->getRequestModel();

        if($request->getActionName() === 'save')
            $this->_validateOutOfStockItems($order);

        $payment = $order->getQuote()->getPayment()->getMethod();

        if(isset($data['payment']['method'])){
            $payment = $data['payment']['method'];
        }

        if (isset($data['set_free_surcharge'])
            && $data['set_free_surcharge']
            && ($payment == \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_COD
                || $payment == \Riki\NpAtobarai\Model\Payment\NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE)
        ) {
            $session->setFreeSurcharge(1);
        } else {
            $session->setFreeSurcharge(0);
        }

        if(isset($data['set_free_shipping'])){
            if($data['set_free_shipping']){
                $session->setFreeShippingFlag(1);
            }else{
                $session->setFreeShippingFlag(0);
            }
        }

        if(isset($data['set_earned_point'])){
            if($data['set_earned_point']){
                $session->setAllowedEarnedPoint(1);
            }else{
                $session->setAllowedEarnedPoint(0);
            }
        }

        /**
         * always use billing for shipping address
         */
        if ($this->_salesAdminHelper->isMultipleShippingAddressCart()){
            $order->setShippingAsBilling(1);
        }

        /**
         * set default normal type for order
         */

        $orderChargeType = $session->getChargeType();

        if(is_null($orderChargeType)){
            $session->setChargeType(\Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_NORMAL);
        }

        return $this;
    }

    /**
     * validate stock of quote items
     *
     * @param \Magento\Sales\Model\AdminOrder\Create $order
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _validateOutOfStockItems(\Magento\Sales\Model\AdminOrder\Create $order){
        $quote = $order->getQuote();

        if($this->_salesAdminHelper->allowToAddOutOfStockProduct())
            return true;

        foreach($quote->getAllItems() as $quoteItem){
            $result = $this->validateStockByQuoteItem($quoteItem);

            if($result !== true){
                throw new \Magento\Framework\Exception\LocalizedException($result);
            }
        }

        return true;
    }

    /**
     * validate stock of a quote item
     *
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return bool|\Magento\Framework\Phrase
     */
    public function validateStockByQuoteItem(\Magento\Quote\Model\Quote\Item $quoteItem){

        $result = true;

        /** @var \Magento\CatalogInventory\Model\Stock\Item $stockItem */
        $stockItem = $this->stockRegistry->getStockItem(
            $quoteItem->getProduct()->getId(),
            $quoteItem->getProduct()->getStore()->getWebsiteId()
        );
        /* @var $stockItem \Magento\CatalogInventory\Api\Data\StockItemInterface */
        if (!$stockItem instanceof \Magento\CatalogInventory\Api\Data\StockItemInterface) {
            return __('The stock item for Product is not valid.');
        }

        $parentStockItem = false;

        /**
         * Check if product in stock. For composite products check base (parent) item stock status
         */
        if ($quoteItem->getParentItem()) {
            $product = $quoteItem->getParentItem()->getProduct();
            $parentStockItem = $this->stockRegistry->getStockItem(
                $product->getId(),
                $product->getStore()->getWebsiteId()
            );
        }
        $productOptionFromQuote = $quoteItem->getProduct()->getCustomOption('machine_type_id');
        $needToCheckStock = true;
        if ($productOptionFromQuote && $productOptionFromQuote->getValue()) {
            $needToCheckStock = false;
        }
        if ($stockItem && $needToCheckStock) {
            if (
                !$stockItem->getIsInStock() ||
                ($parentStockItem && !$parentStockItem->getIsInStock()) ||
                (!$quoteItem->getHasChildren() && !$this->checkQty($quoteItem, $stockItem, $this->_rikiQuoteHelper->getProductBuyRequestQtyByQuoteItem($quoteItem)))
            ) {
                return __('The product %1  is out of stock.', $parentStockItem? $quoteItem->getParentItem()->getName() : $quoteItem->getName());
            }
        }

        return $result;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param \Magento\CatalogInventory\Model\Stock\Item $stockItem
     * @param $qty
     * @return bool
     */
    public function checkQty(\Magento\Quote\Model\Quote\Item $quoteItem,\Magento\CatalogInventory\Model\Stock\Item $stockItem, $qty)
    {
        if (!$stockItem->getManageStock()) {
            return true;
        }

        if ($stockItem->getBackorders() != \Magento\CatalogInventory\Model\Stock::BACKORDERS_NO) {
            return true;
        }

        if ($stockItem->getQty() - $stockItem->getMinQty() - $qty < 0) {
            return false;
        }

        if ($quoteItem->getUnitCase() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
            $product =$quoteItem->getProduct();
            if($product){
                $product->setCaseDisplay(\Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY);
                $product->setUnitQty($quoteItem->getUnitQty());
                return $this->helperInventory->checkWarehousePieceCase($quoteItem->getProduct(), $qty);
            }
        }

        return true;
    }
}
