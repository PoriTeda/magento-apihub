<?php

namespace Riki\Checkout\Model;

use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Customer\Model\Registration as CustomerRegistration;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\Url as CustomerUrlManager;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Locale\CurrencyInterface as CurrencyManager;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartItemRepositoryInterface as QuoteItemRepository;
use Magento\Quote\Api\ShippingMethodManagementInterface as ShippingMethodManager;
use Magento\Catalog\Helper\Product\ConfigurationPool;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Framework\Locale\FormatInterface as LocaleFormat;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;


class DefaultConfigProvider extends \Magento\Checkout\Model\DefaultConfigProvider
{
    /**
     * @var CheckoutHelper
     */
    protected $checkoutHelper;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var CustomerUrlManager
     */
    protected $customerUrlManager;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var QuoteItemRepository
     */
    protected $quoteItemRepository;

    /**
     * @var ShippingMethodManager
     */
    protected $shippingMethodManager;

    /**
     * @var ConfigurationPool
     */
    protected $configurationPool;
    /**
     * @var \Magento\Framework\Pricing\Adjustment\CalculatorInterface
     */
    protected $calculator;
    /**
     * @var \Amasty\Promo\Helper\Item
     */
    protected $_promoItemHelper;
    /**
     * @var \Bluecom\Paygent\Model\PaygentHistory
     */
    protected $paygentHistory;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    public function __construct(
        CheckoutHelper $checkoutHelper,
        CheckoutSession $checkoutSession,
        CustomerRepository $customerRepository,
        CustomerSession $customerSession,
        CustomerUrlManager $customerUrlManager,
        HttpContext $httpContext,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        QuoteItemRepository $quoteItemRepository,
        ShippingMethodManager $shippingMethodManager,
        ConfigurationPool $configurationPool,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        LocaleFormat $localeFormat,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Customer\Model\Address\Config $addressConfig,
        FormKey $formKey,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Framework\View\ConfigInterface $viewConfig,
        \Magento\Directory\Model\Country\Postcode\ConfigInterface $postCodesConfig,
        \Magento\Checkout\Model\Cart\ImageProvider $imageProvider,
        \Magento\Directory\Helper\Data $directoryHelper,
        CartTotalRepositoryInterface $cartTotalRepository,
        ScopeConfigInterface $scopeConfig,
        \Magento\Shipping\Model\Config $shippingMethodConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement,
        UrlInterface $urlBuilder,
        \Magento\Framework\Pricing\Adjustment\CalculatorInterface $calculator,
        \Amasty\Promo\Helper\Item $promoItemHelper,
        \Bluecom\Paygent\Model\PaygentHistory $paygentHistory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
    ) {
        $this->checkoutHelper = $checkoutHelper;
        $this->checkoutSession = $checkoutSession;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->customerUrlManager = $customerUrlManager;
        $this->httpContext = $httpContext;
        $this->quoteRepository = $quoteRepository;
        $this->quoteItemRepository = $quoteItemRepository;
        $this->shippingMethodManager = $shippingMethodManager;
        $this->configurationPool = $configurationPool;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->localeFormat = $localeFormat;
        $this->addressMapper = $addressMapper;
        $this->addressConfig = $addressConfig;
        $this->formKey = $formKey;
        $this->imageHelper = $imageHelper;
        $this->viewConfig = $viewConfig;
        $this->postCodesConfig = $postCodesConfig;
        $this->imageProvider = $imageProvider;
        $this->directoryHelper = $directoryHelper;
        $this->cartTotalRepository = $cartTotalRepository;
        $this->scopeConfig = $scopeConfig;
        $this->shippingMethodConfig = $shippingMethodConfig;
        $this->storeManager = $storeManager;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->urlBuilder = $urlBuilder;
        $this->calculator = $calculator;
        $this->_promoItemHelper = $promoItemHelper;
        $this->paygentHistory = $paygentHistory;
        $this->productRepository = $productRepository;
        $this->timezone = $timezone;
        $this->dateTime = $dateTime;
        parent::__construct($checkoutHelper,
            $checkoutSession,
            $customerRepository,
            $customerSession,
            $customerUrlManager,
            $httpContext,
            $quoteRepository,
            $quoteItemRepository,
            $shippingMethodManager,
            $configurationPool,
            $quoteIdMaskFactory,
            $localeFormat,
            $addressMapper,
            $addressConfig,
            $formKey,
            $imageHelper,
            $viewConfig,
            $postCodesConfig,
            $imageProvider,
            $directoryHelper,
            $cartTotalRepository,
            $scopeConfig,
            $shippingMethodConfig,
            $storeManager,
            $paymentMethodManagement,
            $urlBuilder
        );
    }


