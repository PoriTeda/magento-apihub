<?php

namespace Riki\DeliveryType\Model;

use Riki\DeliveryType\Api\Data\QuoteItemAddressDdateProcessorInterface;

use Riki\DeliveryType\Model\Delitype as Dtype;
use Psr\Log\LoggerInterface as Logger;
use Riki\ProductStockStatus\Helper\StockData;
class QuoteItemAddressDdateProcessor implements QuoteItemAddressDdateProcessorInterface
{
    /**
     * @var $modelDeliveryDate \Riki\DeliveryType\Model\DeliveryDate
     */
    protected $modelDeliveryDate;
    /**
     * @var $pointOfSaleFactory \Wyomind\PointOfSale\Model\PointOfSaleFactory
     */
    protected $pointOfSaleFactory;

    /**
     * @var $logger \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Magento\Framework\Pricing\Adjustment\CalculatorInterface
     */
    protected $calculator;
    /**
     * @var \Riki\Promo\Helper\Data
     */
    protected $_promoItemHelper;
    /**
     * @var \Riki\Preorder\Helper\Data
     */
    protected $preOrderHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Wyomind\AdvancedInventory\Model\Stock
     */
    protected $stock;
    /**
     * @var \Riki\DeliveryType\Helper\Data
     */
    protected $_dlHelper;

    protected $stockHelper;
    public function __construct(
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory,
        Logger $logger,
        DeliveryDate $modelDeliveryDate,
        \Magento\Framework\Pricing\Adjustment\CalculatorInterface $calculator,
        \Riki\Promo\Helper\Data $promoItemHelper,
        \Riki\Preorder\Helper\Data $preOrderHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Wyomind\AdvancedInventory\Model\Stock $stock,
        \Riki\DeliveryType\Helper\Data $dlHelper,
        StockData $stockData
    ) {
        $this->modelDeliveryDate = $modelDeliveryDate;
        $this->pointOfSaleFactory = $pointOfSaleFactory;
        $this->logger = $logger;
        $this->calculator = $calculator;
        $this->_promoItemHelper = $promoItemHelper;
        $this->preOrderHelper = $preOrderHelper;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->scopeConfig = $scopeConfig;
        $this->stock = $stock;
        $this->_dlHelper = $dlHelper;
        $this->stockHelper = $stockData;
    }

    /**
     * Caculate list days will disable for calendar , group item have same delivery type
     *
     * @param $listWh
     * @param $listType
     * @param $regionCode
     * @return array
     */
    public function getDeliveryCalendar($listWh, $listType, $regionCode, $extendInfo = [])
    {
        //caculate number next date
        $leadTimeCollection = $this->modelDeliveryDate->caculateDate($listWh, $listType, $regionCode);

        $numberNextDate = 0;
        $posCode = $listWh[0];

        if ($leadTimeCollection) {
            $numberNextDate = $leadTimeCollection['shipping_lead_time'];
            $posCode = $leadTimeCollection['warehouse_id'];
        }

        $finalDelivery = $this->modelDeliveryDate->caculateFinalDay($numberNextDate, $posCode, $extendInfo);

        //caculate preriod display calendar
        if ($this->modelDeliveryDate->getCalendarPeriod()) {
            $period = $this->modelDeliveryDate->getCalendarPeriod() + count($finalDelivery) - 1;
        } else {
            $period = 29 + count($finalDelivery);
        }
        $calendar = [
            "period" => $period,
            "deliverydate" => $finalDelivery
        ];

        return $calendar;
    }


