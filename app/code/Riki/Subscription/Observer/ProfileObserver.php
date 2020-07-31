<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\Subscription\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Payment\Model\Method\Free;
use Riki\Subscription\Model\Constant;
use Riki\Subscription\Model\Frequency\Frequency;
use Riki\Subscription\Model\Profile\Profile;
use Riki\DeliveryType\Model\Delitype as Dtype;
use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;
use Riki\Subscription\Helper\Order\Data as SubscriptionOrderHelper;

/**
 * Create subscription profile data in table
 *
 * Class PlaceOrderLogic
 * @package Riki\Subscription\Observer
 */
class ProfileObserver implements ObserverInterface
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var
     */
    private $order;
    /**
     * @var
     */
    private $quote;
    /**
     * @var
     */
    private $course;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfile;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $tzHelper;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $customerAddress;

    /**
     * @var \Riki\Subscription\Helper\Hanpukai\Data
     */
    protected $helperHanpukai;

    /**
     * @var \Magento\Quote\Model\Quote\Address\ItemFactory
     */
    protected $quoteAddressItemFactory;

    /**
     * @var \Magento\Quote\Model\Quote\AddressFactory
     */
    protected $quoteAddress;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dtHelper;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Riki\Customer\Model\CustomerRepository
     */
    protected $rikiCustomerRepository;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;
    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper
     */
    protected $deliveryDateGenerateHelper;
    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $courseModel;
    /**
     * @var \Riki\Subscription\Model\ProductCart\ProductCartFactory
     */
    protected $productCartFactory;
    /**
     * @var Frequency
     */
    protected $frequency;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var Profile
     */
    protected $profile;
    /**
     * @var \Riki\Subscription\Model\DB\TransactionFactory
     */
    protected $transactionFactory;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * ProfileObserver constructor.
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dtHelper
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $tzHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\AddressFactory $customerAddress
     * @param \Riki\Subscription\Helper\Hanpukai\Data $helperHanpukai
     * @param \Magento\Quote\Model\Quote\Address\ItemFactory $itemAddressFactory
     * @param \Magento\Quote\Model\Quote\AddressFactory $quoteAddress
     * @param \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper $deliveryDateGenerateHelper
     * @param \Riki\SubscriptionCourse\Model\Course $courseModel
     * @param \Riki\Subscription\Model\ProductCart\ProductCartFactory $productCartFactory
     * @param Frequency $frequency
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param Profile $profile
     * @param \Riki\Subscription\Model\DB\TransactionFactory $transactionFactory
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\App\State $state,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Magento\Framework\Stdlib\DateTime\DateTime $dtHelper,
        \Magento\Framework\Stdlib\DateTime\Timezone $tzHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\AddressFactory $customerAddress,
        \Riki\Subscription\Helper\Hanpukai\Data $helperHanpukai,
        \Magento\Quote\Model\Quote\Address\ItemFactory $itemAddressFactory,
        \Magento\Quote\Model\Quote\AddressFactory $quoteAddress,
        \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper $deliveryDateGenerateHelper,
        \Riki\SubscriptionCourse\Model\Course $courseModel,
        \Riki\Subscription\Model\ProductCart\ProductCartFactory $productCartFactory,
        \Riki\Subscription\Model\Frequency\Frequency $frequency,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Riki\Subscription\Model\Profile\Profile $profile,
        \Riki\Subscription\Model\DB\TransactionFactory $transactionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->profile = $profile;
        $this->timezone = $timezone;
        $this->frequency = $frequency;
        $this->productCartFactory = $productCartFactory;
        $this->courseModel = $courseModel;
        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
        $this->state = $state;
        $this->quoteRepository = $quoteRepository;
        $this->helperProfile = $helperProfile;
        $this->dtHelper = $dtHelper;
        $this->logger = $logger;
        $this->tzHelper = $tzHelper;
        $this->messageManager = $messageManager;
        $this->customerAddress = $customerAddress;
        $this->helperHanpukai = $helperHanpukai;
        $this->quoteAddressItemFactory = $itemAddressFactory;
        $this->quoteAddress = $quoteAddress;
        $this->rikiCustomerRepository = $rikiCustomerRepository;
        $this->customerRepository = $customerRepository;
        $this->authSession = $authSession;
        $this->deliveryDateGenerateHelper = $deliveryDateGenerateHelper;
        $this->transactionFactory = $transactionFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param $frequencyInterval
     * @param $strFrequencyUnit
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function collectDeliveryDate($frequencyInterval, $strFrequencyUnit)
    {
        $arrDD = [];
        // If not Admin (Frontend - maybe :D)
        if ($this->state->getAreaCode() !== 'adminhtml') {
            if (!$this->order->getData('is_multiple_shipping')) {
                $checkedTypes = [];

                /** @var \Magento\Sales\Model\Order\Item $orderItem */
                foreach ($this->order->getAllItems() as $orderItem) {
                    $deliveryType = $orderItem->getData('delivery_type');

                    if (!in_array($deliveryType, [Dtype::COSMETIC, Dtype::COLD, Dtype::CHILLED])) {
                        $deliveryType = Dtype::COOL_NORMAL_DM;
                    }

                    if (!in_array($deliveryType, $checkedTypes)) {
                        $nextdeliveryDate = $orderItem->getData('delivery_date') ?
                            $this->_calNextDeliveryDate($this->tzHelper->date($orderItem->getData('delivery_date'))->getTimestamp(), $frequencyInterval, $strFrequencyUnit) : null;
                        $arrDD[] = [
                            'deliveryName' => $deliveryType,
                            'deliveryDate' => $orderItem->getData('delivery_date'),
                            'deliveryTime' => $orderItem->getData('delivery_timeslot_id'),
                            'nextDeliveryDate' => $nextdeliveryDate
                        ];
                    }
                    $checkedTypes[] = $deliveryType;
                }
            }
        } else { // admin
            $arrParam = $this->request->getParams();
            if (isset($arrParam['order']['delivery_date'][0])) {
                foreach ($arrParam['order']['delivery_date'][0] as $deliveryType => $deliveryDate) {

                    $arrDD[] = [
                        'deliveryName' => $deliveryType,
                        'deliveryDate' => $deliveryDate,
                        'deliveryTime' => isset($arrParam['order']['delivery_timeslot'][0][$deliveryType]) ? $arrParam['order']['delivery_timeslot'][0][$deliveryType] : null,
                        'nextDeliveryDate' => $arrParam['order']['next_delivery_date'][0][$deliveryType]
                    ];
                }
            }
        }
        return $arrDD;
    }

    /**
     * Set persistent data into quote
     *
     * @param EventObserver $observer
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        $this->order = $order;
        $gilletteSku = $this->scopeConfig->getValue(
            \Nestle\Gillette\Model\ProductInfo::GILLETTE_PRODUCT_SKU
        );
        if ($order->getRelationParentId()) {
            return false;
        }

        /** @var \Magento\Quote\Model\Quote $objQuote */
        $objQuote = $observer->getEvent()->getQuote();
        $this->quote = $objQuote;
        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            $this->order->setData("riki_type", \Riki\Sales\Helper\Order::RIKI_TYPE_SPOT);
            $this->order->setData("gillette_sku", $gilletteSku);
            $courseId = $this->quote->getData(Constant::RIKI_COURSE_ID);
            $frequencyId = $this->quote->getData(Constant::RIKI_FREQUENCY_ID);
            if ($courseId && $frequencyId) {
                $this->order->setData("riki_type", \Riki\Sales\Helper\Order::RIKI_TYPE_SUBSCRIPTION);
            }
            return false;
        }
        if ($objQuote->getData(SubscriptionOrderHelper::IS_PROFILE_GENERATED_ORDER_KEY) == 1) {
            return false;
        }

        $courseId = $this->quote->getData(Constant::RIKI_COURSE_ID);
        if (empty($courseId)) {
            return false;
        }
        //Load Course data
        $this->_loadQuote($courseId);
        if (!$this->course->getId()) {
            return true;
        }
        /**
         * Get List of Region if this is not virtual order
         */
        $productIds = [];
        $arrRegion = [];
        if (!$objQuote->isVirtual()) {
            foreach ($this->order->getAllItems() as $item) {
                $productIds[] = $item->getProductId();
            }
            $arrRegion[] = $this->order->getShippingAddress()->getRegionId();
        }
        //Load subscription order Frequency data
        $frequencyId = $objQuote->getData(Constant::RIKI_FREQUENCY_ID);
        $objFrequency = $this->frequency;
        $objFrequency->load($frequencyId);
        $strFrequencyUnit = $objFrequency->getData("frequency_unit");
        $frequencyInterval = $objFrequency->getData("frequency_interval");

        $multipleCheckout = false;
        //Admin order will not earn point
        $allowEarnPoint = 0;
        if ($this->state->getAreaCode() !== 'adminhtml') {
            $allowEarnPoint = 1;
        }

        //Collect array of delivery date
        $arrDD = $this->collectDeliveryDate($frequencyInterval, $strFrequencyUnit);
        $countArrDD = count($arrDD);
        if ($countArrDD == 1) {
            $strMinNextDD = $this->_calNextDeliveryDate($this->tzHelper->date(new \DateTime($arrDD[0]['deliveryDate']))->getTimestamp(), $frequencyInterval, $strFrequencyUnit);
        } else {
            //Multiple shipment
            if ($arrDD == null || empty($arrDD)) {
                $multipleCheckout = true;
                $arrDD = $this->getDeliveryDateOfOrderItem($this->order);
                $strMinNextDD = empty($arrDD) ? null : min($arrDD);
            } else {
                $strMinDD = $arrDD[0]['deliveryDate'];
                for ($i = 1; $i < count($arrDD); $i++) {
                    $strMinDD = $arrDD[$i]['deliveryDate'] < $strMinDD ? $arrDD[$i]['deliveryDate'] : $strMinDD;
                }
                $strMinNextDD = $this->_calNextDeliveryDate($this->tzHelper->date(new \DateTime($strMinDD))->getTimestamp(), $frequencyInterval, $strFrequencyUnit);
            }
        }
        /*default delivery type is COOL_NORMAL_DM*/
        for ($j = 0; $j < $countArrDD; $j++) {
            /*make sure delivery data is not empty and is array*/
            if (!empty($arrDD[$j]) && is_array($arrDD[$j])) {
                /*empty delivery name or delivery name is not a valid value*/
                if (empty($arrDD[$j]['deliveryName']) || (
                        $arrDD[$j]['deliveryName'] != Dtype::CHILLED
                        && $arrDD[$j]['deliveryName'] != Dtype::COLD
                        && $arrDD[$j]['deliveryName'] != Dtype::COSMETIC
                    )) {
                    /*set default delivery type for empty and invalid value case*/
                    $arrDD[$j]['deliveryName'] = Dtype::COOL_NORMAL_DM;
                }
            }
        }

        /*In case client don't choose anything then calculate the delivery date ourselves*/
        if (!$strMinNextDD) {
            $intCurrentDate = strtotime($this->tzHelper->date()->format("Y-m-d"));
            $strMinNextDD = $this->_calNextDeliveryDate($intCurrentDate, $frequencyInterval, $strFrequencyUnit);
        }
        $nextDeliveryDate = $strMinNextDD;
        /**
         * Save delivery date default for
         */
        $nextDeliveryDateDefault = $dayOfWeek = $nthWeekdayOfMonth = null;
        if ($strFrequencyUnit == 'month' && isset($arrDD[0]['deliveryDate']) && $arrDD[0]['deliveryDate'] != null) {
            $dayDeliveryDate = (int)date('d', strtotime($arrDD[0]['deliveryDate']));
            if ($dayDeliveryDate > 28) {
                $nextDeliveryDateDefault = trim($arrDD[0]['deliveryDate']);
                $nextDeliveryDate = $this->deliveryDateGenerateHelper->getLastDateOfMonth(
                    $nextDeliveryDate,
                    $nextDeliveryDateDefault
                );
            }
            // NED-638: Calculation of the next delivery date
            // If subscription course setup with Next Delivery Date Calculation Option = "day of the week"
            // AND interval_unit="month"
            // AND not Stock Point
            if ($this->course->getData('next_delivery_date_calculation_option')
                == \Riki\SubscriptionCourse\Model\Course::NEXT_DELIVERY_DATE_CALCULATION_OPTION_DAY_OF_WEEK
            ) {
                $dayOfWeek = date('l', strtotime($arrDD[0]['deliveryDate']));
                $nthWeekdayOfMonth = $this->deliveryDateGenerateHelper->calculateNthWeekdayOfMonth(
                    $arrDD[0]['deliveryDate']
                );
                $nextDeliveryDate = $this->deliveryDateGenerateHelper->getDeliveryDateForSpecialCase(
                    $nextDeliveryDate,
                    $dayOfWeek,
                    $nthWeekdayOfMonth
                );
            }
        }

        //Calculate Next Order Date
        $nextOrderDate = $this->helperProfile->calNextOrderDate($nextDeliveryDate, $arrRegion, $productIds,
            $this->helperProfile->getCourseData($courseId)->getData('exclude_buffer_days'));

        $salesCount = 0;
        foreach ($this->order->getAllItems() as $item) {
            if ($item->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                continue;
            }
            $buyRequest = $item->getBuyRequest();

            if (isset($buyRequest['options']['ampromo_rule_id'])) {
                continue;
            }
            if ($item->getData('prize_id')) {
                continue;
            }
            if ($item->getData('is_riki_machine') and $item->getData('price') == 0) {
                continue;
            }
            if ($item->getSku() == $gilletteSku) {
                $item->setData('is_gillette_product', 1);
                continue;
            }
            $salesCount += $item->getQtyOrdered();
        }
        $salesValueCount = $this->order->getGrandTotal();
        /**
         * Prepare data for Subscription Profile
         */
        $objProfile = $this->profile;
        $objProfile->setData(
            [
                'course_id' => $courseId,
                'course_name' => $this->course->getData("course_name"),
                'customer_id' => $this->order->getCustomerId(),
                'store_id' => $this->order->getStoreId(),
                'frequency_unit' => $strFrequencyUnit,
                'frequency_interval' => $frequencyInterval,
                'payment_method' => $this->order->getData("payment")->getData("method") !== Free::PAYMENT_METHOD_FREE_CODE ? $this->order->getData("payment")->getData("method") : null,
                /** REM-121 $shippingFee incluce tax for subscription_profile table */
                'shipping_fee' => $this->order->getData("shipping_incl_tax"),
                'shipping_condition' => $this->order->getData("shipping_method"),
                'order_channel' => $this->order->getData("order_channel"),
                'ship_next_delivery' => false,
                'penalty_amount' => false,
                'next_delivery_date' => $nextDeliveryDate,
                'next_order_date' => $nextOrderDate,
                'status' => Profile::STATUS_ENABLED,
                'order_times' => 1,
                'sales_count' => $salesCount,
                'sales_value_count' => $salesValueCount,
                'earn_point_on_order' => $allowEarnPoint,
                'data_generate_delivery_date' => $nextDeliveryDateDefault,
                'day_of_week' => $dayOfWeek,
                'nth_weekday_of_month' => $nthWeekdayOfMonth
            ]
        );
        if ($this->authSession->getUser()) {
            $objProfile->setData('created_user', $this->authSession->getUser()->getUserName());
            $objProfile->setData('updated_user', $this->authSession->getUser()->getUserName());
        }
        $subCourseType = $this->course->getData('subscription_type');
        if ($this->order->getData("payment")->getData("method") == \Bluecom\Paygent\Model\Paygent::CODE) {
            $objProfile->setData('trading_id', $this->order->getIncrementId());
        }
        if ($subCourseType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
            if ($objQuote->getData(Constant::RIKI_HANPUKAI_QTY)) {
                $objProfile->setData('hanpukai_qty', $objQuote->getData(Constant::RIKI_HANPUKAI_QTY));
            }
        }

        $this->order->setData('subscription_order_time', 1);
        $isFreeOrder = false;
        if ($this->order->getGrandTotal() == 0) {
            $isFreeOrder = true;
        }
        /** if subscription course allow delay payment then set order riki type is DELAY_PAYMENT */
        if ($this->course->isDelayPayment() && !$isFreeOrder) {
            $this->order->setData("riki_type", \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT);
        } elseif ($subCourseType == SubscriptionType::TYPE_HANPUKAI) {
            $this->order->setData("riki_type", \Riki\Sales\Helper\Order::RIKI_TYPE_HANPUKAI);
        } elseif ($subCourseType == SubscriptionType::TYPE_SUBSCRIPTION || $subCourseType == SubscriptionType::TYPE_MULTI_MACHINES) {
            $this->order->setData("riki_type", \Riki\Sales\Helper\Order::RIKI_TYPE_SUBSCRIPTION);
        }
        try {
            /**
             * Add some items to transaction
             * @var \Riki\Subscription\Model\DB\TransactionFactory
             */
            $transaction = $this->transactionFactory->create();
            $transaction->addObject($objProfile);
            $transaction->addObject($this->order);
            //Collect cart_product_item for the transaction
            $transaction = $this->saveProductCart($this->order, $courseId, $objProfile, $multipleCheckout, $arrDD, $transaction);
            $this->saveTransaction($transaction, $objProfile, $this->order);
        } catch (\Exception $e) {
            $this->logger->critical("Fail to create Order Profile: #".$this->order->getId()." ".$e->getMessage());
            //Update order status
            $this->order->setData('status', 'canceled')
                ->setData('profile_id', null)
                ->save();
            //Throw error to frontend
            throw $e;
        }
        /*Cal API to consumer DB to update delivery number*/
        $this->updateDeliveryNumberToConsumerDB($this->order->getCustomerId(), $subCourseType);

        return true;
    }

    /**
     * @param $transaction
     * @param $profile
     * @param $order
     * @return mixed
     * @throws \Exception
     */
    protected function saveTransaction($transaction, $profile, $order)
    {
        $transaction->startTransaction();
        $error = false;

        $profileClass = get_class($profile);
        $orderClass = get_class($order);
        $newProfileId = 0;
        try {
            foreach ($transaction->objects as $object) {
                if ($object instanceof $profileClass) {   //subscription_profile
                    $newObj = $object->save();
                    $newProfileId = $newObj->getProfileId();
                } elseif ($object instanceof $orderClass) {   //sales_order
                    if ($newProfileId > 0) {
                        //Assign new profile id in transaction
                        $object->setData("subscription_profile_id", $newProfileId);
                        $object->save();
                    } else {
                        $e = new \Exception(__("Internal error. Check exception log for details."));
                        throw $e;
                    }
                } else {  //subscription_profile_product_cart
                    if ($newProfileId > 0) {
                        $object->setData('profile_id', $newProfileId);  //Assign new profile id in transaction
                        $object->save();
                    } else {
                        $e = new \Exception(__("Internal error. Check exception log for details."));
                        throw $e;
                    }
                }
            }
        } catch (\Exception $e) {
            $error = $e;
        }

        if ($error === false) {
            try {
                $transaction->runCallbacks();
            } catch (\Exception $e) {
                $error = $e;
            }
        }
        if ($error) {
            $transaction->rollbackTransaction();
            throw $error;
        } else {
            $transaction->commitTransaction();
            /**
             * NED-346: Log earn_point_on_order value when creating profile
             */
            $profileLogger = new \Zend\Log\Logger();
            $profileWriter = new \Zend\Log\Writer\Stream(BP . '/var/log/earn_point_on_order.log');
            $profileLogger->addWriter($profileWriter);
            $profileLogger->info(" profile_id: ".$newProfileId." earn_point_on_order : " . $profile->getEarnPointOnOrder());
        }

        return $transaction;
    }

    /**
     * @param $order
     * @param $courseId
     * @param $objProfile
     * @param $multipleCheckout
     * @param $arrDD
     * @param $transaction
     * @return \Magento\Framework\DB\TransactionFactory
     * @throws \Exception
     */
    protected function saveProductCart($order, $courseId, $objProfile, $multipleCheckout, $arrDD, $transaction)
    {
        $arrItemIdProductId = $this->getArrItemIdProductId($order);
        /* Save product cart here */
        $addressId = 0;
        $customerAddress = $this->customerAddress->create()->getCollection();
        $customerAddress->addFieldToFilter('parent_id', $order->getCustomerId());
        $customerAddressDefault = 0;
        $nextDeliveryDate = $objProfile->getData('next_delivery_date');
        if ($customerAddress->getSize() >= 1) {
            $customerAddressDefault = $customerAddress->getFirstItem()->getData('entity_id');
        }
        if ($this->helperHanpukai->getHanpukaiType($courseId) == 'hsequence') {
            $productInfo = [
                'profile_id' => $objProfile->getData("profile_id"),
                'shipping_address_id' => $order->getShippingAddress()
                    ->getCustomerAddressId() == null ?
                    $customerAddressDefault : $order->getShippingAddress()->getCustomerAddressId(),
                'billing_address_id' => $order->getBillingAddress()
                    ->getCustomerAddressId() == null ?
                    $customerAddressDefault : $order->getBillingAddress()->getCustomerAddressId(),
                'delivery_date' => $nextDeliveryDate
            ];
            $courseObj = $this->helperProfile->getCourseData($courseId);
            if ($courseObj->getData('hanpukai_type') ==
                \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI_SEQUENCE) {
                $multiQty = $objProfile->getData('hanpukai_qty');
            } else {
                $multiQty = 1;
            }
            $newProduct = $this->helperHanpukai->replaceHanpukaiSequenceProduct(
                $courseId,
                2,
                $productInfo,
                $multiQty
            );
            foreach ($newProduct as $itemCart) {
                $product_cart = $this->productCartFactory->create();
                $product_cart->setData($itemCart);
                try {

                    /**
                     * Doest not save bundle item of product bundle
                     */
                    if ($product_cart->getParentItemId() <= 0 || $product_cart->getParentItemId() == null) {
                        //Add to transaction
                        $transaction->addObject($product_cart);
                        //$product_cart->save();
                    }
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
        } else {
            if ($multipleCheckout) {
                foreach ($this->order->getAllItems() as $item) {
                    $product_cart = $this->productCartFactory->create();
                    // NED-661: Not save product cart if it has parent item
                    if ($item->getParentItemId()) {
                        continue;
                    }

                    $buyRequest = $item->getBuyRequest();

                    if (isset($buyRequest['options']['ampromo_rule_id'])) {
                        continue;
                    }
                    if ($item->getData('prize_id')) {
                        continue;
                    }
                    if ($item->getData('is_riki_machine')) {
                        continue;
                    }
                    if ($item->getData('is_gillette_product')) {
                        continue;
                    }
                    $shippingAddressId = $this->getOrderItemAddress($item->getQuoteItemId());
                    if (!$shippingAddressId) {
                        $shippingAddressId = $order->getShippingAddress()->getCustomerAddressId() == null ?
                            $customerAddressDefault : $order->getShippingAddress()->getCustomerAddressId();
                    }
                    $deliveryDate = ($item->getData('product_type') == 'virtual') ?
                        null : $item->getData('delivery_date');
                    $product_cart->setData(
                        [
                            //'profile_id' => $objProfile->getData("profile_id"),
                            'qty' => $item->getQtyOrdered(),
                            'product_type' => $item->getProductType(),
                            'product_id' => $item->getProductId(),
                            'product_options' => json_encode($item->getProductOptions()),
                            'parent_item_id' => $item->getParentItemId() != '' ?
                                $arrItemIdProductId[$item->getParentItemId()] : '',
                            'shipping_address_id' => $shippingAddressId,
                            'billing_address_id' => $order->getBillingAddress()->getCustomerAddressId() == null ?
                                $customerAddressDefault : $order->getBillingAddress()->getCustomerAddressId(),
                            'gift_message_id' => $item->getGiftMessageId() == null ? null : $item->getGiftMessageId(),
                            'gw_id' => $item->getGwId() == null ? null : $item->getGwId(),
                            'delivery_date' => $deliveryDate == null || $deliveryDate == '0000-00-00' ?
                                $nextDeliveryDate : $deliveryDate,
                            'delivery_time_slot' => ($item->getData('product_type') == 'virtual') ?
                                null : $item->getData('delivery_time'),
                            'unit_case' => ($item->getUnitCase() != null) ? $item->getUnitCase() : 'EA',
                            'unit_qty' => ($item->getUnitQty() != null) ? $item->getUnitQty() : 1
                        ]
                    );

                    /**
                     * Doest not save bundle item of product bundle
                     */
                    if ($product_cart->getParentItemId() <= 0 || $product_cart->getParentItemId() == null) {
                        //Add to transaction
                        $transaction->addObject($product_cart);
                        //$product_cart->save();
                    }
                }
            } else {
                foreach ($arrDD as $dd) {
                    $ddate[$dd['deliveryName']]['nextDeliveryDate'][] = $nextDeliveryDate;
                    $ddate[$dd['deliveryName']]['deliveryTime'][] = $dd['deliveryTime'];
                }
                foreach ($this->order->getAllItems() as $item) {
                    // NED-661: Not save product cart if it has parent item
                    if ($item->getParentItemId()) {
                        continue;
                    }
                    
                    $buyRequest = $item->getBuyRequest();

                    if (isset($buyRequest['options']['ampromo_rule_id'])) {
                        continue;
                    }
                    if ($item->getData('prize_id')) {
                        continue;
                    }
                    if ($item->getData('is_riki_machine')) {
                        continue;
                    }
                    if ($item->getData('is_gillette_product')) {
                        continue;
                    }
                    $deliveryTypeProduct = $item->getData("delivery_type");

                    if ($deliveryTypeProduct == null) {
                        $this->messageManager->addNotice(
                            sprintf(
                                "The product have id: %s do not have delivery type yet ",
                                $item->getProduct()->getId()
                            )
                        );
                        /* Don't save this product */
                        continue;
                    }

                    $deliveryType = $deliveryTypeProduct;
                    if ($deliveryTypeProduct === \Riki\DeliveryType\Model\Delitype::NORMAl
                        ||
                        $deliveryTypeProduct === \Riki\DeliveryType\Model\Delitype::COOL
                        ||
                        $deliveryTypeProduct === \Riki\DeliveryType\Model\Delitype::DM
                    ) {
                        $deliveryType = \Riki\DeliveryType\Model\Delitype::COOL_NORMAL_DM;
                    }

                    $nextDeliveryDateProductCart = '';
                    if (!empty($ddate[$deliveryType]['nextDeliveryDate'])) {
                        $nextDeliveryDateProductCart = $ddate[$deliveryType]['nextDeliveryDate'][$addressId];
                    }
                    if ($nextDeliveryDateProductCart == null || $nextDeliveryDateProductCart == '') {
                        $nextDeliveryDateProductCart = $nextDeliveryDate;
                    }
                    $deliveryTime = '';
                    if (!empty($ddate[$deliveryType]['deliveryTime'])) {
                        $deliveryTime = $ddate[$deliveryType]['deliveryTime'][$addressId];
                    }
                    $shippingAddressId = $this->getOrderItemAddress($item->getQuoteItemId());
                    if (!$shippingAddressId) {
                        $shippingAddressId = $order->getShippingAddress()->getCustomerAddressId() == null ?
                            $customerAddressDefault : $order->getShippingAddress()->getCustomerAddressId();
                    }

                    /**
                     * Force delivery of product cart by delivery date of profile
                     */
                    $nextDeliveryDateProductCart = $nextDeliveryDate;

                    $product_cart = $this->productCartFactory->create();
                    $product_cart->setData(
                        [
                            'profile_id' => $objProfile->getData("profile_id"),
                            'qty' => $item->getQtyOrdered(),
                            'product_type' => $item->getProductType(),
                            'product_id' => $item->getProductId(),
                            'product_options' => json_encode($item->getProductOptions()),
                            'parent_item_id' => $item->getParentItemId() != '' ?
                                $arrItemIdProductId[$item->getParentItemId()] : '',
                            'shipping_address_id' => $shippingAddressId,
                            'gift_message_id' => $item->getGiftMessageId() == null ? null : $item->getGiftMessageId(),
                            'gw_id' => $item->getGwId() == null ? null : $item->getGwId(),
                            'billing_address_id' => $order->getBillingAddress()->getCustomerAddressId() == null ?
                                $customerAddressDefault : $order->getBillingAddress()->getCustomerAddressId(),
                            'delivery_date' => ($item->getData('product_type') == 'virtual') ?
                                null : $nextDeliveryDateProductCart,
                            'delivery_time_slot' => ($item->getData('product_type') == 'virtual') ?
                                null : $deliveryTime,
                            'unit_case' => ($item->getUnitCase() != null) ? $item->getUnitCase() : 'EA',
                            'unit_qty' => ($item->getUnitQty() != null) ? $item->getUnitQty() : 1,
                            'is_addition' => $item->getData('is_addition')
                        ]
                    );
                    /**
                     * Doest not save bundle item of product bundle
                     */
                    if ($product_cart->getParentItemId() <= 0 || $product_cart->getParentItemId() == null) {
                        //Add to transaction
                        $transaction->addObject($product_cart);
                        //$product_cart->save();
                    }
                }
            }
        }

        return $transaction;
    }

    /**
     * @param $order
     * @return array
     */
    protected function getArrItemIdProductId($order)
    {
        $result = [];
        foreach ($order->getAllItems() as $item) {
            $result[$item->getId()] = $item->getProductId();
        }
        return $result;
    }

    /**
     * @param $time
     * @param $frequencyInterval
     * @param $strFrequencyUnit
     * @return string
     */
    private function _calNextDeliveryDate($time, $frequencyInterval, $strFrequencyUnit)
    {
        $timestamp = strtotime($frequencyInterval . " " . $strFrequencyUnit, $time);

        $objDate = $this->timezone->date();
        $objDate->setTimestamp($timestamp);

        return $objDate->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
    }

    /**
     * @param $time
     * @param $frequencyInterval
     * @param $strFrequencyUnit
     * @return int
     */
    private function _calNextOrderDate($time, $frequencyInterval, $strFrequencyUnit)
    {
        $timestamp = strtotime($frequencyInterval . " " . $strFrequencyUnit, $time);

        $objDate = $this->timezone->date();
        $objDate->setTimestamp($timestamp);

        return $objDate->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
    }

    /**
     * @param $courseId
     * @return void
     */
    private function _loadQuote($courseId)
    {
        $objCourse = $this->courseModel->load($courseId);
        $this->course = $objCourse;
    }

    /**
     * Get Array Delivery Date of each item in order
     *
     * @param $order
     * @return array
     */
    protected function getDeliveryDateOfOrderItem($order)
    {
        $dDate = [];
        foreach ($order->getAllItems() as $item) {
            if ($item->getData('delivery_date') != null || $item->getData('delivery_date') != '') {
                $dDate[] = $item->getData('delivery_date');
            }
        }
        return $dDate;
    }

    /**
     * @param $quoteItemId
     * @return int|mixed
     */
    protected function getOrderItemAddress($quoteItemId)
    {
        $itemAddressModel = $this->quoteAddressItemFactory->create()->getCollection();
        $itemAddressModel->addFieldToFilter('quote_item_id', $quoteItemId);
        $itemAddressModel = $itemAddressModel->getFirstItem();
        if ($itemAddressModel->getId()) {
            $quoteAddressId = $itemAddressModel->getData('quote_address_id');
            $quoteAddressModel = $this->quoteAddress->create()->load($quoteAddressId);
            if ($quoteAddressModel->getId()) {
                return $quoteAddressModel->getData('customer_address_id');
            }
        }
        return 0;
    }

    /**
     * Send delivery number to Consumer DB
     *
     * @param $customerId
     * @param $subCourseType
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function updateDeliveryNumberToConsumerDB($customerId, $subCourseType)
    {
        $customer = $this->customerRepository->getById($customerId);
        if ($customer->getId()) {
            $consumerId = $customer->getCustomAttribute('consumer_db_id');
            if ($consumerId) {
                $consumerId = $consumerId->getValue();
                $consumerData = $this->rikiCustomerRepository->prepareAllInfoCustomer($consumerId);
                if (is_array($consumerData['customer_sub_api'])) {
                    $customerSub = $consumerData['customer_sub_api'];
                    $previousDeliveryNumber = 0;
                    if (isset($customerSub['SUBSCRIPTION_CUMU_DELIVERY'])) {
                        $previousDeliveryNumber = $customerSub['SUBSCRIPTION_CUMU_DELIVERY'];
                    }
                    $deliveryNumber = $previousDeliveryNumber + 1;
                    try {
                        if ($subCourseType == 'hanpukai') {
                            $this->rikiCustomerRepository->setCustomerSubAPI($consumerId, [1137 => 1]);
                        } else {
                            $this->rikiCustomerRepository->setCustomerSubAPI(
                                $consumerId,
                                [1132 => $deliveryNumber, 1131 => 1]
                            );
                        }
                    } catch (\Exception $e) {
                        $this->logger->critical($e);
                    }
                }
            }
        }
    }
}
