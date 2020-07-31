<?php
namespace Riki\Sales\Helper;

use Magento\Sales\Model\Order\Address\Renderer;
use Riki\Sales\Model\Config\Source\OrderType as OrderChargeType;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const ORDER_DISTRIBUTION_CHANEL_14 = '14';
    const ORDER_DISTRIBUTION_CHANEL_02 = '02';
    const ORDER_DISTRIBUTION_CHANEL_06 = '06';

    const XML_PATH_RIKI_ORDER_VISIBILITY_MONTH  = 'riki_order/order/order_visibility';

    const XML_PATH_RIKI_ORDER_ORDER_RANDOM_EMAIL  = 'riki_order/order_customer/order_random_email_domain';

    const XML_PATH_ORDER_CANCEL_EMAIL_TEMPLATE = 'riki_email/order_cancel/template';
    const XML_PATH_ORDER_CANCEL_EMAIL_ENABLE = 'riki_email/order_cancel/enable_send_mail';

    const XML_PATH_SUBSCRIPTION_ORDER_CANCEL_EMAIL_TEMPLATE = 'subscriptioncourse/cancelsubscription/cancel_template';
    
    const XML_PATH_ORDER_CANCEL_CVS_ADMIN_EMAIL_TEMPLATE = 'riki_email/order_cancel/cvs_template';

    const XML_PATH_ORDER_CANCEL_EMAIL_SENDER = 'riki_email/order_cancel/identity';

    const XML_PATH_FREE_ORDER_PAYMENT_SHIPMENT_FEE_EMAIL_SENDER = 'riki_order/free_payment_shipment_fee_email/identity';
        
    const XML_PATH_FREE_ORDER_PAYMENT_SHIPMENT_FEE_EMAIL_TEMPLATE = 'riki_order/free_payment_shipment_fee_email/template';

    const XML_PATH_FREE_ORDER_PAYMENT_SHIPMENT_FEE_EMAIL_RECEIVER = 'riki_order/free_payment_shipment_fee_email/email_receiver';
    
    const XML_PATH_EMAILS_ADMIN_RECEIVER_CSV_CANCEL = 'riki_email/order_cancel/admin_mail_csv_payment';

    const XML_PATH_CANCEL_PREORDER_EMAIL_TEMPLATE = 'rikipreorder/email/cancellation_template';

    const XML_PATH_CONFIRMATION_PREORDER_EMAIL_TEMPLATE = 'rikipreorder/email/confirmation_template';
    
    const XML_PATH_CANCEL_FRAUD_LOGIC_ORDER = 'riki_email_notifications/cancel_order_fraud_logic/';

    const XML_PATH_CANCEL_FRAUD_SEGMENT_ORDER = 'riki_email_notifications/cancel_order_fraud_segment/';

    const FRAUD_ORDER_CANCEL_ENABLE = 'enable';

    const FRAUD_ORDER_CANCEL_EMAIL_SENDER = 'email_sender';

    const FRAUD_ORDER_CANCEL_EMAIL_TEMPLATE = 'template';

    protected $storeManager;

    protected $_connection;

    protected $_connectionHelper;

    /**
     * @var Renderer
     */
    protected $addressRenderer;

    /**
     * @var \Riki\CvsPayment\Model\CvsPayment
     */
    protected $cvsPayment;

    /**
     * @var \Riki\PaymentBip\Model\InvoicedBasedPayment
     */
    protected $invoicedBasedPayment;

    protected $_quoteSession;

    protected $_customerFactory;

    protected $_quoteFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $_tzHelper;
    /**
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $_rewardManagement;
    /**
     * @var \Riki\SubscriptionEmail\Helper\Data
     */
    protected $_subscriptionEmailHelper;
    /**
     * @var \Riki\Prize\Model\PrizeFactory
     */
    protected $_prizeFactory;
    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $_ruleFactory;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $_ruleCustomerFactory;

    /**
     * @var \Magento\SalesRule\Model\Coupon
     */
    protected $_coupon;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Coupon\Usage
     */
    protected $_couponUsage;
    /**
     * @var \Amasty\Promo\Helper\Item
     */
    protected $_promoItemHelper;
    /**
     * @var \Riki\Sales\Logger\LoggerSales
     */
    protected $salesLogger;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /** @var \Riki\Quote\Helper\Data  */
    protected $_quoteHelper;

    protected $_promoHelper;
    /**
     * @var \Magento\Framework\Mail\Template\SenderResolverInterface
     */
    protected $senderResolver;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context               $context
     * @param \Magento\Store\Model\StoreManagerInterface          $storeManager
     * @param \Riki\CvsPayment\Model\CvsPayment                   $cvsPayment
     * @param \Riki\PaymentBip\Model\InvoicedBasedPayment         $invoicedBasedPayment
     * @param \Magento\Backend\Model\Session\Quote                $quoteSession
     * @param \Magento\Customer\Model\CustomerFactory             $customerFactory
     * @param \Magento\Quote\Model\QuoteFactory                   $quoteFactory
     * @param \Magento\Framework\Stdlib\DateTime\Timezone         $tzHelper
     * @param \Riki\Loyalty\Model\RewardManagement                $rewardManagement
     * @param \Riki\SubscriptionEmail\Helper\Data                 $subscriptionEmailHelper
     * @param \Magento\Framework\App\ResourceConnection           $resourceConnection
     * @param \Riki\Prize\Model\PrizeFactory                      $prizeFactory
     * @param Renderer                                            $addressRenderer
     * @param \Magento\SalesRule\Model\RuleFactory                $ruleFactory
     * @param \Magento\SalesRule\Model\Rule\CustomerFactory       $ruleCustomerFactory
     * @param \Magento\SalesRule\Model\Coupon                     $coupon
     * @param \Magento\SalesRule\Model\ResourceModel\Coupon\Usage $couponUsage
     * @param ConnectionHelper                                    $connectionHelper
     * @param \Amasty\Promo\Helper\Item                           $promoItemHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Riki\CvsPayment\Model\CvsPayment $cvsPayment,
        \Riki\PaymentBip\Model\InvoicedBasedPayment $invoicedBasedPayment,
        \Magento\Backend\Model\Session\Quote $quoteSession,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Framework\Stdlib\DateTime\Timezone $tzHelper,
        \Riki\Loyalty\Model\RewardManagement  $rewardManagement,
        \Riki\SubscriptionEmail\Helper\Data $subscriptionEmailHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Riki\Prize\Model\PrizeFactory $prizeFactory,
        Renderer $addressRenderer,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\SalesRule\Model\Rule\CustomerFactory $ruleCustomerFactory,
        \Magento\SalesRule\Model\Coupon $coupon,
        \Magento\SalesRule\Model\ResourceModel\Coupon\Usage $couponUsage,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper,
        \Amasty\Promo\Helper\Item $promoItemHelper,
        \Riki\Sales\Logger\LoggerSales $salesLogger,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Quote\Helper\Data $quoteHelper,
        \Riki\Promo\Helper\Data $promoHelper,
        \Magento\Framework\Mail\Template\SenderResolverInterface $senderResolver
    ) {
        $this->storeManager = $storeManager;
        $this->cvsPayment = $cvsPayment;
        $this->addressRenderer = $addressRenderer;
        $this->invoicedBasedPayment = $invoicedBasedPayment;
        $this->_quoteSession = $quoteSession;
        $this->_customerFactory = $customerFactory;
        $this->_quoteFactory = $quoteFactory;
        $this->_tzHelper = $tzHelper;
        $this->_rewardManagement = $rewardManagement;
        $this->_subscriptionEmailHelper = $subscriptionEmailHelper;
        $this->_prizeFactory = $prizeFactory;
        $this->_connection = $resourceConnection->getConnection();
        $this->_ruleFactory = $ruleFactory;
        $this->_ruleCustomerFactory = $ruleCustomerFactory;
        $this->_coupon = $coupon;
        $this->_couponUsage = $couponUsage;
        $this->_connectionHelper = $connectionHelper;
        $this->_promoItemHelper = $promoItemHelper;
        $this->salesLogger = $salesLogger;
        $this->customerRepository = $customerRepository;
        $this->_quoteHelper = $quoteHelper;
        $this->_promoHelper = $promoHelper;
        $this->senderResolver = $senderResolver;
        parent::__construct($context);
    }

    /**
     * Return store configuration value of your template field that which id you set for template
     *
     * @param string $path
     * @param int $storeId
     * @return mixed
     */
    protected function getConfigValue($path, $storeId)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    /**
     * Get current store
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStore()
    {
        return $this->storeManager->getStore();
    }

    /**
     * @return array
     */
    public function getDeliDate($idOrder){
        $arrayDate = array();
        // Trongnd clean here , delivery date not use table riki_delivery_date
        // Please get in order item or shipment item

        return $arrayDate;
    }

    /**
     * Get setting Visibility Months
     *
     * @return mixed
     */
    public function getVisibilityMonths()
    {
        return $this->getConfigValue(
            self::XML_PATH_RIKI_ORDER_VISIBILITY_MONTH,
            $this->getStore()->getStoreId()
        );
    }

    /**
     *GetOrderRandomDomain
     *
     * @return mixed
     */
    public function getOrderRandomDomain()
    {
        return $this->getConfigValue(
            self::XML_PATH_RIKI_ORDER_ORDER_RANDOM_EMAIL,
            $this->getStore()->getStoreId()
        );
    }
    
    /**
     * Check order can cancel
     *
     * @param $order
     *
     * @return bool
     */
    public function checkStatusOrderCancel($order)
    {
        $status = $order->getStatus();
        $check = false;

        if ($status != \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_SHIPPED_ALL &&
            $status != 'delivery_completed' &&
            $status != 'delivered'
        ) {
            $check = true;
        }

        return $check;
    }

    /**
     * Check order use CVS method
     * @param $order
     *
     * @return bool
     */
    public function isCVSMethod($order)
    {
        $payment = $order->getPayment();

        return $payment->getMethod() == $this->cvsPayment->getCode();
    }

    /**
     * @param $order
     * @return bool
     */
    public function  checkCVSMethod($order){

            return $order->getPaymentStatus() == 'payment_collected';

    }
    /**
     * @param $order
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isInvoiceMethod($order)
    {
        $payment = $order->getPayment();

        return $payment->getMethod() == $this->invoicedBasedPayment->getCode();
    }

    /**
     * Get email template
     *
     * @return mixed
     */
    public function getTemplateEmail()
    {
        return $this->getConfigValue(
            self::XML_PATH_ORDER_CANCEL_EMAIL_TEMPLATE,
            $this->getStore()->getStoreId()
        );
    }
    public function getEnableEmail()
    {
        return $this->getConfigValue(
            self::XML_PATH_ORDER_CANCEL_EMAIL_ENABLE,
            $this->getStore()->getStoreId()
        );
    }
    /**
     * Get cancelation of subscription email template
     *
     * @return mixed
     */
    public function getTemplateEmailCancelSubscription()
    {
        return $this->getConfigValue(
            self::XML_PATH_SUBSCRIPTION_ORDER_CANCEL_EMAIL_TEMPLATE,
            $this->getStore()->getStoreId()
        );
    }
    /**
     * Get email template
     *
     * @return mixed
     */
    public function getTemplateEmailCVSAdmin()
    {
        return $this->getConfigValue(
            self::XML_PATH_ORDER_CANCEL_CVS_ADMIN_EMAIL_TEMPLATE,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * Get cancellation preorder email template
     *
     * @return mixed
     */
    public function getCancelPreorderEmailTemplate()
    {
        return $this->getConfigValue(
            self::XML_PATH_CANCEL_PREORDER_EMAIL_TEMPLATE,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * Get cancellation preorder email template
     *
     * @return mixed
     */
    public function getConfirmationPreorderEmailTemplate()
    {
        return $this->getConfigValue(
            self::XML_PATH_CONFIRMATION_PREORDER_EMAIL_TEMPLATE,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * Get senders which send warning email
     *
     * @return mixed
     */
    public function getSenderEmail()
    {
        return $this->getConfigValue(
            self::XML_PATH_ORDER_CANCEL_EMAIL_SENDER,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * Get recipients which send order cancel email
     *
     * @return mixed
     */
    public function getReceiverEmail()
    {
        return $this->getConfigValue(
            self::XML_PATH_EMAILS_ADMIN_RECEIVER_CSV_CANCEL,
            $this->getStore()->getStoreId()
        );
    }


    /**
     * @param $order
     * @return bool
     * @throws \Magento\Framework\Exception\PaymentException
     */
    public function revertShoppingPointCancel($order)
    {
        return $this->_rewardManagement->revertRedeemed($order);
    }
    /**
     * Prepare Data Cancel Order Template
     *
     * @param $order
     *
     * @return array
     */
    public function prepareDataCancelOrderTemplate($order)
    {
        $payment = $order->getPayment();
        $paymentFee = $this->_subscriptionEmailHelper->getFormatPrice($order->getData('fee'));

        $transport = [
            'order' => $order,
            'store' => $order->getStore(),
            'formattedShippingAddressText' => $this->getFormattedShippingAddressText($order),
            'formattedBillingAddressText' => $this->getFormattedBillingAddressText($order),
            'increment_id' => $order->getIncrementId(),
            'created_order' => $order->getCreatedAtFormatted(2),
            'grand_total_order' => $order->getGrandTotal(),
            'total_money_items' => $order->getSubtotalInclTax(),
            'shipping_fee' => $order->getShippingAmount(),
            'payment_method' => $payment->getMethodInstance()->getTitle(),
            'payment_fee' => $paymentFee
        ];
        return $transport;
    }

    /**
     * GetFormattedBillingAddressText
     *
     * @param $order
     *
     * @return mixed
     */
    protected function getFormattedBillingAddressText($order)
    {
        return $this->addressRenderer->format($order->getBillingAddress(), 'text');
    }

    /**
     * GetFormattedShippingAddressText
     *
     * @param $order
     *
     * @return null
     */
    protected function getFormattedShippingAddressText($order)
    {
        return $order->getIsVirtual()
            ? null
            : $this->addressRenderer->format($order->getShippingAddress(), 'text');
    }

    /**
     * @return string
     */
    public function getDistributionChanelByCurrentCustomer(){
        $customer = $this->_customerFactory->create()->load($this->_quoteSession->getQuote()->getCustomerId()) ;

        $memberships = $customer->getMembership();

        if(
            in_array(\Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership::AMB_MEMBERSHIP, explode(',', $memberships))
            || $customer->getAmbType() == \Riki\Customer\Model\AmbType::CODE_1
        ){
            return self::ORDER_DISTRIBUTION_CHANEL_06;
        }

        return self::ORDER_DISTRIBUTION_CHANEL_14;
    }

    /**
     * @param $customerId
     * @return string
     */
    public function getDistributionChanelByCustomerId($customerId){

        if(!$customerId){
            return self::ORDER_DISTRIBUTION_CHANEL_14;
        }
        $customerRepository = null;
        try{
            $customerRepository = $this->customerRepository->getById($customerId);
        }catch (\Exception $e){
            $customerRepository = null;
        }


        $memberships = '';
        if($customerRepository && $customerRepository->getCustomAttribute('membership')){
            $memberships = $customerRepository->getCustomAttribute('membership')->getValue();
        }

        $ambType = '';
        if($customerRepository && $customerRepository->getCustomAttribute('amb_type')){
            $ambType = $customerRepository->getCustomAttribute('amb_type')->getValue();
        }

        $ambSale = '';
        if($customerRepository && $customerRepository->getCustomAttribute('amb_sale')){
            $ambSale = $customerRepository->getCustomAttribute('amb_sale')->getValue();
        }

        if(
            in_array(\Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership::AMB_MEMBERSHIP, explode(',', $memberships)) ||
            $ambType == \Riki\Customer\Model\AmbType::CODE_1 ||
            $ambSale == 1
        ){
            return self::ORDER_DISTRIBUTION_CHANEL_06;
        }

        return self::ORDER_DISTRIBUTION_CHANEL_14;
    }

    /**
     *
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function isSubscriptionCourseOrder($order){
        if($order->getSubscriptionProfileId())
            return true;

        $quote = $this->_quoteFactory->create()->load($order->getQuoteId());

        if($quote instanceof \Magento\Quote\Model\Quote && $quote->getRikiCourseId())
            return true;
        return false;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return mixed
     */
    public function getQuoteByOrder($order){
        $quote = $this->_quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());
        if($quote instanceof \Magento\Quote\Model\Quote && $quote->getId())
            return $quote;
        return new \Magento\Framework\DataObject();
    }

    /**
     * Get current date server
     *
     * @return string
     */
    public function getCurrentDateServer() {
        return $this->_tzHelper->date()->format("Y-m-d");
    }

    /**
     * @param $order
     * @throws \Exception
     */
    public  function cancelPrize($order){
        if($order->getAllItems()){
            foreach ($order->getAllItems() as $item){
                if($item->getData('prize_id') != null){
                    $model = $this->_prizeFactory->create();
                    $model = $model->load($item->getData('prize_id'));
                    if ($model->getId()) {
                        $model->setStatus(0);
                        $model->save();
                    }
                }
            }
        }
    }
    /**
     * @param array $itemIds
     * @return array
     */
    public function orderItemIdToQtyOrdered(array $itemIds){
        if(count($itemIds)){

            $salesConnection = $this->_connectionHelper->getSalesConnection();

            $select = $salesConnection->select()->from(
                'sales_order_item',
                ['item_id', 'qty_ordered']
            )->where(
                'sales_order_item.item_id IN(?)',
                $itemIds
            );

            return $salesConnection->fetchPairs($select);
        }

        return [];
    }
    /**
     * Check shipment export
     * @param $order
     * @return bool
     */
    public function  checkShipment($order){
        $shipExport = false;
        $allShipment = $order->getShipmentsCollection();
        if ($allShipment->getSize()) {
            foreach ($allShipment as $shipment) {
                if ($shipment->getData('shipment_status') != null && $shipment->getData('shipment_status') != 'created') {
                    $shipExport = true;
                }
            }
        }
        return $shipExport;
    }

    /**
     * @param $order
     * @throws \Exception
     */
    public function  cancelShipment($order){
        $allShipment = $order->getShipmentsCollection();
        if ($allShipment->getSize()) {
            foreach ($allShipment as $shipment) {
                $shipment->setShipmentStatus(ShipmentStatus::SHIPMENT_STATUS_CANCEL);
                $shipment->save();
            }
        }
    }

    /**
     * Return coupon of order
     *
     * @param $order
     * @return $this
     */
    public function updateCoupon($order){

        if (!$order) {
            return $this;
        }

        // lookup rule ids
        $ruleIds = explode(',', $order->getAppliedRuleIds());
        $ruleIds = array_unique($ruleIds);

        $ruleCustomer = null;
        $customerId = $order->getCustomerId();

        // use each rule (and apply to customer, if applicable)
        foreach ($ruleIds as $ruleId) {
            if (!$ruleId) {
                continue;
            }
            /** @var \Magento\SalesRule\Model\Rule $rule */
            $rule = $this->_ruleFactory->create();
            $rule->load($ruleId);
            if ($rule->getId()) {
                $rule->loadCouponCode();
                $rule->setTimesUsed($rule->getTimesUsed() - 1);
                $rule->save();

                if ($customerId) {
                    /** @var \Magento\SalesRule\Model\Rule\Customer $ruleCustomer */
                    $ruleCustomer = $this->_ruleCustomerFactory->create();
                    $ruleCustomer->loadByCustomerRule($customerId, $ruleId);

                    if ($ruleCustomer->getId()) {
                        $ruleCustomer->setTimesUsed($ruleCustomer->getTimesUsed() - 1);
                    } else {
                        $ruleCustomer->setCustomerId($customerId)->setRuleId($ruleId)->setTimesUsed(1);
                    }
                    $ruleCustomer->save();
                }
            }
        }

        $this->_coupon->load($order->getCouponCode(), 'code');
        if ($this->_coupon->getId()) {
            $this->_coupon->setTimesUsed($this->_coupon->getTimesUsed() - 1);
            $this->_coupon->save();
            if ($customerId) {
                $this->_couponUsage->updateCustomerCouponTimesUsed($customerId, $this->_coupon->getId());
            }
        }

        return $this;
    }

    /**
     * Check order status shipped
     *
     * @param $order
     * @return bool
     */
    public function validateOrderShip($order){
        $status = $order->getStatus();
        $check = false;

        if ($status == \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_SHIPPED_ALL ||
            $status == \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_PARTIALLY_SHIPPED
        ) {
            $check = true;
        }

        return $check;
    }

    /**
     *  Get Email sender config
     * @return mixed
     */
    public function getFreeOrderShipmentPaymentFeeSender(){
        return $this->getConfigValue(
            self::XML_PATH_FREE_ORDER_PAYMENT_SHIPMENT_FEE_EMAIL_SENDER,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * Get  Email template config
     * @return mixed
     */
    public function getFreeOrderShipmentPaymentFeeTemplate(){
        return $this->getConfigValue(
            self::XML_PATH_FREE_ORDER_PAYMENT_SHIPMENT_FEE_EMAIL_TEMPLATE,
            $this->getStore()->getStoreId()
        );
          
    }

    /**
     *  Ger list of email receiver form config
     * @return mixed
     */
    public function getFreeOrderShipmentPaymentFeeReceivers(){
        $emailConfig =  $this->getConfigValue(
            self::XML_PATH_FREE_ORDER_PAYMENT_SHIPMENT_FEE_EMAIL_RECEIVER,
            $this->getStore()->getStoreId()
        );
        $emailArray = explode(',',$emailConfig);
        return $emailArray;

    }

    /**
     * Check item free gift
     *
     * @param $item
     * @return bool
     */
    public function checkFreegiftItem($item)
    {
        if ( $item->getData('prize_id')
            || (($item->getData('is_riki_machine') && $item->getData('price') == 0))
            || $this->_promoItemHelper->isPromoItem($item)
        ) {
            return true;
        }
        return false;
    }

    /**
     * Combine item cart by address (multiple addresses)
     *
     * @param $quote
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function combineItems($quote)
    {
        $groupItems = [];
        //get all item
        $quoteItems = $quote->getAllItems();

        foreach($quoteItems as $item) {
            // do not check case configurable
            $productType = $item->getProductType();
            if($productType != \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE
                && $productType != \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE
            ) {
                continue ;
            }
            $isFreegift = $this->checkFreegiftItem($item);
            if($item->getParentItemId() || $isFreegift) {
                continue ;
            }
            $addressId = $item->getAddressId();
            $groupItems[$addressId][$item->getId()]['product_id'] = $item->getProductId();
            $groupItems[$addressId][$item->getId()]['gw_id'] = $item->getGwId();
        }

        $arrHandled = [];
        foreach ( $groupItems as $addressId => $itemArr) {

            foreach ($itemArr as $parentId => $itemData) {

                $arrHandled[] = $parentId;
                $productId = $itemData['product_id'];
                $gwId = $itemData['gw_id'];

                foreach($quoteItems as $cartItemObject) {
                    if($cartItemObject->getId() == $parentId
                        || $cartItemObject->getAddressId() != $addressId
                        || in_array($cartItemObject->getId(),$arrHandled)
                        || $this->checkFreegiftItem($cartItemObject)
                    ) {
                        continue 1;
                    }

                    if( $cartItemObject->getAddressId() == $addressId
                        && $cartItemObject->getProductId() == $productId
                        && $cartItemObject->getGwId() == $gwId
                    ){
                        $parentItem = $quote->getItemById($parentId);
                        $combineQty = (int) $parentItem->getQty() + (int) $cartItemObject->getQty();
                        $parentItem->setQty($combineQty);
                        $parentItem->setData('qty_combine', $parentItem->getQty());
                        try {
                            $parentItem->save();
                            $quote->deleteItem($cartItemObject);
                        }
                        catch(\Exception $e){
                            throw new \Magento\Framework\Exception\LocalizedException(
                                __("Unable to remove unnessary quote item , detail :" . $e->getMessage()));
                        }
                    }
                }
            }
        }

        try {
            $quote->collectTotals();
            $quote->save();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__("Unable to recollect total , detail :" . $e->getMessage()));
        }


        return true;
    }

    /**
     *
     */
    public function isSpotFreeGift(\Magento\Sales\Model\Order\Item $item){
        if (
            $item->getData('prize_id') ||
            $this->_promoHelper->isPromoOrderItem($item)
        ) {
            return true;
        }
        return false;
    }

    /**
     *
     */
    public function isFreeGift(\Magento\Sales\Model\Order\Item $item){
        if($this->isSpotFreeGift($item))
            return true;

        if(($item->getData('is_riki_machine') && $item->getData('price') == 0))
            return true;

        return false;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return bool
     */
    public function isAttachmentItem(\Magento\Sales\Model\Order\Item $item)
    {
        if ($this->_promoHelper->isPromoOrderItem($item)
            || $item->getData('prize_id')
            || $item->getData('is_riki_machine')
            || $this->isCumulativeGiftItem($item)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return bool
     */
    public function isFreeAttachmentItem(\Magento\Sales\Model\Order\Item $item)
    {
        if ($this->_promoHelper->isPromoOrderItem($item)
            || $item->getData('prize_id')
            || $this->isCumulativeGiftItem($item)
        ) {
            return true;
        }

        $buyRequest = $item->getBuyRequest();
        if (isset($buyRequest['options']['free_machine_item'])) {
            return true;
        }

        return false;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item|\Magento\Sales\Model\Order\Item $item
     * @return bool
     */
    public function isCumulativeGiftItem($item)
    {
        $buyRequest = $item->getBuyRequest();

        if (isset($buyRequest['options']['ampromo_rule_id'])
            && $buyRequest['options']['ampromo_rule_id'] == 'cumulative'
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param \Magento\Quote\Model\Quote|null $quote
     * @return bool
     */
    public function isFreeOfChargeOrder(\Magento\Quote\Model\Quote $quote)
    {
        $chargeType = $quote->getData('charge_type');

        return $this->isFreeOfChargeType($chargeType);
    }

    /**
     * @param $chargeType
     * @return bool
     */
    public function isFreeOfChargeType($chargeType)
    {
        switch ($chargeType) {
            case OrderChargeType::ORDER_TYPE_FREE_SAMPLE:
                $result = true;
                break;
            case OrderChargeType::ORDER_TYPE_REPLACEMENT:
                $result = true;
                break;
            default:
                $result = false;
        }

        return $result;
    }

    /**
     * Check Order status is IN_PROCESSING and all shipments are exported
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function isOrderInProcessingAndExported(\Magento\Sales\Model\Order $order)
    {
        $orderStatus = $order->getStatus();
        if($orderStatus == \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_IN_PROCESSING) {
            $shipmentCollection = $order->getShipmentsCollection();
            if ($shipmentCollection->getTotalCount()) {
                foreach($shipmentCollection->getItems() as $shipment) {
                    if (!$shipment->getData('is_chirashi') &&
                       !$shipment->getData('ship_zsim') &&
                        $shipment->getShipmentStatus()!= ShipmentStatus::SHIPMENT_STATUS_EXPORTED ) {
                        return false;
                    }
                }
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function isFraudLogicEnable()
    {
        return $this->getConfigValue(
            self::XML_PATH_CANCEL_FRAUD_LOGIC_ORDER . self::FRAUD_ORDER_CANCEL_ENABLE,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @return mixed
     */
    public function isFraudSegmentEnable()
    {
        return $this->getConfigValue(
            self::XML_PATH_CANCEL_FRAUD_SEGMENT_ORDER . self::FRAUD_ORDER_CANCEL_ENABLE,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @param string $shippingDescription
     * @return array
     * @throws \Magento\Framework\Exception\MailException
     */
    public function getFraudEmailSender($shippingDescription)
    {
        if ($shippingDescription == Order::FRAUD_LOGIC_CODE)
        {
            return $this->senderResolver->resolve($this->getConfigValue(
                self::XML_PATH_CANCEL_FRAUD_LOGIC_ORDER . self::FRAUD_ORDER_CANCEL_EMAIL_SENDER,
                $this->getStore()->getStoreId()
            ));
        }
        if ($shippingDescription == Order::FRAUD_SEGMENT_CODE)
        {
            return $this->senderResolver->resolve($this->getConfigValue(
                self::XML_PATH_CANCEL_FRAUD_SEGMENT_ORDER . self::FRAUD_ORDER_CANCEL_EMAIL_SENDER,
                $this->getStore()->getStoreId()
            ));
        }
    }

    /**
     * @return mixed
     */
    public function getFraudLogicTemplateEmail()
    {
        return $this->getConfigValue(
            self::XML_PATH_CANCEL_FRAUD_LOGIC_ORDER . self::FRAUD_ORDER_CANCEL_EMAIL_TEMPLATE,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @return mixed
     */
    public function getFraudSegmentTemplateEmail()
    {
        return $this->getConfigValue(
            self::XML_PATH_CANCEL_FRAUD_SEGMENT_ORDER . self::FRAUD_ORDER_CANCEL_EMAIL_TEMPLATE,
            $this->getStore()->getStoreId()
        );
    }
}
