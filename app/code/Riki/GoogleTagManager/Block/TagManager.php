<?php

namespace Riki\GoogleTagManager\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Model\Context as contextCustomer;

/**
 * Google Tag Manager Block
 */
class TagManager extends Template {

    const YES_DATA = 'YES';
    const NO_DATA  = 'NO';
    const MEMBER_SHIP_LOADER  = 'MembershipLoaded';

    /**
     * @var HttpContext
     */
    protected $httpContext;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSessionFactory;
    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory
     */
    protected $groupCustomerFactory;
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $resourceModelOrder;
    /**
     * @var \Riki\Subscription\Model\Profile\Profile
     */
    protected $subscriptionProfile;

    protected $orderCollection;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $salesOrderCollection;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerRepository;

    protected $membershipID ;
    protected $isAMBMember ;
    protected $isNestleMember;
    protected $isSpotPurchase;
    protected $isRegularPurchaseSubscriber;
    protected $isPastRegularPurchaseSubscriber;
    protected $courseHelper;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $subProfileModel;
    
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfileData;

    /**
     * @var \Riki\Sales\Helper\Data
     */
    protected $saleHelper;

    /**
     * TagManager constructor.
     *
     * @param Context $context
     * @param HttpContext $httpContext
     * @param \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCustomerFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Sales\Model\Order $resourceModelOrder
     * @param \Riki\Subscription\Model\Profile\Profile $subscriptionProfile
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesOrderCollection
     * @param \Magento\Customer\Api\CustomerRepositoryInterface
     * @param \Riki\Sales\Helper\Data $saleHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        HttpContext $httpContext,
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCustomerFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\SessionFactory $sessionFactory,
        \Magento\Sales\Model\Order $resourceModelOrder,
        \Riki\Subscription\Model\Profile\Profile $subscriptionProfile,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesOrderCollection,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\SubscriptionCourse\Helper\Data $courseHelper,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Helper\Profile\Data $helperProfileData,
        \Riki\Sales\Helper\Data $saleHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->httpContext          = $httpContext;
        $this->groupCustomerFactory = $groupCustomerFactory;
        $this->customerSession      = $customerSession;
        $this->customerSessionFactory = $sessionFactory;
        $this->resourceModelOrder   = $resourceModelOrder;
        $this->subscriptionProfile  = $subscriptionProfile;
        $this->salesOrderCollection = $salesOrderCollection;
        $this->customerRepository   = $customerRepository;
        $this->scopeConfig = $context->getScopeConfig();
        $this->courseHelper = $courseHelper;
        $this->subProfileModel = $profileFactory;
        $this->sessionManager = $context->getSession();
        $this->helperProfileData = $helperProfileData;
        $this->saleHelper = $saleHelper;
    }

    /**
     * @return bool
     */
    public function isLogin()
    {
        if ($this->customerSessionFactory->create()->isLoggedIn()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * If customer group is is one of (Club member, Subscribers) then "YES", else "NO"
     *
     * @param \Riki\Customer\Model\Customer $customer
     * @return int
     */
    public function isNestleMember(\Riki\Customer\Model\Customer $customer)
    {
        //set default value
        $this->isNestleMember = self::NO_DATA;
        //change conditions by ticket NED-1359
        if (!$customer->getPrimaryAddress('default_shipping')) {
            return $this->isNestleMember;
        }
        $shippingAddress = $customer->getPrimaryAddress('default_shipping');
        $regionId = $shippingAddress->getRegionId();
        $prefectureCode = $this->helperProfileData->getPrefectureCodeOfRegion([$regionId]);
        if ($customer->getFirstname() && $customer->getLastname() &&
            $customer->getFirstnamekana() && $customer->getLastnamekana() &&
            $customer->getDob() && $customer->getEmail() &&
            $shippingAddress->getPostcode() && $prefectureCode
            && $shippingAddress->getTelephone()
        ) {
            $this->isNestleMember = self::YES_DATA;
        }
        return $this->isNestleMember;
    }

    /**
     * If customer is login and if he his purchase history contains at
     * least one order with order type is SUBSCRIPTION then "YES", else "NO"
     *
     * @param $customer
     * @return int
     */
    public function isRegularPurchaseSubscriber($customer)
    {
        $this->isRegularPurchaseSubscriber = self::NO_DATA;
        $listRikiType = [
            \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT,
           \Riki\Sales\Helper\Order::RIKI_TYPE_SUBSCRIPTION
        ];
        $totalOrder = $this->resourceModelOrder->getCollection()
            ->addFieldToFilter('customer_id', $customer->getId())
            ->addFieldToFilter('riki_type', ['in' => $listRikiType])
            ->setPageSize(1)
            ->count();
        if( $totalOrder && $totalOrder>0 ) {
            $this->isRegularPurchaseSubscriber = self::YES_DATA;
        }
        return $this->isRegularPurchaseSubscriber;
    }

    /**
     * If customer is login and if he his purchase history contains
     * at least one order with order type is SPOT then "YES", else "NO"
     *
     * @param $customer
     * @return int
     */
    public function isSpotPurchase($customer)
    {
        $this->isSpotPurchase = self::NO_DATA;
        $totalOrder = $this->resourceModelOrder->getCollection()
            ->addFieldToFilter('customer_id', $customer->getId())
            ->addFieldToFilter('riki_type', 'SPOT')
            ->setPageSize(1)
            ->count();
        if ( $totalOrder && $totalOrder>0 ) {
            $this->isSpotPurchase = self::YES_DATA;
        }
        return $this->isSpotPurchase;
    }

    /**
     * If customer is login and if he got at least one subscription profile, then set "YES", else "NO"
     *
     * @param $customer
     * @return int
     */
    public function isPastRegularPurchaseSubscriber($customer)
    {
        $this->isPastRegularPurchaseSubscriber = self::NO_DATA;
        $totalProfile = $this->subscriptionProfile->getCollection()
                                ->addFieldToFilter('customer_id', $customer->getId())
                                ->setPageSize(1)
                                ->count();
        if ($totalProfile && $totalProfile >0) {
            $this->isPastRegularPurchaseSubscriber = self::YES_DATA;
        }

        return $this->isPastRegularPurchaseSubscriber;
    }

    /**
     * If customer.membership contains "ambassador" then "YES"else "NO"
     *
     * @param $customer
     * @return int
     */
    public function isAMBMember($customer)
    {
        $this->isAMBMember = self::NO_DATA;
        if ($customer->getData('amb_type')) {
            if ($customer->getData('amb_type')==1) {
                $this->isAMBMember = self::YES_DATA;
            }
        }
        return $this->isAMBMember;
    }

    /**
     * ConsumerDB ID
     *
     * @param $customer
     * @return null
     */
    public function membershipID($customer)
    {
        $this->membershipID = null;
        if ($customer->getData('consumer_db_id')) {
            $this->membershipID = $customer->getData('consumer_db_id');
        }
        return $this->membershipID;
    }

    /**
     * Set Data Customer
     *
     * @param $customerSession
     * @return $this
     */
    public function setDataCustomer($customerSession)
    {
        $customer = $this->customerRepository->getById($customerSession->getId());
        if ($customer) {
            $this->isAMBMember($customer);

            $this->isNestleMember($customer);

            $this->isRegularPurchaseSubscriber($customer);

            $this->isSpotPurchase($customer);

            $this->isPastRegularPurchaseSubscriber($customer);

            $this->membershipID($customer);
        }
        return $this;
    }

    /**
     * Get data layer
     *
     * @return string
     */
    public function getDataLayer()
    {
        $dataLayer['isAMBMember']    = $this->isAMBMember;
        $dataLayer['isNestleMember'] = $this->isNestleMember;
        $dataLayer['isSpotPurchase'] = $this->isSpotPurchase;
        $dataLayer['isRegularPurchaseSubscriber']     = $this->isRegularPurchaseSubscriber;
        $dataLayer['isPastRegularPurchaseSubscriber'] = $this->isPastRegularPurchaseSubscriber;
        if ($this->membershipID !=null) {
            $dataLayer['membershipID']   = $this->membershipID;
        } else {
            $dataLayer['membershipID']   = '';
        }
        $dataLayer['event'] = self::MEMBER_SHIP_LOADER;
        return $dataLayer;
    }

    /**
     * Get order collection
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function getOrderCollection()
    {
        $orderIds = $this->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return;
        }

        if (!$this->orderCollection) {
            $this->orderCollection = $this->salesOrderCollection->create();
            $this->orderCollection->addFieldToFilter('entity_id', ['in' => $orderIds]);
        }

        return $this->orderCollection;
    }

    /**
     * Show qty of EA or cs
     * If unit case = CS, qty cs = qty/unit_qty
     *
     * @param $item
     *
     * @return string
     *
     */
    public function getQtyEaCs($item)
    {
        $unitCase  = $item->getUnitCase();
        $qty       = round($item->getQtyOrdered());
        $text      = $qty ;
        if ($unitCase =='CS') {
            $unitQty = $item->getUnitQty();
            $qtyCs  = ($qty/$unitQty);
            //$text = $qtyCs ." (".__('CS')." ($qty ".__('EA')."))";
            $text = $qtyCs;
        }
        return $text;
    }

    /**
     *  Get price product EA or CS
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return float|int
     */
    public function getPriceEaCs($item)
    {
        $price = $item->getPriceInclTax();
        return $price;
    }

    /**
     * @return string|void
     */
    public function getOrdersTrackingCode()
    {
        $collection = $this->getOrderCollection();
        if (!$collection) {
            return;
        }

        $result  = [];
        $product = [];
        foreach ($collection as $order) {
            $subscriptionCode =  $this->getCourseSubsciption($order);
            
            $this->saveGaClientId($order);
            
            foreach ($order->getAllVisibleItems() as $item) {
                $product[] = [
                    'name'     => $item->getName(),
                    'price'    => (int)$this->getPriceEaCs($item),
                    'quantity' => $this->getQtyEaCs($item),
                    'sku' => $item->getSku(),
                    'category' => $subscriptionCode
                ];
            }
            $transaction = [
                'transactionId' => $order->getIncrementId(),
                'transactionAffiliation' => 'shop.nestle.jp',
                'transactionTotal'    => (int)$order->getGrandTotal(),
                'transactionTax'      => (int)$order->getTaxRikiTotal(),
                'transactionShipping' => (int)$order->getShippingInclTax(),
                    'transactionProducts' => $product
            ];

            $result = $transaction;
        }

        return \Zend_Json::encode($result);
    }

    /**
     * Check allow show google tag manager
     *
     * @return bool
     */
    public function checkAllowShowTag()
    {
        $enableGoogleAnalytic = $this->scopeConfig->getValue(
            'google/analytics/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeManager->getStore()->getId()
        );
        $enableTagManager = $this->scopeConfig->getValue(
            'google/analytics/type',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeManager->getStore()->getId()
        );
        if ($enableGoogleAnalytic &&  $enableTagManager =='tag_manager') {
            return true;
        }
        return false;
    }

    /**
     * @param $order
     * @return mixed|string
     */
    public function getCourseSubsciption($order)
    {
        if ($order->getData('riki_type') != \Riki\SubscriptionCourse\Model\Course\Type::TYPE_ORDER_SPOT) {
            $profile = $this->subProfileModel->create()->load($order->getData('subscription_profile_id'));
            if ($profile->getId()) {
                if ($subCourseCode = $this->courseHelper->getCourseCodeByCourseId($profile->getData('course_id'))) {
                    return $subCourseCode;
                }
            }
        }
        return '';
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return string
     */
    public function isWellnessAMB($customer)
    {
        if ($customer->hasData('membership') && $customer->getData('membership')) {
            $listMemberShip = explode(',', $customer->getData('membership'));
            // 15 id of Wellness Ambassador Members
            if (in_array(15, $listMemberShip)) {
                return self::YES_DATA;
            }
        }
        return self::NO_DATA;
    }

    /**
     * Get data layer
     *
     * @return string
     */
    public function getDataLayerByCustomer()
    {
        $customerFactory  = $this->customerSessionFactory->create();
        $dataLayer = [];
        if ($customerFactory->getCustomerId() != null) {
            $customer = $customerFactory->getCustomer();
            $dataLayer['isAMBMember']    = $this->isAMBMember($customer);
            $dataLayer['isNestleMember'] = $this->isNestleMember($customer);
            $dataLayer['isSpotPurchase'] = $this->isSpotPurchase($customer);
            $dataLayer['isRegularPurchaseSubscriber']     = $this->isRegularPurchaseSubscriber($customer);
            $dataLayer['isPastRegularPurchaseSubscriber'] = $this->isPastRegularPurchaseSubscriber($customer);
            if ($memberShip = $this->membershipID($customer)) {
                $dataLayer['membershipID']   = $memberShip;
            } else {
                $dataLayer['membershipID']   = '';
            }
            $dataLayer['event'] = self::MEMBER_SHIP_LOADER;
            $boolWellnessAMb = $this->isWellnessAMB($customer);
            $dataLayer['isWellnessAMBHome'] = $boolWellnessAMb;
            $dataLayer['isWellnessAMBOffice'] = $boolWellnessAMb;
            $dataLayer['lineID'] = $this->getLineId($customer);
            $dataLayer['activeCourseCode'] = $this->getActiveCourseCode($customer);

        }
        return $dataLayer;
    }

    /**
     * Save value of ga client id to order
     *
     * @param $order
     * @return bool
     */
    public function saveGaClientId($order)
    {
        if ($order) {
            $gaClientId = $this->sessionManager->getData('gaClientId');
            if ($gaClientId !=null) {
                /** @var \Magento\Sales\Model\Order $order */
                $order->setData('ga_client_id', $gaClientId);
                $order->getResource()->saveAttribute($order, 'ga_client_id');
                return true;
            }
        }
        return false;
    }

    /**
     * Get data for AFF tag
     *
     * @return array
     */
    public function getDataForAffTag()
    {
        $collection = $this->getOrderCollection();
        if (!$collection) {
            return;
        }

        $result = [];
        foreach ($collection as $order) {
            $subscriptionCode =  $this->getCourseSubsciption($order);
            $totalAmount = 0;

            foreach ($order->getAllVisibleItems() as $item) {
                // Check item is free gift
                if (!$this->saleHelper->isFreeGift($item)) {
                    if ($item->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                        $totalAmount += (int)($this->getOriginalPriceForBundleItem($item) * $item->getQtyOrdered());
                    } else {
                        $totalAmount += (int)($item->getOriginalPrice() * $item->getQtyOrdered());
                    }
                }
            }

            $content = '&_buid=' . $order->getIncrementId();

            foreach ($order->getAllVisibleItems() as $item) {
                $content .= '&ni=' . $item->getSku();
                $content .= ',' . $subscriptionCode;
                // Check item is free gift
                if ($this->saleHelper->isFreeGift($item)) {
                    $content .= ',0';
                } else {
                    if ($item->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                        $content .= ',' . (int)$this->getOriginalPriceForBundleItem($item);
                    } else {
                        $content .= ',' . (int)$item->getOriginalPrice();
                    }
                }
                $content .= ',' . (int)$item->getQtyOrdered();
                $content .= ',' . (int)$totalAmount;
            }

            $result[$order->getIncrementId()] = $content;
        }

        return $result;
    }

    /**
     * Get original price for bundle item
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return int
     */
    public function getOriginalPriceForBundleItem($item)
    {
        $originalPrice = 0;

        $productOption = $item['product_options'];

        if ($productOption && !empty($productOption['bundle_options'])) {
            foreach ($productOption['bundle_options'] as $options) {
                if (!empty($options['value'])) {
                    foreach ($options['value'] as $productInfo) {
                        $productPrice = !empty($productInfo['price']) ? (int)$productInfo['price'] : 0;
                        $originalPrice += $productPrice;
                    }
                }
            }
        }
        return $originalPrice;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return string
     */
    public function getLineId($customer)
    {
        if ($customer->hasData('line_id')) {
            return $customer->getData('line_id');
        }
        return '';
    }

    /**
     * Get active course code
     *
     * @param $customer
     * @return string
     */
    public function getActiveCourseCode($customer)
    {
        $courseCode = '';
        $customerId = $customer->getData('entity_id');
        if ($customerId) {
            $courseCode = $this->courseHelper->getListCourseCodeByCustomerId($customerId);
        }

        return $courseCode;
    }
}
