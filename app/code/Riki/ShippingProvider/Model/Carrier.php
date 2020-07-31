<?php

namespace Riki\ShippingProvider\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Config;
use Magento\Shipping\Model\Rate\ResultFactory;
use Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface;
use Riki\DeliveryType\Model\Delitype;
use Magento\Store\Model\ScopeInterface;
use Magento\Checkout\Model\Session\Proxy as Session;
use Magento\Backend\Model\Session\Quote as BackendSessionQuote;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\App\State;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Header;
use Riki\Sales\Helper\Admin as SalesAdmin;

class Carrier extends AbstractCarrier implements CarrierInterface
{
    const XML_PATH_FREE_SHIPPING_CONDITION = 'delivery_free_shipping_amount';
    const XML_PATH_IS_TAX_INCLUDED = 'tax/calculation/shipping_includes_tax';
    const XML_PATH_TAX_CLASS = 'tax/classes/shipping_tax_class';
    const FREE_SHIPPING_FLAG = 'free_shipping';
    const NA = 'N/A';

    /**
     * Carrier's code
     *
     * @var string
     */
    protected $_code = 'riki_shipping';

    /**
     * Whether this carrier has fixed rates calculation
     *
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * ResultFactory
     *
     * @var ResultFactory
     */
    protected $rateResultFactory;

    /**
     * MethodFactory
     *
     * @var MethodFactory
     */
    protected $rateMethodFactory;

    /**
     * Frontend session
     *
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * Backend session
     *
     * @var BackendSessionQuote
     */
    protected $_checkoutBackendSession;

    /**
     * FrontNameResolver
     *
     * @var FrontNameResolver
     */
    protected $_area;

    /**
     * @var \Magento\Quote\Model\Quote\Proxy
     */
    protected $quote;

    /**
     * State
     *
     * @var State
     */
    protected $_appState;

    /**
     * ScopeConfigInterface
     *
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Delivery type
     *
     * @var Delitype
     */
    protected $_delitype;

    /**
     * HTTP prefer
     *
     * @var Header
     */
    protected $httpHeader;

    /**
     * Sales admin
     *
     * @var SalesAdmin
     */
    protected $salesAdmin;

    protected $_deliveryTypeAdminHelper;


