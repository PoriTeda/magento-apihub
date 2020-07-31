<?php

namespace Riki\Loyalty\Helper;

use Magento\Framework\App\Helper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\DateTime;
use Riki\Loyalty\Model\ConsumerDb\ShoppingPoint;
use Riki\Loyalty\Model\Reward;
use Magento\Payment\Model\Method\AbstractMethod;

class Data extends Helper\AbstractHelper
{

    const XPATH_EXPIRY_PERIOD = 'riki_loyalty/point/expiration';
    const XPATH_EXPIRY_RETRY = 'riki_loyalty/point/retrypoint';

    /**
     * @var \Riki\Sales\Helper\ConnectionHelper
     */
    protected $_connection;

    /**
     * @var DateTime
     */
    protected $_dateTime;

    /**
     * @var TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var \Riki\Loyalty\Model\RewardFactory
     */
    protected $_rewardFactory;
    /**
     * @var \Riki\Loyalty\Model\Config\Source\Reward\Type
     */
    protected $_rewardTypeSource;
    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentHelper;

    /** @var  \Magento\Payment\Model\Checks\SpecificationFactory */
    protected $methodSpecificationFactory;
    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customerModel;
    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $_addressRepository;
    /**
     * Session quote
     *
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_sessionQuote;

    /**
     * Data constructor.
     * @param Helper\Context                                     $context
     * @param DateTime                                           $dateTime
     * @param TimezoneInterface                                  $localeDate
     * @param \Riki\Sales\Helper\ConnectionHelper                $connectionHelper
     * @param \Riki\Loyalty\Model\RewardFactory                  $rewardFactory
     * @param \Riki\Loyalty\Model\Config\Source\Reward\Type      $rewardTypeSource
     * @param \Magento\Payment\Helper\Data                       $paymentHelper
     * @param \Magento\Customer\Model\Customer                   $customerModel
     * @param \Magento\Payment\Model\Checks\SpecificationFactory $methodSpecificationFactory
     * @param \Magento\Customer\Api\AddressRepositoryInterface   $addressRepository
     * @param \Magento\Backend\Model\Session\Quote               $sessionQuote
     */
    public function __construct(
        Helper\Context $context,
        DateTime $dateTime,
        TimezoneInterface $localeDate,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper,
        \Riki\Loyalty\Model\RewardFactory $rewardFactory,
        \Riki\Loyalty\Model\Config\Source\Reward\Type $rewardTypeSource,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Customer\Model\Customer $customerModel,
        \Magento\Payment\Model\Checks\SpecificationFactory $methodSpecificationFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Backend\Model\Session\Quote $sessionQuote
    )
    {
        $this->_dateTime = $dateTime;
        $this->_localeDate = $localeDate;
        $this->_connection = $connectionHelper;
        $this->_rewardFactory = $rewardFactory;
        $this->_rewardTypeSource = $rewardTypeSource;
        $this->_paymentHelper = $paymentHelper;
        $this->methodSpecificationFactory = $methodSpecificationFactory;
        $this->_customerModel = $customerModel;
        $this->_addressRepository = $addressRepository;
        $this->_sessionQuote = $sessionQuote;
        parent::__construct($context);
    }

    /**
     * @return integer
     */
    public function getDefaultExpiryPeriod()
    {
        $config = (int) $this->scopeConfig->getValue(self::XPATH_EXPIRY_PERIOD);
        if (!$config) {
            return Reward::DEFAULT_RETRY;
        }
        return $config;
    }
    /**
     * @return integer
     */
    public function getDefaultRetryPoint()
    {
        $config = (int) $this->scopeConfig->getValue(self::XPATH_EXPIRY_RETRY);
        if (!$config) {
            return Reward::DEFAULT_EXPIRY;
        }
        return $config;
    }

    /**
     * Point action date, depend on time zone config
     *
     * @return string
     */
    public function pointActionDate()
    {
        return $this->_localeDate->date()->format('Y-m-d');
    }

    /**
     * @param int $expiryPeriod
     * @return string
     * @throws \Zend_Date_Exception
     */
    public function scheduledExpiredDate($expiryPeriod = null)
    {
        if (!$expiryPeriod) {
            $expiryPeriod = $this->getDefaultExpiryPeriod();
        }
        $scheduledExpiredDate = new \Zend_Date();
        $scheduledExpiredDate->setTimezone($this->_localeDate->getConfigTimezone());
        $scheduledExpiredDate->addDay($expiryPeriod);
        //round up to the end of month
        return (string) date('Y/m/t', $scheduledExpiredDate->getTimestamp());
    }

    /**
     * Subtract days with magento timezone
     *
     * @param string $date
     * @return integer
     */
    public function dayOffset($date)
    {
        $toDate = $this->_localeDate->date($date);
        $result = $this->_localeDate->date()->diff($toDate)->days;
        return abs($result);
    }

