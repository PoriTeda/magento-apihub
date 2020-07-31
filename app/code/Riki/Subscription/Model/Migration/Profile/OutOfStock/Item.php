<?php
namespace Riki\Subscription\Model\Migration\Profile\OutOfStock;

use Riki\CreateProductAttributes\Model\Product\CaseDisplay;
use Riki\Subscription\Helper\Order\Data as SubscriptionOrderHelper;

class Item extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Riki\Subscription\Model\Migration\Profile\OutOfStock
     */
    protected $oosMigration;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Riki\Subscription\Api\ProfileProductCartRepositoryInterface
     */
    protected $profileProductCartRepository;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $customerAddressRepository;

    /**
     * @var \Magento\Quote\Model\Quote\ItemFactory
     */
    protected $quoteItemFactory;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @should: use repository, but many field custom does not have api data
     *
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerAddressFactory;


    public function __construct(
        \Magento\Customer\Api\AddressRepositoryInterface $customerAddressRepository,
        \Magento\Customer\Model\AddressFactory $customerAddressFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory,
        \Riki\Subscription\Api\ProfileProductCartRepositoryInterface $profileProductCartRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->customerAddressRepository = $customerAddressRepository;
        $this->customerAddressFactory = $customerAddressFactory;
        $this->customerRepository = $customerRepository;
        $this->quoteFactory = $quoteFactory;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->profileProductCartRepository = $profileProductCartRepository;
        $this->productRepository = $productRepository;
        $this->functionCache = $functionCache;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->profileRepository = $profileRepository;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Riki\Subscription\Model\ResourceModel\Migration\Profile\OutOfStock\Item::class);
    }

    /**
     * Add message
     *
     * @param $message
     * @param $type
     *
     * @return $this
     */
    public function addMessage($message, $type = 'info')
    {
        $messages = $this->getData('messages');
        if (!isset($messages[$type])) {
            $messages[$type] = [];
        }
        $messages[$type][] = $message;
        $this->setData('messages', $messages);

        return $this;
    }

    /**
     * Add error message
     *
     * @param $message
     *
     * @return $this
     */
    public function addErrorMessage($message)
    {
        return $this->addMessage($message, 'error');
    }

    /**
     * Get has_error
     *
     * @return bool
     */
    public function getHasError()
    {
        if (!$this->hasData('has_error')) {
            $messages = $this->getData('messages');
            $hasError = $messages && isset($messages['error']) && $messages['error'];
            $this->setData('has_error', $hasError);
        }
        return (bool)$this->getData('has_error');
    }

    /**
     * Get profile
     *
     * @return \Riki\Subscription\Api\Data\ApiProfileInterface|null
     */
    public function getProfile()
    {
        if ($this->functionCache->has($this->getData('profile_id'))) {
            return $this->functionCache->load($this->getData('profile_id'));
        }

        $query = $this->searchCriteriaBuilder
            ->addFilter('old_profile_id', $this->getData('profile_id'))
            ->create();
        $results = $this->profileRepository->getList($query)->getItems();
        $result = $results ? end($results) : null;

        $this->functionCache->store($result, $this->getData('profile_id'));

        return $result;
    }


    /**
     * Get product
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    public function getProduct()
    {
        if ($this->functionCache->has($this->getData('product_id'))) {
            return $this->functionCache->load($this->getData('product_id'));
        }

        try {
            $result = $this->productRepository->get($this->getData('product_id'));
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $result = null;
            $this->_logger->warning($e);
        }

        $this->functionCache->store($result, $this->getData('product_id'));

        return $result;
    }

    /**
     * Get customer
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getCustomer()
    {
        $profile = $this->getProfile();
        if (!$profile) {
            return null;
        }

        if ($this->functionCache->has($profile->getCustomerId())) {
            return $this->functionCache->load($profile->getCustomerId());
        }

        try {
            $result = $this->customerRepository->getById($profile->getCustomerId());
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->_logger->warning($e);
            $result = null;
        }
        $this->functionCache->store($result, $profile->getCustomerId());

        return $result;
    }

    /**
     * Get customer address which is home type
     *
     * @return \Magento\Customer\Api\Data\AddressInterface|null
     */
    public function getCustomerHomeAddress()
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return null;
        }

        if ($this->functionCache->has($customer->getId())) {
            return $this->functionCache->load($customer->getId());
        }

        $addresses = $customer->getAddresses();
        if (!$addresses) {
            return null;
        }

        $result = null;
        foreach ($addresses as $address) {
            $addressTypeAttr = $address->getCustomAttribute('riki_type_address');
            if ($addressTypeAttr && $addressTypeAttr->getValue() == 'home') { // @should: use const
                $result = $address;
                break;
            }
        }

        $this->functionCache->store($result, $customer->getId());

        return $result;
    }

    /**
     * Get customer address which is migrated from consumer
     *
     * @return \Magento\Customer\Api\Data\AddressInterface|null
     */
    public function getCustomerConsumerAddress()
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return null;
        }

        if ($this->functionCache->has($customer->getId())) {
            return $this->functionCache->load($customer->getId());
        }

        $addresses = $customer->getAddresses();
        if (!$addresses) {
            return null;
        }

        $result = null;
        foreach ($addresses as $address) {
            $addressTypeAttr = $address->getCustomAttribute('consumer_db_address_id');
            if ($addressTypeAttr) { // @should: use const
                $result = $address;
                break;
            }
        }

        $this->functionCache->store($result, $customer->getId());

        return $result;
    }

    /**
     * Get customer address which is company type
     *
     * @return \Magento\Customer\Api\Data\AddressInterface|null
     */
    public function getCustomerCompanyAddress()
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return null;
        }

        if ($this->functionCache->has($customer->getId())) {
            return $this->functionCache->load($customer->getId());
        }

        $addresses = $customer->getAddresses();
        if (!$addresses) {
            return null;
        }

        $result = null;
        foreach ($addresses as $address) {
            $addressTypeAttr = $address->getCustomAttribute('riki_type_address');
            if ($addressTypeAttr && $addressTypeAttr->getValue() == 'company') { // @should: use const
                $result = $address;
                break;
            }
        }

        $this->functionCache->store($result, $customer->getId());

        return $result;
    }

    /**
     * Set profile_id
     *
     * @param $profileId
     *
     * @return $this
     */
    public function setProfileId($profileId)
    {
        $this->setData('profile_id', $profileId);

        if (empty($profileId)) {
            $this->addErrorMessage(__('profile_id is required'));
        }

        if (!$this->getProfile()) {
            $this->addErrorMessage(__('Profile Id %1 does not exist', $profileId));
        }

        return $this;
    }

    /**
     * Set product_id
     *
     * @param $productId
     *
     * @return $this
     */
    public function setProductId($productId)
    {
        $this->setData('product_id', $productId);
        if (empty($productId)) {
            $this->addErrorMessage(__('product_id is required'));
        }

        $product = $this->getProduct();
        if (!$product) {
            $this->addErrorMessage(__('Product Sku %1 does not exist', $productId));
        }

        return $this;
    }

    /**
     * Set qty
     *
     * @param $qty
     *
     * @return $this
     */
    public function setQty($qty)
    {
        if (empty($qty)) {
            $this->addErrorMessage(__('qty is required and greater than 0'));
        }

        return $this->setData('qty', $qty);
    }

    /**
     * Set billing_address_id
     *
     * @param $billingAddressId
     *
     * @return $this
     */
    public function setBillingAddressId($billingAddressId)
    {
        if ($this->hasData('shipping_address_id')) {
            $shippingAddressId = $this->getData('shipping_address_id');
            if ($shippingAddressId != 0 && $shippingAddressId != 99999999) {
                $billingAddressId = 0;
            } else {
                $billingAddressId = $shippingAddressId;
            }
        }

        if ($billingAddressId == 0) {
            $address = $this->getCustomerHomeAddress();
            if (!$address) {
                $this->addErrorMessage(__('billing_address_id is invalid'));
            }
        } elseif ($billingAddressId == 99999999) {
            $address = $this->getCustomerCompanyAddress();
            if (!$address) {
                $this->addErrorMessage(__('billing_address_id is invalid'));
            }
        } else {
            $address = $this->getCustomerConsumerAddress();
            if (!$address || $address->getCustomAttribute('consumer_db_address_id') != $billingAddressId) {
                $this->addErrorMessage(__('billing_address_id is invalid'));
            }
        }

        if (isset($address)) {
            $billingAddressId = $address->getId();
        }

        return $this->setData('billing_address_id', $billingAddressId);
    }

    /**
     * Set shipping_address_id
     *
     * @param $shippingAddressId
     *
     * @return $this
     */
    public function setShippingAddressId($shippingAddressId)
    {
        if ($shippingAddressId == 0) {
            $address = $this->getCustomerHomeAddress();
            if (!$address) {
                $this->addErrorMessage(__('shipping_address_id is invalid'));
            }
        } elseif ($shippingAddressId == 99999999) {
            $address = $this->getCustomerCompanyAddress();
            if (!$address) {
                $this->addErrorMessage(__('shipping_address_id is invalid'));
            }
        } else {
            $address = $this->getCustomerConsumerAddress();
            if (!$address || $address->getCustomAttribute('consumer_db_address_id') != $shippingAddressId) {
                $this->addErrorMessage(__('shipping_address_id is invalid'));
            }
        }

        if (isset($address)) {
            $shippingAddressId = $address->getId();
        }

        return $this->setData('shipping_address_id', $shippingAddressId);
    }

    /**
     * Set delivery_date
     *
     * @param $deliveryDate
     *
     * @return $this
     */
    public function setDeliveryDate($deliveryDate)
    {
        if (!empty($deliveryDate)) {
            $deliveryDate = date('Y-m-d H:i:s', strtotime($deliveryDate));
            if ($deliveryDate == '1970-01-01 00:00:00') {
                $this->addErrorMessage(__('delivery_date is invalid format for datetime'));
            }
        }

        return $this->setData('delivery_date', $deliveryDate);
    }

    /**
     * Set unit_price
     *
     * @param $unitPrice
     *
     * @return $this
     */
    public function setUnitPrice($unitPrice)
    {
        return $this->setData('unit_price', floatval($unitPrice));
    }

    /**
     * Set retail_price
     *
     * @param $retailPrice
     *
     * @return $this
     */
    public function setRetailPrice($retailPrice)
    {
        return $this->setData('retail_price', floatval($retailPrice));
    }

    /**
     * Set order_times
     *
     * @param $orderTimes
     *
     * @return $this
     */
    public function setOrderTimes($orderTimes)
    {
        if (empty($orderTimes)) {
            $this->addErrorMessage(__('order_times is required'));
        }

        $profile = $this->getProfile();
        if ($profile && $profile->getOrderTimes() < $orderTimes) {
            $this->addErrorMessage(__('order_times is greater than current order_times of profile #%1', $this->getData('profile_id')));
        }

        return $this->setData('order_times', $orderTimes);
    }

    /**
     * Set created_at
     *
     * @param $createdAt
     *
     * @return $this
     */
    public function setCreateAt($createdAt)
    {
        if (!empty($createdAt)) {
            $createdAt = date('Y-m-d H:i:s', strtotime($createdAt));
            if ($createdAt == '1970-01-01 00:00:00') {
                $this->addErrorMessage(__('created_at is invalid format for datetime'));
            }
        }
        return $this->setData('created_at', $createdAt);
    }

    /**
     * Set updated_at
     *
     * @param $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        if (!empty($updatedAt)) {
            $updatedAt = date('Y-m-d H:i:s', strtotime($updatedAt));
            if ($updatedAt == '1970-01-01 00:00:00') {
                $this->addErrorMessage(__('created_at is invalid format for datetime'));
            }
        }
        return $this->setData('updated_at', $updatedAt);
    }

    /**
     * Set unit
     *
     * @param $unit
     *
     * @return $this
     */
    public function setUnit($unit)
    {
        if (empty($unit)) {
            $unit = CaseDisplay::PROFILE_UNIT_PIECE;
        } else {
            if ($unit == 1) {
                $unit = CaseDisplay::PROFILE_UNIT_CASE;
            } elseif ($unit == 3) {
                $unit = CaseDisplay::PROFILE_UNIT_PIECE;
            } else {
                $this->addErrorMessage(__('unit is invalid.'));
            }
        }

        return $this->setData('unit', $unit);
    }

    /**
     * Set unit_qty
     *
     * @param $unitQty
     *
     * @return $this
     */
    public function setUnitQty($unitQty)
    {
        if (empty($unitQty)) {
            $unitQty = 1;
        }

        return $this->setData('unit_qty', $unitQty);
    }

    /**
     * Set applied_point_rate
     *
     * @param $appliedPointRate
     *
     * @return $this
     */
    public function setAppliedPointRate($appliedPointRate)
    {
        return $this->setData('applied_point_rate', floatval($appliedPointRate));
    }

    /**
     * Validate data
     *
     * @param array $data
     *
     * @return bool
     */
    public function isValid($data = [])
    {
        $required = [
            'profile_id' => null,
            'product_id' => null,
            'qty' => null,
            'order_times' => null,
            'unit_price' => null,
            'retail_price' => null,
            'delivery_date' => null,
            'unit' => null,
            'unit_qty' => null,
            'created_at' => null,
            'updated_at' => null,
            'applied_point_rate' => null
        ];
        $data = array_merge($required, $data);
        //we substract by 1 to make align order times between KSS and Magento
        if((int)$data['order_times']){
            $data['order_times'] = $data['order_times'] - 1;
        }
        $this->setProfileId($data['profile_id']);
        $this->setProductId($data['product_id']);
        $this->setQty($data['qty']);
        $this->setOrderTimes($data['order_times']);
        $this->setUnitPrice($data['unit_price']);
        $this->setRetailPrice($data['retail_price']);
        $this->setUnit($data['unit']);
        $this->setUnitQty($data['unit_qty']);
        $this->setAppliedPointRate($data['applied_point_rate']);
        $this->setDeliveryDate($data['delivery_date']);
        $this->setShippingAddressId($data['shipping_address_id']);
        $this->setBillingAddressId($data['billing_address_id']);
        $this->setCreateAt($data['created_at']);
        $this->setUpdatedAt($data['updated_at']);


        return !$this->getHasError();
    }

    /**
     * Generate quote
     *
     * @return \Magento\Quote\Model\Quote
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateQuote()
    {
        /** @var \Riki\Subscription\Api\Data\ApiProfileInterface $profile */
        $profile = $this->getProfile();
        if (!$profile) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Generate quote failed via missing profile data'));
        }

        /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
        $customer = $this->getCustomer();
        if (!$customer) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Generate quote failed via missing customer data'));
        }

        /** @var \Magento\Customer\Model\Address $billingAddress */
        $billingAddress = $this->customerAddressFactory->create()->load($this->getData('billing_address_id'));
        if (!$billingAddress->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Generate quote failed via missing billing address data'));
        }

        /** @var \Magento\Customer\Model\Address $shippingAddress */
        $shippingAddress = $this->customerAddressFactory->create()->load($this->getData('shipping_address_id'));
        if (!$shippingAddress->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Generate quote failed via missing shipping address data'));
        }

        if ($this->functionCache->has([$profile->getProfileId(), $this->getData('order_times')])) {
            return $this->functionCache->load([$profile->getProfileId(), $this->getData('order_times')]);
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteFactory->create();
        $quote->setData('is_active', 0);
        $quote->setData('store_id', $profile->getStoreId());
        $quote->setData(SubscriptionOrderHelper::IS_PROFILE_GENERATED_ORDER_KEY, 1);
        $quote->setData('profile_id', $profile->getProfileId());
        $quote->assignCustomer($customer);
        $quote->getBillingAddress()->addData($billingAddress->getData());
        $quote->getShippingAddress()
            ->addData($shippingAddress->getData())
            ->setShippingMethod($profile->getShippingCondition());
        $quote->getPayment()
            ->setMethod($profile->getPaymentMethod());

        $this->functionCache->store($quote, [$profile->getProfileId(), $this->getData('order_times')]);

        return $quote;
    }

    /**
     * Generate quote item
     *
     * @return \Magento\Quote\Model\Quote\Item
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateQuoteItem()
    {
        $product = $this->getProduct();
        if (!$product) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Generate quote item failed via missing product data'));
        }

        $profile = $this->getProfile();
        if (!$profile) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Generate quote item failed via missing profile data'));
        }

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $this->quoteItemFactory->create();
        $quoteItem->setProduct($product);
        $quoteItem->setData('product_id', $product->getId());
        $quoteItem->setData('store_id', $profile->getStoreId());
        $quoteItem->setData('sku', $product->getSku());
        $quoteItem->setData('name', $product->getName());
        $quoteItem->setData('unit_case', $this->getData('unit'));
        $quoteItem->setData('unit_qty', $this->getData('unit_qty'));

        $qty = floatval($this->getData('qty'));
        $unitQty = floatval($this->getData('unit_qty'));
        $quoteItem->setData('qty', $qty * $unitQty);

        $unitPrice = floatval($this->getData('unit_price'));
        $quoteItem->setData('price', $unitPrice);
        $quoteItem->setData('base_price', $unitPrice);

        $retailPrice = floatval($this->getData('retail_price'));
        $discountAmount = abs($retailPrice - $unitPrice);
        $quoteItem->setData('discount_amount', $discountAmount);
        $quoteItem->setData('base_discount_amount', $discountAmount);

        $taxAmount = ($retailPrice * $qty) - ceil($retailPrice*$qty/1.08);
        $quoteItem->setData('tax_amount', $taxAmount);
        $quoteItem->setData('base_tax_amount', $taxAmount);
        $quoteItem->setData('tax_riki', $taxAmount);

        $rowTotal = $retailPrice * $qty + $discountAmount;
        $quoteItem->setData('row_total_incl_tax', $rowTotal);
        $quoteItem->setData('base_row_total_incl_tax', $rowTotal);
        $quoteItem->setData('row_total', $rowTotal - $taxAmount);
        $quoteItem->setData('base_row_total', $quoteItem->getData('row_total'));
        $quoteItem->setData('row_total_with_discount', $retailPrice * $qty);
        $quoteItem->setData('base_row_total_with_discount', $retailPrice * $qty);

        $quoteItem->setData('price_incl_tax', $unitPrice);
        $quoteItem->setData('base_price_incl_tax', $quoteItem->getData('price_incl_tax'));

        $quoteItem->setData('delivery_date', $this->getData('delivery_date'));

        $appliedPointRate = floatval($this->getData('applied_point_rate'));
        $appliedPointAmount = floatval($appliedPointRate / 100 * (ceil($retailPrice*$qty/1.08)));
        $additionalData = [
            'applied_point_rate' => $appliedPointRate,
            'applied_point_amount' => $appliedPointAmount,
            'subscription_order_time' => $this->getData('order_times')
        ];
        $quoteItem->setData('additional_data', \Zend_Json::encode($additionalData));

        return $quoteItem;
    }
}