<?php

namespace Riki\Subscription\Block\Adminhtml\Profile;


class Index extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $_template = 'subscription-profile.phtml';

    protected $profile;

    /**
     * @var \Riki\Subscription\Model\Profile\Profile
     */
    protected $_profileModel;
    
    protected $_profileResourceModel;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    protected $_helperPrice;

    protected $_dateTime;

    protected $_addressModel;

    protected $_helperProfile;

    protected $deliveryType;

    protected $_subscriptionPageHelper;

    protected $_timezone;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\Subscription\Model\Profile\Profile $profile,
        \Riki\Subscription\Model\Profile\ResourceModel\Profile $profileResource,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Pricing\Helper\Data $helperPrice,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Sales\Model\Order\Address $address,
        \Riki\DeliveryType\Model\Product\Deliverytype $deliverytype,
        \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper,
        array $data = []
    ) {
        $this->_subscriptionPageHelper = $subscriptionPageHelper;
        $this->coreRegistry = $registry;
        $this->_profileModel = $profile;
        $this->_profileResourceModel = $profileResource;
        $this->_helperPrice = $helperPrice;
        $this->_dateTime = $dateTime;
        $this->_addressModel = $address;
        $this->_helperProfile = $helperProfile;
        $this->deliveryType = $deliverytype;
        $this->_timezone = $context->getLocaleDate();
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('Edit / confirm the contents of subscription'));
    }

    /**
     * @return bool|\Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function getSubscriptionsProfile()
    {
        if (!($customerId = $this->getCustomerId())) {
            return false;
        }
        if (!$this->profile) {
            $this->profile = $this->_profileModel->getCustomerSubscriptionProfile($customerId);
        }
        return $this->profile;
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getSubscriptionsProfile()) {
            $pager = $this->getLayout()->createBlock(
                'Magento\Theme\Block\Html\Pager',
                'customer.subscription.profile.pager'
            )->setCollection(
                $this->getSubscriptionsProfile()
            );
            $this->setChild('pager', $pager);
            $this->getSubscriptionsProfile()->load();
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('customer/account/');
    }

    public function getCustomerId()
    {
        $customerId = $this->coreRegistry->registry('current_subscription_profile_customer');
        return $customerId;
    }

    public function getShippingFee($profileId,$storeId) {
        return $this->_helperProfile->getShippingFeeByProfileId($profileId,$storeId);
    }

    public function getDateSubscription($date) {
        return $this->_dateTime->date('Y/m/d', $date);
    }

    public function getBaseUrlSubcriptionProfile($id){
        return $this->getUrl('subscriptions/profile/view/id/'.(int)$id);
    }

    public function getBaseUrlSubscription()
    {
        return $this->getUrl('subscriptions/profile/ajax');
    }

    public function getDeliveryType($profileId = 0) {
        $arrDelivery =  array();
        if($profileId) {
            $products = $this->_helperProfile->getProductSubscriptionProfile($profileId);
            if(count($products) >0 ) {
                $deliveryTypeCollection = $this->_helperProfile->getAttributesProduct($products);
                foreach ($deliveryTypeCollection as $delivery) {
                    if ($delivery->getDeliveryType() != null)
                        $arrDelivery[] = $delivery->getDeliveryType();
                }
            }
            return $arrDelivery;
        }
        return $arrDelivery;
    }
    
    public function getDeliveryTypeText($key){
        $arrDelivery = $this->deliveryType->getOptionArray();
        if($arrDelivery) {
            if(isset($arrDelivery[$key])){
                return $arrDelivery[$key];
            }
        }
        return null;
    }

    public function allowChangeSkipNextDelivery($subscription)
    {
        $dateCheck = $this->checkDateToEditSkipNextDelivery($subscription);
        $orderFlag = $subscription->getData('create_order_flag');
        if($this->_subscriptionPageHelper->getSubscriptionType($subscription->getData('course_id'))
            == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
            $isHanpukai = true;
        } else {
            $isHanpukai = false;
        }

        if ($dateCheck && !$isHanpukai && $orderFlag != 1) {
            return true;
        } else {
            return false;
        }
    }

    public function checkDateToEditSkipNextDelivery($subscription) {
        $nextOrderDate = $subscription->getData('next_order_date');
        $OrderDate = $this->_dateTime->gmtDate('Ymd',$nextOrderDate);
        $nextDeliveryDate = $subscription->getData('next_delivery_date');
        $DeliveryDate = $this->_dateTime->gmtDate('Ymd',$nextDeliveryDate);
        $originDate =  $this->_timezone->formatDateTime($this->_dateTime->gmtDate(),2);
        $currentDate = $this->_dateTime->gmtDate('Ymd',$originDate);
        if($currentDate >= $OrderDate && $currentDate <= $DeliveryDate) {
            return false;
        }
        return true;
    }

}
