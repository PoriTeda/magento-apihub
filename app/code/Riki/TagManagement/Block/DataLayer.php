<?php

namespace Riki\TagManagement\Block;

class DataLayer extends \Magento\Framework\View\Element\Template
{
    const FREQUENCY_UNIT = 'month';
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerModel;
    /**
     * @var \Riki\TagManagement\Helper\Helper
     */
    protected $helper;
    /**
     * @var \Magento\Sales\Api\OrderAddressRepositoryInterface|\Magento\Sales\Model\Order\Address
     */
    protected $_salesOrderAddressModel;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;
    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $_cookieManager;
    /**
     * OnePageSuccess constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Riki\TagManagement\Helper\Helper $helper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Api\OrderAddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\TagManagement\Helper\Helper $helper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Api\OrderAddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        array $data = []
    )
    {
        $this->helper = $helper;
        $this->_checkoutSession = $checkoutSession;
        $this->_salesOrderAddressModel = $addressRepository;
        $this->_customerModel = $customerRepository;
        $this->customerSession = $customerSession;
        $this->profileFactory = $profileFactory;
        $this->_cookieManager = $cookieManager;
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     */
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {

        if (false != $this->getTemplate()) {
            return parent::_toHtml();
        };
        $html = '';
        $order = $this->_checkoutSession->getLastRealOrder();
        //order have gift wrapping
        $subscriptionCode = $this->getCodeProfileSubscription($order);

        if($subscriptionCode !=null) {
            $html .= $this->insertScripInOrderSubscription($order,$subscriptionCode);
        }

        $courCode = (isset($subscriptionCode['course_code'])) ? $subscriptionCode['course_code'] : null;
        $html .= $this->helper->displayScriptA8SPT($order,$courCode);
        $html .= $this->helper->formatGMOSPTScript($order,$courCode);
        $html .= $this->helper->displayScriptAT($order,$courCode);
        $html .= $this->helper->displayScriptA8NDG($order,$courCode);
        $html .= $this->helper->displayScriptGMONDGNBA($order,$courCode);
        $html .= $this->helper->displayScriptA8($order,$courCode);
        return $html;
    }


    /**
     * @param $addressId
     * @param null $orderItemAddressId
     * @return array
     */
    public function getDetailFromAddressId($addressId, $orderItemAddressId = null)
    {
        $result = [];
        if ($orderItemAddressId != null) {
            $addressModel = $this->_salesOrderAddressModel->get($orderItemAddressId);
            if (!$addressModel->getId()) {
                $addressModel = $this->_salesOrderAddressModel->get($addressId);
            }
        } else {
            $addressModel = $this->_salesOrderAddressModel->get($addressId);
        }

        if($addressModel) {
            $result['region_id'] = $addressModel->getData('region_id');
            $result['city'] = $addressModel->getData('city');
            $result['street'] = $addressModel->getData('street');
            $result['riki_type_address'] = $addressModel->getData('riki_type_address');
            $result['first_name'] = $addressModel->getData('firstname');
            $result['last_name'] = $addressModel->getData('lastname');
            $result['first_name_kana'] = $addressModel->getData('firstnamekana');
            $result['last_name_kana'] = $addressModel->getData('lastnamekana');
        }
        return $result;
    }