    public function getConfig()
    {
        $quoteId = $this->checkoutSession->getQuote()->getId();
        $output['formKey'] = $this->formKey->getFormKey();
        $output['customerData'] = $this->getCustomerData();
        $output['quoteData'] = $this->getQuoteData();
        $output['quoteItemData'] = $this->getQuoteItemData();
        $output['isCustomerLoggedIn'] = $this->isCustomerLoggedIn();
        $output['selectedShippingMethod'] = $this->getSelectedShippingMethod();
        $output['storeCode'] = $this->getStoreCode();
        $output['isGuestCheckoutAllowed'] = $this->isGuestCheckoutAllowed();
        $output['isCustomerLoginRequired'] = $this->isCustomerLoginRequired();
        $output['registerUrl'] = $this->getRegisterUrl();
        $output['checkoutUrl'] = $this->getCheckoutUrl();
        $output['pageNotFoundUrl'] = $this->pageNotFoundUrl();
        $output['forgotPasswordUrl'] = $this->getForgotPasswordUrl();
        $output['staticBaseUrl'] = $this->getStaticBaseUrl();
        $output['priceFormat'] = $this->localeFormat->getPriceFormat(
            null,
            $this->checkoutSession->getQuote()->getQuoteCurrencyCode()
        );
        $output['basePriceFormat'] = $this->localeFormat->getPriceFormat(
            null,
            $this->checkoutSession->getQuote()->getBaseCurrencyCode()
        );
        $output['postCodes'] = $this->postCodesConfig->getPostCodes();
        $output['imageData'] = $this->imageProvider->getImages($quoteId);
        $output['totalsData'] = $this->getTotalsData();
        $output['shippingPolicy'] = [
            'isEnabled' => $this->scopeConfig->isSetFlag(
                'shipping/shipping_policy/enable_shipping_policy',
                ScopeInterface::SCOPE_STORE
            ),
            'shippingPolicyContent' => nl2br(
                $this->scopeConfig->getValue(
                    'shipping/shipping_policy/shipping_policy_content',
                    ScopeInterface::SCOPE_STORE
                )
            )
        ];
        $output['activeCarriers'] = $this->getActiveCarriers();
        $output['originCountryCode'] = $this->getOriginCountryCode();
        $output['paymentMethods'] = $this->getPaymentMethods();
        $output['autocomplete'] = $this->isAutocompleteEnabled();
        $output['freegift_message'] = $this->scopeConfig->getValue(
            'ampromo/messages/cart_message',
            ScopeInterface::SCOPE_STORE
        );
        $output['cc_used_date'] = $this->getCcLastUsedDate();
        $output['wrappingServicesLink'] = $this->_getWrappingServicesLink();
        return $output;
    }

