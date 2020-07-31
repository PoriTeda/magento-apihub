<?php

namespace Riki\Sales\Helper;

use Magento\Quote\Model\Quote\Item;
use Magento\Backend\App\Area\FrontNameResolver;
use Riki\Sales\Model\Config\DeliveryOrderType;
use Riki\Loyalty\Model\Reward as LoyaltyReward;
use Riki\Sales\Model\Config\Source\OrderType as OrderChargeType;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;
use Magento\Catalog\Model\Product\Type\AbstractType;

class Admin extends \Magento\Framework\App\Helper\AbstractHelper
{
    const DELIVERY_ORDER_TYPE_SESSION_NAME = 'delivery_order_type';

    protected $_layoutFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Item\Updater
     */
    protected $_quoteItemUpdater;

    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    protected $_quote;

    protected $_quoteFactory;

    protected $_addressHelper;

    protected $_orderItemResource;

    protected $_timeSlotHelper;

    protected $_deliveryTypeGroupFreeShipping;

    /**
     * State
     *
     * @var State
     */
    protected $_appState;

    protected $_authorization;

    protected $_orderCreate;

    protected $_connection;

    protected $_backendHelper;

    protected $_orderItemsShippedQty = [];

    protected $_deliveryTypeAdminHelper;

    protected $_cutOffDateModel;

    protected $_catalogProductHelper;

    protected $_adminQuoteSession;

    protected $_productRepository;

    /**
     * @var \Riki\Loyalty\Helper\Data
     */
    protected $_loyaltyHelper;

    protected $_orderItemIdToEarnedPoints = [];

    protected $_customerRepository;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json|null
     */
    private $serializer;

    /**
     * @var Data
     */
    protected $helperSale;

