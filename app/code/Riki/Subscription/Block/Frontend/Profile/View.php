<?php
namespace Riki\Subscription\Block\Frontend\Profile;

use Riki\Subscription\Helper\Data as SubscriptionHelperData;
use Riki\Subscription\Model\Profile\ResourceModel\ProfileLink\CollectionFactory as ProfileLinkCollectionFactory;

class View extends \Magento\Framework\View\Element\Template
{
    const XML_PATH_TAX_ORDER_DISPLAY_CONFIG = 'tax/sales_display/shipping';
    /**
     * @var string
     */
    protected $_template = 'subscription-profile-view.phtml';

    protected $profile;

    /**
     * @var \Riki\Subscription\Model\Profile\Profile
     */
    protected $_profileModel;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */

    protected $coreRegistry;
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $_helperPrice;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $_helperProfile;
    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $_courseModel;
    /**
     * @var \Riki\Subscription\Helper\Data
     */
    protected $_helperData;
    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $_helperImage;

    /**
     * @var \Magento\Catalog\Block\Product\ListProduct
     */
    protected $_blockProduct;
    /**
     * @var Index
     */
    protected $_blockProfileIndex;
    /**
     * @var \Riki\Subscription\Model\Profile\ResourceModel\Profile
     */
    protected $_profileResource;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $_addressRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;

    protected $_productFactory;

    protected $_simpleCache;
    /**
     * @var \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory
     */
    protected $wrappingCollectionFactory;
    /**
     * @var \Riki\Subscription\Helper\Order\Simulator
     */
    protected  $helperSimulator;

    /* @var \Riki\Subscription\Model\Profile\ResourceModel\ProfileLink\CollectionFactory */
    protected $profileLinkCollection;
    protected $_checkoutDataHelper;