    /**
     * Get last used date of credit card
     *
     * @return bool
     */
    protected function getCcLastUsedDate()
    {
        $customerId = $this->customerSession->getCustomerId();
        $customer = $this->customerSession->getCustomer();
        $paygentTrading = $customer->getData('paygent_transaction_id');
        if(!$paygentTrading) {
            return false;
        }
        $paygentExpire = $customer->getData('paygent_transaction_expire');
        $realExpire = $this->dateTime->date('Y-m-d H:i:s', strtotime($paygentExpire . ' +365 day'));

        $today = $this->timezone->date()->format('Y-m-d H:i:s');

        if ( $paygentTrading && ($realExpire > $today) ) {

            /*get credit card last used date*/
            $collection = $this->paygentHistory->getCollection()
                ->addFieldToFilter('customer_id', ['eq' => $customerId])
                //->addFieldToFilter('order_number', ['neq' => ''])
                ->addFieldToFilter('type', ['eq' => 'authorize'])
                ->setOrder('id', 'desc')
                ->setPageSize(1);

            if (!$collection->getSize()) {
                /*return true for paygent_transaction_id is imported from legacy order*/
                return true;
            }
            return  $collection->getFirstItem()->getUsedDate();
        }

        return false;
    }

    /**
     * Is autocomplete enabled for storefront
     *
     * @return string
     * @codeCoverageIgnore
     */
    private function isAutocompleteEnabled()
    {
        return $this->scopeConfig->getValue(
            \Magento\Customer\Model\Form::XML_PATH_ENABLE_AUTOCOMPLETE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) ? 'on' : 'off';
    }

    /**
     * Retrieve customer data
     *
     * @return array
     */
    private function getCustomerData()
    {
        $customerData = [];
        if ($this->isCustomerLoggedIn()) {
            $customer = $this->customerRepository->getById($this->customerSession->getCustomerId());
            $customerData = $customer->__toArray();
            foreach ($customer->getAddresses() as $key => $address) {
                $customerData['addresses'][$key]['inline'] = $this->getCustomerAddressInline($address);
            }
        }
        return $customerData;
    }

    /**
     * @param $customerId
     * @return mixed
     */
    public function getConfigCustomerData($customerId){
        $customer = $this->customerRepository->getById($customerId);
        $customerData = $customer->__toArray();
        foreach ($customer->getAddresses() as $key => $address) {
            $customerData['addresses'][$key]['inline'] = $this->getCustomerAddressInline($address);
        }
        return $customerData;
    }

    /**
     * Set additional customer address data
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return string
     */
    private function getCustomerAddressInline($address)
    {
        $builtOutputAddressData = $this->addressMapper->toFlatArray($address);
        return $this->addressConfig
            ->getFormatByCode(\Magento\Customer\Model\Address\Config::DEFAULT_ADDRESS_FORMAT)
            ->getRenderer()
            ->renderArray($builtOutputAddressData);
    }