    public function __construct(
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Riki\Sales\Helper\Address $addressHelper,
        \Magento\Sales\Model\ResourceModel\Order\Item $orderItemResource,
        \Riki\TimeSlots\Helper\Data $timeSlotHelper,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\AuthorizationInterface $authorization,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Backend\Helper\Data $backendHelper,
        \Riki\DeliveryType\Helper\Admin $deliveryTypeAdminHelper,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Backend\Model\Session\Quote $adminQuoteSession,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface,
        \Riki\Loyalty\Helper\Data $loyaltyHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Quote\Model\Quote\Item\Updater $quoteItemUpdater,
        \Riki\Sales\Helper\Data $helperSale,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        $this->_productRepository = $productRepositoryInterface;
        parent::__construct($context);
        $this->_layoutFactory = $layoutFactory;
        $this->_quoteFactory = $quoteFactory;
        $this->_addressHelper = $addressHelper;
        $this->_orderItemResource = $orderItemResource;
        $this->_timeSlotHelper = $timeSlotHelper;
        $this->_appState = $appState;
        $this->_authorization = $authorization;
        $this->_orderCreate = $orderCreate;
        $this->_urlBuilder = $context->getUrlBuilder();
        $this->_connection = $resourceConnection->getConnection();
        $this->_backendHelper = $backendHelper;
        $this->_deliveryTypeAdminHelper = $deliveryTypeAdminHelper;
        $this->_cutOffDateModel = $addressHelper->getCutOffDateModel();
        $this->_catalogProductHelper = $productHelper;
        $this->_adminQuoteSession = $adminQuoteSession;
        $this->_customerRepository = $customerRepository;
        $this->_loyaltyHelper = $loyaltyHelper;
        $this->_quoteItemUpdater = $quoteItemUpdater;
        $this->helperSale = $helperSale;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getConnection()
    {
        return $this->_connection;
    }

    /**
     * @return Address
     */
    public function getAddressHelper()
    {
        return $this->_addressHelper;
    }

    /**
     * @return \Riki\DeliveryType\Helper\Admin
     */
    public function getDeliveryTypeHelper()
    {
        return $this->_deliveryTypeAdminHelper;
    }

    /**
     * Retrieve session object
     *
     * @return \Magento\Backend\Model\Session\Quote
     */
    protected function _getSession()
    {
        return $this->_adminQuoteSession;
    }

    /**
     * @return \Magento\Backend\Model\Session\Quote
     */
    public function getSession()
    {
        return $this->_adminQuoteSession;
    }

    /**
     * Retrieve quote object model
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        if (!$this->_quote) {
            $this->_quote = $this->_getSession()->getQuote();
        }

        return $this->_quote;
    }

    /**
     * get list of address by quote item
     *
     * @param Item $item
     * @return mixed
     */
    public function getAddressListHtmlByCartItem(Item $item)
    {
        return $this->_layoutFactory->create()
            ->createBlock('Riki\Sales\Block\Adminhtml\Order\Create\Items\Renderer\Addresses')
            ->setItem($item)
            ->toHtml();
    }

    /**
     * @param $customerId
     * @return \Magento\Customer\Api\Data\AddressInterface[]
     */
    public function getAddressListByCustomerId($customerId)
    {
        return $this->_addressHelper->getAddressListByCustomerId($customerId);
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return mixed
     */
    public function getAddressListByOrderItem(\Magento\Sales\Model\Order\Item $item)
    {
        return $this->_layoutFactory->create()
            ->createBlock('Riki\Sales\Block\Adminhtml\Order\View\Items\Renderer\Addresses')
            ->setItem($item)
            ->toHtml();
    }

    /**
     *
     *
     * @param $order
     * @return bool
     */
    public function isSubscriptionCourseOrder($order)
    {
        if ($order->getSubscriptionProfileId()) {
            return true;
        }

        $quote = $this->_quoteFactory->create()->load($order->getQuoteId());

        if ($quote instanceof \Magento\Quote\Model\Quote && $quote->getRikiCourseId()) {
            return true;
        }
        return false;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param array $data
     * @return $this
     * @throws \Exception
     */
    public function updateOrderItemDeliveryInfo($order, $data)
    {

        if (!$this->isAllowToChangeDeliveryInfo($order)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('This order is not allowed to change delivery information.'));
        }

        $this->_orderItemResource->getConnection()->beginTransaction();

        $itemIds = [];
        foreach ($order->getAllItems() as $item) {
            $itemIds[] = $item->getId();
        }

        $itemIdsToAddressIds = $this->_addressHelper->getAddressIdsByOrderItemIdsForEdit($itemIds);

        try {
            foreach ($order->getAllItems() as $item) {
                $itemId = $item->getId();
                $deliveryType = $item->getDeliveryType();
                $updateData = [];
                if (isset($data['delivery_date'][$itemIdsToAddressIds[$itemId]][$deliveryType])) {
                    $updateData = [
                        'delivery_date' => $data['delivery_date'][$itemIdsToAddressIds[$itemId]][$deliveryType],
                    ];
                }
                if (isset($data['delivery_timeslot'][$itemIdsToAddressIds[$itemId]][$deliveryType])) {
                    $timeSlotModel = $this->_timeSlotHelper->_getTimeSlotFromCollectionById($data['delivery_timeslot'][$itemIdsToAddressIds[$itemId]][$deliveryType]);
                    if (!is_null($timeSlotModel)) {
                        $updateData['delivery_time'] = $timeSlotModel->getSlotName();
                        $updateData['delivery_timeslot_id'] = $timeSlotModel->getId();
                        $updateData['delivery_timeslot_from'] = $timeSlotModel->getFrom();
                        $updateData['delivery_timeslot_to'] = $timeSlotModel->getTo();
                    }
                }
                if (isset($data['next_delivery_date'][$itemIdsToAddressIds[$itemId]][$deliveryType])) {
                    $updateData['next_delivery_date'] = $data['next_delivery_date'][$itemIdsToAddressIds[$itemId]][$deliveryType];
                }
                if (sizeof($updateData)) {
                    $item->addData($updateData)->save();
                }
            }

            $this->_cutOffDateModel->calculateCutoffDate($order);
        } catch (\Exception $e) {
            $this->_orderItemResource->getConnection()->rollback();
            throw $e;
        }
        $this->_orderItemResource->getConnection()->commit();
        return $this;
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
                $deliveryTypeGroupFreeShipping = $this->_getSession()->getDeliverytypeGroupFreeShipping();

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

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function isAllowedToEditOrderAddresses($order)
    {
        if ($order->hasShipments()) {
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function isAllowedToCustomCreateOrderItemPrice()
    {
        return $this->_authorization->isAllowed('Riki_Sales::create_custom_item_price');
    }

    /**
     * @return bool
     */
    public function isFreeOfChargeOrder()
    {
        return $this->helperSale->isFreeOfChargeType($this->_getSession()->getChargeType());
    }

    /**
     * @return bool
     */
    public function isMultipleShippingAddressCart()
    {
        return $this->_getSession()->getData(self::DELIVERY_ORDER_TYPE_SESSION_NAME) == \Riki\Sales\Model\Config\DeliveryOrderType::MULTIPLE_ADDRESS;
    }

    /**
     * @return $this
     */
    public function resetQuoteAdditionalData(){

        $quote = $this->_orderCreate->getQuote();

        $quoteAttributes = [
            'original_order_id',
            'replacement_reason',
            'siebel_enquiry_id',
            'replacement_reason_code',
            'substitution',
            'free_of_charge'
        ];

        $itemQuoteAttributes = [
            'booking_wbs',
            'booking_account',
            'booking_center',
            'free_of_charge'
        ];

        foreach ($quoteAttributes as $attribute) {
            $quote->unsetData($attribute);
        }

        foreach ($quote->getAllItems() as $item) {
            foreach ($itemQuoteAttributes as $attribute) {
                $item->unsetData($attribute);
            }
        }

        return $this;
    }

    /**
     * @param $chargeType
     * @return $this
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processOrderWithChargeType($chargeType)
    {
        $this->resetQuoteAdditionalData();

        switch ($chargeType) {
            case OrderChargeType::ORDER_TYPE_NORMAL:
                $this->_getSession()->setFreeShippingFlag(0);
                $this->_getSession()->setFreeSurcharge(0);
                break;
            case OrderChargeType::ORDER_TYPE_REPLACEMENT:
                $this->_getSession()->setFreeShippingFlag(1);
                $this->_getSession()->setFreeSurcharge(1);

                break;
            case OrderChargeType::ORDER_TYPE_FREE_SAMPLE:
                $this->_getSession()->setFreeShippingFlag(1);
                $this->_getSession()->setFreeSurcharge(1);
                break;
            default:
                throw new \Magento\Framework\Exception\LocalizedException(__('Order type is invalid'));
        }

        $this->_orderCreate->getQuote()->setChargeType($chargeType);

        $this->_orderCreate->resetShippingMethod(true);

        $this->_getSession()->setChargeType($chargeType);
        $this->_orderCreate->getQuote()->setChargeType($chargeType);

        /**
         * Update quote items
         */

        $preparedItems = [];
        $items = $this->getQuote()->getAllItems();

        foreach ($items as $item) {
            $preparedItems[$item->getId()] = [
                'qty' => $item->getQty(),
                'use_discount' => 1,
                'address_id' => $item->getAddressId()
            ];
        }

        $items = $this->_processFiles($preparedItems);
        $this->_orderCreate->updateQuoteItems($items);

        return $this;
    }

    /**
     * Process buyRequest file options of items
     *
     * @param array $items
     * @return array
     */
    protected function _processFiles($items)
    {
        foreach ($items as $id => $item) {
            if (!$id) {
                return false;
            }

            $buyRequest = new \Magento\Framework\DataObject($item);
            $params = ['files_prefix' => 'item_' . $id . '_'];
            $buyRequest = $this->_catalogProductHelper->addParamsToBuyRequest($buyRequest, $params);
            if ($buyRequest->hasData()) {
                $items[$id] = $buyRequest->toArray();
            }
        }
        return $items;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return mixed
     */
    public function getAbleChangeAddressOrderItemQty(\Magento\Sales\Model\Order\Item $item)
    {

        if (!isset($this->_orderItemsShippedQty[$item->getId()])) {
            $select = $this->_connection->select()->from(
                'sales_shipment_item',
                'qty'
            )->join(
                'sales_shipment',
                'sales_shipment_item.parent_id=sales_shipment.entity_id'
            )->where(
                'sales_shipment_item.order_item_id = ?',
                $item->getId()
            )->where(
                'sales_shipment.is_exported = 1'
            );

            $shippedQty = (int)$this->_connection->fetchOne($select);
            $this->_orderItemsShippedQty[$item->getId()] = max($item->getQtyOrdered() - $shippedQty, 0);
        }

        return $this->_orderItemsShippedQty[$item->getId()];
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return bool
     */
    public function allowToChangeShippingAddressOfOrderItem(\Magento\Sales\Model\Order\Item $item)
    {
        return $this->allowToChangeShippingAddressOfOrder($item->getOrder());
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function allowToChangeShippingAddressOfOrder(\Magento\Sales\Model\Order $order)
    {

        if (!$order->canShip()) {
            return false;
        }
        if (!$order->getIsMultipleShipping()) {
            return false;
        }
        return true;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function isAllowToChangeDeliveryInfo(\Magento\Sales\Model\Order $order)
    {
        if (!$order->canShip() || $order->getShipmentsCollection()->getSize()) {
            return false;
        }

        return true;
    }

    /**
     * @param $orderId
     * @return string
     */
    public function getUpdateShippingAddressUrl($orderId)
    {
        return $this->_backendHelper->getUrl('riki_sales/order_edit/updateShippingAddress', ['order_id' => $orderId]);
    }

    /**
     * @param $type
     * @param \Magento\Sales\Model\AdminOrder\Create $order
     * @return $this
     */
    public function changeShippingAddressType($type, $order)
    {

        $session = $this->_getSession();

        if ($type == $session->getData(self::DELIVERY_ORDER_TYPE_SESSION_NAME)) {
            return $this;
        }

        $session->setData(self::DELIVERY_ORDER_TYPE_SESSION_NAME, $type);

        if ($type == DeliveryOrderType::MULTIPLE_ADDRESS) {
            $this->convertQuoteItemToMultipleCase($order);
        } else {
            $this->convertQuoteItemToSingleCase($order);
        }

        $order->saveQuote();

        return $this;
    }

    /**
     * @param \Magento\Sales\Model\AdminOrder\Create $order
     * @return $this
     */
    public function convertQuoteItemToMultipleCase($order)
    {
        $quote = $this->_getSession()->getQuote();

        $quote->setData('is_multiple_shipping', 1);

        foreach ($quote->getAllItems() as $item) {
            if ($this->canShipQuoteItemToMultipleAddress($item)) {
                /**
                 * @var \Magento\Quote\Model\Quote\Item $item
                 */
                $qty = $item->getQty();

                $unitQty = 1;
                if ('CS' == $item->getUnitCase()) {
                    $unitQty = ($item->getUnitQty() != null) ? $item->getUnitQty() : 1;
                }

                if ($qty / $unitQty > 1 && !$item->getParentItemId()) {
                    for ($_i = 0; $_i < ($qty / $unitQty - 1); $_i++) {
                        $newItem = clone $item;
                        $newItem->setQty($unitQty);
                        $quote->addItem($newItem);

                        if ($item->getHasChildren() && (!$newItem->getHasChildren() || count($newItem->getChildren()) == 0)) {
                            foreach ($item->getChildren() as $child) {
                                $newChild = clone $child;
                                $newChild->setParentItem($newItem);
                                $quote->addItem($newChild);
                            }
                        }
                    }

                    $item->setQty($unitQty);
                    $order->setRecollect(true);
                }
            }
        }


        return $this;
    }

    /**
     * Combine item by address
     *
     * @return bool
     */
    public function combineItemsAddress()
    {
        $quote = $this->_getSession()->getQuote();
        return $this->helperSale->combineItems($quote);
    }


    /**
     * @param Item $item
     * @return bool
     */
    public function canShipQuoteItemToMultipleAddress(\Magento\Quote\Model\Quote\Item $item)
    {
        return true;
    }

    /**
     * @param \Magento\Sales\Model\AdminOrder\Create $order
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function convertQuoteItemToSingleCase($order)
    {
        $quote = $this->_getSession()->getQuote();

        $quote->setData('is_multiple_shipping', 0);

        $itemCollection = $quote->getItemsCollection();

        $quote->removeAllItems();

        /** @var \Magento\Quote\Model\Quote\Item $oldItem */
        foreach ($itemCollection as $oldItem) {
            if ($this->canConvertQuoteItemToSingleAddress($oldItem)) {
                try {
                    /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
                    $product = $this->_productRepository->getById(
                        $oldItem->getProductId(),
                        false,
                        $this->_getSession()->getStoreId()
                    );

                    if ($product->getId()) {
                        $product->setSkipCheckRequiredOption(true);
                        $buyRequest = $oldItem->getBuyRequest();

                        $item = $this->getQuote()->addProduct($product, $buyRequest);
                        if (is_string($item)) {
                            break;
                        }

                        if ($additionalOptions = $oldItem->getOptionByCode('additional_options')) {
                            $item->addOption(
                                new \Magento\Framework\DataObject(
                                    [
                                        'product' => $item->getProduct(),
                                        'code' => 'additional_options',
                                        'value' => $this->serializer->serialize($additionalOptions)
                                    ]
                                )
                            );
                        }
                    }

                    $order->setRecollect(true);
                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('We can not switch to single shipping address now.'));
                }
            }
        }

        return $this;
    }

    /**
     * @param Item $item
     * @return bool
     */
    public function canConvertQuoteItemToSingleAddress(\Magento\Quote\Model\Quote\Item $item)
    {
        if ($item->getParentItemId()) {
            return false;
        }

        return true;
    }

    /**
     * Get payment status
     *
     * @param $optionId
     * @return array|null
     */
    public function getOrderPaymentStatus($optionId = null)
    {
        $paymentStatus = null;
        if ($optionId != null) {
            $status = \Riki\Shipment\Model\ResourceModel\Status\Options\Payment::getOptionText($optionId);
            if ($status != null) {
                $paymentStatus = $status;
            }
        }
        return $paymentStatus;
    }


    /**
     * get earned point for a specific order item
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return mixed
     */
    public function getEarnedPointByOrderItem(\Magento\Sales\Model\Order\Item $item)
    {

        if (!isset($this->_orderItemIdToEarnedPoints[$item->getItemId()])) {
            $order = $item->getOrder();

            $itemIds = [];

            $visibleItems = $order->getAllVisibleItems();

            /** @var \Magento\Sales\Model\Order\Item $orderItem */
            foreach ($visibleItems as $orderItem) {
                $itemIds[] = $orderItem->getId();

                $this->_orderItemIdToEarnedPoints[$orderItem->getId()] = [];
            }

            $orderItemsEarnedPoint = $this->_loyaltyHelper->getOrderItemsPointEarned($itemIds);

            foreach ($orderItemsEarnedPoint as $itemEarnedPointData) {
                if (!isset($this->_orderItemIdToEarnedPoints[$itemEarnedPointData['order_item_id']])) {
                    $this->_orderItemIdToEarnedPoints[$itemEarnedPointData['order_item_id']] = [];
                }
                $this->_orderItemIdToEarnedPoints[$itemEarnedPointData['order_item_id']][] = $itemEarnedPointData;
            }
        }

        if (!isset($this->_orderItemIdToEarnedPoints[$item->getItemId()])) {
            $this->_orderItemIdToEarnedPoints[$item->getItemId()] = [];
        }

        return $this->_orderItemIdToEarnedPoints[$item->getItemId()];
    }

    /**
     * generate html for order item earned points content
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return string
     */
    public function generateOrderItemEarnedPointHtml(\Magento\Sales\Model\Order\Item $item)
    {
        $earnedPoints = $this->getEarnedPointByOrderItem($item);

        $totalEarnedPoint = 0;

        $earnedPointsDetail = [];

        foreach ($earnedPoints as $earnedPointData) {
            $earnedPointDataItem = $earnedPointData['point'] * $earnedPointData['qty'];

            $totalEarnedPoint += $earnedPointDataItem;
            $earnedPointsDetail[] = $this->renderPointTypeTitleForOrderItem(
                $earnedPointData['point_type'],
                $earnedPointDataItem,
                $earnedPointData['sales_rule_id']
            );
        }

        $result = $totalEarnedPoint . __(' point(s)');

        if ($totalEarnedPoint > 0) {
            $result .= '</br>' . implode('</br>', $earnedPointsDetail);
        }

        return $result;
    }

    /**
     * @param $type
     * @param $point
     * @param null $ruleId
     * @return \Magento\Framework\Phrase|string
     */
    public function renderPointTypeTitleForOrderItem($type, $point, $ruleId = null)
    {
        switch ($type) {
            case LoyaltyReward::TYPE_CAMPAIGN:
                $result = __('Promotion %1: %2', $ruleId, $point);
                break;
            case LoyaltyReward::TYPE_PURCHASE:
                $result = __('Item: %1', $point);
                break;
            default:
                $result = $this->_loyaltyHelper->getRewardTypeTitleByValue($type) . ': ' . $point;
                break;
        }

        return $result;
    }

    /**
     * @param $customerId
     * @return string
     */
    public function getCustomerNameKana($customerId)
    {
        $customerName = '';
        try {
            $customer = $this->_customerRepository->getById($customerId);
            $customerName = $this->getCustomerAttribute($customer, 'lastnamekana') . ' ' . $this->getCustomerAttribute($customer, 'firstnamekana');
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }

        return $customerName;
    }

    /**
     * @param $customer
     * @param $attribute
     * @return bool|mixed
     */
    public function getCustomerAttribute($customer, $attribute)
    {
        if (!empty($customer->getCustomAttribute($attribute))) {
            return $customer->getCustomAttribute($attribute)->getValue();
        } else {
            return false;
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return string
     */
    public function getOriginalIncludeTaxByOrderItem(\Magento\Sales\Model\Order\Item $item)
    {
        if ($this->helperSale->isFreeGift($item)) {
            $price = 0;
        } elseif ($this->hasCatalogDiscount($item) ||
            (int)$item->getStockPointAppliedDiscountRate()) {
            $price = $this->calOrderItemPriceInclTax($item);
        } else {
            $price = $item->getPriceInclTax();
        }

        return $item->getOrder()->formatPrice($price);
    }

    /**
     * @param null|object $item
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function isChildCalculated($item = null)
    {
        if ($item) {
            $parentItem = $item->getParentItem();
            if ($parentItem) {
                $options = $parentItem->getProductOptions();
                if ($options) {
                    return (isset($options['product_calculations'])
                        && $options['product_calculations'] == AbstractType::CALCULATE_CHILD);
                }
            } else {
                $options = $item->getProductOptions();
                if ($options) {
                    return !(isset($options['product_calculations'])
                        && $options['product_calculations'] == AbstractType::CALCULATE_CHILD);
                }
            }
        }

        return false;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return float
     */
    private function calOrderItemPriceInclTax(\Magento\Sales\Model\Order\Item $item)
    {
        if ($item->getHasChildren()) {
            // use flow of module-bundle/Block/Adminhtml/Sales/Order/View/Items/Renderer.php:143
            $price = $item->getOriginalPrice();

            foreach ($item->getChildrenItems() as $childrenItem) {
                if (!$this->isChildCalculated($childrenItem)) {
                    $attributes = $this->getSelectionAttributes($childrenItem);
                    if ($attributes) {
                        $price += $attributes['price'];
                    }
                }
            }
        } else {
            $price = $item->getOriginalPrice();
        }

        return floor($price * (1 + $item->getTaxPercent() / 100));
    }

    /**
     * @param mixed $item
     * @return mixed
     */
    private function getSelectionAttributes($item)
    {
        if ($item instanceof \Magento\Sales\Model\Order\Item) {
            $options = $item->getProductOptions();
        } else {
            $options = $item->getOrderItem()->getProductOptions();
        }
        if (isset($options['bundle_selection_attributes'])) {
            return $this->serializer->unserialize($options['bundle_selection_attributes']);
        }
        return null;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return bool
     */
    private function hasCatalogDiscount(\Magento\Sales\Model\Order\Item $item)
    {
        if ((float)$item->getRulePrice()) {
            return true;
        }

        if ($item->getRulePrice() == 0 && $item->getAppliedRulesCatalog() !== null) {
            return true;
        }

        return false;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return string
     */
    public function getCatalogDiscountAmountByOrderItem(
        \Magento\Sales\Model\Order\Item $item
    ) {
        if ($this->hasCatalogDiscount($item)) {
            return $item->getOrder()->formatPrice(
                $this->getDiscountAmountFromCatalogRuleAndStockPoint($item)
                - $this->_getStockPointAppliedDiscountAmount($item)
            );
        } else {
            return $item->getOrder()->formatPrice(0);
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return float|int
     */
    private function _getStockPointAppliedDiscountAmount(
        \Magento\Sales\Model\Order\Item $item
    ) {
        if ((int)$item->getStockPointAppliedDiscountRate()) {
            if ($this->hasCatalogDiscount($item)) {
                return floor($item->getStockPointAppliedDiscountAmount() * (1 + $item->getTaxPercent() / 100));
            } else {
                return $this->getDiscountAmountFromCatalogRuleAndStockPoint($item);
            }
        } else {
            return 0;
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return string
     */
    public function getFormatPriceStockPointDiscountAmount(
        \Magento\Sales\Model\Order\Item $item
    ) {
        return $item->getOrder()->formatPrice(
            $this->_getStockPointAppliedDiscountAmount($item)
        );
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return float
     */
    private function getDiscountAmountFromCatalogRuleAndStockPoint(
        \Magento\Sales\Model\Order\Item $item
    ) {
        if ($this->helperSale->isFreeGift($item)) {
            return 0;
        }

        return $this->calOrderItemPriceInclTax($item)
            - (float)$item->getPriceInclTax();
    }

    /**
     * @param \Magento\Sales\Model\ResourceModel\Order\Item\Collection $orderItemCollection
     * @return array|\Magento\Sales\Model\ResourceModel\Order\Item\Collection
     */
    public function sortOrderItemByAddressAndSku(\Magento\Sales\Model\ResourceModel\Order\Item\Collection $orderItemCollection)
    {

        $order = $orderItemCollection->getSalesOrder();

        if (!$order->getIsMultipleShipping()) {
            return $orderItemCollection;
        }

        $result = [];
        $itemByAddressAndSku = [];

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($orderItemCollection as $item) {
            if ($item->getParentItemId()) {
                continue;
            }

            $addressId = $item->getAddressId();

            if (!isset($itemByAddressAndSku[$addressId])) {
                $itemByAddressAndSku[$addressId] = [];
            }

            if (!isset($itemByAddressAndSku[$addressId][$item->getSku()])) {
                $itemByAddressAndSku[$addressId][$item->getSku()] = [];
            }

            $itemByAddressAndSku[$addressId][$item->getSku()][] = $item;
        }

        foreach ($itemByAddressAndSku as $addressId => $itemBySku) {
            foreach ($itemBySku as $sku => $items) {
                foreach ($items as $item) {
                    $result[] = $item;
                }
            }
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function allowToAddOutOfStockProduct(){
        return $this->scopeConfig->getValue(
            'cataloginventory/order_options/allow_create_order_out_of_stock',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isSubscriptionOrder(\Magento\Sales\Model\Order $order)
    {
        if (in_array($order->getRikiType(), [
            SubscriptionType::TYPE_ORDER_SUBSCRIPTION,
            SubscriptionType::TYPE_ORDER_HANPUKAI,
            \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT
            ])
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getOrderCancelReasons()
    {
        $result = [];
        $optionsConfig = $this->scopeConfig->getValue('riki_order/cancellation/reason');

        if ($optionsConfig) {
            $options = $this->serializer->unserialize($optionsConfig);

            if (is_array($options)) {
                foreach ($options as $option) {
                    $result[$option['code']] = $option['title'];
                }
            }
        }

        return $result;
    }
}
