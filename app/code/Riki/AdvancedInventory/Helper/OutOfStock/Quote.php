<?php
namespace Riki\AdvancedInventory\Helper\OutOfStock;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\Free;
use Magento\Quote\Model\Quote\Item;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;

class Quote
{
    const RIKI_IS_OOS_QUOTE_ID = 'is_oos_quote';

    /**
     * @var \Magento\Quote\Api\Data\AddressInterfaceFactory
     */
    protected $addressFactory;

    /**
     * @var \Magento\Quote\Api\Data\CartInterfaceFactory
     */
    protected $quoteFactory;

    /**
     * @var \Magento\Quote\Api\Data\CartItemInterfaceFactory
     */
    protected $quoteItemFactory;

    /**
     * @var \Riki\AdvancedInventory\Helper\OutOfStock
     */
    protected $outOfStockHelper;

    /**
     * @var \Riki\SubscriptionMachine\Model\MachineSkusFactory
     */
    protected $machineSkuFactory;

    /**
     * @var \Bluecom\Paygent\Model\PaygentOptionFactory
     */
    protected $paygentOptionFactory;

    /**
     * @var \Riki\AdvancedInventory\Helper\Logger
     */
    protected $loggerHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Quote\Model\Quote\PaymentInterfaceFactory
     */
    protected $paymentFactory;

    /**
     * @var \Magento\Quote\Api\Data\CurrencyInterfaceFactory $currencyFactory
     */
    protected $currencyFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface $customer
     */
    protected $customer;

    /**
     * @var \Riki\Subscription\Helper\Order\Data $subHelper
     */
    protected $subHelper;

    /**
     * @var \Riki\Loyalty\Api\CheckoutRewardPointInterface
     */
    protected $checkoutRewardPoint;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Riki\Loyalty\Api\RewardPointManagementInterface
     */
    protected $rewardPointManagement;

    /**
     * @var \Bluecom\PaymentFee\Api\FeeManagementInterface
     */
    protected $paymentFeeManagement;

    /**
     * @var \Riki\Customer\Model\CustomerRepository
     */
    protected $rikiCustomerRepository;

    /**
     * @var \Magento\Framework\DataObject\Copy
     */
    protected $objectCopyService;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var \Riki\TimeSlots\Model\TimeSlotsFactory
     */
    protected $timeSlotsFactory;

    /**
     * @var array
     */
    protected $updatedConsumers = [];

    /**
     * @var \Riki\AdvancedInventory\Model\OutOfStock\TaxChange\TotalAdjustment
     */
    protected $totalAdjustment;

    /**
     * @var \Riki\AdvancedInventory\Model\OutOfStock\ShippingCalculator
     */
    protected $shippingCalculator;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    protected $objectFactory;

    /**
     * Quote constructor.
     * @param \Bluecom\PaymentFee\Api\FeeManagementInterface $paymentFeeManagement
     * @param \Riki\Loyalty\Api\RewardPointManagementInterface $rewardPointManagement
     * @param \Riki\Loyalty\Api\CheckoutRewardPointInterface $checkoutRewardPoint
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\AdvancedInventory\Helper\Logger $loggerHelper
     * @param \Bluecom\Paygent\Model\PaygentOptionFactory $paygentOptionFactory
     * @param \Riki\SubscriptionMachine\Model\MachineSkusFactory $machineSkusFactory
     * @param \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper
     * @param \Magento\Quote\Api\Data\CartItemInterfaceFactory $quoteItemFactory
     * @param \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory
     * @param \Magento\Quote\Api\Data\AddressInterfaceFactory $addressFactory
     * @param \Magento\Quote\Api\Data\PaymentInterfaceFactory $payment
     * @param \Magento\Quote\Api\Data\CurrencyInterfaceFactory $currencyFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customer
     * @param \Riki\Subscription\Helper\Order\Data $subHelper
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     */
    public function __construct(
        \Bluecom\PaymentFee\Api\FeeManagementInterface $paymentFeeManagement,
        \Riki\Loyalty\Api\RewardPointManagementInterface $rewardPointManagement,
        \Riki\Loyalty\Api\CheckoutRewardPointInterface $checkoutRewardPoint,
        \Magento\Framework\Registry $registry,
        \Riki\AdvancedInventory\Helper\Logger $loggerHelper,
        \Bluecom\Paygent\Model\PaygentOptionFactory $paygentOptionFactory,
        \Riki\SubscriptionMachine\Model\MachineSkusFactory $machineSkusFactory,
        \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper,
        \Magento\Quote\Api\Data\CartItemInterfaceFactory $quoteItemFactory,
        \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory,
        \Magento\Quote\Api\Data\AddressInterfaceFactory $addressFactory,
        \Magento\Quote\Api\Data\PaymentInterfaceFactory $payment,
        \Magento\Quote\Api\Data\CurrencyInterfaceFactory $currencyFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customer,
        \Riki\Subscription\Helper\Order\Data $subHelper,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Riki\AdvancedInventory\Model\OutOfStock\TaxChange\TotalAdjustment $totalAdjustment,
        \Riki\AdvancedInventory\Model\OutOfStock\ShippingCalculator $shippingCalculator,
        \Riki\TimeSlots\Model\TimeSlotsFactory $timeSlotsFactory,
        ProductFactory $productFactory,
        \Magento\Framework\DataObject\Factory $objectFactory
    ) {
        $this->paymentFeeManagement = $paymentFeeManagement;
        $this->rewardPointManagement = $rewardPointManagement;
        $this->checkoutRewardPoint = $checkoutRewardPoint;
        $this->registry = $registry;
        $this->loggerHelper = $loggerHelper;
        $this->paygentOptionFactory = $paygentOptionFactory;
        $this->machineSkuFactory = $machineSkusFactory;
        $this->outOfStockHelper = $outOfStockHelper;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->quoteFactory = $quoteFactory;
        $this->addressFactory = $addressFactory;
        $this->paymentFactory = $payment;
        $this->currencyFactory = $currencyFactory;
        $this->customerRepository = $customer;
        $this->subHelper = $subHelper;
        $this->objectCopyService = $objectCopyService;
        $this->rikiCustomerRepository = $subHelper->getRikiCustomerRepository();
        $this->cartRepository = $cartRepository;
        $this->totalAdjustment = $totalAdjustment;
        $this->shippingCalculator = $shippingCalculator;
        $this->productFactory = $productFactory;
        $this->objectFactory = $objectFactory;
        $this->timeSlotsFactory = $timeSlotsFactory;
    }