    /**
     * Get point status label
     *
     * @param integer $status
     * @return string
     */
    public function getPointStatusLabel($status)
    {
        switch ($status) {
            case Reward::STATUS_TENTATIVE:
                return __('Tentative');
            case Reward::STATUS_SHOPPING_POINT:
                return __('Shopping point');
            case Reward::STATUS_REDEEMED:
                return __('Redeemed');
            case Reward::STATUS_PENDING_APPROVAL:
                return __('Pending approval');
            case Reward::STATUS_ERROR:
                return __('Error');
            case Reward::STATUS_CANCEL:
                return __('Cancel');
            default:
                return $status;
        }
    }

    /**
     * Get point type label
     *
     * @param integer $type
     * @return string
     */
    public function getPointTypeLabel($type)
    {
        switch ($type) {
            case ShoppingPoint::TYPE_POINT:
                return __('Shopping point');
            case ShoppingPoint::TYPE_COIN:
                return __('Nestle coin');
            default:
                return $type;
        }
    }

    /**
     * Get point issue type label
     *
     * @param integer $type
     * @return string
     */
    public function getPointIssueType($type)
    {
        switch ($type) {
            case Reward::TYPE_CAMPAIGN:
                return __('CAMPAIGN');
            case Reward::TYPE_PURCHASE:
                return __('PURCHASE');
            case Reward::TYPE_PAID:
                return __('PAID');
            case Reward::TYPE_ADJUSTMENT:
                return __('ADJUSTMENT');
            default:
                return $type;
        }
    }

    /**
     * Net selling price for unit item
     *
     * @param \Magento\Quote\Model\Quote\Item|\Magento\Sales\Model\Order\Item $quoteItem
     * @return float
     */
    public function netSellingPrice($quoteItem)
    {
        switch (true) {
            case $quoteItem instanceof \Magento\Quote\Model\Quote\Item:
                $qty = $quoteItem->getQty();
                break;
            case $quoteItem instanceof \Magento\Sales\Model\Order\Item:
                $qty = $quoteItem->getQtyOrdered();
                break;
            default:
                return 0.0000;
        }
        $taxPercent = $quoteItem->getTaxPercent()/100;
        $discountExclTax = $quoteItem->getBaseDiscountAmount()/(1 + $taxPercent);
        return floor($quoteItem->getBasePrice() - $discountExclTax/$qty);
    }

    /**
     * @param array $orderItemIds
     * @return array
     */
    public function getOrderItemsPointEarned(array $orderItemIds)
    {
        /** @var \Riki\Loyalty\Model\ResourceModel\Reward $rewardResourceModel */
        $rewardResourceModel = $this->_rewardFactory->create()->getResource();

        return $rewardResourceModel->getOrderItemsPointEarned($orderItemIds);
    }

    /**
     * @param $orderId
     * @return array
     */
    public function getOrderFullPointEarned($orderId)
    {
        if ($orderId instanceof \Magento\Sales\Model\Order) {
            $orderId = $orderId->getIncrementId();
        }

        $salesConnection = $this->_connection->getSalesConnection();
        $select = $salesConnection->select()->from(
            'riki_reward_point',
            ['order_item_id', 'point_type', 'point', 'sales_rule_id', 'qty', 'level']
        )->where(
            'riki_reward_point.order_no = ?',
            (string)$orderId
        )->where(
            'riki_reward_point.level IN(?)',
            [\Riki\Loyalty\Model\Reward::LEVEL_ITEM, \Riki\Loyalty\Model\Reward::LEVEL_ORDER]
        )->where(
            'riki_reward_point.status=?',
            \Riki\Loyalty\Model\Reward::STATUS_SHOPPING_POINT
        );

        return $salesConnection->fetchAll($select);
    }

    /**
     * @param \Magento\Sales\Model\Order|string $order
     * @return array|int
     */
    public function getEarnedPointByOrder($order)
    {
        $result = 0;

        if ($order instanceof \Magento\Sales\Model\Order) {
            $order = $order->getIncrementId();
        }

        if ($order) {
            $salesConnection = $this->_connection->getSalesConnection();

            $select = $salesConnection->select()->from(
                'riki_reward_point',
                ['SUM(point)']
            )->where(
                'riki_reward_point.order_no = ?',
                (string)$order
            );

            $result = $salesConnection->fetchCol($select);
        }

        return $result;
    }

    /**
     * Check this order is waiting point approve or not, for adminhtml only
     *
     * @param \Magento\Sales\Model\Order
     * @return boolean
     */
    public function waitingPointApprove($order)
    {
        if (!$order->getData('allowed_earned_point')) {
            return false;
        }
        /** @var \Riki\Loyalty\Model\Reward $rewardModel */
        $rewardModel = $this->_rewardFactory->create();
        $pendingPoint = $rewardModel->getResource()->pointOrderByStatus(
            $order->getIncrementId(),
            \Riki\Loyalty\Model\Reward::STATUS_PENDING_APPROVAL
        );
        if ($pendingPoint) {
            return true;
        }
        return false;
    }

