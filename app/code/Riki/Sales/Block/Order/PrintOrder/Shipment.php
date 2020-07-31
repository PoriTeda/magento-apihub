<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Sales\Block\Order\PrintOrder;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\AbstractBlock;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Riki\CvsPayment\Api\ConstantInterface;

/**
 * Sales order details block
 */
class Shipment extends \Magento\Sales\Block\Items\AbstractItems
{
    /**
     * Tracks for Shippings
     *
     * @var array
     */
    protected $tracks = [];

    /**
     * Order shipments collection
     *
     * @var array|\Magento\Sales\Model\ResourceModel\Order\Shipment\Collection
     */
    protected $shipmentsCollection;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * @var \Magento\Sales\Model\Order\Address\Renderer
     */
    protected $addressRenderer;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $_helperPrice;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_product;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;

    /**
     * @var \Magento\Framework\View\Element\Template\Context
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * @var \Magento\SalesRule\Api\RuleRepositoryInterface
     */
    protected $_ruleRepository;

    /**
     * @var \Magento\Framework\Api\Filter
     */
    protected $_filter;

    /**
     * @var \Magento\Framework\Api\Search\FilterGroup
     */
    protected $_filterGroup;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaInterface
     */
    protected $_searchCriteriaInterface;

    /**
     * @var \Magento\SalesRule\Model\Coupon
     */
    protected $_coupon;

    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * @var \Riki\Promo\Helper\Data
     */
    protected $_rikiPromoHelper;

    /**
     * @var array
     */
    public $arrChildProductBundle = [];

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Riki\Sales\Helper\Address
     */
    protected $salesAddressHelper;

    /**
     * @var \Riki\Sales\Helper\Address
     */
    protected $addressHelper;

    /**
     * @var \Riki\DeliveryType\Helper\Admin
     */
    protected $deliveryTypeAdminHelper;

    /**
     * @var \Magento\Sales\Api\OrderAddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var \Riki\Sales\Block\Adminhtml\Order\View\DeliveryInfo
     */
    protected $viewDeliveryInfo;

    protected $orderHelper;

    protected $_currency;

    protected $_config;


    /**
     * @var \Riki\Tax\Helper\Data
     */
    protected $taxHelper;