    /**
     * Clone a new address object from other address object
     *
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     *
     * @return \Magento\Quote\Api\Data\AddressInterface
     */
    public function cloneAddress(\Magento\Quote\Api\Data\AddressInterface $address)
    {
        /** @var \Magento\Quote\Api\Data\AddressInterface $newAddress */
        $newAddress = $this->addressFactory->create();

        $newAddress->setCity($address->getCity());
        $newAddress->setCompany($address->getCompany());
        $newAddress->setCountryId($address->getCountryId());
        $newAddress->setCustomerAddressId($address->getCustomerAddressId());
        $newAddress->setCustomerId($address->getCustomerId());
        $newAddress->setEmail($address->getEmail());
        $newAddress->setFax($address->getFax());
        $newAddress->setFirstname($address->getFirstname());
        $newAddress->setLastname($address->getLastname());
        $newAddress->setMiddlename($address->getMiddlename());
        $newAddress->setPostcode($address->getPostcode());
        $newAddress->setPrefix($address->getPrefix());
        $newAddress->setRegion($address->getRegion());
        $newAddress->setRegionCode($address->getRegionCode());
        $newAddress->setRegionId($address->getRegionId());
        $newAddress->setSameAsBilling($address->getSameAsBilling());
        $newAddress->setSaveInAddressBook($address->getSaveInAddressBook());
        $newAddress->setSuffix($address->getSuffix());
        $newAddress->setStreet($address->getStreet());
        $newAddress->setTelephone($address->getTelephone());
        $newAddress->setVatId($address->getVatId());
        $newAddress->setData('firstnamekana', $address->getData('firstnamekana'));
        $newAddress->setData('lastnamekana', $address->getData('lastnamekana'));
        $newAddress->setData('riki_type_address', $address->getData('riki_type_address'));
        $newAddress->setData('apartment', $address->getData('apartment'));

        if ($address->getCustomAttributes()) {
            $newAddress->setCustomAttributes($address->getCustomAttributes());
        }
        if ($address->getExtensionAttributes()) {
            $newAddress->setExtensionAttributes($address->getExtensionAttributes());
        }
        if ($address->getShippingMethod()) {
            $newAddress->setShippingMethod($address->getShippingMethod());
        }

        return $newAddress;
    }

    /**
     * Clone a new address object from order address object
     *
     * @param \Riki\Sales\Model\Order\Address $address
     *
     * @return \Magento\Quote\Api\Data\AddressInterface
     */
    public function cloneAddressFromOrder(\Riki\Sales\Model\Order\Address $address)
    {
        /** @var \Magento\Quote\Api\Data\AddressInterface $newAddress */
        $newAddress = $this->addressFactory->create();

        $newAddress->setCity($address->getCity());
        $newAddress->setCompany($address->getCompany());
        $newAddress->setCountryId($address->getCountryId());
        $newAddress->setCustomerAddressId($address->getCustomerAddressId());
        $newAddress->setCustomerId($address->getCustomerId());
        $newAddress->setEmail($address->getEmail());
        $newAddress->setFax($address->getFax());
        $newAddress->setFirstname($address->getFirstname());
        $newAddress->setLastname($address->getLastname());
        $newAddress->setMiddlename($address->getMiddlename());
        $newAddress->setPostcode($address->getPostcode());
        $newAddress->setPrefix($address->getPrefix());
        $newAddress->setRegion($address->getRegion());
        $newAddress->setRegionCode($address->getRegionCode());
        $newAddress->setRegionId($address->getRegionId());
        $newAddress->setSameAsBilling($address->getSameAsBilling());
        $newAddress->setSaveInAddressBook($address->getSaveInAddressBook());
        $newAddress->setSuffix($address->getSuffix());
        $newAddress->setStreet($address->getStreet());
        $newAddress->setTelephone($address->getTelephone());
        $newAddress->setVatId($address->getVatId());
        $newAddress->setData('firstnamekana', $address->getData('firstnamekana'));
        $newAddress->setData('lastnamekana', $address->getData('lastnamekana'));
        $newAddress->setData('riki_type_address', $address->getData('riki_type_address'));
        $newAddress->setData('apartment', $address->getData('apartment'));

        if ($address->getCustomAttributes()) {
            $newAddress->setCustomAttributes($address->getCustomAttributes());
        }
        if ($address->getExtensionAttributes()) {
            $newAddress->setExtensionAttributes($address->getExtensionAttributes());
        }
        if ($address->getShippingMethod()) {
            $newAddress->setShippingMethod($address->getShippingMethod());
        }

        return $newAddress;
    }