    /**
     * @param $value
     * @return string
     */
    public function getRewardTypeTitleByValue($value){
        return $this->_rewardTypeSource->getTitleByValue($value);
    }
    /**
     * Check payment method model
     *
     * @param \Magento\Payment\Model\MethodInterface $method
     * @return bool
     */
    protected function _canUseMethod($method,$quote)
    {
        return $this->methodSpecificationFactory->create(
            [
                AbstractMethod::CHECK_USE_FOR_COUNTRY,
                AbstractMethod::CHECK_USE_FOR_CURRENCY,
                AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
            ]
        )->isApplicable(
            $method,
            $quote
        );
    }

    /**
     * Check and prepare payment method model
     *
     * Redeclare this method in child classes for declaring method info instance
     *
     * @param \Magento\Payment\Model\MethodInterface $method
     * @return $this
     */
    protected function _assignMethod($method,$quote)
    {
        $method->setInfoInstance($quote->getPayment());
        return $this;
    }

    /**
     * Retrieve available payment methods
     *
     * @return array
     */
    public function getMethods($quote)
    {

            $store = $quote ? $quote->getStoreId() : null;
            $methods = [];
            $result = [];
            $specification = $this->methodSpecificationFactory->create([AbstractMethod::CHECK_ZERO_TOTAL]);
            foreach ($this->_paymentHelper->getStoreMethods($store, $quote) as $method) {
                if ($this->_canUseMethod($method,$quote) && $specification->isApplicable($method, $quote)) {
                    $this->_assignMethod($method,$quote);
                    $methods[] = $method;
                }
            }
            $isB2bCustomer = $this->isB2b($quote->getCustomerId());

            $removeInvoicePayment = false;
            $removeCodPayment = false;

            // check b2b_flag for display invoicedbasedpayment
            if (!$isB2bCustomer) {
                $removeInvoicePayment = true;
            }
            //check gift order for display COD method
            if ($this->isGiftOrder($quote)) {
                $removeCodPayment = true;
            }

            foreach ($methods as $method) {
                if(
                    ($removeInvoicePayment && $method->getCode() == 'invoicedbasedpayment') ||
                    ($removeCodPayment && $method->getCode() == 'cashondelivery')
                ) {
                    continue;
                }
                $result[] = $method;
            }
            if (count($result) > 0) {
                return $result;
            }

            return $methods;
    }
    /**
     * Check is ambassador
     *
     * @param string $customerId customer id
     *
     * @return bool
     */
    public function checkIsAmbassador($customerId)
    {
        $customer = $this->_customerModel->load($customerId);
        if ($customer) {
            $customerMemberShip = $customer->getMembership();

            if (strpos($customerMemberShip, '3') !== false) {
                return true;
            }
        }
        return false;
    }
    /**
     * Is B2b customer
     *
     * @param string $customerId customer id
     *
     * @return mixed
     */
    public function isB2b($customerId)
    {
        $customer = $this->_customerModel->load($customerId);
        return $customer->getData('b2b_flag') && $customer->getData('shosha_business_code');
    }
    /**
     * Is gift order
     *
     * @return bool
     */
    public function isGiftOrder($quote)
    {
        $shippingAddress = $quote->getShippingAddress();;
        $shippingAddressDetail =  $this->getShippingAddressType($shippingAddress);
        if($shippingAddressDetail == \Riki\Customer\Model\Address\AddressType::HOME){
            return false;
        } elseif ($shippingAddressDetail == \Riki\Customer\Model\Address\AddressType::OFFICE) {
            $customerIsAmbassador = $this->checkIsAmbassador($quote->getCustomerId());
            if($customerIsAmbassador){
                return false;
            }
        }
        return true;
    }
    /**
     * @param $shippingAddress
     * @return mixed|string
     */
    public function getShippingAddressType($shippingAddress)
    {
        $rs = \Riki\Customer\Model\Address\AddressType::HOME;
        if ( !empty( $shippingAddress->getCustomerAddressId() ) ) {
            $address = $this->getAddressById($shippingAddress->getCustomerAddressId());
            if ( !empty($address) && !empty($address->getCustomAttribute('riki_type_address')) ) {
                $addressType = $address->getCustomAttribute('riki_type_address')->getValue();
                if (!empty($addressType)) {
                    $rs = $addressType;
                }
            }
        }

        return $rs;
    }
    /**
     * @param $addressId
     * @return bool|\Magento\Customer\Api\Data\AddressInterface
     */
    public function getAddressById($addressId)
    {
        try {
            return $this->_addressRepository->getById($addressId);
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            return false;
        }
    }
    /**
     * Get preferred payment method of customer
     *
     * @return string|false
     */
    public function getPreferredMethod()
    {
        $customerId = $this->_sessionQuote->getCustomerId();

        if (!$customerId) {
            return false;
        }

        $customer = $this->_customerModel->load($customerId);
        $preferredMethod = $customer->getData('preferred_payment_method');

        if (!$preferredMethod) {
            return false;
        }

        return $preferredMethod;
    }
}