    /**
     * View constructor.
     * @param \Magento\Checkout\Helper\Data $checkoutDataHelper
     * @param ProfileLinkCollectionFactory $profileLinkCollectionFactory
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Pricing\Helper\Data $helperPrice
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param \Riki\SubscriptionCourse\Model\Course $courseModel
     * @param SubscriptionHelperData $helperData
     * @param \Magento\Catalog\Helper\Image $helperImage
     * @param \Magento\Catalog\Block\Product\ListProduct $blockProduct
     * @param Index $blockIndex
     * @param \Riki\Subscription\Model\Profile\ResourceModel\Profile $profileResource
     * @param \Magento\Customer\Model\ResourceModel\Address\CollectionFactory $addressCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Riki\Subscription\Helper\Order\Simulator $simulator
     * @param array $data
     */
    public function __construct(
        \Magento\Checkout\Helper\Data $checkoutDataHelper,
        ProfileLinkCollectionFactory $profileLinkCollectionFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\Subscription\Model\Profile\Profile $profile,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Pricing\Helper\Data $helperPrice,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Riki\SubscriptionCourse\Model\Course $courseModel,
        SubscriptionHelperData $helperData,
        \Magento\Catalog\Helper\Image $helperImage,
        \Magento\Catalog\Block\Product\ListProduct $blockProduct,
        \Riki\Subscription\Block\Frontend\Profile\Index $blockIndex,
        \Riki\Subscription\Model\Profile\ResourceModel\Profile $profileResource,
        \Magento\Customer\Model\ResourceModel\Address\CollectionFactory $addressCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Riki\Subscription\Helper\Order\Simulator $simulator,
        array $data = []
    )
    {
        $this->_checkoutDataHelper = $checkoutDataHelper;
        $this->profileLinkCollection = $profileLinkCollectionFactory;
        $this->coreRegistry = $registry;
        $this->_profileModel = $profile;
        $this->_helperPrice = $helperPrice;
        $this->_dateTime = $dateTime;
        $this->_helperProfile = $helperProfile;
        $this->_courseModel = $courseModel;
        $this->_helperData = $helperData;
        $this->_helperImage = $helperImage;
        $this->_blockProduct = $blockProduct;
        $this->_blockProfileIndex = $blockIndex;
        $this->_profileResource = $profileResource;
        $this->_addressRepository = $addressCollectionFactory;
        $this->_timezone = $context->getLocaleDate();
        $this->_dateTime = $dateTime;
        $this->_productFactory = $productFactory;
        $this->wrappingCollectionFactory = $wrappingCollectionFactory;
        $this->helperSimulator = $simulator;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('Delivery schedule of regular flights'));
        $this->getCustomer();
    }

    public function getEntity()
    {
        return $this->coreRegistry->registry('riki_subscription_profile_view');
    }
    /**
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('customer/account/');
    }

    public function getCustomer() {
        $this->_profileModel->setCustomerId($this->getEntity()->getData("customer_id"));
        return $this;
    }
    public function getListProduct()
    {
        if(!empty($this->_simpleCache[__FUNCTION__])) {
            $this->_simpleCache[__FUNCTION__];
        }
        
        $arrProductCats = $this->getEntity()->getProductCartData();

        $arrReturn = [];

        foreach($arrProductCats as $pcartId => $arrData) {
            if ($arrData['parent_item_id'] == null || $arrData['parent_item_id'] == 0) {
                $arrReturn[$pcartId]['profile'] = $arrData;
                $arrReturn[$pcartId]['details'] = $this->_productFactory->create()->load($arrData['product_id']);
                if ($arrData['product_type'] != 'bundle') {
                    $amount = $this->getRenderPrice($arrReturn[$pcartId]['details']);
                } else {
                    $amount = $this->_helperData->getBundleMaximumPrice($arrReturn[$pcartId]['details']);
                }
                $arrReturn[$pcartId]['amount'] = $amount;
            }
        }

        $this->_simpleCache[__FUNCTION__] = $arrReturn;

        return $arrReturn;
    }

    public function getProductPrice($product) {
        return $this->_blockProduct->getProductPrice($product);
    }
    public function getBaseUrlSubProfile() {

        return $this->getUrl('subscriptions/profile/');
    }

    public function getCourseInfo($course_id) {
        if($course_id > 0){
            $model = $this->_courseModel->load($course_id);
            return $model;
        }
        return [];
    }

    public function getShippingAddressInfo(){
        $profile_id = $this->getEntity()->getData("profile_id");
        $shippingIds =$this->_profileResource->getShippingAddress($profile_id);
        $arr_shipping = array();
        foreach ($shippingIds as $shippingId) {
            if(!isset($arr_shipping[$shippingId['shipping_address_id']])) {
                $arr_shipping[$shippingId['shipping_address_id']] = 1;
            }
        }
        $customer_id = array($this->getEntity()->getData("customer_id"));
        $arr_shipping = array_keys($arr_shipping);
        if(count($arr_shipping) > 0) {
            $collection = $this->_addressRepository->create();
            $collection->setCustomerFilter($customer_id);
            $result = $collection->addAttributeToFilter('entity_id', ['in' => $arr_shipping]);
            return $result;
        }
        return ;
    }

    public function getPaymentMethod() {
        $payment = $payment = $this->getEntity()->getPaymentFee();
        return $payment['payment_name'];
    }

    public function getFormatPriceProfile($price) {
        return $this->_helperPrice->currency($price, true, false);
    }

    public function getProductImagesProfile($product) {
        return $this->_helperImage->init($product, 'cart_page_product_thumbnail')
            ->keepFrame(false)
            ->constrainOnly(true)
            ->resize(160, 160);

    }


    public function getTotalProductsPrice(){

        if(!empty($this->_simpleCache[__FUNCTION__])) {
            return $this->_simpleCache[__FUNCTION__];
        }

        $total = $this->getEntity()->getTotalProductsPrice();

        $this->_simpleCache[__FUNCTION__] = $total;

        return $total;
    }

    public function getGiftWrappingFee(){
        $giftFee = $this->getEntity()->getGiftWrappingFee();
        return $giftFee;
    }

    public function getFinalShippingFee(){
        $_shippingfee = $this->_helperProfile->getShippingFeeView($this->getEntity()->getData('productcats'),$this->getEntity()->getData('store_id'));
        return $_shippingfee;
    }

    public function getPaymentFee(){
        $payment = $this->getEntity()->getPaymentFee();
        return $payment['fixed_amount'];
    }

    public function getPointsUsed(){
        $points = $this->getEntity()->getCurrencyPointsUsed();
        return $points;
    }

    public function getCashOnDeliveryFee(){
        $payment = $this->_profileModel->getPaymentFee($this->getEntity()->getData("payment_method"));
        return $payment['fixed_amount'];
    }

    public function getTotalPaymentFee(){
        $total = ($this->getTotalProductsPrice()
                + $this->getGiftWrappingFee()
                + $this->getFinalShippingFee()
                + $this->getPaymentFee())
            - $this->getPointsUsed();
        return $this->getFormatPriceProfile($total);
    }

    public function getTentativePointEarned(){
        $tentativePoint = $this->_profileModel->getTentativePointEarned();
        return number_format($tentativePoint);
    }

    public function getDeliveryType($profile_id) {
        $deliverys = $this->_blockProfileIndex->getDeliveryType($profile_id);
        $arr_deliverys = array();
        foreach ($deliverys as $delivery) {
            $arr_deliverys[] = $this->_blockProfileIndex->getDeliveryTypeText($delivery);
        }
        if(count($arr_deliverys) > 0) {
            return implode(',', $arr_deliverys);
        }

        return ;
    }

    public function checkAllowEditSubscriptionProfile() {
        $nextOrderDate = $this->getEntity()->getData('next_order_date');
        $OrderDate = $this->_dateTime->gmtDate('YmdHis',$nextOrderDate);
        $nextDeliveryDate = $this->getEntity()->getData('next_delivery_date');
        $DeliveryDate = $this->_dateTime->gmtDate('YmdHis',$nextDeliveryDate);
        $origin_date =  $this->_timezone->formatDateTime($this->_dateTime->gmtDate(),2);
        $currentDate = $this->_dateTime->gmtDate('YmdHis',$origin_date);
        if($currentDate >= $OrderDate && $currentDate <= $DeliveryDate) {
            return false;
        }
        return true;
    }

    /**
     * Get Address Name of Address by AddressId
     *
     * @param $addressId
     * @return mixed
     */
    public function getAddressName($addressId){
        $addressName = $this->_helperProfile->getAddressNameByAddressId($addressId);
        return $addressName;
    }
    /**
     * @param $attributeString
     * @return string
     */
    public function getAttributeName($attributeString){
        $nameAtt = '';
        $giftnameCollection = $this->wrappingCollectionFactory->create()
            ->addFieldToFilter('wrapping_id', $attributeString)
            ->addWebsitesToResult()->load();
        if($giftnameCollection){
            $nameAtt = $giftnameCollection->getFirstItem()->getData('gift_name');
        }
        return $nameAtt;
    }

    public function getSimulatorOrderOfProfile($profileId)
    {
        try {
            $simulatorOrder = $this->helperSimulator->createMageOrder($profileId);
            if ($simulatorOrder instanceof \Riki\Subscription\Model\Emulator\Order) {
                return $simulatorOrder;
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }

        return false;
    }

    public function getShippingFeeAfterSimulator($simulatorOrder,$storeId){
        $taxDisplay = $this->_scopeConfig->getValue(self::XML_PATH_TAX_ORDER_DISPLAY_CONFIG,\Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId);
        if($taxDisplay == 1){//exclude tax
            return $simulatorOrder->getShippingAmount();
        }
        else{
            return $simulatorOrder->getShippingInclTax();
        }
    }

    /**
     * Check profile id is tmp return origin profile
     *
     * @param $profileId
     * @return bool
     */
    public function getProfileOriginFromTmp($profileId)
    {
        $profileLinkCollection = $this->profileLinkCollection->create()
            ->addFieldToFilter('linked_profile_id', $profileId)->setOrder('link_id', 'desc');
        if ($profileLinkCollection->getSize() > 0) {
            return $profileLinkCollection->getFirstItem()->getData('profile_id');
        } else {
            return $profileId;
        }
    }

    /**
     * Get render price of product
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return float|\Magento\Framework\Pricing\Amount\AmountInterface
     */
    public function getRenderPrice(\Magento\Catalog\Model\Product $product)
    {
        $finalPrice =  $product->getPriceInfo()->getPrice(\Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE);
        $amount = ($finalPrice instanceof \Magento\Catalog\Pricing\Price\FinalPrice)
            ? $finalPrice->getAmount()
            : $product->getFinalPrice();

        $amount = ($amount instanceof \Magento\Framework\Pricing\Amount\AmountInterface)
            ? filter_var($this->_checkoutDataHelper->formatPrice($amount->getValue()), FILTER_SANITIZE_NUMBER_FLOAT)
            : $amount;

        return $amount;
    }

}