    /**
     * Clone a new quote from other quote
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     *
     * @return \Magento\Quote\Api\Data\CartInterface
     */
    public function cloneQuote(\Magento\Quote\Api\Data\CartInterface $quote)
    {
        /** @var \Magento\Quote\Api\Data\CartInterface $newQuote */
        $newQuote = $this->quoteFactory->create();

        $newQuote->setCurrency($quote->getCurrency());
        $newQuote->setCustomer($quote->getCustomer());
        $newQuote->setIsActive(true);
        $newQuote->setStoreId($quote->getStoreId());
        $newQuote->setInventoryProcessed(false);
        $newQuote->setAddressType($quote->getAddressType());
        $newQuote->setRemoteIp('0.0.0.0'); // bypass validate @see \Bluecom\Paygent\Model\Paygent::initialize
        if ($quote->getExtensionAttributes()) {
            $newQuote->setExtensionAttributes($quote->getExtensionAttributes());
        }
        $newQuote->setData('riki_course_id', $quote->getData('riki_course_id'));
        $newQuote->setData('riki_frequency_id', $quote->getData('riki_frequency_id'));
        $newQuote->setData('order_channel', $quote->getData('order_channel'));

        return $newQuote;
    }
    /**
     * Clone a new quote from order info
     *
     * @param \Riki\Sales\Model\Order  $order
     * @param \Riki\AdvancedInventory\Model\OutOfStock $outOfStock
     *
     * @return \Magento\Quote\Api\Data\CartInterface
     */
    public function cloneQuoteFromOrder(
        \Riki\Sales\Model\Order $order,
        \Riki\AdvancedInventory\Model\OutOfStock $outOfStock
    ) {
        /** @var \Magento\Quote\Api\Data\CartInterface $newQuote */
        $newQuote = $this->quoteFactory->create();
        $currency = $this->currencyFactory->create();
        $currency->setQuoteCurrencyCode($order->getOrderCurrencyCode());
        $currency->setBaseCurrencyCode($order->getOrderCurrencyCode());
        $newQuote->setCurrency($currency);

        $customer = $this->customerRepository->getById($order->getCustomerId());
        $newQuote->setCustomer($customer);
        $newQuote->setIsActive(true);
        $newQuote->setStoreId($order->getStoreId());
        $newQuote->setInventoryProcessed(false);
        $newQuote->setAddressType($order->getAddressType());
        $newQuote->setRemoteIp('0.0.0.0'); // bypass validate @see \Bluecom\Paygent\Model\Paygent::initialize

        $profile = $outOfStock->getProfile();
        if ($profile instanceof \Riki\Subscription\Model\Profile\Profile) {
            $frequencyId = $this->subHelper->getFrequencyIdFromProfile(
                $profile->getData('frequency_unit'),
                $profile->getData('frequency_interval')
            );

            $newQuote->setData('riki_course_id', $profile->getData('course_id'));
            $newQuote->setData('riki_frequency_id', $frequencyId);
            $newQuote->setData('order_channel', $profile->getData('order_channel'));
        }
        return $newQuote;
    }

    /**
     * generate quote for out of stock item
     *
     * @param [] $outOfStockList
     * @return \Magento\Quote\Api\Data\CartInterface
     *
     * @throws \Exception
     */
    public function generate(array $outOfStockList)
    {
        if (!$outOfStockList) {
            throw new LocalizedException(__('Out of stock item is invalid'));
        }

        $order = $this->getOutOfStockOriginalOrder($outOfStockList);

        if (!$order) {
            throw new LocalizedException(__('Original does not exist.'));
        }

        // call KSS to update customer info
        $this->syncKssDataForCustomer($order, $outOfStockList);

        /*quote for out of stock item*/
        $newQuote = $this->generateQuoteForOutOfStock($order, $outOfStockList);

        if (!$newQuote) {
            throw new \Exception('An error occurred when generate new quote');
        }

        // so should load quote with store id
        $newQuote = $this->quoteFactory->create()
            ->setSharedStoreIds([$newQuote->getStoreId()])
            ->load($newQuote->getId());

        $billingAddress = $this->_initBillingAddressFromOrder($order, $newQuote);
        $shippingAddress = $this->_initShippingAddressFromOrder($order, $newQuote);

        /*out of stock - quote item*/
        $quoteItems = $this->generateQuoteItemForOutOfStockQuote($newQuote, $outOfStockList);

        if (!$shippingAddress->getShippingMethod()) {
            $shippingAddress->setShippingMethod('riki_shipping_riki_shipping');
        }

        // calc total
        foreach ($quoteItems as $quoteItem) {
            if ($quoteItem->getParentItemId()) {
                continue;
            }
            if (!$quoteItem->getIsFreeOutOfStockItem()
                && ($quoteItem->getPriceInclTax() === null || $quoteItem->getRowTotalInclTax() === null)
                // it is not passed through collect totals
            ) {
                throw new LocalizedException(__('Quote item %1 is incorrect data', $quoteItem->getItemId()));
            }
            $itemQty = $this->totalAdjustment->getQuoteItemFinalQty($quoteItem);
            $giftWrappingTaxAmount = $quoteItem->getData('gw_tax_amount') * $itemQty;

            $subtotal = floatval($shippingAddress->getData('subtotal')) + floatval($quoteItem->getRowTotal());
            $subtotalInclTax = floatval($shippingAddress->getData('subtotal_incl_tax'))
                + floatval($quoteItem->getRowTotalInclTax());

            $subtotalDiscount = floatval($shippingAddress->getData('subtotal_with_discount'))
                + floatval($quoteItem->getRowTotalWithDiscount());

            $taxAmount = floatval($shippingAddress->getData('tax_amount'))
                + floatval($quoteItem->getTaxAmount())
                + floatval($giftWrappingTaxAmount);

            $discountAmount = floatval($shippingAddress->getData('discount_amount'))
                - floatval($quoteItem->getDiscountAmount());

            try {
                $additionalData = \Zend_Json::decode($quoteItem->getData('additional_data') ?: '{}');
                if (isset($additionalData['earn_point'])
                    || isset($additionalData['earn_rule_point'])
                ) {
                    $additionalData['applied_point_amount'] = 0;

                    $additionalData['applied_point_amount'] += isset($additionalData['earn_point'])
                        ? $additionalData['earn_point'] : 0;

                    if (isset($additionalData['earn_rule_point'])) {
                        foreach ($additionalData['earn_rule_point'] as $ruleId => $earnRulePointData) {
                            $additionalData['applied_point_amount'] +=
                                $earnRulePointData['point'] * $quoteItem->getQty();
                        }
                    }
                }
                if (isset($additionalData['applied_point_amount'])) {
                    $shippingAddress->setData('bonus_point_amount', $additionalData['applied_point_amount']);
                }
            } catch (\Zend_Json_Exception $e) {
                $this->loggerHelper->getOosLogger()->warning((string)$quoteItem->getData('additional_data'));
                $this->loggerHelper->getOosLogger()->warning($e);
            }

            $this->applyGiftWrapping($quoteItem, $shippingAddress);

            /* calculator total row */
            $shippingAddress->setSubtotal($subtotal);
            $shippingAddress->setBaseSubtotal($subtotal);
            $shippingAddress->setSubtotalInclTax($subtotalInclTax);
            $shippingAddress->setBaseSubtotalInclTax($subtotalInclTax);
            $shippingAddress->setGrandTotal(
                $subtotalInclTax + $discountAmount + $shippingAddress->getGwItemsPriceInclTax()
            );
            $shippingAddress->setBaseGrandTotal(
                $subtotalInclTax + $discountAmount + $shippingAddress->getGwItemsPriceInclTax()
            );
            $shippingAddress->setSubtotalWithDiscount($subtotalDiscount);
            $shippingAddress->setBaseSubtotalWithDiscount($subtotalDiscount);
            $shippingAddress->setTaxAmount($taxAmount);
            $shippingAddress->setBaseTaxAmount($taxAmount);
            $shippingAddress->setDiscountAmount($discountAmount);
            $shippingAddress->setBaseDiscountAmount($discountAmount);

            if (($quoteItem->getIsFreeShipping() || $quoteItem->getFreeShipping())
                || $quoteItem->getIsFreeOutOfStockItem()
            ) {
                $shippingAddress->setFreeShipping(1);
                $shippingAddress->setIsFreeShipping(1);
            }
        }
        $newQuote->setItems($quoteItems)
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress);
        $newQuote->getShippingAddress()
            ->setCollectShippingRates(true)
            ->collectShippingRates();