    /**
     * Shipment constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Framework\Pricing\Helper\Data $helperPrice
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepositoryInterface
     * @param \Magento\Framework\Api\Filter $filter
     * @param \Magento\Framework\Api\Search\FilterGroup $filerGroup
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteriaInterface
     * @param \Magento\SalesRule\Model\Coupon $coupon
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param \Riki\Promo\Helper\Data $rikiPromoHelper
     * @param \Riki\Sales\Helper\Address $salesAddressHelper
     * @param \Riki\Sales\Helper\Address $addressHelper
     * @param \Riki\DeliveryType\Helper\Admin $deliveryTypeAdminHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Framework\Pricing\Helper\Data $helperPrice,
        \Magento\Catalog\Model\Product $product,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepositoryInterface,
        \Magento\Framework\Api\Filter $filter,
        \Magento\Framework\Api\Search\FilterGroup $filerGroup,
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteriaInterface,
        \Magento\SalesRule\Model\Coupon $coupon,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\Promo\Helper\Data $rikiPromoHelper,
        \Riki\Sales\Helper\Address $salesAddressHelper,
        \Riki\Sales\Helper\Address $addressHelper,
        \Riki\DeliveryType\Helper\Admin $deliveryTypeAdminHelper,
        \Magento\Sales\Api\OrderAddressRepositoryInterface $addressRepository,
        \Riki\Sales\Block\Adminhtml\Order\View\DeliveryInfo $viewDeliveryInfo,
        \Riki\Sales\Helper\Order $orderHelper,
        \Magento\Directory\Model\Currency $currency,
        \Riki\Tax\Helper\Data $taxHelper,
        array $data = []
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->coreRegistry = $registry;
        $this->addressRenderer = $addressRenderer;
        $this->_helperPrice = $helperPrice;
        $this->_product = $product;
        $this->_dateTime = $dateTime;
        $this->_timezone = $context->getLocaleDate();
        $this->_scopeConfig = $context;
        $this->_quoteFactory = $quoteFactory;
        $this->_ruleRepository = $ruleRepositoryInterface;
        $this->_filter = $filter;
        $this->_filterGroup = $filerGroup;
        $this->_searchCriteriaInterface = $searchCriteriaInterface;
        $this->_coupon = $coupon;
        $this->profileRepository = $profileRepository;
        $this->courseFactory = $courseFactory;
        $this->_rikiPromoHelper = $rikiPromoHelper;
        $this->storeManager = $context->getStoreManager();
        $this->salesAddressHelper = $salesAddressHelper;
        $this->addressHelper = $addressHelper;
        $this->deliveryTypeAdminHelper = $deliveryTypeAdminHelper;
        $this->addressRepository = $addressRepository;
        $this->viewDeliveryInfo = $viewDeliveryInfo;
        $this->orderHelper = $orderHelper;
        $this->_currency = $currency;
        $this->_config = $context->getScopeConfig();
        $this->taxHelper = $taxHelper;
        parent::__construct($context, $data);
    }

    /**
     * Load all tracks and save it to local cache by shipments
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $tracksCollection = $this->getOrder()->getTracksCollection();
        foreach ($tracksCollection->getItems() as $track) {
            $shipmentId = $track->getParentId();
            $this->tracks[$shipmentId][] = $track;
        }
        $this->shipmentsCollection = $this->getOrder()->getShipmentsCollection();
        return parent::_beforeToHtml();
    }

    /**
     * @return void
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Order Detail ( Order Number: # %1)', $this->getOrder()->getRealOrderId()));
        $infoBlock = $this->paymentHelper->getInfoBlock($this->getOrder()->getPayment(), $this->getLayout());
        $this->setChild('payment_info', $infoBlock);
    }

    /**
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/history');
    }

    /**
     * @return string
     */
    public function getPrintUrl()
    {
        return $this->getUrl('*/*/print');
    }

    /**
     * @return string
     */
    public function getPaymentInfoHtml()
    {
        return $this->getChildHtml('payment_info');
    }

    /**
     * @return array|null
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * @return array|null
     */
    public function getShipment()
    {
        return $this->coreRegistry->registry('current_shipment');
    }

    /**
     * @param AbstractBlock $renderer
     * @return $this
     */
    protected function _prepareItem(AbstractBlock $renderer)
    {
        $renderer->setPrintStatus(true);

        return parent::_prepareItem($renderer);
    }

    /**
     * Retrieve order shipments collection
     *
     * @return array|\Magento\Sales\Model\ResourceModel\Order\Shipment\Collection
     */
    public function getShipmentsCollection()
    {
        return $this->shipmentsCollection;
    }

    /**
     * Getter for order tracking numbers collection per shipment
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     *
     * @return array
     */
    public function getShipmentTracks($shipment)
    {
        $tracks = [];
        if (!empty($this->tracks[$shipment->getId()])) {
            $tracks = $this->tracks[$shipment->getId()];
        }
        return $tracks;
    }

    /**
     * Getter for shipment address by format
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     *
     * @return string
     */
    public function getShipmentAddressFormattedHtml($shipment)
    {
        $shippingAddress = $shipment->getShippingAddress();
        if (!$shippingAddress instanceof \Magento\Sales\Model\Order\Address) {
            return '';
        }
        return $this->addressRenderer->format($shippingAddress, 'html');
    }

    /**
     * Getter for billing address of order by format
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return string
     */
    public function getBillingAddressFormattedHtml($order)
    {
        $billingAddress = $order->getBillingAddress();
        if (!$billingAddress instanceof \Magento\Sales\Model\Order\Address) {
            return '';
        }
        return $this->addressRenderer->format($billingAddress, 'html');
    }

    /**
     * Getter for billing address of order by format
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     *
     * @return array
     */
    public function getShipmentItems($shipment)
    {
        $res = [];
        foreach ($shipment->getItemsCollection() as $item) {
            if (!$item->getOrderItem()->getParentItem()) {
                $res[] = $item;
            }
        }
        return $res;
    }