    /**
     * @param $customerId
     * @return bool
     */
    public function checkIsAmbassador($customerId)
    {
        /**
         * @var \Magento\Customer\Api\Data\CustomerInterface $customer
         */
        $customer = $this->_customerModel->getById($customerId);
        if ($customer->getCustomAttribute('membership') != null) {
            $customerMemberShip = $customer->getCustomAttribute('membership')->getValue();
            if (strpos($customerMemberShip, '3') !== false) {
                return true;
            }
        }
        return false;
    }
    /**
     * @param $shippingAddressId
     * @param $customerId
     * @param null $orderItemShippingAddressId
     * @return bool
     */
    public function checkGiftOrder($shippingAddressId, $customerId, $orderItemShippingAddressId = null)
    {
        if(!$shippingAddressId){
            return false;
        }
        $shippingAddressDetail = $this->getDetailFromAddressId($shippingAddressId, $orderItemShippingAddressId);
        if($shippingAddressDetail['riki_type_address'] == \Riki\Customer\Model\Address\AddressType::HOME){
            return false;
        } elseif ($shippingAddressDetail['riki_type_address'] == \Riki\Customer\Model\Address\AddressType::OFFICE) {
            $customerIsAmbassador = $this->checkIsAmbassador($customerId);
            if($customerIsAmbassador){
                return false;
            }
        }
        return true;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getCodeProfileSubscription(\Magento\Sales\Model\Order $order){
        $result = [];
        if($order->hasData('riki_type')){
            if ($order->getData('riki_type') == \Riki\Sales\Helper\Order::RIKI_TYPE_SUBSCRIPTION ||
                $order->getData('riki_type') == \Riki\Sales\Helper\Order::RIKI_TYPE_HANPUKAI ||
                $order->getData('riki_type') == \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT
            ) {
                $profileId = $order->getData('subscription_profile_id');
                /**
                 * @var \Riki\Subscription\Model\Profile\Profile $profile
                 */
                $profile = $this->profileFactory->create()->load($profileId);
                if($profile->getId()){
                    $result['frequency_unit'] = $profile->getData('frequency_unit');
                    $result['frequency_interval'] = $profile->getData('frequency_interval');
                    $result['course_id'] = $profile->getData('course_id');
                    $course = $profile->getSubscriptionCourse();
                    if($course->getId()){
                        $result['course_code'] = $course->getData('course_code');
                        return $result;
                    }
                }
            }
        }
        return null;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function insertScripInOrderSubscription(\Magento\Sales\Model\Order $order,$subscriptionCode){
        if(isset($subscriptionCode['course_code'])){
            if($subscriptionCode['course_code'] == 'RT000020S'){
                return $this->helper->getConfigScriptSubscriptionCodeRT000020S($order);
            }
            if($subscriptionCode['course_code'] == 'RT000019S'){
                return $this->helper->getConfigScriptSubscriptionCodeRT000019S($order);
            }
            if($subscriptionCode['course_code'] == 'RT000002S'){
                return $this->helper->getConfigScriptSubscriptionCodeRT000002S($order);
            }

            if($subscriptionCode['course_code'] == 'RT000033S'&&
                $subscriptionCode['frequency_unit'] == self::FREQUENCY_UNIT &&
                $subscriptionCode['frequency_interval'] == 2
            ){
                if($this->_cookieManager->getCookie(\Riki\SubscriptionPage\Controller\View\Index::COOKIE_NAME_RT000033S) != null){
                    return $this->helper->getConfigScriptSubscriptionCodeRT000033S($order,$subscriptionCode['course_id']);
                }
            }
            if($subscriptionCode['course_code'] == 'RT000032S' &&
                $subscriptionCode['frequency_unit'] == self::FREQUENCY_UNIT &&
                $subscriptionCode['frequency_interval'] == 1
            ){
                if($this->_cookieManager->getCookie(\Riki\SubscriptionPage\Controller\View\Index::COOKIE_NAME_RT000032S) != null){
                    return $this->helper->getConfigScriptSubscriptionCodeRT000032S($order,$subscriptionCode['course_id']);
                }
            }
            if($subscriptionCode['course_code'] == 'RT000034S' &&
                $subscriptionCode['frequency_unit'] == self::FREQUENCY_UNIT &&
                $subscriptionCode['frequency_interval'] == 2
            ){
                if($this->_cookieManager->getCookie(\Riki\SubscriptionPage\Controller\View\Index::COOKIE_NAME_RT000034S) != null){
                    return $this->helper->getConfigScriptSubscriptionCodeRT000034S($order,$subscriptionCode['course_id']);
                }
            }
        }
        return '';
    }
}