    /**
     * Retrieve quote data
     *
     * @return array
     */
    private function getQuoteData()
    {
        $quoteData = [];
        if ($this->checkoutSession->getQuote()->getId()) {
            $quote = $this->quoteRepository->get($this->checkoutSession->getQuote()->getId());
            $quoteData = $quote->toArray();
            $quoteData['is_virtual'] = $quote->getIsVirtual();

            if (!$quote->getCustomer()->getId()) {
                /** @var $quoteIdMask \Magento\Quote\Model\QuoteIdMask */
                $quoteIdMask = $this->quoteIdMaskFactory->create();
                $quoteData['entity_id'] = $quoteIdMask->load(
                    $this->checkoutSession->getQuote()->getId(),
                    'quote_id'
                )->getMaskedId();
            }

        }
        return $quoteData;
    }
    /**
     * Initialize product instance from request data
     *
     * @return \Magento\Catalog\Model\Product|false
     */
    protected function _initProduct($productId)
    {
        if ($productId) {
            $storeId = $this->storeManager->getStore()->getId();
            try {
                return $this->productRepository->getById($productId, false, $storeId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }
    /**
     * Retrieve quote item data
     *
     * @return array
     */
    public function getQuoteItemData()
    {
        $quoteItemData = [];
        $quoteId = $this->checkoutSession->getQuote()->getId();
        if ($quoteId) {
            $quoteItems = $this->quoteItemRepository->getList($quoteId);
            foreach ($quoteItems as $index => $quoteItem) {
                $quoteItemData[$index] = $quoteItem->toArray();
                $quoteItemData[$index]['visibleInCart'] = 1;
                $quoteItemData[$index]['options'] = $this->getFormattedOptionValue($quoteItem);
                $quoteItemData[$index]['thumbnail'] = $this->imageHelper->init(
                    $quoteItem->getProduct(),
                    'product_thumbnail_image'
                )->getUrl();


                /** @var \Magento\Catalog\Model\Product $product */
                $product = $this->_initProduct($quoteItem->getProductId());

                $useConfigMinSale = $product->getExtensionAttributes()->getStockItem()->getData('use_config_min_sale_qty');
                $useConfigMaxSale = $product->getExtensionAttributes()->getStockItem()->getData('use_config_max_sale_qty');

                if($useConfigMinSale) {
                    $minSaleQty = $this->scopeConfig->getValue(\Magento\CatalogInventory\Model\Configuration::XML_PATH_MIN_SALE_QTY,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    );

                } else {
                    $minSaleQty = $product->getExtensionAttributes()->getStockItem()->getData('min_sale_qty');
                }
                if($useConfigMaxSale) {
                    $maxSaleQty = $this->scopeConfig->getValue(\Magento\CatalogInventory\Model\Configuration::XML_PATH_MAX_SALE_QTY,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    );
                } else {
                    $maxSaleQty = $product->getExtensionAttributes()->getStockItem()->getData('max_sale_qty');
                }

                $quoteItemData[$index]['min_sale_qty'] = $minSaleQty;

                if($maxSaleQty == 0 || $maxSaleQty > 99) {
                    $maxSaleQty = 99;
                }
                $quoteItemData[$index]['max_sale_qty'] = $maxSaleQty;

                $priceInfo = $product->getPriceInfo();
                $finalPrice = $priceInfo->getPrice('final_price')->getAmount()->getValue();
                $regularPrice = $priceInfo->getPrice('regular_price')->getAmount()->getValue();

                $quoteItemData[$index]['product_regular_price'] = floor($regularPrice);
                $quoteItemData[$index]['product_final_price'] = floor($finalPrice);

                if ($product->getTierPrice()) {
                    $quoteItemData[$index]['tier_price'] = $this->getTierPriceList($quoteItem->getProduct());
                }

                if ($this->_promoItemHelper->isPromoItem($quoteItem)
                    || (($quoteItem->getData('is_riki_machine') && $quoteItem->getData('price') == 0))
                    || $quoteItem->getPrizeId()
                ) {
                    $quoteItemData[$index]['free_item'] = true;
                } else {
                    $quoteItemData[$index]['free_item'] = false;
                }

            }
        }
        return $quoteItemData;
    }

    protected $product;

    /**
     * Get tier price list
     *
     * @param $product
     *
     * @return mixed
     */
    public function getTierPriceList($product)
    {
        $tierPrice = $product->getTierPrice();
        $this->product = $product;
        array_walk(
            $tierPrice,
            function (&$priceData) {
                /* convert string value to float */
                $priceData['price_qty'] = $priceData['price_qty'] * 1;
                $tierPrice = $this->calculator->getAmount($priceData['price'], $this->product);
                $priceData['price'] = floor($tierPrice->getValue());
            }
        );
        return $tierPrice;
    }

    /**
     * Retrieve selected shipping method
     *
     * @return array|null
     */
    private function getSelectedShippingMethod()
    {
        $shippingMethodData = null;
        try {
            $quoteId = $this->checkoutSession->getQuote()->getId();
            $shippingMethod = $this->shippingMethodManager->get($quoteId);
            if ($shippingMethod) {
                $shippingMethodData = $shippingMethod->__toArray();
            }
        } catch (\Exception $exception) {
            $shippingMethodData = null;
        }
        return $shippingMethodData;
    }

    /**
     * Retrieve store code
     *
     * @return string
     * @codeCoverageIgnore
     */
    private function getStoreCode()
    {
        return $this->checkoutSession->getQuote()->getStore()->getCode();
    }

    /**
     * Check if guest checkout is allowed
     *
     * @return bool
     * @codeCoverageIgnore
     */
    private function isGuestCheckoutAllowed()
    {
        return $this->checkoutHelper->isAllowedGuestCheckout($this->checkoutSession->getQuote());
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     * @codeCoverageIgnore
     */
    private function isCustomerLoggedIn()
    {
        return (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }

    /**
     * Check if customer must be logged in to proceed with checkout
     *
     * @return bool
     * @codeCoverageIgnore
     */
    private function isCustomerLoginRequired()
    {
        return $this->checkoutHelper->isCustomerMustBeLogged();
    }

    /**
     * Return forgot password URL
     *
     * @return string
     * @codeCoverageIgnore
     */
    private function getForgotPasswordUrl()
    {
        return $this->customerUrlManager->getForgotPasswordUrl();
    }

    /**
     * Return base static url.
     *
     * @return string
     * @codeCoverageIgnore
     */
    protected function getStaticBaseUrl()
    {
        return $this->checkoutSession->getQuote()->getStore()->getBaseUrl(UrlInterface::URL_TYPE_STATIC);
    }

    /**
     * Return quote totals data
     * @return array
     */
    private function getTotalsData()
    {
        $quote = $this->checkoutSession->getQuote();
        /** @var \Magento\Quote\Api\Data\TotalsInterface $totals */
        $totals = $this->cartTotalRepository->get($quote->getId());
        $items = [];
        /** @var  \Magento\Quote\Model\Cart\Totals\Item $item */
        foreach ($totals->getItems() as $item) {
            $items[] = $item->__toArray();
        }
        $totalSegmentsData = [];
        /** @var \Magento\Quote\Model\Cart\TotalSegment $totalSegment */
        foreach ($totals->getTotalSegments() as $totalSegment) {
            $totalSegmentArray = $totalSegment->toArray();
            if (is_object($totalSegment->getExtensionAttributes())) { // @codingStandardsIgnoreLine
                $totalSegmentArray['extension_attributes'] = $totalSegment->getExtensionAttributes()->__toArray();
            }
            $totalSegmentsData[] = $totalSegmentArray;
        }
        $totals->setItems($items);
        $totals->setTotalSegments($totalSegmentsData);
        $totalsArray = $totals->toArray();
        if (is_object($totals->getExtensionAttributes())) { // @codingStandardsIgnoreLine
            $totalsArray['extension_attributes'] = $totals->getExtensionAttributes()->__toArray();
        }

        return $totalsArray;
    }

    /**
     * Returns active carriers codes
     * @return array
     */
    private function getActiveCarriers()
    {
        $activeCarriers = [];
        foreach ($this->shippingMethodConfig->getActiveCarriers() as $carrier) {
            $activeCarriers[] = $carrier->getCarrierCode();
        }
        return $activeCarriers;
    }

    /**
     * Returns origin country code
     * @return string
     */
    private function getOriginCountryCode()
    {
        return $this->scopeConfig->getValue(
            \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_COUNTRY_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns array of payment methods
     * @return array
     */
    private function getPaymentMethods()
    {
        $paymentMethods = [];
        $quote = $this->checkoutSession->getQuote();
        if ($quote->getIsVirtual()) {
            foreach ($this->paymentMethodManagement->getList($quote->getId()) as $paymentMethod) {
                $paymentMethods[] = [
                    'code' => $paymentMethod->getCode(),
                    'title' => $paymentMethod->getTitle()
                ];
            }
        }
        return $paymentMethods;
    }
    /**
     * @return mixed
     */
    private function _getWrappingServicesLink()
    {
        return $this->scopeConfig->getValue(
            'wrapping_services_link/wrapping_services_group/wrapping_services_input',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