    /**
     * @param array $cartItems
     * @return array
     */
    public function splitQuoteByDeliveryType(array $cartItems)
    {
        $result = [];

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($cartItems as $item) {
            $deliveryType = $item->getData('delivery_type');

            if (!isset($result[$deliveryType])) {
                $result[$deliveryType] = [];
            }

            $result[$deliveryType][] = $item->getId();
        }

        return $result;
    }
    /**
     * Check cart only DM type delivery
     *
     * @param $quote
     * @return array
     */
    public function splitQuoteByDeliveryTypeDm(array $cartItems)
    {
        $onlyDm = true;
        foreach ($cartItems as $item) {
            if (!$item->getParentItemId()) {
                $Dtype = $item->getDeliveryType();
                if($Dtype != Dtype::DM){
                    return false;
                }
            }

        }
        return $onlyDm;

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
     * Check Pre-order
     *
     * @return bool
     */
    private function _isPreOrder($cartItems)
    {
        // Check pre-order
        foreach ($cartItems as $item) {
            $product = $this->_initProduct($item->getProductId());
            if ($this->preOrderHelper->getIsProductPreorder($product)) {
                $dataCalendar = [];
                $dataCalendar['pre_order'] = true;
                $dataCalendar['timeslot'] = [];
                $dataCalendar['period'] = 0;
                $dataCalendar['name'] = $product->getDeliveryType();
                $dataCalendar['code'] = $product->getDeliveryType();

                return $dataCalendar;
            } else {
                $dataCalendar = [];
                $dataCalendar['pre_order'] = false;

                return $dataCalendar;
            }
        }
        return false;
    }

    protected $product;

    /*
     *  {@inherit}
     */
    public function calDeliveryDateFollowAddressItem(
        \Magento\Customer\Api\Data\AddressInterface $customerAddressInterface,
        \Magento\Quote\Api\Data\CartInterface $cart,
        array $cartItems
    ) {
        //data pre-order
        $checkPreOrder = $this->_isPreOrder($cartItems);

        $calendar = [];
        $groupedCartItems = $this->splitQuoteByDeliveryType($cartItems);
        /** var $destination */
        $destination = array(
            "country_code" => $customerAddressInterface->getCountryId(),
            "region_code" => $customerAddressInterface->getRegion()->getRegionCode(),
            "postcode" => $customerAddressInterface->getPostcode(),
        );

        foreach ($groupedCartItems as $key => $deliveryItem) {
            $deliveryItemInfo = array();

            foreach ($deliveryItem as $deliveryItemId) {
                /** @var $deliveryItemObject \Magento\Quote\Model\Quote\Item */
                $deliveryItemObject = $cart->getItemById($deliveryItemId);
                if(!$deliveryItemObject) {
                    continue;
                }
                //get product for full data
                $product = $this->_initProduct($deliveryItemObject->getProductId());
                $stockMessageArr = $this->stockHelper->getStockStatusMessage($product);
                if (array_key_exists('class', $stockMessageArr)
                    && array_key_exists('message', $stockMessageArr)
                ) {
                    $classMessage = $stockMessageArr['class'];
                    $textMessage = $stockMessageArr['message'];
                } else {
                    $classMessage = '';
                    $textMessage = $this->stockHelper->getOutStockMessageByProduct($product);
                }
                $minSaleQty = $this->getMinSale($product);
                $maxSaleQty = $this->getMaxSale($product);

                $price = $deliveryItemObject->getBasePrice() * $deliveryItemObject->getQtyOrdered();
                $pointEarned = $price * (float)$product->getData('point_currency') / 100;
                //calculator final price and special price (incl tax)
                $priceInfo = $product->getPriceInfo();
                $finalPrice = $priceInfo->getPrice('final_price')->getAmount()->getValue();
                $regularPrice = $priceInfo->getPrice('regular_price')->getAmount()->getValue();

                //calculator tier price incl tax
                $tierPriceList = [];
                if ($product->getTierPrice()) {
                    $tierPrice = $product->getTierPrice();
                    foreach ($tierPrice as $val) {
                        //convert string value to float
                        $val['price_qty'] = $val['price_qty'] * 1;
                        $price = $this->calculator->getAmount($val['price'], $product);
                        $val['price'] = floor($price->getValue());
                        $tierPriceList[] = $val;
                    }

                    $minTierPriceAmount = min(array_column($tierPriceList, 'price'));

                    if(floor($finalPrice) <= $minTierPriceAmount) {
                        $tierPriceList = [];
                    }
                }

                //check free item
                $freeItem = $this->checkFreeItem($deliveryItemObject);

                $deliveryItemInfo[] = array(
                    "name" => $deliveryItemObject->getName(),
                    "sku" => $deliveryItemObject->getSku(),
                    "point" => $pointEarned,
                    "id" => $deliveryItemObject->getId(),
                    "product_id" => $deliveryItemObject->getProductId(),
                    "price_incl_tax" => $deliveryItemObject->getPriceInclTax(),
                    "price_excl_tax" => $deliveryItemObject->getPrice(),
                    "row_subtotal_incl_tax" => $deliveryItemObject->getRowTotalInclTax(),
                    "row_subtotal_excl_tax" => $deliveryItemObject->getRowTotal(),
                    "delivery_type" => $deliveryItemObject->getDeliveryType(),
                    "free_shipping" => $deliveryItemObject->getFreeShipping(),
                    "gift_wrapping" => $product->getGiftWrapping(),
                    "gw_id" => $deliveryItemObject->getGwId(),
                    "qty" => $deliveryItemObject->getQty(),
                    "qty_case" => $deliveryItemObject->getQty() / ((int)($deliveryItemObject->getUnitQty()) ? (int)($deliveryItemObject->getUnitQty()) : 1),
                    "unit_case" => $deliveryItemObject->getUnitCase(),
                    "unit_qty" => ((int)($deliveryItemObject->getUnitQty()) ? ((int)$deliveryItemObject->getUnitQty()) : 1),
                    "unit_case_ea" => __($deliveryItemObject->getUnitCase()),
                    "request_path" => $product->getRequestPath(),
                    "item_id" => $deliveryItemObject->getItemId(),
                    "product_regular_price" => floor($regularPrice),
                    "product_final_price" => floor($finalPrice),
                    "tier_price" => $tierPriceList,
                    "free_item" => $freeItem,
                    "is_riki_machine" => $deliveryItemObject->getIsRikiMachine(),
                    "min_sale_qty" => $minSaleQty,
                    "max_sale_qty" => $maxSaleQty,
                    "product_stock_class"=> $classMessage,
                    "product_stock_message"=> $textMessage,
                    "is_visible_in_cart" => $this->isItemVisibleInCart($deliveryItemObject)
                );
            }

            // Pre-Order
            if (isset($checkPreOrder['pre_order']) && $checkPreOrder['pre_order']) {
                $checkPreOrder["cartItems"] = $deliveryItemInfo;
                $calendar[] = $checkPreOrder;
                continue;
            }

            //get assignation warehouse for some item same delivery type
            $assignationGroupByDeliveryType = $this->modelDeliveryDate->calculateWarehouseGroupByItem($destination, $cart, $deliveryItem);
            $listType = [];
            switch ($key) {
                case Dtype::COLD:
                    $listType[] = Dtype::COLD;

                    $listPlace = explode(",", $assignationGroupByDeliveryType['place_ids']);
                    $listWh = $this->getListWH($listPlace);

                    $dataCalendar = $this->getDeliveryCalendar($listWh, $listType, $destination['region_code']);
                    $dataCalendar['timeslot'] = $this->modelDeliveryDate->getListTimeSlotForCheckout();
                    $dataCalendar['name'] = Delitype::COLD;
                    $dataCalendar['code'] = $key;
                    $dataCalendar["cartItems"] = $deliveryItemInfo;
                    $dataCalendar["assignation"] = $assignationGroupByDeliveryType['items'];
                    $dataCalendar['only_dm'] = 0;
                    $calendar[] = $dataCalendar;
                    break;
                case Dtype::CHILLED:
                    $listType[] = Dtype::CHILLED;
                    $listPlace = explode(",", $assignationGroupByDeliveryType['place_ids']);
                    $listWh = $this->getListWH($listPlace);

                    $dataCalendar = $this->getDeliveryCalendar($listWh, $listType, $destination['region_code']);
                    $dataCalendar['timeslot'] = $this->modelDeliveryDate->getListTimeSlotForCheckout();
                    $dataCalendar['name'] = Dtype::CHILLED;
                    $dataCalendar['code'] = $key;
                    $dataCalendar["cartItems"] = $deliveryItemInfo;
                    $dataCalendar["assignation"] = $assignationGroupByDeliveryType['items'];
                    $dataCalendar['only_dm'] = 0;
                    $calendar[] = $dataCalendar;
                    break;
                case Dtype::COSMETIC:
                    $listType[] = \Riki\DeliveryType\Model\Delitype::COSMETIC;
                    $listPlace = explode(",", $assignationGroupByDeliveryType['place_ids']);
                    $listWh = $this->getListWH($listPlace);

                    $dataCalendar = $this->getDeliveryCalendar($listWh, $listType, $destination['region_code']);
                    $dataCalendar['timeslot'] = $this->modelDeliveryDate->getListTimeSlotForCheckout();
                    $dataCalendar['name'] = Dtype::COSMETIC;
                    $dataCalendar['code'] = $key;
                    $dataCalendar["cartItems"] = $deliveryItemInfo;
                    $dataCalendar["assignation"] = $assignationGroupByDeliveryType['items'];
                    $dataCalendar['only_dm'] = 0;
                    $calendar[] = $dataCalendar;
                    break;
                default:
                    $timeSlot = false;
                    $dataCalendar = [];
                    //get list delivery type
                    $code = '';
                    if ($key == Delitype::COOL
                        || $key == Delitype::NORMAl
                        || $key == Delitype::DM) {
                        $code = Delitype::COOL_NORMAL_DM;
                    }
                    if ($assignationGroupByDeliveryType) {
                        $listPlace = explode(",", $assignationGroupByDeliveryType['place_ids']);
                        $listWh = $this->getListWH($listPlace);

                        $listType = $this->modelDeliveryDate->getDeliveryTypeFromListItem($deliveryItem);

                        $dataCalendar = $this->getDeliveryCalendar($listWh, $listType, $destination['region_code']);

                        if (isset($assignationGroupByDeliveryType['items'])) {
                            $checkOnlyDm = $this->modelDeliveryDate->checkOnlyDirectMailCheckout($listType);
                            if ($checkOnlyDm) {
                                $timeSlot = false;
                                $dataCalendar['only_dm'] = 1;
                            } else {
                                $dataCalendar['only_dm'] = 0;
                                $timeSlot = $this->modelDeliveryDate->getListTimeSlotForCheckout();
                            }
                            //get exactly name of group
                            $dataCalendar['name'] = $this->modelDeliveryDate->getNameGroup($listType);
                            $dataCalendar['code'] = $code;
                        } else {
                            $dataCalendar['name'] = $key;
                            $dataCalendar['code'] = $code;
                        }

                    }
                    $dataCalendar['timeslot'] = $timeSlot;
                    $dataCalendar["cartItems"] = $deliveryItemInfo;
                    $dataCalendar["assignation"] = isset($assignationGroupByDeliveryType['items'])? $assignationGroupByDeliveryType['items'] : [];
                    $calendar[] = $dataCalendar;
                    break;
            }
        }
        return $calendar;
    }

    /**
     * Check item is free
     *
     * @param $deliveryItemObject
     *
     * @return bool
     */
    protected function checkFreeItem($deliveryItemObject)
    {
        if ($this->_promoItemHelper->isPromoItem($deliveryItemObject)
            || $deliveryItemObject->getIsRikiMachine()
            || $deliveryItemObject->getPrizeId()
        ) {
            return true;
        }
        return false;
    }

    protected function getMinSale($product)
    {
        $useConfigMinSale = $product->getExtensionAttributes()->getStockItem()->getData('use_config_min_sale_qty');

        if($useConfigMinSale) {
            $minSaleQty = $this->scopeConfig->getValue(\Magento\CatalogInventory\Model\Configuration::XML_PATH_MIN_SALE_QTY,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        } else {
            $minSaleQty = $product->getExtensionAttributes()->getStockItem()->getData('min_sale_qty');
        }

        return $minSaleQty;
    }

    /**
     * Get Max Sale qty
     *
     * @param $product
     *
     * @return int
     */
    protected function getMaxSale($product)
    {
        $useConfigMaxSale = $product->getExtensionAttributes()->getStockItem()->getData('use_config_max_sale_qty');

        if($useConfigMaxSale) {
            $maxSaleQty = $this->scopeConfig->getValue(\Magento\CatalogInventory\Model\Configuration::XML_PATH_MAX_SALE_QTY,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        } else {
            $maxSaleQty = $product->getExtensionAttributes()->getStockItem()->getData('max_sale_qty');
        }

        if($maxSaleQty == 0 || $maxSaleQty > 99) {
            return 99;
        }
        return $maxSaleQty;
    }

    /**
     * Get list warehouse
     *
     * @param $listPlace
     *
     * @return array
     */
    protected function getListWH($listPlace)
    {
        $listWh = [];
        foreach ($listPlace as $posId) {
            if (!in_array($posId, $listWh)) {
                $pointOfSale = $this->pointOfSaleFactory->create()->load($posId);
                $listWh[] = $pointOfSale->getStoreCode();
            }
        }
        return $listWh;
    }

    /**
     * Item is visible in cart
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool
     */
    protected function isItemVisibleInCart(
        \Magento\Quote\Model\Quote\Item $item
    ) {
        $ruleId = $this->_promoItemHelper->getRuleId($item);

        if ($ruleId) {
            if (!$this->_promoItemHelper->isFreeGiftVisibleInCart($ruleId)) {
                return false;
            }
        }

        return true;
    }
}
