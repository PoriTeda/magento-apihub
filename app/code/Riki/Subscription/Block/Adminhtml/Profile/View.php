<?php
namespace Riki\Subscription\Block\Adminhtml\Profile;

class View extends \Magento\Framework\View\Element\Template
{
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
     * @var \Magento\Framework\Pricing\Adjustment\CalculatorInterface
     */
    protected $_adjustmentCalculator;
    /**
     * @var \Riki\Subscription\Helper\Order\Simulator
     */
    protected  $helperSimulator;

    /**
     * View constructor.
     * @param \Magento\Framework\Pricing\Adjustment\CalculatorInterface $adjustmentCalculator
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Pricing\Helper\Data $helperPrice
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param \Riki\SubscriptionCourse\Model\Course $courseModel
     * @param \Riki\Subscription\Helper\Data $helperData
     * @param \Magento\Catalog\Helper\Image $helperImage
     * @param \Magento\Catalog\Block\Product\ListProduct $blockProduct
     * @param Index $blockIndex
     * @param \Riki\Subscription\Model\Profile\ResourceModel\Profile $profileResource
     * @param \Magento\Customer\Model\ResourceModel\Address\CollectionFactory $addressCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory
     * @param array $data
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     */
    public function __construct(
        \Magento\Framework\Pricing\Adjustment\CalculatorInterface $adjustmentCalculator,
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\Subscription\Model\Profile\Profile $profile,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Pricing\Helper\Data $helperPrice,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Riki\SubscriptionCourse\Model\Course $courseModel,
        \Riki\Subscription\Helper\Data $helperData,
        \Magento\Catalog\Helper\Image $helperImage,
        \Magento\Catalog\Block\Product\ListProduct $blockProduct,
        \Riki\Subscription\Block\Frontend\Profile\Index $blockIndex,
        \Riki\Subscription\Model\Profile\ResourceModel\Profile $profileResource,
        \Magento\Customer\Model\ResourceModel\Address\CollectionFactory $addressCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory,
        array $data = [],
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Riki\Subscription\Helper\Order\Simulator $simulator
    )
    {
        $this->_adjustmentCalculator = $adjustmentCalculator;
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
        $this->pageConfig->getTitle()->set(__('定期便のお届け内容の確認・変更'));
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
            $arrReturn[$pcartId]['profile'] = $arrData;
            $arrReturn[$pcartId]['details'] = $this->_productFactory->create()->load($arrData['product_id']);
            $arrReturn[$pcartId]['amount'] = $this->_adjustmentCalculator->getAmount($arrReturn[$pcartId]['details']->getFinalPrice($arrData['qty']), $arrReturn[$pcartId]['details'])->getValue();
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

    public function getCourseInfo($courseId) {
        if($courseId > 0){
            $model = $this->_courseModel->load($courseId);
            return $model;
        }
        return [];
    }

    public function getShippingAddressInfo(){
        $profileId = $this->getEntity()->getData("profile_id");
        $shippingIds =$this->_profileResource->getShippingAddress($profileId);
        $arrShipping = array();
        foreach ($shippingIds as $shippingId) {
            if(!isset($arrShipping[$shippingId['shipping_address_id']])) {
                $arrShipping[$shippingId['shipping_address_id']] = 1;
            }
        }
        $customerId = array($this->getEntity()->getData("customer_id"));
        $arrShipping = array_keys($arrShipping);
        if(count($arrShipping) > 0) {
            $collection = $this->_addressRepository->create();
            $collection->setCustomerFilter($customerId);
            $result = $collection->addAttributeToFilter('entity_id', ['in' => $arrShipping]);
            return $result;
        }
        return ;
    }

    public function getPaymentMethod() {
        $payment = $this->_profileModel->getPaymentFee($this->getEntity()->getData("payment_method"));
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

    public function getDeliveryType($profileId) {
        $deliveries = $this->_blockProfileIndex->getDeliveryType($profileId);
        $arrDeliveries = array();
        foreach ($deliveries as $delivery) {
            $arrDeliveries[] = $this->_blockProfileIndex->getDeliveryTypeText($delivery);
        }
        if(count($arrDeliveries) > 0) {
            return implode(',', $arrDeliveries);
        }

        return ;
    }

    public function checkAllowEditSubscriptionProfile() {
        $nextOrderDate = $this->getEntity()->getData('next_order_date');
        $OrderDate = $this->_dateTime->gmtDate('YmdHis',$nextOrderDate);
        $nextDeliveryDate = $this->getEntity()->getData('next_delivery_date');
        $DeliveryDate = $this->_dateTime->gmtDate('YmdHis',$nextDeliveryDate);
        $originDate =  $this->_timezone->formatDateTime($this->_dateTime->gmtDate(),2);
        $currentDate = $this->_dateTime->gmtDate('YmdHis',$originDate);
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
}
