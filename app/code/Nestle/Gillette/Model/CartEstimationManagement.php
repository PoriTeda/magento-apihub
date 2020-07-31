<?php

namespace Nestle\Gillette\Model;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Nestle\Gillette\Api\Data\CartEstimationInterface;
use Nestle\Purina\Api\DeliveryDateInterface;
use Nestle\Gillette\Api\Data\CartEstimationResultInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Riki\DeliveryType\Model\DeliveryDate;
use Magento\Payment\Model\MethodList;

/**
 * Class CartEstimationManagement
 * @package Nestle\Gillette\Model
 */
Class CartEstimationManagement
    implements \Nestle\Gillette\Api\CartEstimationManagementInterface
{
    /**
     * @var Validator
     */
    protected $gilletteValidator;

    /**
     * @var AddressRepositoryInterface
     */
    protected $customerAddressRepository;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Nestle\Gillette\Api\Data\CartEstimationResultInterfaceFactory
     */
    protected $cartEstimationResultFactory;

    /**
     * @var \Nestle\Purina\Api\DeliveryDateInterface
     */
    protected $deliveryDateInterface;

    /**
     * @var \Bluecom\PaymentFee\Model\PaymentFeeFactory
     */
    protected $paymentFeeFactory;

    /**
     * @var \Riki\Coupons\Helper\Coupon
     */
    protected $couponHelper;
    /**
     * @var \Riki\DeliveryType\Model\DeliveryDate
     */
    protected $deliveryDateModel;

    /**
     * @var \Riki\Subscription\Helper\Order\Simulator
     */
    protected $simulator;

    /**
     * @var \Magento\Payment\Model\MethodList
     */
    protected $methodList;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Nestle\Gillette\Helper\Data
     */
    public $gilletteHelper;

    /**
     * @var \Magento\Framework\Api\Filter
     */
    protected $filter;
    /**
     * @var \Magento\Framework\Api\Search\FilterGroup
     */
    protected $filterGroup;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaInterface
     */
    protected $searchCriteriaInterface;

    /**
     * @var \Magento\SalesRule\Api\RuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * @var \Magento\GiftWrapping\Api\WrappingRepositoryInterface
     */
    protected $wrappingRepository;
    /**
     * @var \Riki\Subscription\Helper\Order\Data
     */
    protected $subscriptionHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;


    public function __construct(
    Validator $validator,
    AddressRepositoryInterface $customerAddressRepository,
    ProductRepositoryInterface $productRepository,
    CartEstimationResultInterfaceFactory $cartEstimationResultFactory,
    DeliveryDateInterface $deliveryDateInterface,
    \Bluecom\PaymentFee\Model\PaymentFeeFactory $paymentFeeFactory,
    DeliveryDate $deliveryDateModel,
    \Riki\Subscription\Helper\Order\Simulator $simulator,
    MethodList $methodList,
    \Psr\Log\LoggerInterface $logger,
    \Nestle\Gillette\Helper\Data $gilletteHelper,
    \Magento\Framework\Api\Filter $filter,
    \Magento\Framework\Api\Search\FilterGroup  $filterGroup,
    \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria,
    \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepository,
    \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepository,
    \Riki\Subscription\Helper\Order\Data $subscriptionHelper,
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ){
        $this->gilletteValidator = $validator;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->productRepository = $productRepository;
        $this->cartEstimationResultFactory = $cartEstimationResultFactory;
        $this->deliveryDateInterface =  $deliveryDateInterface;
        $this->paymentFeeFactory = $paymentFeeFactory;
        $this->deliveryDateModel = $deliveryDateModel;
        $this->simulator = $simulator;
        $this->methodList = $methodList;
        $this->logger = $logger;
        $this->gilletteHelper = $gilletteHelper;
        $this->filter = $filter;
        $this->filterGroup = $filterGroup;
        $this->searchCriteriaInterface = $searchCriteria;
        $this->ruleRepository = $ruleRepository;
        $this->wrappingRepository = $wrappingRepository;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param CartEstimationInterface $cartEstimation
     * @return \Nestle\Gillette\Api\Data\CartEstimationResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function cartEstimation(CartEstimationInterface $cartEstimation)
    {
        $request = clone $cartEstimation;
        $paymentMethod = $cartEstimation->getPaymentMethod();
        if ($paymentMethod) {
            $request->setPaymentMethod($paymentMethod->getData());
        }

        if ($cartEstimation->getConsumerDbId()) {
            $this->gilletteValidator->validateData($cartEstimation, 'cart');
            $this->gilletteHelper->buildRequestLog($request, 'CartEstimation');
            $result = $this->getCartEstimationForCustomer($cartEstimation);
            $this->logger->info('CartEstimation response::'.json_encode($result->getData(), JSON_UNESCAPED_UNICODE));
            return  $result;
        } else {
            throw new \Riki\SubscriptionMachine\Exception\InputException(__(InputException::REQUIRED_FIELD, ['fieldName' => 'consumer_db_id']));;
            /**
             * @TODO: do something
             */
            return $this->getCartEstimationForGuest($cartEstimation);
        }
    }

    public function getCartEstimationForGuest(CartEstimationInterface $cartEstimation) {
        return  $this->simulator->simulateCartEstimation($cartEstimation);
    }

    /**
     * @param CartEstimationInterface $cartEstimation
     * @return Data\CartEstimationResult
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCartEstimationForCustomer(CartEstimationInterface $cartEstimation) {
        $consumerDbId = $cartEstimation->getConsumerDbId();
        /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
        $customer = $this->gilletteValidator->getCustomerByConsumerDbId($consumerDbId);

        /*Time Slot*/
        $timeSlots =  $this->deliveryDateModel->getListTimeSlot();

        $orderSimulator = $this->simulator->simulateCartEstimation($cartEstimation, $customer);
        $quote = $orderSimulator->getData('quote');

        /*Cart info*/
        if ($orderSimulator->getData('riki_type') == 'SUBSCRIPTION') {
            $orderSimulator->setData('course_code', $cartEstimation->getCourseCode());
            $orderSimulator->setData('riki_frequency_id', $cartEstimation->getFrequencyId());
        }
        $cartInfo = $this->getCartInfo($orderSimulator, $customer);
        $cartInfo['delivery_time'] = new DataObject();
        if ($cartEstimation->getDeliveryTime()) {
            foreach ($orderSimulator->getItems() as $item) {
                $cartInfo['delivery_time'] = [
                    'value' => $item->getData('delivery_timeslot_id'),
                    'label' => $item->getData('delivery_time')
                ];
                break;
            }
        }

        /*Cart Items*/
        $cartItems  = $this->getCartItems($orderSimulator);

        /*Date Range*/
        /** @var \Nestle\Purina\Api\Data\DeliverytimeDataInterface[] $dateRangeData */
        $shippingAddressId = $cartEstimation->getShippingAddressId()? : $orderSimulator->getShippingAddress()->getCustomerAddressId();
        if (!$shippingAddressId) {
            $firstShippingAddressId = null;
            foreach ($customer->getAddresses() as $address) {
                $firstShippingAddressId = $address->getId();
                break;
            }
            if (!$firstShippingAddressId) {
                throw new LocalizedException(__('The customer %s does not have address', $cartEstimation->getConsumerDbId()));
            }
            $shippingAddressId = $customer->getDefaultShipping()? : $firstShippingAddressId;
        }
        $dateRange = $this->getDateRange($quote, $shippingAddressId);

        /* get Payment method available*/
        $paymentMethods = $this->getPaymentMethodList($quote);

        /** @var \Nestle\Gillette\Model\Data\CartEstimationResult $result */
        $result = $this->cartEstimationResultFactory->create();
        $result->setCartInformation([$cartInfo]);
        $result->setCartItems($cartItems);
        $customerAddresses = $this->getCustomerAddress($customer);
        $result->setCustomerAddresses($customerAddresses);
        $result->setPaymentMethodAvailable($paymentMethods);
        $result->setDateRange($dateRange);
        $result->setTimeSlot($timeSlots);
        return $result;
    }


    /**
     * @param \Magento\Sales\Api\Data\OrderInterface|object|null $order
     * @return array
     */
    private function getCartInfo($order, $customer) {
        $cartInfo =[];
        $quote = $order->getData('quote');
        $cartInfo['course_code'] = $order->getData('course_code');
        $cartInfo['frequency_id'] = $quote->getData('riki_frequency_id');
        $cartInfo['grand_total'] = $order->getGrandTotal();
        $cartInfo['sub_total'] = $order->getSubtotalInclTax();
        $cartInfo['discount'] = $order->getDiscountAmount();
        $cartInfo['shipping_fee'] = $order->getShippingInclTax();
        $cartInfo['payment_fee'] = (int)$order->getFee();
        $cartInfo['earn_point'] = $order->getBonusPointAmount();
        $cartInfo['used_point'] = $order->getData('used_point');
        $cartInfo['gw_amount'] = $order->getGwItemsPriceInclTax();
        $cartInfo['billing_address_id'] = $order->getBillingAddress()->getCustomerAddressId();
        $cartInfo['shipping_address_id'] = $order->getShippingAddress()->getCustomerAddressId();
        if ($order->getData('new_shipping_address')) {
            $cartInfo['current_shipping_address'] = $order->getData('new_shipping_address');
        }
        $cartInfo['payment_method'] = $order->getPayment()->getMethod();
        $cartInfo['coupon_code'] = $order->getCouponCode();
        $cartInfo['promotion'] = null;
        if ($ruleIds = $order->getAppliedRuleIds()) {
            $ruleName = [];
            $filters[] = $this->filter
                ->setField('rule_id')
                ->setConditionType('in')
                ->setValue($ruleIds);

            $filterGroup[] = $this->filterGroup->setFilters($filters);
            $searchCriteria = $this->searchCriteriaInterface->setFilterGroups($filterGroup);
            $searchResults = $this->ruleRepository->getList($searchCriteria);
            if ($searchResults->getTotalCount()) {
                $data = $searchResults->getItems();
                foreach ($data as $rule) {
                    $title = $rule->getName();
                    foreach ($rule->getStoreLabels() as $storeLabel) {
                        if ($storeLabel->getStoreId() == 0) { // global store
                            $title = $storeLabel->getStoreLabel();
                            continue;
                        }
                        if ($storeLabel->getStoreId() == $quote->getStoreId()) {
                            $title = $storeLabel->getStoreLabel();
                            break;
                        }
                    }
                    $ruleName[] = $title;
                }
            }
            $cartInfo['promotion'] = implode(', ', $ruleName);
        }
        $cartInfo['reward_point'] = $this->gilletteHelper->getRewardPoint($order, $customer);
        return $cartInfo;
    }

    /**
     * @param $orderSimulator
     * @return array
     * @throws NoSuchEntityException
     */
    public function getCartItems($orderSimulator) {
        $cartItems = [];
        foreach ($orderSimulator->getAllVisibleItems() as $item) {
            $cartItem = [];
            $cartItem['product_id'] = (int)$item->getProduct()->getId();
            $cartItem['product_name'] = $item->getName();
            $cartItem['sku'] = $item->getSku();
            $cartItem['price'] = $item->getPriceInclTax();
            $qty = $item->getQtyOrdered();
            $cartItem['qty'] = $item->getData('unit_case')=='CS'?$qty/$item->getData('unit_qty'):$qty;
            $cartItem['type'] = \Riki\Sales\Helper\Order::RIKI_TYPE_SPOT;
            $cartItem['frequency_id'] = null;
            if ($orderSimulator->getData('riki_type') != 'SPOT') {
                $buyRequest = $item->getBuyRequest();
                if (!($item->getData('is_riki_machine')
                    or isset($buyRequest['options']['ampromo_rule_id'])
                    or $item->getData('prize_id')
                    or $item->getSku() == $orderSimulator->getData('gillette_sku')
                )) {
                    $cartItem['type'] = \Riki\Sales\Helper\Order::RIKI_TYPE_SUBSCRIPTION;
                    $cartItem['frequency_id'] = $orderSimulator->getData('riki_frequency_id');
                }
            }
            $cartItem['giftwrap'] = new DataObject();
            if ($item->getData('gw_id')) {
                $gw = [];
                $wrapping = $this->wrappingRepository->get($item->getData('gw_id'));
                if (!$wrapping) {
                    continue;
                }
                $gw['gw_value'] = $item->getData('gw_id');
                $gw['gw_label'] = $wrapping->getGiftName();
                $gw['gw_code'] = $wrapping->getGiftCode();
                $gw['price'] = $item->getData('gw_price') + $item->getData('gw_tax_amount');
                $cartItem['giftwrap'] = $gw;
            }
            $cartItem['frontend_image_url'] = $this->gilletteHelper->getImageUrl($item->getProduct(),'product_thumbnail_image');
            $cartItems[] = $cartItem;
        }
        return $cartItems;
    }

    /**
     * Get payment method available for cart
     * @param \Magento\Quote\Model\Quote $quote
     * @return array
     */
    protected function getPaymentMethodList($quote) {
        $paymentMethodManagement = $this->methodList->getAvailableMethods($quote);
        $paymentCodes = [];
        $onlyFreePayment = false;
        if ($quote->getGrandTotal() == 0) {
            $onlyFreePayment = true;
        }
        foreach ($paymentMethodManagement as $paymentMethod) {
            if ($onlyFreePayment and $paymentMethod->getCode() != 'free') {
                continue;
            }
            elseif (!$onlyFreePayment and $paymentMethod->getCode() == 'free') {
                continue;
            }
            $paymentMethods[] = [
                'code' => $paymentMethod->getCode(),
                'title' => $paymentMethod->getTitle()
            ];
            $paymentCodes[] = $paymentMethod->getCode();
        }
        $paymentFeeModel = $this->paymentFeeFactory->create()->getCollection();
        $paymentFeeModel->addFieldToFilter('payment_code', $paymentCodes);
        foreach ($paymentFeeModel as $paymentFee) {
            foreach ($paymentMethods as $key => $paymentMethod) {
                if ($paymentMethod['code'] == $paymentFee->getPaymentCode()) {
                    $paymentMethod['price'] = (int)$paymentFee->getData('fixed_amount');
                    if ($paymentMethod['code'] == \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_PAYGENT) {
                        $paymentMethod['used_card'] =  $this->gilletteValidator->getPreviousCard($quote->getCustomerId());
                    }
                    $paymentMethods[$key] = $paymentMethod;
                    break;
                }
            }
        }
        return $paymentMethods;
    }

    /**
     * Get customer addresses
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return array
     */
    private function getCustomerAddress($customer) {
        $customerAddresses = [];
        foreach ($customer->getAddresses() as $address) {
            $addressData = $customAttribute = [];
            $addressType = __('shipping address');
            if ($address->getId() == $customer->getDefaultBilling()) {
                $addressType = __('billing address');
            }
            $addressData['addressId'] = (int)$address->getId();
            $addressData['countryId'] = $address->getCountryId();
            $addressData['addressType'] = $addressType;

            $addressData['regionCode'] = $address->getRegion()->getRegionCode();
            $addressData['region'] = $address->getRegion()->getRegionId();

            $addressData['street'] = $address->getStreet();
            $addressData['telephone'] = $address->getTelephone();
            $addressData['postcode'] = $address->getPostcode();
            $addressData['city'] = $address->getCity();
            $addressData['firstname'] = $address->getFirstname();
            $addressData['lastname'] = $address->getLastname();

            $customAttribute[] = $this->customAttributeValues(
                $address->getCustomAttribute('firstnamekana')
            );
            $customAttribute[] = $this->customAttributeValues(
                $address->getCustomAttribute('lastnamekana')
            );
            $customAttribute[] = $this->customAttributeValues(
                $address->getCustomAttribute('riki_nickname')
            );

            $addressData['customAttributes'] = $customAttribute;
            $customerAddresses[$address->getId()] =  $addressData;
        }
        return $customerAddresses;
    }
    /**
     * Prepare custom attributes for customer
     *
     * @param mixed $customerObject customer_object
     *
     * @return array
     */
    protected function customAttributeValues($customerObject)
    {
        $customObj = [];
        $customObj["attributeCode"] = $customerObject->getAttributeCode();
        $customObj["value"] = $customerObject->getValue();
        return $customObj;
    }

    /**
     * Get Date Range
     * @param $quote
     * @param $addressId
     * @return mixed
     */
    protected function getDateRange($quote, $addressId) {
        $customAvailableDate = $quote->getData('custom_available_date');
        $haveBladeSku = $quote->getData('have_blade_sku');
        $calendarPeriod = $this->scopeConfig->getValue('deliverydate/calendar_period/day_period');
        if ($customAvailableDate and $haveBladeSku) {
            $availableStartDate = $this->subscriptionHelper->calculateDeliveryDateForGillette($quote, $customAvailableDate);
        } else {
            $availableStartDate = $this->subscriptionHelper->calculateDeliveryDateForGillette($quote);
        }
        $dateRange = [$availableStartDate];
        for ($i=1; $i <= $calendarPeriod; $i ++) {
            $dateRange[] = date('Y-m-d', strtotime($availableStartDate . " + $i day"));
        }
        return $dateRange;

    }

    /**
     * Get Destination
     * @param $addressId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function _getDestination($addressId)
    {
        $address = $this->customerAddressRepository->getById($addressId);

        $destination = [];
        $destination['country_code'] = 'JP';
        $destination['address_id'] = $address->getId();
        try {
            $destination['region_code'] = $address->getRegion()->getRegionCode();
            $destination['postcode'] = $address->getPostcode();
            $destination['region'] = $address->getRegion()->getRegion();
        }
        catch (\Magento\Framework\Exception\LocalizedException $e) {
            $destination['region_code'] = '';
            $destination['postcode'] = '';
            $destination['region'] = '';
        }
        return $destination;
    }
}