        $shippingAddress = $newQuote->getShippingAddress();

        $this->buildShippingAmountData($newQuote, $shippingAddress, $billingAddress);

        $shippingRate = $shippingAddress->getShippingRateByCode($shippingAddress->getShippingMethod());
        if ($shippingRate) {
            $shippingDescription= $shippingRate->getCarrierTitle() . ' - ' . $shippingRate->getMethodTitle();
            $shippingAddress->setShippingDescription(trim($shippingDescription, ' -'));
        }

        $newQuote->setData('bonus_point_amount', $shippingAddress->getData('bonus_point_amount'));
        $newQuote->setData('allowed_earned_point', $order->getAllowedEarnedPoint());
        if ($shippingFeeByAddress = $this->registry->registry('shipping_fee_by_address')) {
            $newQuote->setShippingFeeByAddress($shippingFeeByAddress);
        }
        if ($this->isUseAllPointForOutOfStockOrder($outOfStockList)) {
            // original use all point to checkout
            $this->registry->unregister(self::RIKI_IS_OOS_QUOTE_ID);
            $this->registry->register(self::RIKI_IS_OOS_QUOTE_ID, true);
            $this->checkoutRewardPoint->useAllPoint($newQuote->getId());

            $points = $this->rewardPointManagement->getPointBalance($order->getCustomerConsumerDbId());
            $amountFromPoints = $this->rewardPointManagement->getAmountFromPoint($points);
            $usedPointAmount = 0;
            if ($amountFromPoints >= $shippingAddress->getGrandTotal()) {
                $usedPointAmount = $shippingAddress->getGrandTotal();
            } elseif ($amountFromPoints > 0) {
                $usedPointAmount = $amountFromPoints;
            }
            list ($realUsePoint, $pointDiscountAmount) = $this->totalAdjustment->getUsePointAdjustData(
                $amountFromPoints,
                $outOfStockList,
                $shippingAddress
            );
            if ($realUsePoint && $pointDiscountAmount) {
                $usedPointAmount = $realUsePoint;
                $shippingAddress->setDiscountAmount($shippingAddress->getDiscountAmount() - $pointDiscountAmount);
                $shippingAddress->setBaseDiscountAmount(
                    $shippingAddress->getBaseDiscountAmount() - $pointDiscountAmount
                );
                $shippingAddress->setGrandTotal($shippingAddress->getGrandTotal() - $pointDiscountAmount);
                $shippingAddress->setBaseGrandTotal($shippingAddress->getBaseGrandTotal() - $pointDiscountAmount);
                $shippingAddress->setHasAjustPointAmount(true);
            }

            if ($usedPointAmount) {
                $shippingAddress->setAmountFromPoints($amountFromPoints);
                $shippingAddress->setUsedPoint($usedPointAmount);
                $shippingAddress->setBaseUsedPoint($usedPointAmount);
                $shippingAddress->setUsedPointAmount($usedPointAmount);
                $shippingAddress->setBaseUsedPointAmount($usedPointAmount);
                $shippingAddress->setGrandTotal($shippingAddress->getGrandTotal()-$usedPointAmount);
                $shippingAddress->setBaseGrandTotal($shippingAddress->getGrandTotal());
            }
        }

        $newQuote->setSubtotal($shippingAddress->getSubtotal());
        $newQuote->setBaseSubtotal($shippingAddress->getBaseSubtotal());
        $newQuote->setSubtotalInclTax($shippingAddress->getSubtotalInclTax());
        $newQuote->setBaseSubtotalInclTax($shippingAddress->getBaseSubtotalInclTax());
        $newQuote->setGrandTotal($shippingAddress->getGrandTotal());
        $newQuote->setBaseGrandTotal($shippingAddress->getBaseGrandTotal());
        $newQuote->setSubtotalWithDiscount($shippingAddress->getSubtotalWithDiscount());
        $newQuote->setBaseSubtotalWithDiscount($shippingAddress->getBaseSubtotalWithDiscount());
        $newQuote->setTaxAmount($shippingAddress->getTaxAmount());
        $newQuote->setBaseTaxAmount($shippingAddress->getBaseTaxAmount());
        $newQuote->setDiscountAmount($shippingAddress->getDiscountAmount());
        $newQuote->setBaseDiscountAmount($shippingAddress->getBaseDiscountAmount());
        $newQuote->setUsedPoint($shippingAddress->getUsedPoint());
        $newQuote->setBaseUsedPoint($shippingAddress->getBaseUsedPoint());
        $newQuote->setUsedPointAmount($shippingAddress->getUsedPointAmount());
        $newQuote->setBaseUsedPointAmount($shippingAddress->getBaseUsedPointAmount());