    /**
     * Format price
     *
     * @param null $price
     *
     * @return float|null|string
     */
    public function getFormatPrice($price = null)
    {
        if ($price != null) {
            return $this->_helperPrice->currency($price, true, false);
        }
        return null;
    }

    /**
     * Get price gift
     *
     * @param $item
     *
     * @return int|null
     */
    public function getGiftWarp($item)
    {
        $total = 0;
        if ($item->getGwBasePrice() != '' && $item->getGwBasePrice() > 0) {
            $total += $item->getGwBasePrice();
        }

        if ($item->getGwTaxAmount() != '' && $item->getGwTaxAmount() > 0) {
            $total += $item->getGwTaxAmount();
        }
        return $total;
    }

    /**
     * Get product by sku
     *
     * @param $shipmentItem
     *
     * @return string
     */
    public function getProductSku($shipmentItem)
    {
        $product = $shipmentItem->getOrderItem()->getProduct();
        if ($product && $product->getId() != null) {
            return $product->getProductUrl();
        }
        return '#';
    }

    /**
     * Get product by sku
     *
     * @param $product
     *
     * @return string
     */
    public function getProductSkuNotShipment($product)
    {
        if ($product && $product->getId() != null) {
            return $product->getProduct()->getProductUrl();
        }
        return '#';
    }

    /**
     * Get list product shipment
     *
     * @param $shipment
     *
     * @return array
     */
    public function getListProductOnShipment($shipment)
    {
        $items = [];
        foreach ($shipment->getItemsCollection() as $item) {
            $items[] = $item;
        }
        return $items;
    }