    protected $_deliveryTypeGroupFreeShipping;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializer;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Registry
     * @param ScopeConfigInterface $scopeConfig            ScopeConfigInterface
     * @param ErrorFactory         $rateErrorFactory       ErrorFactory
     * @param LoggerInterface      $logger                 LoggerInterface
     * @param ResultFactory        $rateResultFactory      ResultFactory
     * @param MethodFactory        $rateMethodFactory      MethodFactory
     * @param Session              $checkoutSession        Session
     * @param BackendSessionQuote  $checkoutBackendSession BackendSessionQuote
     * @param FrontNameResolver    $area                   FrontNameResolver
     * @param State                $appState               State
     * @param Delitype             $delitype               Delitype
     * @param Header               $httpHeader             Header
     * @param SalesAdmin           $salesAdmin             Sales Admin
     * @param \Riki\DeliveryType\Helper\Admin $deliveryTypeAdminHelper
     * @param array                $data                   array
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        Session $checkoutSession,
        BackendSessionQuote $checkoutBackendSession,
        FrontNameResolver $area,
        State $appState,
        Delitype $delitype,
        \Riki\DeliveryType\Helper\Admin $deliveryTypeAdminHelper,
        Header $httpHeader,
        SalesAdmin $salesAdmin,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_checkoutBackendSession = $checkoutBackendSession;
        $this->_area = $area;
        $this->_appState = $appState;
        $this->_scopeConfig = $scopeConfig;
        $this->_delitype = $delitype;
        $this->_deliveryTypeAdminHelper = $deliveryTypeAdminHelper;
        $this->httpHeader = $httpHeader;
        $this->salesAdmin = $salesAdmin;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Framework\Serialize\Serializer\Json::class
        );
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * Generates list of allowed carrier`s shipping methods
     * Displays on cart price rules page
     *
     * @return array
     * @api
     */
    public function getAllowedMethods()
    {
        return [$this->getCarrierCode() => __($this->getConfigData('name'))];
    }

    /**
     * Collect and get rates for storefront
     *
     * @param RateRequest $request RateRequest
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return \Magento\Shipping\Model\Rate\Result|bool
     * @api
     */
    public function collectRates(RateRequest $request)
    {
        /**
         * Make sure that Shipping method is enabled
         */
        if (!$this->isActive()) {
            return false;
        }

        /**
         * Build Rate for each location
         * Each Rate displayed as shipping method
         *     under Carrier(In-Store Pickup) on frontend
         */
        $result = $this->rateResultFactory->create();
        $method = $this->rateMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData("methodname"));


        // Calculate rate depend on delivery type /total amount / campaign
        $price = $this->calculateFeeForEachAddress($request);

        $method->setPrice($price);
        $method->setData('cost', $price);

        $result->append($method);

        return $result;
    }

    /**
     * Calculate Shipping Fee for each address
     *
     * @param RateRequest $request RateRequest
     *
     * @throws \Exception
     *
     * @return number
     */
    public function calculateFeeForEachAddress($request)
    {
        $calculatedFeeForEachAddress = array();
        $total = 0;

        if (!$request->getFreeShipping() && !$this->_isAdminFreeShipping()) {
            $groupedItemByAddressId = $this->groupItemsByAddresses($request);

            foreach ($groupedItemByAddressId as $addressId => $items) {
                $calculatedFeeForEachAddress[$addressId][] = $this->calculateShippingFee($items);
            }

            $total = $this->calculateTotalFeeForAddresses($calculatedFeeForEachAddress);
        }
        $this->setFeeToQuote($calculatedFeeForEachAddress);

        $request->setData('calculatedFeeForEachAddress', $calculatedFeeForEachAddress);
        return $total;
    }

    /**
     * @param $calculatedFeeForEachAddress
     *
     * @return void
     */
    protected function setFeeToQuote($calculatedFeeForEachAddress)
    {
        // Save shipping fee detail into quote table
        $this->getQuote()->setData('shipping_fee_by_address', \Zend_Json::encode($calculatedFeeForEachAddress));
        $httpReferer = $this->httpHeader->getHttpReferer();
        $isAdmin = $this->_appState->getAreaCode() == FrontNameResolver::AREA_CODE;
        if (strpos($httpReferer, '/checkout') !== false && !$isAdmin) {
            $this->_checkoutSession->getQuote()->setData('shipping_fee_by_address', \Zend_Json::encode($calculatedFeeForEachAddress));
        }
        $this->registry->unregister('shipping_fee_by_address');
        $this->registry->register('shipping_fee_by_address', \Zend_Json::encode($calculatedFeeForEachAddress));
    }

    /**
     * Calculate Shipping Fee for each address
     *
     * @param array $calculatedFeeForEachAddress CalculatedFeeForEachAddress
     *
     * @return number
     */
    public function calculateTotalFeeForAddresses($calculatedFeeForEachAddress)
    {
        $total = 0;

        foreach ($calculatedFeeForEachAddress as $fees) {
            foreach ($fees as $fee) {
                $total+= array_sum($fee);
            }
        }

        return $total;
    }

    /**
     * @param RateRequest $request RateRequest
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function groupItemsByAddresses($request)
    {
        /* @var \Magento\Quote\Model\Quote\Item $item */
        $groupedItemsByAddress = array();

        $allItems = $request->getAllItems();
        $httpReferer = $this->httpHeader->getHttpReferer();
        $isAdmin = $this->_appState->getAreaCode() == FrontNameResolver::AREA_CODE;
        $isMultiCheckout = true;
        $isAdminMultiCheckout = $this->salesAdmin->isMultipleShippingAddressCart();

        if (strpos($httpReferer, '/checkout') !== false
            || ($isAdmin && !$isAdminMultiCheckout)) {
            $isMultiCheckout = false;
        }

        foreach ($allItems as $item) {
            /**
             * Not sure this is the best solution:
             * Which products contain parent item id that mean products are belong to their parent
             * Need to check before calculate shipping fee
             *
             * RIKI-8691: Avoid free attachment items join into the calculation process
             */
            $isFreeAttachmentItem = $this->isFreeAttachmentItem($item);

            if (!$item->getParentItemId() && !$isFreeAttachmentItem && !$item->getParentItem()) {
                $addressId = self::NA;
                if ($isMultiCheckout) {
                    $addressId = $item->getData('address_id') ? $item->getData('address_id') : self::NA;
                }
                $key = implode(
                    '_',
                    [
                        $item->getProductId(),
                        $item->getPriceInclTax(),
                        $item->getGwPrice(),
                        $item->getDiscountAmount()
                    ]
                );
                if (!isset($groupedItemsByAddress[$addressId][$key])
                    || $groupedItemsByAddress[$addressId][$key] == null) {
                    $item->setData('tmp_qty', $item->getQty());
                    $groupedItemsByAddress[$addressId][$key] = $item;
                } else {
                    if ($isMultiCheckout) {
                        /* @var \Magento\Quote\Model\Quote\Item $existedItem */
                        $existedItem = $groupedItemsByAddress[$addressId][$key];
                        $tmpQty = $existedItem->getData('tmp_qty') + $item->getQty();
                        $existedItem->setData('tmp_qty', $tmpQty);
                        $groupedItemsByAddress[$addressId][$key] = $existedItem;
                    } else {
                        /* @var \Magento\Quote\Model\Quote\Item $existedItem */
                        /* duplicated items */
                        $this->_logger->info(
                            __(
                                'Duplicated quote items: %1, %2 of Quote Id %2',
                                $groupedItemsByAddress[$addressId][$key]->getItemId(),
                                $item->getItemId(),
                                $item->getQuoteId()
                            )
                        );
                        $existedItem = $groupedItemsByAddress[$addressId][$key];
                        $tmpQty = $existedItem->getData('tmp_qty') + $item->getQty();
                        $existedItem->setData('tmp_qty', $tmpQty);
                        $existedItem->setData('qty', $tmpQty);
                        $existedItem->save();
                        $groupedItemsByAddress[$addressId][$key] = $existedItem;
                        //delete duplicated quote item id
                        $item->delete();
                    }
                }
            }
        }

        return $groupedItemsByAddress;
    }

    /**
     * Check is free attachment item
     *
     * @param \Magento\Quote\Model\Quote\Item $item Item
     *
     * @return bool
     */
    protected function isFreeAttachmentItem($item)
    {
        $options = $item->getOptions();

        if (count($options)) {
            /** @var array $optionsValue */
            $optionsValue = $this->serializer->unserialize($options[0]->getData('value'));
            if (isset($optionsValue['options'])) {
                if (isset($optionsValue['options']['is_free_attachment'])
                    && $optionsValue['options']['is_free_attachment'] == '1'
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check is free shipping by admin
     *
     * @return bool
     */
    protected function _isAdminFreeShipping()
    {
        $adminFreeShipping = false;
        if ($this->_appState->getAreaCode() == FrontNameResolver::AREA_CODE) {
            if ($this->_checkoutBackendSession->getFreeShippingFlag()) {
                $adminFreeShipping = true;
            }
        }

        return $adminFreeShipping;
    }

    /**
     * Calculate Shipping Fee
     *
     * @param RateRequest $request               RateRequest
     *
     * @return array
     */
    public function calculateShippingFee($request)
    {
        $arrayFee = array();

        $freeShippingAmount = floatval(
            $this->getConfigData(self::XML_PATH_FREE_SHIPPING_CONDITION)
        );

        $feeTypes = $this->_delitype->getResourceCollection()
            ->addFieldToSelect(array('code', 'shipping_fee'));

        $arrayFeeTypes = array();
        foreach ($feeTypes as $feeType) {
            $feeCode = $feeType->getData('code');
            $feeShip = $feeType->getData('shipping_fee');
            $arrayFeeTypes[$feeCode] = $feeShip;
        }
        $allItems = $request;

        $conditionFreeAllCoolNormalDm = array();
        $conditionFreeChilled = array();
        $conditionFreeCold = array();
        $conditionFreeCosmetic = array();

        /* @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($allItems as $item) {
            $deliveryType = $item->getData('delivery_type');
            if ($deliveryType) {
                $arrayFee[$deliveryType][] = $arrayFeeTypes[$deliveryType];

                $isFreeShippingItem = $this->_isFreeShippingItem($item);

                if ($isFreeShippingItem) {
                    array_push($arrayFee[$deliveryType], self::FREE_SHIPPING_FLAG);
                }

                $gwPriceIncludeTax = (float)$item->getData('gw_price') + (float)$item->getData('gw_tax_amount');
                $totalGwPriceIncludeTax = $gwPriceIncludeTax * $item->getData('tmp_qty') / ($item->getData('unit_qty') ?: 1); // gw apply only case
                $totalPriceInclTax = $item->getPriceInclTax() * $item->getData('tmp_qty');

                if ($this->_isCoolNormalDmType($deliveryType)) {
                    $conditionFreeAllCoolNormalDm[] = $totalPriceInclTax + $totalGwPriceIncludeTax;
                } elseif ($this->_isChilledType($deliveryType)) {
                    $conditionFreeChilled[] = $totalPriceInclTax + $totalGwPriceIncludeTax;
                } elseif ($this->_isColdType($deliveryType)) {
                    $conditionFreeCold[] = $totalPriceInclTax + $totalGwPriceIncludeTax;
                } elseif ($this->_isCosmeticType($deliveryType)) {
                    $conditionFreeCosmetic[] = $totalPriceInclTax + $totalGwPriceIncludeTax;
                }
            }
        }

        if ($arrayFee) {
            $arrayFee = $this->_calculateTotalFee($arrayFee);
        }
        if (array_sum($conditionFreeAllCoolNormalDm) >= $freeShippingAmount) {
            $arrayFee[Delitype::COOL_NORMAL_DM] = 0;
        }

        if (array_sum($conditionFreeChilled) >= $freeShippingAmount) {
            $arrayFee[Delitype::CHILLED] = 0;
        }

        if (array_sum($conditionFreeCold) >= $freeShippingAmount) {
            $arrayFee[Delitype::COLD] = 0;
        }

        if (array_sum($conditionFreeCosmetic) >= $freeShippingAmount) {
            $arrayFee[Delitype::COSMETIC] = 0;
        }

        return $arrayFee;
    }

    /**
     * Check is free shipping
     *
     * @param \Magento\Quote\Model\Quote\Item $item Item
     *
     * @return bool
     */
    protected function _isFreeShippingItem($item)
    {
        $itemDeliveryType = $this->_deliveryTypeAdminHelper->prepareDeliveryType($item->getDeliveryType());
        $addressId = (int)$item->getAddressId();

        if($free = $this->isFreeDeliveryTypeGroup($addressId, $itemDeliveryType)){
            return $free;
        }

        $freeShippingAmount = floatval(
            $this->getConfigData(self::XML_PATH_FREE_SHIPPING_CONDITION)
        );

        $isItemFreeShipping = $item->getFreeShipping();
        $gwPriceIncludeTax = (float)$item->getData('gw_price') + (float)$item->getData('gw_tax_amount');
        $totalGwPriceIncludeTax = $gwPriceIncludeTax * $item->getData('tmp_qty') / ($item->getData('unit_qty') ?: 1);
        $totalPriceInclTax = $item->getPriceInclTax() * $item->getData('tmp_qty');
        $totalInclTax = $totalPriceInclTax + $totalGwPriceIncludeTax;

        return $isItemFreeShipping || $totalInclTax >= $freeShippingAmount;
    }

    /**
     * Build Rate based on location data
     *
     * @param string $locationId Shipping method(location) identifier
     * @param array  $location   Location info
     *
     * @return Method
     */
    protected function buildRateForLocation($locationId, array $location)
    {
        $rateResultMethod = $this->rateMethodFactory->create();
        /**
         * Set carrier's method data
         */
        $rateResultMethod->setData('carrier', $this->getCarrierCode());
        $rateResultMethod->setData('carrier_title', $this->getConfigData('title'));

        /**
         * Displayed as shipping method under Carrier(In-Store Pickup)
         */
        $methodTitle = sprintf(
            '%s, %s, %s, %s (%s)',
            $location['street'],
            $location['city'],
            $location['country_id'],
            $location['postcode'],
            $location['message']
        );
        $rateResultMethod->setData('method_title', $methodTitle);
        $rateResultMethod->setData('method', $locationId);

        $rateResultMethod->setPrice(10);
        $rateResultMethod->setData('cost', 10);

        return $rateResultMethod;
    }

    /**
     * Get configured Store Shipping Origin
     *
     * @return array
     */
    protected function getShippingOrigin()
    {
        /**
         * Get Shipping origin data from store scope config
         * Displays data on storefront
         */
        return [
            'country_id' => $this->_scopeConfig->getValue(
                Config::XML_PATH_ORIGIN_COUNTRY_ID,
                ScopeInterface::SCOPE_STORE,
                $this->getData('store')
            ),
            'region_id' => $this->_scopeConfig->getValue(
                Config::XML_PATH_ORIGIN_REGION_ID,
                ScopeInterface::SCOPE_STORE,
                $this->getData('store')
            ),
            'postcode' => $this->_scopeConfig->getValue(
                Config::XML_PATH_ORIGIN_POSTCODE,
                ScopeInterface::SCOPE_STORE,
                $this->getData('store')
            ),
            'city' => $this->_scopeConfig->getValue(
                Config::XML_PATH_ORIGIN_CITY,
                ScopeInterface::SCOPE_STORE,
                $this->getData('store')
            )
        ];
    }

    /**
     * Check Cool Normal Dm delivery type
     *
     * @param array $arrayFee array
     *
     * @return array
     */
    protected function _calculateTotalFee($arrayFee)
    {
        $itemFees = $this->_calculateItemsFee($arrayFee);

        $feeCoolNormalDm = $this->_feeCoolNormalDm($itemFees);

        $fees = array(
            Delitype::COOL_NORMAL_DM => $feeCoolNormalDm,
            Delitype::COLD => $itemFees[Delitype::COLD],
            Delitype::CHILLED => $itemFees[Delitype::CHILLED],
            Delitype::COSMETIC => $itemFees[Delitype::COSMETIC]
        );

        return $fees;
    }

    /**
     * Calculate Cool Normal Dm fee
     *
     * @param array $itemFees array
     *
     * @return int|float
     */
    protected function _feeCoolNormalDm($itemFees)
    {
        $freeFlag = self::FREE_SHIPPING_FLAG;
        $isFreeCoolNormalDm = $itemFees[Delitype::COOL] == $freeFlag
            || $itemFees[Delitype::NORMAl] == $freeFlag
            || $itemFees[Delitype::DM] == $freeFlag;

        if ($isFreeCoolNormalDm) {
            $itemFees[Delitype::COOL] = 0;
            $itemFees[Delitype::NORMAl] = 0;
            $itemFees[Delitype::DM] = 0;
            $feeCoolNormalDm = 0;

            return $feeCoolNormalDm;
        }

        if ($itemFees[Delitype::COOL] != self::NA) {
            $feeCoolNormalDm = $itemFees[Delitype::COOL];
            $itemFees[Delitype::NORMAl] = $itemFees[Delitype::DM] = 0;

            return $feeCoolNormalDm;
        }

        if ($itemFees[Delitype::NORMAl] != self::NA) {
            $feeCoolNormalDm = $itemFees[Delitype::NORMAl];
            $itemFees[Delitype::COOL] = $itemFees[Delitype::DM] = 0;

            return $feeCoolNormalDm;
        }

        if ($itemFees[Delitype::DM] != self::NA) {
            $feeCoolNormalDm = $itemFees[Delitype::DM];
            $itemFees[Delitype::COOL] = $itemFees[Delitype::NORMAl] = 0;

            return $feeCoolNormalDm;
        }

        return self::NA;
    }


    /**
     * Calculate items fee
     *
     * @param array $arrayFee Array fee
     *
     * @return array
     */
    protected function _calculateItemsFee($arrayFee)
    {
        $isCoolExisted = array_key_exists(Delitype::COOL, $arrayFee);
        $isNormalExisted = array_key_exists(Delitype::NORMAl, $arrayFee);
        $isDmExisted = array_key_exists(Delitype::DM, $arrayFee);
        $isColdExisted = array_key_exists(Delitype::COLD, $arrayFee);
        $isChilledExisted = array_key_exists(Delitype::CHILLED, $arrayFee);
        $isCosmeticExisted = array_key_exists(Delitype::COSMETIC, $arrayFee);
        $coolFee = self::NA;
        $normalFee = self::NA;
        $dmFee = self::NA;
        $coldFee = self::NA;
        $chilledFee = self::NA;
        $cosmeticFee = self::NA;

        if ($isCoolExisted) {
            $coolFee = $this->_calculateCoolFee($arrayFee);
        }
        if ($isNormalExisted) {
            $normalFee = $this->_calculateNormalFee($arrayFee);
        }
        if ($isDmExisted) {
            $dmFee = $this->_calculateDmFee($arrayFee);
        }
        if ($isColdExisted) {
            $coldFee = $this->_calculateColdFee($arrayFee);
        }
        if ($isChilledExisted) {
            $chilledFee = $this->_calculateChilledFee($arrayFee);
        }
        if ($isCosmeticExisted) {
            $cosmeticFee = $this->_calculateCosmeticFee($arrayFee);
        }

        $itemFees = array(
            Delitype::COOL => $coolFee,
            Delitype::NORMAl => $normalFee,
            Delitype::DM => $dmFee,
            Delitype::COLD => $coldFee,
            Delitype::CHILLED => $chilledFee,
            Delitype::COSMETIC => $cosmeticFee
        );
        return $itemFees;
    }

    /**
     * Calculate COOL TYPE
     *
     * @param array $arrayFee Array Fee
     *
     * @return string|float
     */
    protected function _calculateCoolFee($arrayFee)
    {
        $coolFee = floatval($arrayFee[Delitype::COOL][0]);

        if (in_array(self::FREE_SHIPPING_FLAG, $arrayFee[Delitype::COOL])) {
            $coolFee = self::FREE_SHIPPING_FLAG;
        }

        return $coolFee;
    }

    /**
     * Calculate NORMAl TYPE
     *
     * @param array $arrayFee Array Fee
     *
     * @return string|float
     */
    protected function _calculateNormalFee($arrayFee)
    {
        $normalFee = floatval($arrayFee[Delitype::NORMAl][0]);

        if (in_array(self::FREE_SHIPPING_FLAG, $arrayFee[Delitype::NORMAl])) {
            $normalFee = self::FREE_SHIPPING_FLAG;
        }

        return $normalFee;
    }

    /**
     * Calculate DM TYPE
     *
     * @param array $arrayFee Array Fee
     *
     * @return string|float
     */
    protected function _calculateDmFee($arrayFee)
    {
        $dmFee = floatval($arrayFee[Delitype::DM][0]);

        if (in_array(self::FREE_SHIPPING_FLAG, $arrayFee[Delitype::DM])) {
            $dmFee = self::FREE_SHIPPING_FLAG;
        }

        return $dmFee;
    }

    /**
     * Calculate COLD TYPE
     *
     * @param array $arrayFee Array Fee
     *
     * @return string|float
     */
    protected function _calculateColdFee($arrayFee)
    {
        $coldFee = floatval($arrayFee[Delitype::COLD][0]);

        if (in_array(self::FREE_SHIPPING_FLAG, $arrayFee[Delitype::COLD])) {
            $coldFee = self::FREE_SHIPPING_FLAG;
        }

        return $coldFee;
    }

    /**
     * Calculate CHILLED TYPE
     *
     * @param array $arrayFee Array Fee
     *
     * @return string|float
     */
    protected function _calculateChilledFee($arrayFee)
    {
        $chilledFee = floatval($arrayFee[Delitype::CHILLED][0]);

        if (in_array(self::FREE_SHIPPING_FLAG, $arrayFee[Delitype::CHILLED])) {
            $chilledFee = self::FREE_SHIPPING_FLAG;
        }

        return $chilledFee;
    }

    /**
     * Calculate COSMETIC TYPE
     *
     * @param array $arrayFee Array Fee
     *
     * @return string|float
     */
    protected function _calculateCosmeticFee($arrayFee)
    {
        $cosmeticFee = floatval($arrayFee[Delitype::COSMETIC][0]);

        if (in_array(self::FREE_SHIPPING_FLAG, $arrayFee[Delitype::COSMETIC])) {
            $cosmeticFee = self::FREE_SHIPPING_FLAG;
        }

        return $cosmeticFee;
    }


    /**
     * Check is Cool Normal Dm
     *
     * @param string $deliveryType Delivery type
     *
     * @return bool
     */
    protected function _isCoolNormalDmType($deliveryType)
    {
        if ($deliveryType !== Delitype::COOL
            && $deliveryType !== Delitype::NORMAl
            && $deliveryType !== Delitype::DM
        ) {
            return false;
        }

        return true;
    }
    /**
     * Check is Chilled
     *
     * @param string $deliveryType Delivery type
     *
     * @return bool
     */
    protected function _isChilledType($deliveryType)
    {
        if ($deliveryType == Delitype::CHILLED) {
            return true;
        }

        return false;
    }
    /**
     * Check is Cold
     *
     * @param string $deliveryType Delivery type
     *
     * @return bool
     */
    protected function _isColdType($deliveryType)
    {
        if ($deliveryType == Delitype::COLD) {
            return true;
        }

        return false;
    }
    /**
     * Check is Cosmetic
     *
     * @param string $deliveryType Delivery type
     *
     * @return bool
     */
    protected function _isCosmeticType($deliveryType)
    {
        if ($deliveryType == Delitype::COSMETIC) {
            return true;
        }

        return false;
    }

    protected function _getCurrentSession()
    {
        $isAdmin = $this->_appState->getAreaCode() == FrontNameResolver::AREA_CODE;
        if ($isAdmin) {
            $currentSession = $this->_checkoutBackendSession->getQuote();
        } else {
            $currentSession = $this->_checkoutSession;
        }

        return $currentSession;
    }

    /**
     * Get Quote base on area
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        if(is_null($this->quote)){
            $this->quote = $this->_getCurrentSession();
        }

        return $this->quote;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     */
    public function setQuote(\Magento\Quote\Model\Quote $quote){
        $this->quote = $quote;
    }

    /**
     * Check tax enabled/disabled
     *
     * @return bool
     */
    protected function _isTaxIncluded()
    {
        return $this->_scopeConfig->getValue(self::XML_PATH_IS_TAX_INCLUDED);
    }

    /**
     * @param $addressId
     * @param $deliveryType
     * @return bool
     */
    public function isFreeDeliveryTypeGroup($addressId, $deliveryType)
    {
        if ($this->_appState->getAreaCode() == FrontNameResolver::AREA_CODE) {
            if (is_null($this->_deliveryTypeGroupFreeShipping)) {
                $deliveryTypeGroupFreeShipping = $this->_checkoutBackendSession->getDeliverytypeGroupFreeShipping();

                if (!is_null($deliveryTypeGroupFreeShipping)) {
                    $this->_deliveryTypeGroupFreeShipping = $this->serializer->unserialize($deliveryTypeGroupFreeShipping);
                }
            }

            if (is_array($this->_deliveryTypeGroupFreeShipping)) {
                if (isset($this->_deliveryTypeGroupFreeShipping[$addressId . '_' . $deliveryType]) && $this->_deliveryTypeGroupFreeShipping[$addressId . '_' . $deliveryType]) {
                    return true;
                }
            }
        }

        return false;
    }
}