        // make sure payment method correct depend on grand total
        if ($newQuote->getGrandTotal() > 0) {
            if ($newQuote->getPayment()->getMethod() == Free::PAYMENT_METHOD_FREE_CODE) {
                $profileData = $this->getProfileDataForOutOfStockOrder($outOfStockList);
                if ($profileData) {
                    $newQuote->getPayment()
                        ->setMethod($profileData->getPaymentMethod())
                        ->save();
                }
            }
        } else {
            $newQuote->getPayment()->setMethod(Free::PAYMENT_METHOD_FREE_CODE)->save();
        }

        // cover payment fee on first oos order, if has not any payment fee on original order
        if ($newQuote->getGrandTotal() > 0
            && $this->canChargePaymentFeeForOutOfStockOrder($outOfStockList)
        ) {
            $paymentMethod = $newQuote->getPayment()->getMethod();
            $paymentFee = $this->paymentFeeManagement->getFeeByMethod($paymentMethod);
            $shippingAddress->setFee($paymentFee);
            $shippingAddress->setBaseFee($paymentFee);
            $shippingAddress->setGrandTotal($shippingAddress->getGrandTotal() + $paymentFee);
            $shippingAddress->setBaseGrandTotal($shippingAddress->getGrandTotal());
            $newQuote->setFee($shippingAddress->getFee());
            $newQuote->setBaseFee($shippingAddress->getBaseFee());
            $newQuote->setGrandTotal($shippingAddress->getGrandTotal());
            $newQuote->setBaseGrandTotal($shippingAddress->getBaseGrandTotal());
        }

        $billingAddress->save();
        $this->totalAdjustment->applyAdjustment($newQuote, $shippingAddress, $outOfStockList);

        $shippingAddress->save();
        $newQuote->setData('out_of_stock_ignore_collect_total', 1);
        $this->cartRepository->save($newQuote);