    /**
     * Get link carrier url
     *
     * @param $carrier
     *
     * @return string
     */
    public function getCarrierUrl($carrier)
    {
        $trackingUrl = $this->_scopeConfig->getValue(
            'carriers/' . $carrier . '/production_webservices_url',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
        return $trackingUrl;
    }

    /**
     * Get message carrier
     *
     * @param $shipment
     *
     * @return mixed
     */
    public function getMessageCarrier($shipment)
    {
        $allTracking = $shipment->getTracks();
        if (is_array($allTracking) && count($allTracking) > 0) {
            foreach ($allTracking as $trackingShipment) {
                $carrier = $trackingShipment->getCarrierCode();
                if ($carrier != null) {
                    return $this->_scopeConfig->getValue('carriers/' . $carrier . '/inquiry_name', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
                }
            }
        }
    }

    /**
     * Get tracking url
     *
     * @param $shipment
     *
     * @return null|string
     */
    public function getTrackingUrl($shipment)
    {
        $allTracking = $shipment->getTracks();
        if (is_array($allTracking) && count($allTracking) > 0) {
            foreach ($allTracking as $trackingShipment) {
                $carrier = $trackingShipment->getCarrierCode();
                if ($carrier != null) {
                    return $this->getCarrierUrl($carrier);
                }
            }
        }
        return null;
    }

    /**
     * Get tracking id
     *
     * @param $shipment
     *
     * @return null|string
     */
    public function getTrackingId($shipment)
    {
        $allTracking = $shipment->getTracks();
        if (is_array($allTracking) && count($allTracking) > 0) {
            foreach ($allTracking as $trackingShipment) {
                $tracking = $trackingShipment->getTrackNumber();
                if ($tracking != null) {
                    return  $tracking;
                }
            }
        }
        return null;
    }

    /**
     * Get date format date
     *
     * @param $data
     *
     * @return string
     */
    public function getFormatDateTIme($data)
    {
        try {
            $originDate = $this->_timezone->formatDateTime($data, \IntlDateFormatter::MEDIUM);
            return $this->_dateTime->gmtDate('Y/m/d', $originDate);
        } catch (\Exception $e) {
            return __('N/A');
        }
    }

    /**
     * Get padservices order
     *
     * @param $listOrder
     * @param $orderItemId
     *
     * @return float|null|string
     */
    public function getPadServices($listOrder, $orderItemId)
    {
        $arrItems = $listOrder->getItems();
        $padServices = 0;
        if (is_array($arrItems) && count($arrItems) > 0) {
            if (isset($arrItems[$orderItemId])) {
                $orderItem = $arrItems[$orderItemId];
                $padServices = $orderItem->getGwBasePrice() + $orderItem->getGwBaseTaxAmount();
            }
        }
        return $this->getFormatPrice($padServices);
    }

    /**
     * Get shipping address item
     *
     * @param $shipment
     * @return $this|bool|string
     */
    public function getShipmentAddressItem($shipment)
    {
        $shipmentItems = $shipment->getItemsCollection();
        $firstOrderItemId =  $shipmentItems[array_keys($shipmentItems)[0]]->getOrderItemId();

        //multiple address
        $shippingAddress  = $this->salesAddressHelper->getOrderAddressByOrderItem($firstOrderItemId);
        if (!$shippingAddress instanceof \Magento\Sales\Model\Order\Address) {
            //single address
            $shippingAddress = $shipment->getShippingAddress();
            if (!$shippingAddress instanceof \Magento\Sales\Model\Order\Address) {
                return '';
            }
        }
        return $shippingAddress;
    }

    /**
     * Get street
     *
     * @param $shippingAddress
     *
     * @return string
     */
    public function getStreetName($shippingAddress)
    {
        if (!$shippingAddress instanceof \Magento\Sales\Model\Order\Address) {
            return '';
        }

        $street    = $shippingAddress->getStreet() ? current($shippingAddress->getStreet()) : '';
        $postCode  = $shippingAddress->getPostcode();
        $region    = $shippingAddress->getRegion();
        $apartment = ($shippingAddress->getApartment() != '' ? ' '.$shippingAddress->getApartment() : '');
        return  __('〒') . $postCode . ' ' . $region . ' ' . $street . $apartment;
    }

    /**
     * Get subtotal
     *
     * @param $shipmentItems
     *
     * @return mixed
     */
    public function getSubTotal($shipmentItems)
    {
        $price = $this->getPriceFromShipmentItem($shipmentItems);
        $qty   = $this->getQtyShipmentItem($shipmentItems);
        $subTotal = $qty * $price;
        return $subTotal;
    }

    /**
     * Get quote by quote id
     *
     * @param $order
     *
     * @return null
     */
    public function getShippingFeeAddress($order)
    {
        $shippingFeeAddress = null;
        if ($order->getShippingFeeByAddress() != null) {
            $shippingFeeAddress = $order->getShippingFeeByAddress();
        }
        return $shippingFeeAddress;
    }

    /**
     * Get shipment name
     *
     * @param array $data data
     *
     * @return null|string
     */
    public function getShipmentName($data)
    {
        $dataType = [];
        $result = null;

        if (is_array($data) && count($data) > 0) {
            if (isset($data['CoolNormalDm']) && $data['CoolNormalDm'] != 'N/A') {
                $dataType['CoolNormalDm'] = __('Cool');
            }

            if (isset($data['cold']) && $data['cold'] != 'N/A') {
                $dataType['cold'] = __('Cold');
            }

            if (isset($data['chilled']) && $data['chilled'] != 'N/A') {
                $dataType['chilled'] = __('Chilled');
            }

            if (isset($data['cosmetic']) && $data['cosmetic'] != 'N/A') {
                $dataType['cosmetic'] = __('Cosmetic');
            }
        }

        if (is_array($dataType) && count($dataType) > 0) {
            $result = '( ' . implode(' / ', $dataType) . ' )';
        }

        return $result;
    }

    /**
     * Get list address for order
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getAddressIdsForOrderDetail(\Magento\Sales\Model\Order $order)
    {
        $itemIds = [];
        foreach ($order->getAllItems() as $item) {
            $itemIds[] = $item->getId();
        }
        $orderItemIdToAddressId = $this->addressHelper->getAddressIdsByOrderItemIdsForEdit($itemIds);
        return $orderItemIdToAddressId;
    }

    /**
     * Get delivery type of order detail
     *
     * @param \Magento\Sales\Model\Order $order
     * @return null
     */
    public function getDeliveryTypeByShipment(\Magento\Sales\Model\Order $order)
    {
        $addressGroups = [];
        $itemIdsToAddressIds = $this->getAddressIdsForOrderDetail($order);
        foreach ($order->getAllItems() as $_item) {
            $deliveryType = $this->deliveryTypeAdminHelper->prepareDeliveryType($_item->getDeliveryType());
            $addressId    = isset($itemIdsToAddressIds[$_item->getId()]) ? $itemIdsToAddressIds[$_item->getId()] : 0;
            $addressGroups[$addressId] = $deliveryType;
        }
        return $addressGroups;
    }

    /**
     * Get nam of group item cool_normal_directmail
     *
     * @param $listType
     * @return \Magento\Framework\Phrase|string
     */
    public function getNameGroup($listType)
    {
        $cool = $normal = $directMail = false;
        foreach ($listType as $type) {
            if ($type == \Riki\DeliveryType\Model\Delitype::COLD) {
                return __('Cold');
            } elseif ($type == \Riki\DeliveryType\Model\Delitype::CHILLED) {
                return __('Chilled');
            } elseif ($type == \Riki\DeliveryType\Model\Delitype::COSMETIC) {
                return __('Cosmetic');
            } else {
                if ($type == \Riki\DeliveryType\Model\Delitype::COOL) {
                    $cool = true;
                } elseif ($type == \Riki\DeliveryType\Model\Delitype::NORMAl) {
                    $normal = true;
                } else {
                    $directMail = true;
                }
            }
        }

        //DM+Normal+Cool ->Cool
        if ($directMail && $normal && $cool) {
            $cool = true;
        }

        //DM+Cool ->Cool
        if ($directMail && $cool) {
            $cool = true;
        }

        //Normal+Cool ->Cool
        if ($normal && $cool) {
            $cool = true;
        }

        //DM+Normal ->Normal
        if ($normal && $directMail) {
            $normal = true;
        }

        if ($cool) {
            return __('Cool');
        } elseif ($normal) {
            return __('Normal');
        } elseif ($directMail) {
            return __('DM');
        }
        return '';
    }

    /**
     * Get group name for CoolNormalDm
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getDeliveryTypeForCoolNormalDm($shipment)
    {
        $shipmentItems = $shipment->getItems();
        $listType = [];
        foreach ($shipmentItems as $_item) {
            $orderItem = $_item->getOrderItem();
            $listType [$orderItem->getDeliveryType()] = $orderItem->getDeliveryType();
        }

        return $listType;
    }

    /**
     * Get type shipment
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param $shippingAddressItem
     * @param array $arrDeliveryType
     *
     * @return string
     */
    public function getTypeShipment($shipment, $shippingAddressItem, $arrDeliveryType = [])
    {
        $dataType      = null;
        $addressId     = $shippingAddressItem->getId();
        if (isset($arrDeliveryType[$addressId])) {
            $addressType = $arrDeliveryType[$addressId];

            if ($addressType == \Riki\DeliveryType\Model\Delitype::COLD) {
                $dataType = __('Cold');
            } elseif ($addressType == \Riki\DeliveryType\Model\Delitype::CHILLED) {
                $dataType = __('Chilled');
            } elseif ($addressType == \Riki\DeliveryType\Model\Delitype::COSMETIC) {
                $dataType = __('Cosmetic');
            } else {
                //group CoolNormalDm
                $listType = $this->getDeliveryTypeForCoolNormalDm($shipment);
                $dataType = $this->getNameGroup($listType);
            }
        } else {
            if (is_array($arrDeliveryType) && count($arrDeliveryType) > 0) {
                foreach ($arrDeliveryType as $key => $addressType) {
                    if ($addressType == \Riki\DeliveryType\Model\Delitype::COLD) {
                        $dataType = __('Cold');
                    } elseif ($addressType == \Riki\DeliveryType\Model\Delitype::CHILLED) {
                        $dataType = __('Chilled');
                    } elseif ($addressType == \Riki\DeliveryType\Model\Delitype::COSMETIC) {
                        $dataType = __('Cosmetic');
                    } else {
                        //group CoolNormalDm
                        $listType = $this->getDeliveryTypeForCoolNormalDm($shipment);
                        $dataType = $this->getNameGroup($listType);
                    }
                }
            }
        }

        return  $result = '( ' . $dataType . ' )';
    }

    /**
     * Get applied promotion
     *
     * @param $order
     *
     * @return mixed
     */
    public function hasAppliedPromotionAndCoupon($order)
    {
        $data = null;
        $ruleIds = [];

        //rule id coupon code
        $couponRuleId = $this->getRuleIdByCouponCode($order);
        $arrCouponId = [];
        if ($couponRuleId != null) {
            $arrCouponId[] = $couponRuleId;
        }

        $ruleIds = $order->getData('applied_rule_ids');
        $arrPromotionId = [];
        if ($ruleIds) {
            $arrPromotionId = array_map('trim', explode(',', $ruleIds));
        }

        if (count($arrCouponId) > 0 || count($arrPromotionId) > 0) {
            $ruleIds = array_merge($arrCouponId, $arrPromotionId);
        }

        if (is_array($ruleIds) && count($ruleIds) > 0) {
            $ruleIds = $this->_rikiPromoHelper->filterVisibleInUserAccountRuleByIds($ruleIds);

            if (count($ruleIds)) {
                $filters[] = $this->_filter
                    ->setField('rule_id')
                    ->setConditionType('in')
                    ->setValue($ruleIds);

                $filterGroup[] = $this->_filterGroup->setFilters($filters);
                $searchCriteria = $this->_searchCriteriaInterface->setFilterGroups($filterGroup);
                $searchResults = $this->_ruleRepository->getList($searchCriteria);
                if ($searchResults->getTotalCount()) {
                    $data = $searchResults->getItems();
                }
            }
        }


        return $data;
    }

    /**
     *
     * Get name coupon code
     *
     * @param $order
     *
     * @return null|string
     */
    public function getRuleIdByCouponCode($order)
    {
        $data = null;
        $code = $order->getCouponCode();
        if ($code != null) {
            $couponCode = $this->_coupon->loadByCode($code);
            if ($couponCode) {
                $data = $couponCode->getRuleId();
            }
        }
        return $data;
    }

    /**
     * Get delivery date for shipment item
     *
     * @param $shipment
     *
     * @return null|string
     */
    public function getShipmentDeliveryDate($shipment)
    {
        $result = null;
        $deliveryDate = $shipment->getDeliveryDate();
        if ($deliveryDate != null && $deliveryDate != '0000-00-00' && $deliveryDate != '0000-00-00 00:00:00') {
            $result = $this->getFormatDateTIme($deliveryDate);
        }
        return $result;
    }

    /**
     * Get subscription course name
     *
     * @param $order
     *
     * @return string
     */
    public function getSubscriptionCourseName($order)
    {
        $iProfileId = $order->getSubscriptionProfileId();

        try {
            $oProfile = $this->profileRepository->get($iProfileId);
            $course = $this->courseFactory->create()->load($oProfile->getCourseId());
            $courseName = $course->getCourseName();
        } catch (NoSuchEntityException $e) {
            $courseName = '';
        }

        return $courseName;
    }

    /**
     * @param $product
     * @return array
     */

    public function setChildItemOfBundledProduct($product)
    {
        if ($product) {
            $typeId = $product->getTypeId();
            if ($typeId == 'bundle') {
                $typeInstance = $product->getTypeInstance();
                $arrChildItem = $typeInstance->getChildrenIds($product->getId(), false);
                if (is_array($arrChildItem) && count($arrChildItem) > 0) {
                    foreach ($arrChildItem as $child) {
                        foreach ($child as $childProductId) {
                            $this->arrChildProductBundle[$childProductId] = $childProductId;
                        }
                    }
                }
            }
        }
        return $this->arrChildProductBundle;
    }

    /**
     * Not show child item of product bundle on list
     *
     * @param $product
     *
     * @return bool
     */
    public function checkProductBundledNotShowOnList($product)
    {
        $this->setChildItemOfBundledProduct($product);

        $arrChildProductBundle = $this->arrChildProductBundle;
        if ($product) {
            $productId = $product->getId();
            if (is_array($arrChildProductBundle) && count($arrChildProductBundle) > 0) {
                if (isset($arrChildProductBundle[$productId])) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Get price from shipment item
     *
     * @param $shipmentItem
     * @return int
     */
    public function getPriceFromShipmentItem($shipmentItem)
    {
        $orderItem = $shipmentItem->getOrderItem();
        $price = 0;
        if ($orderItem) {
            $price = $orderItem->getPriceInclTax() * $this->getItemUnitQty($shipmentItem);
        }
        return $price;
    }

    /**
     * Get unit display
     *
     * @param $unitCase
     * @return \Magento\Framework\Phrase
     */
    public function getDisplayCase($unitCase)
    {
        $label = __('EA');
        if ($unitCase == 'CS') {
            $label = __('CS');
        }
        return $label;
    }

    /**
     * Get unit qty
     *
     * @param $item
     * @return int|null
     */
    public function getItemUnitQty($item)
    {
        $unitQty = 1;
        if ($item->getUnitCase() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
            $unitQty = (null !== $item->getUnitQty()) ? $item->getUnitQty() : 1;
        }
        return $unitQty;
    }

    /**
     * Get qty from shipment item
     *
     * @param $item
     * @return int|null
     */
    public function getQtyShipmentItem($item)
    {
        $qty = (int)$item->getQty();

        if ($item->getUnitCase() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
            $unitQty = (null !== $item->getUnitQty()) ? $item->getUnitQty() : 1;
            $qty = $qty / $unitQty;
        }

        return $qty;
    }

    /**
     * Get qty from order item
     *
     * @param $item
     * @return int|null
     */
    public function getQtyOrderItem($item)
    {
        $qty = (int)$item->getQtyOrdered();

        if ($item->getUnitCase() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
            $unitQty = (null !== $item->getUnitQty()) ? $item->getUnitQty() : 1;
            $qty = $qty / $unitQty;
        }

        return $qty;
    }

    /**
     * Get label of rule
     *
     * @param  \Magento\SalesRule\Model\Data\Rule $rule
     *
     * @return mixed|null
     */
    public function getLabelCartPriceRules($rule)
    {
        $storeCurrentId = $this->storeManager->getStore()->getId();
        $label = null;
        if ($rule->getStoreLabels()) {
            $storeLabelDefault = null;
            foreach ($rule->getStoreLabels() as $ruleLabel) {
                if ($ruleLabel->getStoreId() == 0) {
                    $storeLabelDefault = $ruleLabel->getStoreLabel();
                }

                if ($ruleLabel->getStoreId() == $storeCurrentId) {
                    $label = $ruleLabel->getStoreLabel();
                }
            }

            if ($label == null) {
                $label = $storeLabelDefault;
            }
        } else {
            $label = $rule->getName();
        }
        return $label;
    }

    /**
     * Check order is subscription order or normal order
     *
     * @param $order
     * @return bool
     */
    public function isSubscriptionOrder($order)
    {
        if ($order->getData('riki_type') == \Riki\Sales\Helper\Order::RIKI_TYPE_SUBSCRIPTION ||
            $order->getData('riki_type') == \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT ||
            $order->getData('riki_type') == \Riki\Sales\Helper\Order::RIKI_TYPE_HANPUKAI
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check order is subscription order which type is hanpukai
     *
     * @param $order
     * @param bool $isSubscriptionOrder
     * @return bool
     */
    public function isHanpukaiOrder($order, $isSubscriptionOrder = true)
    {
        if (!$isSubscriptionOrder) {
            $isSubscriptionOrder = $this->isSubscriptionOrder($order);
        }

        if ($isSubscriptionOrder) {
            if (strtolower($order->getData('riki_type')) == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get address info by id
     *
     * @param $addressId
     * @return \Magento\Sales\Api\Data\OrderAddressInterface
     */
    public function getShippingAddressById($addressId)
    {
        return $this->addressRepository->get($addressId);
    }

    /**
     * Get split address when order not shipment
     *
     * @return array
     */
    public function getAddressGroup()
    {
        return $this->viewDeliveryInfo->getAddressGroups();
    }

    /**
     * Check data of date
     *
     * @param $data
     * @param string $formatDate
     * @return null|string
     */
    public function checkDataDate($data, $formatDate = 'Y/m/d')
    {
        $result = null;
        if ($data != null && $data != '0000-00-00' && $data != '0000-00-00 00:00:00') {
            $result = $this->_dateTime->gmtDate($formatDate, $data);
        }
        return $result;
    }

    /**
     * Get shipment status
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param $status string
     * @return null
     */
    public function getShipmentStatus($shipment, $status)
    {
        $result = null;
        if ($shipment instanceof \Magento\Sales\Model\Order\Shipment) {
            switch ($status) {
                case ShipmentStatus::SHIPMENT_STATUS_EXPORTED:
                    $result = $this->checkDataDate($shipment->getExportDate());
                    break;
                case ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT:
                    $result = $this->checkDataDate($shipment->getShippedOutDate());
                    break;
                case ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED:
                    $result = $this->checkDataDate($shipment->getDeliveryCompleteDate());
                    break;
            }
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getPaymentTotalOrder()
    {
        $order = $this->getOrder();
        return $this->orderHelper->getOrderTotals($order);
    }

    /**
     * @param $total
     * @return mixed
     */
    public function formatValue($total)
    {
        if (!$total->getIsFormated()) {
            return $this->getOrder()->formatPrice($total->getValue());
        }
        return $total->getValue();
    }

    public function formatCurrencyInvoice($value)
    {
        $symbol = $this->_currency->getCurrencySymbol();
        if (is_object($value)) {
            $formatNumber = $value->getValue();
        } else {
            $formatNumber = $value;
        }
        return $symbol.number_format(intval($formatNumber), 0, '', ',');
    }

    /**
     * @return mixed
     */
    public function getPaymentTitle()
    {
        $paymentMethod = $this->getOrder()->getPayment()->getMethod();
        $configPath = 'payment/'.$paymentMethod.'/title';
        return $this->_config->getValue($configPath, \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    /**
     * Get delivery information of stock point order
     *
     * @param $orderNumber
     * @return array
     */
    public function getStockPointDeliveryOrderInfo($orderNumber)
    {
        return $this->orderHelper->getStockPointDeliveryOrderInfo($orderNumber);
    }

    /**
     * Get stock point delivery status url api
     *
     * @return mixed
     */
    public function getStockPointDeliveryStatusApiUrl()
    {
        return $this->orderHelper->getStockPointDeliveryStatusApiUrl();
    }

    /**
     * Get request value for Delivery Status API
     *
     * @param $orderNumber
     * @return string
     */
    public function getRequestDataValueForDeliveryStatus($orderNumber)
    {
        return $this->orderHelper->getRequestDataValueForDeliveryStatus($orderNumber);
    }

    /**
     * @param int $orderId
     * @return bool
     */
    public function canApplyTaxChangeFromDate($orderId)
    {
        return $this->taxHelper->canApplyTaxChangeFromDate($orderId);
    }

    /**
     * Get config tax percent to compare to current product tax
     * @return int
     */
    public function getCompareTaxPercent()
    {
        return $this->taxHelper->getCompareTaxPercent();
    }

    /**
     * @param \Riki\Sales\Model\Order $order
     * @return bool
     */
    public function isOrderCvsCreateByCommand($order)
    {
        if ($order->getPayment()->getMethod() == \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE) {
            $valueConfig = $this->_config->getValue(
                ConstantInterface::CONFIG_PATH_COMMAND_CREATE_ORDER_CVS_PAYMENT_SKU
            );
            $productSkus = ($valueConfig) ? array_map('trim', explode(';', strtolower($valueConfig))) : null;
            if (!empty($productSkus)) {
                foreach ($order->getItems() as $item) {
                    if (in_array(strtolower($item->getSKu()), $productSkus)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