        return $newQuote;
    }

    /**
     * Clone payment
     *
     * @param \Magento\Quote\Model\Quote\Payment $payment
     *
     * @return \Magento\Quote\Model\Quote\Payment
     */
    public function clonePayment(\Magento\Quote\Model\Quote\Payment $payment)
    {
        if ($payment->getMethod() == \Bluecom\Paygent\Model\Paygent::CODE) {
            /** @var \Bluecom\Paygent\Model\PaygentOption $paygentOption */
            $paygentOption = $this->paygentOptionFactory->create()
                ->loadByAttribute(
                    'customer_id',
                    $payment->getQuote()->getCustomerId()
                );

            $paygentOption->setData('option_checkout', 0); // authorize without redirect
            $paygentOption->save();
        }

        return $payment;
    }

    /**
     * Clone payment from old order
     *
     * @param \Riki\Sales\Model\Order\Payment $paymentOrder
     * @param \Magento\Quote\Model\Quote\Payment $paymentQuote
     * @return \Magento\Quote\Model\Quote\Payment
     * @throws LocalizedException
     */
    public function clonePaymentFromOrder(
        \Riki\Sales\Model\Order\Payment $paymentOrder,
        \Magento\Quote\Model\Quote\Payment $paymentQuote
    ) {
        if ($paymentOrder->getMethod() == \Bluecom\Paygent\Model\Paygent::CODE) {
            /** @var \Bluecom\Paygent\Model\PaygentOption $paygentOption */
            $paygentOption = $this->paygentOptionFactory->create()
                ->loadByAttribute(
                    'customer_id',
                    $paymentOrder->getOrder()->getCustomerId()
                );

            if ($paygentOption) {
                $paygentOption->setData('option_checkout', 0); // authorize without redirect

                try {
                    $paygentOption->save();
                } catch (\Exception $e) {
                    $this->loggerHelper->getOosLogger()->critical($e);
                    throw new LocalizedException(__('An error occurred when generate new quote'));
                }
            }
        }
        $paymentQuote->setCcCidEnc($paymentOrder->getCcCidStatus());
        $paymentQuote->setCcLast4($paymentOrder->getCcLast4());
        $paymentQuote->setCcNumberEnc($paymentOrder->getCcNumberEnc());
        $paymentQuote->setCcSsIssue($paymentOrder->getCcSsIssue());
        $paymentQuote->setCcSsStartMonth($paymentOrder->getCcSsStartMonth());
        $paymentQuote->setCcSsStartYear($paymentOrder->getCcSsStartYear());
        $paymentQuote->setMethod($paymentOrder->getMethod());

        return $paymentQuote;
    }

    /**
     * Copy billing address from order
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Magento\Quote\Model\Quote\Address
     */
    protected function _initBillingAddressFromOrder(
        \Magento\Sales\Model\Order $order,
        \Magento\Quote\Model\Quote $quote
    ) {
        $quote->getBillingAddress()->setCustomerAddressId('');
        $this->objectCopyService->copyFieldsetToTarget(
            'sales_copy_order_billing_address',
            'to_order',
            $order->getBillingAddress(),
            $quote->getBillingAddress()
        );

        return $quote->getBillingAddress();
    }

    /**
     * Copy shipping address from order
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Quote\Model\Quote $quote
     * @return $this|\Magento\Framework\DataObject
     */
    protected function _initShippingAddressFromOrder(
        \Magento\Sales\Model\Order $order,
        \Magento\Quote\Model\Quote $quote
    ) {
        /*additional shipping address, only exist if this order use stock point address instead customer address*/
        $additionalShippingAddress = $order->getCustomerShippingAddress();

        if ($additionalShippingAddress) {
            $orderShippingAddress = $additionalShippingAddress;
        } else {
            /*current order shipping address*/
            $orderShippingAddress = $order->getShippingAddress();
        }

        /*quote shipping address*/
        $quoteShippingAddress = $quote->getShippingAddress()->setCustomerAddressId('');

        /*convert order address to quote address*/
        $this->objectCopyService->copyFieldsetToTarget(
            'sales_copy_order_shipping_address',
            'to_order',
            $orderShippingAddress,
            $quoteShippingAddress
        );

        return $quoteShippingAddress;
    }

    /**
     * get out of stock original order
     *
     * @param array $outOfStockList
     * @return bool|\Magento\Sales\Api\Data\OrderInterface
     */
    private function getOutOfStockOriginalOrder(array $outOfStockList)
    {
        /** @var \Riki\AdvancedInventory\Model\OutOfStock $outOfStock */
        foreach ($outOfStockList as $outOfStock) {
            $order = $outOfStock->getOriginalOrder();
            if ($order) {
                return $order;
            }
        }

        return false;
    }

    /**
     * sync kss data for customer
     *
     * @param $order
     * @param $outOfStockList
     */
    private function syncKssDataForCustomer($order, $outOfStockList)
    {
        // call KSS to update customer info
        $consumerId = $order->getData('customer_consumer_db_id');

        if (!in_array($consumerId, $this->updatedConsumers)) {
            $consumerData = $this->rikiCustomerRepository->prepareAllInfoCustomer($consumerId);

            $customer = $this->getOutOfStockCustomerInfo($outOfStockList);

            $this->rikiCustomerRepository->createUpdateEcCustomer(
                $consumerData,
                $consumerId,
                null,
                $customer
            );

            $this->updatedConsumers[] = $consumerId;
        }
    }

    /**
     * get out of stock customer info
     *
     * @param $outOfStockList
     * @return bool|\Magento\Customer\Api\Data\CustomerInterface
     */
    private function getOutOfStockCustomerInfo($outOfStockList)
    {
        /** @var \Riki\AdvancedInventory\Model\OutOfStock $outOfStock */
        foreach ($outOfStockList as $outOfStock) {
            $customer = $outOfStock->getCustomer();
            if ($customer) {
                return $customer;
            }
        }

        return false;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param array $outOfStockList
     * @return bool|\Magento\Quote\Api\Data\CartInterface
     * @throws LocalizedException
     */
    private function generateQuoteForOutOfStock($order, $outOfStockList)
    {
        $defaultOutOfStockItem = $this->getDefaultOutOfStockItem($outOfStockList);

        if (!$defaultOutOfStockItem) {
            return false;
        }

        $outOfStockQuote = false;

        try {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $defaultOutOfStockItem->getQuote();
            $outOfStockQuote = $this->cloneQuote($quote);
            $payment = $this->clonePayment($quote->getPayment());
            if ($defaultOutOfStockItem->getIsFree()) {
                $payment->setMethod(Free::PAYMENT_METHOD_FREE_CODE);
            }
            $outOfStockQuote->setPayment($payment);
            $outOfStockQuote->getResource()->save($outOfStockQuote);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
            // Quote has been deleted.
            if ($order) {
                $paymentOrder = $order->getPayment();
                $newQuotePaymentObject = $this->paymentFactory->create();
                $outOfStockQuote = $this->cloneQuoteFromOrder($order, $defaultOutOfStockItem);
                $payment = $this->clonePaymentFromOrder($paymentOrder, $newQuotePaymentObject);
                if ($defaultOutOfStockItem->getIsFree()) {
                    $payment->setMethod(\Magento\Payment\Model\Method\Free::PAYMENT_METHOD_FREE_CODE);
                }
                $outOfStockQuote->setPayment($payment);
                $outOfStockQuote->setData('invalidate_cache', 1);
                $outOfStockQuote->getResource()->save($outOfStockQuote);
            }
        }

        return $outOfStockQuote;
    }

    /**
     * generate quote item for out of stock quote
     *
     * @param \Magento\Quote\Model\Quote $newQuote
     * @param array $outOfStockList
     * @return Item[]
     * @throws LocalizedException
     */
    private function generateQuoteItemForOutOfStockQuote($newQuote, $outOfStockList)
    {
        $quoteItemList = [];

        /** @var \Riki\AdvancedInventory\Model\OutOfStock $outOfStock */
        foreach ($outOfStockList as $outOfStock) {
            $quoteItems = $this->outOfStockHelper->getQuoteItems($outOfStock);

            if (!$quoteItems) {
                throw new LocalizedException(
                    __('Quote item %1 does not exists', $outOfStock->getQuoteItemId())
                );
            }
            $outOfStock->setData('order_id', $outOfStock->getOriginalOrderId());
            $minDeliveryOrderItem = $this->outOfStockHelper->getMinDeliveryOrderItem($outOfStock);
            foreach ($quoteItems as $quoteItem) {
                $cartCandidates = null;
                if ($quoteItem->getData('product_type') == "bundle" && $this->isAllElementsEmpty($quoteItem->getData('options'))) {
                    $productId = $quoteItem->getProductId();
                    $product = $this->productFactory->create()->load($productId);
                    $productsArray = $this->getBundleOptions($product);
                    $params = [
                        'product' => $productId,
                        'bundle_option' => $productsArray,
                        'qty' => $quoteItem->getQty()
                    ];
                    $params = $this->objectFactory->create($params);

                    if ($product->getId()) {
                        $cartCandidates = $product->getTypeInstance()->prepareForCartAdvanced($params, $product);
                    }
                    foreach ($cartCandidates as $candidate) {
                        if($candidate->getTypeId() == 'bundle') {
                            $quoteItem->setOptions($candidate->getCustomOptions());
                            $quoteItem->setProduct($candidate);
                        }
                    }
                }
                $quoteItem->setQuoteId($newQuote->getId())
                    ->setQuote($newQuote);

                /** if original order is stock point
                 * then assign delivery according to
                 * subscription_profile_product_cart.original_delivery_date
                 */
                if ($this->checkOriginalIsStockPoint($outOfStock->getOriginalOrder())) {
                    $additionalData = $outOfStock->getAdditionalData();
                    if (isset($additionalData['original_delivery_date']) &&
                        isset($additionalData['original_delivery_time_slot'])
                    ) {
                        $originalTimeSlot = $this->timeSlotsFactory->create()
                            ->load($additionalData['original_delivery_time_slot']);
                        $originTime = null;
                        $originTimeTo = null;
                        $originTimeFrom = null;
                        if ($originalTimeSlot->getId()) {
                            $originTime = $originalTimeSlot->getData('slot_name');
                            $originTimeTo = $originalTimeSlot->getData('to');
                            $originTimeFrom = $originalTimeSlot->getData('from');
                        }
                        $quoteItem->setData('delivery_date', $additionalData['original_delivery_date']);
                        $quoteItem->setData('delivery_timeslot_id', $additionalData['original_delivery_time_slot']);
                        $quoteItem->setData('delivery_time', $originTime);
                        $quoteItem->setData('delivery_timeslot_to', $originTimeTo);
                        $quoteItem->setData('delivery_timeslot_from', $originTimeFrom);
                    } else {
                        $this->resetDeliveryDate($quoteItem);
                    }
                } elseif ($minDeliveryOrderItem instanceof \Magento\Sales\Model\Order\Item) {
                    $quoteItem->setData('delivery_date', $minDeliveryOrderItem->getData('delivery_date'));
                    $quoteItem->setData('delivery_time', $minDeliveryOrderItem->getData('delivery_time'));
                    $quoteItem->setData('delivery_timeslot_id', $minDeliveryOrderItem->getData('delivery_timeslot_id'));
                    $quoteItem->setData(
                        'delivery_timeslot_from',
                        $minDeliveryOrderItem->getData('delivery_timeslot_from')
                    );
                    $quoteItem->setData('delivery_timeslot_to', $minDeliveryOrderItem->getData('delivery_timeslot_to'));
                }

                if ($outOfStock->getPrizeId()) {
                    $prize = $this->outOfStockHelper->getPrize($outOfStock);
                    if ($prize) {
                        $quoteItem->setData('foc_wbs', $prize->getData('wbs'));
                    }
                }
                if ($outOfStock->getMachineSkuId()) {
                    /** @var \Riki\SubscriptionMachine\Model\MachineSkus $machineSku */
                    $machineSku = $this->machineSkuFactory->create()
                        ->load($outOfStock->getMachineSkuId()); // machine API should support repository
                    if ($machineSku->getId()) {
                        $quoteItem->setData('foc_wbs', $machineSku->getData('wbs'));
                    }
                }
                if (!$this->isAllElementsEmpty($quoteItem->getData('options'))) {
                    $quoteItem->setOptions($quoteItem->getData('options'));
                }

                $newQuote->addItem($quoteItem);
                $quoteItem->getResource()->save($quoteItem);

                $quoteItem->setIsFreeOutOfStockItem($outOfStock->getIsFree());

                $quoteItemList[] = $quoteItem;

                $bundleItems = $this->getBundleItems($newQuote, $quoteItem, $cartCandidates);
                foreach ($bundleItems as $bundleItem) {
                    $quoteItemList[] = $bundleItem;
                }
            }
        }

        return $quoteItemList;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     */
    public function resetDeliveryDate($quoteItem)
    {
        $quoteItem->setData('delivery_date', null);
        $quoteItem->setData('delivery_timeslot_id', null);
        $quoteItem->setData('delivery_time', null);
        $quoteItem->setData('delivery_timeslot_to', null);
        $quoteItem->setData('delivery_timeslot_from', null);
    }

    /**
     * @param \Riki\Sales\Model\Order $originalOrder
     * @return bool
     */
    public function checkOriginalIsStockPoint($originalOrder)
    {
        if ($originalOrder instanceof \Riki\Sales\Model\Order && $originalOrder->getIsStockPoint()) {
            return true;
        }
        return false;
    }

    /**
     * get default out of stock item from out of stock item list
     *
     * @param $outOfStockItemList
     * @return bool|\Riki\AdvancedInventory\Model\OutOfStock
     */
    private function getDefaultOutOfStockItem($outOfStockItemList)
    {
        if ($outOfStockItemList) {
            foreach ($outOfStockItemList as $oos) {
                if ($oos instanceof \Riki\AdvancedInventory\Model\OutOfStock) {
                    return $oos;
                }
            }
        }

        return false;
    }

    /**
     * is use all point for out of stock order
     *
     * @param $outOfStockList
     * @return bool
     */
    private function isUseAllPointForOutOfStockOrder($outOfStockList)
    {
        if ($outOfStockList) {
            /** @var \Riki\AdvancedInventory\Model\OutOfStock $outOfStock */
            foreach ($outOfStockList as $outOfStock) {
                if (!$outOfStock->getIsFree()
                    && $outOfStock->getIsUseAllPoint()
                    && $outOfStock->getSubscriptionProfileId()
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * get profile data for out of stock order
     *
     * @param $outOfStockList
     * @return bool|null|\Riki\Subscription\Model\Profile\Profile
     */
    private function getProfileDataForOutOfStockOrder($outOfStockList)
    {
        if ($outOfStockList) {
            /** @var \Riki\AdvancedInventory\Model\OutOfStock $outOfStock */
            foreach ($outOfStockList as $outOfStock) {
                if ($outOfStock->getProfile()) {
                    return $outOfStock->getProfile();
                }
            }
        }

        return false;
    }

    /**
     * will collect payment fee for this out of stock order
     *      to avoid collect payment fee again
     *
     * @param $outOfStockList
     * @return bool
     */
    private function canChargePaymentFeeForOutOfStockOrder($outOfStockList)
    {
        if ($outOfStockList) {
            /** @var \Riki\AdvancedInventory\Model\OutOfStock $outOfStock */
            foreach ($outOfStockList as $outOfStock) {
                if (!$outOfStock->getIsFree()
                    && $outOfStock->getSubscriptionProfileId()
                    && $outOfStock->getCanChargePaymentFee()
                ) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * generate shipping address for out of stock order
     *
     * @param \Magento\Sales\Model\Order $originalOrder
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Magento\Framework\DataObject
     */
    public function generateShippingAddressForOutOfStockOrder(
        \Magento\Sales\Model\Order $originalOrder,
        \Magento\Quote\Model\Quote $quote
    ) {
        return $this->_initShippingAddressFromOrder($originalOrder, $quote);
    }

    /**
     * Calculator gift wrapping to shippingAddress
     *
     * @param Item $quoteItem
     * @param \Magento\Quote\Model\Quote\Address $shippingAddress
     */
    private function applyGiftWrapping($quoteItem, $shippingAddress)
    {
        if ($quoteItem->getGwId()) {
            $qty = $this->totalAdjustment->getQuoteItemFinalQty($quoteItem);
            $gwItemsPrice = $quoteItem->getData('gw_price') * $qty;
            $gwItemsBasePrice = $quoteItem->getData('gw_base_price') * $qty;

            $gwItemsTaxAmount = $quoteItem->getData('gw_tax_amount') * $qty;
            $gwItemsBaseTaxAmount = $quoteItem->getData('gw_base_tax_amount') * $qty;

            $gwItemsPriceInclTax = $gwItemsPrice + $gwItemsTaxAmount;
            $gwItemsBasePriceInclTax = $gwItemsBasePrice + $gwItemsBaseTaxAmount;

            $shippingAddress->setGwItemsBasePrice($shippingAddress->getGwItemsBasePrice() + $gwItemsBasePrice);
            $shippingAddress->setGwItemsPrice($shippingAddress->getGwItemsPrice() + $gwItemsPrice);
            $shippingAddress->setGwItemsBaseTaxAmount($shippingAddress->getGwItemsBaseTaxAmount() + $gwItemsBaseTaxAmount);
            $shippingAddress->setGwItemsTaxAmount($shippingAddress->getGwItemsTaxAmount() + $gwItemsTaxAmount);
            $shippingAddress->setGwItemsBasePriceInclTax(
                $shippingAddress->getGwItemsBasePriceInclTax() + $gwItemsBasePriceInclTax
            );
            $shippingAddress->setGwItemsPriceInclTax($shippingAddress->getGwItemsPriceInclTax() + $gwItemsPriceInclTax);
        }
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $newQuote
     * @param \Magento\Quote\Model\Quote\Address $shippingAddress
     * @param \Magento\Quote\Model\Quote\Address $billingAddress
     * @return void
     */
    protected function buildShippingAmountData($newQuote, $shippingAddress, $billingAddress)
    {
        if ($shippingAddress->getShippingAmount() > 0) {
            $shippingTaxDetail = $this->shippingCalculator->getShippingTaxDetail(
                $newQuote,
                $shippingAddress,
                $billingAddress
            );
            $shippingAddress->setTaxAmount($shippingAddress->getTaxAmount() + $shippingTaxDetail->getRowTax());
            $shippingAddress->setBaseTaxAmount($shippingAddress->getBaseTaxAmount() + $shippingTaxDetail->getRowTax());
            $newQuote->setTaxAmount($shippingAddress->getTaxAmount());
            $newQuote->setBaseTaxAmount($shippingAddress->getBaseTaxAmount());
            $shippingAddress->setShippingAmount($shippingTaxDetail->getRowTotal());
            $shippingAddress->setBaseShippingAmount($shippingTaxDetail->getRowTotal());
            $shippingAddress->setShippingTaxAmount($shippingTaxDetail->getRowTax());
            $shippingAddress->setBaseShippingTaxAmount($shippingTaxDetail->getRowTax());
            $shippingAddress->setShippingInclTax($shippingTaxDetail->getRowTotalInclTax());
            $shippingAddress->setBaseShippingInclTax($shippingTaxDetail->getRowTotalInclTax());
            $shippingAddress->setGrandTotal($shippingAddress->getGrandTotal() + $shippingAddress->getShippingInclTax());
            $shippingAddress->setBaseGrandTotal(
                $shippingAddress->getBaseGrandTotal() + $shippingAddress->getShippingInclTax()
            );
        }
    }

    /**
     * @param Quote $newQuote
     * @param Item $parentQuoteItem
     * @return array
     */
    private function getBundleItems($newQuote, $parentQuoteItem, $cartCandidates = null)
    {
        $bundleItems = [];
        $product = $parentQuoteItem->getProduct();
        if ($product->getTypeId() == ProductType::TYPE_BUNDLE) {
            $quoteItemChildren = $parentQuoteItem->getData('children');
            foreach ($quoteItemChildren as $itemChild) {
                $quoteItemModel = $this->quoteItemFactory->create();
                unset($itemChild['item_id']);
                /** Parse qty to integer to map with code in
                vendor/magento/module-quote/Model/Quote/Item/CartItemPersister.php:75 */
                $itemChild['qty'] = (int)$itemChild['qty'];
                $itemChild['parent_item_id'] = $parentQuoteItem->getId();
                $quoteItemModel->setData($itemChild);
                if ($this->isAllElementsEmpty($itemChild['options']) && $cartCandidates != null) {
                    foreach ($cartCandidates as $candidate) {
                        if ($candidate->getEntityId() == $itemChild['product_id']) {
                            $quoteItemModel->setOptions($candidate->getCustomOptions());
                        }
                    }
                } else {
                    $quoteItemModel->setOptions($itemChild['options']);
                }
                $quoteItemModel->setQuoteId($newQuote->getId())
                    ->setQuote($newQuote);
                $newQuote->addItem($quoteItemModel);
                $quoteItemModel->getResource()->save($quoteItemModel);
                $bundleItems[] = $quoteItemModel;
            }
        }
        return $bundleItems;
    }

    private function isAllElementsEmpty($array)
    {
        if (empty($array)) {
            return true;
        }

        foreach ($array as $value) {
            if (!empty($value)) {
                return false;
            }
        }
        return true;
    }

    /**
     * get all the selection products used in bundle product
     * @param $product
     * @return mixed
     */
    private function getBundleOptions(Product $product)
    {
        $selectionCollection = $product->getTypeInstance()
            ->getSelectionsCollection(
                $product->getTypeInstance()->getOptionsIds($product),
                $product
            );
        $bundleOptions = [];
        foreach ($selectionCollection as $selection) {
            $bundleOptions[$selection->getOptionId()][] = $selection->getSelectionId();
        }
        return $bundleOptions;
    }
}
