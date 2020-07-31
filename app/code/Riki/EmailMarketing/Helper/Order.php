<?php
/**
 * Email Marketing Module
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\EmailMarketing
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\EmailMarketing\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Riki\Checkout\Model\ResourceModel\Order\Address\Item\Collection as ItemCollection;
use Magento\Sales\Api\OrderAddressRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Riki\EmailMarketing\Helper\Data as DataHelper;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Riki\Loyalty\Model\ResourceModel\Reward\CollectionFactory
    as PointCollectionFactory;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\State;
use Riki\Subscription\Api\ProfileRepositoryInterface;
use Riki\SubscriptionCourse\Model\CourseFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Riki\Preorder\Helper\Data as PreOrderHelper;
use Magento\GiftWrapping\Api\WrappingRepositoryInterface;
use Riki\DeliveryType\Model\Delitype;
use Magento\Quote\Model\QuoteFactory;
/**
 * Class Order
 *
 * @category  RIKI
 * @package   Riki\EmailMarketing
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Order extends \Magento\Framework\App\Helper\AbstractHelper
{
    CONST NEWLINE = PHP_EOL;

    CONST NEWCOMMA = " : ";

    CONST XML_PATH_SENDING_RETURN_PATH_EMAIL = 'system/smtp/return_path_email';
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $priceHelper;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var TimezoneInterface
     */
    protected $timeZone;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var OrderAddressRepositoryInterface
     */
    protected $orderAddressRepository;
    /**
     * @var ItemCollection
     */
    protected $rikiAddressItemCollection;
    /**
     * @var Data
     */
    protected $dataHelper;
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var PointCollectionFactory
     */
    protected $pointRepository;
    /**
     * @var AreaList
     */
    protected $areaList;
    /**
     * @var State
     */
    protected $state;
    /**
     * @var ProfileRepositoryInterface
     */
    protected $profileRepository;
    /**
     * @var CourseFactory
     */
    protected $courseFactory;
    /**
     * @var PreOrderHelper
     */
    protected $preOrderHelper;
    /**
     * @var WrappingRepositoryInterface
     */
    protected $wrappingRepository;
    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;
    /**
     * @var \Riki\Sales\Helper\Address
     */
    protected $addressHelper;
    /**
     * @var \Riki\DeliveryType\Helper\Admin
     */
    protected $deliveryTypeAdminHelper;
    /**
     * @var \Magento\Sales\Model\Order\AddressFactory
     */
    protected $addressFactory;
    /**
     * @var SplitDeliveryEmail
     */
    protected $splitDeliveryEmail;


    /**
     * Order constructor.
     * @param Context $context
     * @param PriceHelper $priceHelper
     * @param DateTime $dateTime
     * @param TimezoneInterface $timezone
     * @param Data $dataHelper
     * @param ItemCollection $rikiAddressItemCollection
     * @param OrderAddressRepositoryInterface $orderAddressRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CustomerRepositoryInterface $customerRepository
     * @param PointCollectionFactory $collectionFactory
     * @param AreaList $areaList
     * @param State $state
     * @param ProfileRepositoryInterface $profileRepository
     * @param CourseFactory $courseFactory
     * @param PreOrderHelper $preOrderHelper
     * @param WrappingRepositoryInterface $wrappingRepository
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        Context $context,
        PriceHelper $priceHelper,
        DateTime $dateTime,
        TimezoneInterface $timezone,
        DataHelper $dataHelper,
        ItemCollection $rikiAddressItemCollection,
        OrderAddressRepositoryInterface $orderAddressRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerRepositoryInterface $customerRepository,
        PointCollectionFactory $collectionFactory,
        AreaList $areaList,
        State $state,
        ProfileRepositoryInterface $profileRepository,
        CourseFactory $courseFactory,
        PreOrderHelper $preOrderHelper,
        WrappingRepositoryInterface $wrappingRepository,
        QuoteFactory $quoteFactory,
        \Riki\Sales\Helper\Address $addressHelper,
        \Riki\DeliveryType\Helper\Admin $deliveryTypeAdminHelper,
        \Magento\Sales\Model\Order\AddressFactory $addressFactory,
        \Riki\EmailMarketing\Helper\SplitDeliveryEmail $splitDeliveryEmail
    ) {
        $this->_scopeConfig = $context;
        parent::__construct($context);
        $this->priceHelper = $priceHelper;
        $this->dataHelper = $dataHelper;
        $this->dateTime = $dateTime;
        $this->timeZone = $timezone;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->rikiAddressItemCollection = $rikiAddressItemCollection;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
        $this->pointRepository = $collectionFactory;
        $this->areaList = $areaList;
        $this->state = $state;
        $this->profileRepository = $profileRepository;
        $this->courseFactory = $courseFactory;
        $this->preOrderHelper = $preOrderHelper;
        $this->wrappingRepository = $wrappingRepository;
        $this->quoteFactory = $quoteFactory;
        $this->addressHelper = $addressHelper;
        $this->deliveryTypeAdminHelper = $deliveryTypeAdminHelper;
        $this->addressFactory = $addressFactory;
        $this->splitDeliveryEmail = $splitDeliveryEmail;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getOrderProducts
    (
        \Magento\Sales\Model\Order $order
    )
    {
        $newLine = self::NEWLINE;
        $newComma = self::NEWCOMMA;
        $productLists = '';
        $items = $order->getAllVisibleItems();
        foreach($items as $item)
        {
            if(!$item->getPrizeId() && intval($item->getPriceInclTax()) > 0 ) // not free product
            {
                $qty = $item->getQtyOrdered() ?  $item->getQtyOrdered() : 1;
                $rowTotal =$this->priceHelper->currency( $item->getPriceInclTax() * $qty, true, false);
                $wrappingPrice = $this->priceHelper->currency(($item->getGwPrice()+ $item->getGwTaxAmount()) * $qty, true, false); // tax gift wrap
                $productLists .= $newLine . __('Product List Title');
                $productLists .= $newLine . __("Product Name") . $newComma . $item->getName(); //product name
                $productLists .= $newLine . __("Product Price") . $newComma . $rowTotal; //row Total
                $productLists .= $newLine . __("Wrapping Name") . $newComma . $this->getWrappingName($item->getGwId()); // gift Wrapping Name
                $productLists .= $newLine . __("Wrapping Price") . $newComma . $wrappingPrice;
                $productLists .= $newLine . __("Product Qty") . $newComma . $this->getProductUnit($item);
                $productLists .= $newLine ;
            }
        }
        return $productLists;
    }

    public function getAddressIdsForEdit(\Magento\Sales\Model\Order $order){
        $itemIds = [];
        foreach($order->getAllItems() as $item){
            $itemIds[] = $item->getId();
        }
        $orderItemIdToAddressId =  $this->addressHelper->getAddressIdsByOrderItemIdsForEdit($itemIds);
        return $orderItemIdToAddressId;
    }

    /**
     * Get spit product order confirm
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getSplitOrderProductDeliveryType(\Magento\Sales\Model\Order $order)
    {
        $addressGroups = [];
        $isPreOrder = $this->preOrderHelper->getOrderIsPreorderFlag($order);
        $itemIdsToAddressIds = $this->getAddressIdsForEdit($order);
        foreach ($order->getAllItems() as $_item) {
            $deliveryType = $this->deliveryTypeAdminHelper->prepareDeliveryType($_item->getDeliveryType());
            $addressId = isset($itemIdsToAddressIds[$_item->getId()]) ? $itemIdsToAddressIds[$_item->getId()] : 0;

            //LIST ITEM
            if(!$_item->getPrizeId() && intval($_item->getPriceInclTax()) > 0 ) // not free product
            {
                $qty = $_item->getQtyOrdered() ?  $_item->getQtyOrdered() : 1;
                $rowTotal =$this->priceHelper->currency( $_item->getPriceInclTax() * $qty, true, false);
                $wrappingPrice = $this->priceHelper->currency(($_item->getGwPrice()+ $_item->getGwTaxAmount()) * $qty, true, false); // tax gift wrap

                $productLists = null;
                $productLists .= self::NEWLINE . __("Product Name")   . self::NEWCOMMA . $_item->getName();
                $productLists .= self::NEWLINE . __("Product Price")  . self::NEWCOMMA . $rowTotal;
                $productLists .= self::NEWLINE . __("Wrapping Name")  . self::NEWCOMMA . $this->getWrappingName($_item->getGwId()); // gift Wrapping Name
                $productLists .= self::NEWLINE . __("Wrapping Price") . self::NEWCOMMA . $wrappingPrice;
                $productLists .= self::NEWLINE . __("Product Qty")    . self::NEWCOMMA . $this->getProductUnit($_item);
                $productLists .= self::NEWLINE ;
                $addressGroups[$deliveryType][$addressId]['products'][$_item->getId()] = $productLists;
            }

            //SHIPPING ADDRESS
            if($addressId){
                $shippingAddress = $this->_getAddressObjById($addressId);
            }else{
                $shippingAddress = $this->_getAddressObjById($order->getShippingAddress()->getId());

            }

            if ($shippingAddress){
                 $address = __($shippingAddress->getRegion()) . ' '.
                    $shippingAddress->getStreetLine(1). ' '.
                    $shippingAddress->getData('apartment');


                $firstName = $shippingAddress->getFirstName();
                $lastName = $shippingAddress->getLastName();
                $itemShipping =null;
                $itemShipping.= self::NEWLINE . __("Shipping Title");
                $itemShipping.= self::NEWLINE .sprintf(__("Shipping Name %s %s"),$lastName,$firstName);
                $itemShipping.= self::NEWLINE .__("Shipping Postcode").$shippingAddress->getPostcode();
                $itemShipping.= self::NEWLINE . $address;
                $itemShipping.= self::NEWLINE.sprintf(__("Shipping Telephone: %s"),$shippingAddress->getTelephone());
                $addressGroups[$deliveryType][$addressId]['shippingInformation']  = $itemShipping;

                //DELIVERY TYPE
                $infoDeliveryType = null;
                if(!$isPreOrder) {
                    $infoDeliveryType .= self::NEWLINE;
                    $infoDeliveryType .= self::NEWLINE . sprintf(__("Delivery Type Email : %s"), __($deliveryType));
                    $infoDeliveryType .= self::NEWLINE;
                    if ($_item->getDeliveryDate()) {
                        $deliveryDate = $this->dateTime->gmtDate('Y/m/d', $_item->getDeliveryDate());
                        $infoDeliveryType .= self::NEWLINE . sprintf(__("Order Delivery Date : %s"), $deliveryDate);
                    }else{
                        $infoDeliveryType .= self::NEWLINE . __("Delivery date empty");
                    }

                    if($_item->getDeliveryTime())
                    {
                        $infoDeliveryType .= self::NEWLINE . sprintf(__("Order Delivery Time : %s"), $_item->getDeliveryTime());
                    }
                    else
                    {
                        $infoDeliveryType .= self::NEWLINE . __("Delivery Time empty");
                    }
                    $infoDeliveryType .= self::NEWLINE;
                    $addressGroups[$deliveryType][$addressId]['deliveryType']  = $infoDeliveryType;
                }
            }
        }

        return $addressGroups;
    }



    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getOrderProductsPresent
    (
        \Magento\Sales\Model\Order $order
    )
    {
        $newLine = self::NEWLINE;
        $newComma = self::NEWCOMMA;
        $productLists = '';
        $items = $order->getAllVisibleItems();
        foreach($items as $item)
        {
            if(intval($item->getPriceInclTax())==0 || $item->getPrizeId() ) // free product
            {
                $qty = $item->getQtyOrdered() ?  $item->getQtyOrdered() : 1;
                $rowTotal =$this->priceHelper->currency( $item->getPriceInclTax() * $qty, true, false);
                $wrappingPrice = $this->priceHelper->currency(($item->getGwPrice()+ $item->getGwTaxAmount()) * $qty, true, false); // tax gift wrap
                $productLists .= $newLine . __('Product List Title Present');
                $productLists .= $newLine ;
                $productLists .= $newLine . __("Product Name") . $newComma . $item->getName(); //product name
                $productLists .= $newLine . __("Product Price") . $newComma . $rowTotal; //row total
                if ($item->getGwId()) {
                    $productLists .= $newLine . __("Wrapping Name") . $newComma . $this->getWrappingName($item->getGwId()); //amount giftwrap
                    $productLists .= $newLine . __("Wrapping Price") . $newComma . $wrappingPrice;
                } else {
                    $productLists .= $newLine . __("Wrapping Name") . $newComma;
                    $productLists .= $newLine . __("Wrapping Price") . $newComma;
                }
                $productLists .= $newLine . __("Product Qty") . $newComma  . $this->getProductUnit($item);
                $productLists .= $newLine ;
            }
        }
        return $productLists;
    }

    /**
     * @param $wrappingId
     * @return string
     */
    public function getWrappingName($wrappingId)
    {
        $wrappingName = '';
        if($wrappingId) {
            try {
                $wrappingObject = $this->wrappingRepository->get($wrappingId);
                $wrappingName = $wrappingObject->getGiftName();
            } catch (\Exception $e) {
                $wrappingName = '';
            }
        }
        return $wrappingName;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return \Magento\Framework\Phrase
     */
    public function getProductUnit
    (
        \Magento\Sales\Model\Order\Item $orderItem
    )
    {
        $unit = $orderItem->getData('unit_case');
        $unitQty = $orderItem->getData('unit_qty');
        $qty  = round($orderItem->getQtyOrdered());
        if(strtoupper($unit)=="CS")
        {
            $eaLable = __('EA');
            $qtyCs = round($qty / $unitQty);
            $unitLable = $qtyCs .__('CS');
            return $unitLable.' ('.$unitQty.$eaLable.')';
        }
        else
        {
            $unitLable =  __('EA');
        }
        return $qty.' ('.$unitLable.')';
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getOrderInformation
    (
        \Magento\Sales\Model\Order $order
    )
    {

        $rawDate =  $this->timeZone->formatDateTime($order->getCreatedAt(),2,2);
        $createdAt = $this->dateTime->gmtDate('Y/m/d H:i:s',$rawDate);
        $grandTotal = $this->priceHelper->currency(intval($order->getGrandTotal()), true, false );
        $paymentFee = $this->priceHelper->currency(intval($order->getFee()),true,false);
        $information = '';
        $information.= self::NEWLINE. sprintf(__("Order increment id: %s"), $order->getIncrementId());
        $information.= self::NEWLINE. sprintf(__("Order creation date: %s"),$createdAt);
        $information.= self::NEWLINE. sprintf(__("Order total: %s"),$grandTotal);
        $information.= self::NEWLINE. sprintf(__("Order Payment fee: %s"),$paymentFee);
        $information.= self::NEWLINE. sprintf(__("Order used point: %d"), $order->getUsedPoint());
        $information.= self::NEWLINE. sprintf(__("Order earn point: %d"),$order->getBonusPointAmount());
        return $information;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getOrderBillingInformation
    (
        \Magento\Sales\Model\Order $order
    )
    {

        $billingInformation = __("Billing Title");
        $addressBilling = $order->getBillingAddress();
        $firstName = $addressBilling->getFirstname();
        $lastName = $addressBilling->getLastname();
        $billingInformation.= self::NEWLINE . sprintf(__("Billing Name %s %s"),$lastName,$firstName);
        $billingInformation .= self::NEWLINE .__("Billing Postcode"). $addressBilling->getPostcode();
        $billingInformation .= self::NEWLINE . __($addressBilling->getRegion());
        $streetLine = $addressBilling->getStreetLine(1);
        $apartment = $addressBilling->getData('apartment');
        $formatAddress = $streetLine.' '.$apartment;
        $billingInformation.= $formatAddress;
        $billingInformation.= self::NEWLINE . sprintf(__("Billing Telephone: %s"),
                $addressBilling->getTelephone());
        return $billingInformation;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getOrderPaymentTotal
    (
        \Magento\Sales\Model\Order $order
    )
    {
        $subtotalInc = $order->getSubtotalInclTax();
        $subTotal = $this->priceHelper->currency($subtotalInc,true,false);
        $paymentFee = $this->priceHelper->currency($order->getFee(), true, false );
        $shippingFee = $this->priceHelper->currency($order->getShippingAmount() + $order->getShippingTaxAmount(), true, false );
        $grandTotalAmount = $this->getFinalGrandTotal($order);
        $grandTotal = $this->priceHelper->currency($grandTotalAmount, true, false );
        $wrappingFee = $this->priceHelper->currency($order->getData('gw_items_price_incl_tax'), true, false );

        $paymentInformation = __("Payment Title");
        $paymentInformation .=self::NEWLINE. sprintf(__("Sub Total : %s"),$subTotal);
        $paymentInformation .=self::NEWLINE. sprintf(__("Total wrapping Fee : %s"),$wrappingFee);
        $paymentInformation .=self::NEWLINE. sprintf(__("Shipping Fee : %s"),$shippingFee);
        $paymentInformation .=self::NEWLINE. sprintf(__("Payment Fee Email: %s"),$paymentFee);
        $paymentInformation.= self::NEWLINE. sprintf(__("PT Order used point: %d"), $order->getUsedPoint());
        $paymentInformation .=self::NEWLINE. sprintf(__("Grand Total : %s"),$grandTotal);
        return $paymentInformation;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getOrderPaymentMethodInformation
    (
        \Magento\Sales\Model\Order $order
    )
    {
        $paymentCode = $order->getPayment()->getMethod();
        $paymentTitle = $this->dataHelper->getPaymentTitle($paymentCode);
        $paymentFee = $this->priceHelper->currency($order->getFee(), true, false );
        $paymentInformation =  self::NEWLINE . __("Payment Method Order").
            self::NEWCOMMA. __($paymentTitle);
        $paymentInformation .=  self::NEWLINE . __("Payment Fee Total").
            self::NEWCOMMA. $paymentFee;
        return $paymentInformation;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getOrderShippingInformation
    (
        \Magento\Sales\Model\Order $order
    )
    {
        $isPreOrder = $this->preOrderHelper->getOrderIsPreorderFlag($order);
        $shippingInformation = '';
        $multiShippingAddress = $this->groupMultiAddressItems($order);
        $isMultiCheckout = $order->getData('is_multiple_shipping');
        if($isMultiCheckout && $multiShippingAddress)
        {
            foreach ($order->getAllItems() as $_item) {
                //prevent children items of bundle product
                if(!$_item->getParentItemId() && array_key_exists($_item->getId(),$multiShippingAddress ))
                {
                    $shippingAddress = $multiShippingAddress[$_item->getId()];

                        $address =  __($shippingAddress->getRegion()) . ' '.
                        $shippingAddress->getStreetLine(1). ' '.
                        $shippingAddress->getData('apartment');

                    $firstName = $shippingAddress->getFirstName();
                    $lastName = $shippingAddress->getLastName();
                    $shippingInformation.= self::NEWLINE . __("Shipping Title");
                    $shippingInformation.= self::NEWLINE .sprintf(__("Shipping Name %s %s"),$lastName,$firstName);
                    $shippingInformation.= self::NEWLINE .__("Shipping Postcode").$shippingAddress->getPostcode();
                    $shippingInformation.= self::NEWLINE . $address;
                    $shippingInformation.= self::NEWLINE.sprintf(__("Shipping Telephone: %s"),$shippingAddress->getTelephone());
                    if(!$isPreOrder) {
                        $shippingInformation .= self::NEWLINE;
                        $shippingInformation .= self::NEWLINE . sprintf(__("Delivery Type Email : %s"), __($_item->getDeliveryType()));
                        $shippingInformation .= self::NEWLINE;
                        if ($_item->getDeliveryDate()) {
                            $deliveryDate = $this->dateTime->gmtDate('Y/m/d', $_item->getDeliveryDate());
                            $shippingInformation .= self::NEWLINE . sprintf(__("Order Delivery Date : %s"), $deliveryDate);
                        }else{
                            $shippingInformation .= self::NEWLINE . __("Delivery date empty");
                        }
                        
                        if($_item->getDeliveryTime())
                        {
                            $shippingInformation .= self::NEWLINE . sprintf(__("Order Delivery Time : %s"), $_item->getDeliveryTime());
                        }
                        else
                        {
                            $shippingInformation .= self::NEWLINE . __("Delivery Time empty");
                        }
                        $shippingInformation .= self::NEWLINE;
                    }
                }
            }
        }
        else
        {
            $shippingAddress = $order->getShippingAddress();

                $address =  __($shippingAddress->getRegion()) . ' '.
                $shippingAddress->getStreetLine(1). ' '.
                $shippingAddress->getData('apartment');

            $firstName = $shippingAddress->getFirstName();
            $lastName = $shippingAddress->getLastName();
            $shippingInformation.= self::NEWLINE . __("Shipping Title");
            $shippingInformation.= self::NEWLINE .sprintf(__("Shipping Name %s %s"),$lastName,$firstName);
            $shippingInformation.= self::NEWLINE .__("Shipping Postcode").$shippingAddress->getPostcode();
            $shippingInformation.= self::NEWLINE . $address;
            $shippingInformation.= self::NEWLINE.sprintf(__("Shipping Telephone: %s"),$shippingAddress->getTelephone());
            if(!$isPreOrder) {
                $shippingInformation.= $this->groupItemsDelivery($order->getAllItems());
            }
        }
        return $shippingInformation;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getSpitOrderShippingInformation
    (
        \Magento\Sales\Model\Order $order
    )
    {
        $isPreOrder = $this->preOrderHelper->getOrderIsPreorderFlag($order);
        $shippingInformation = '';
        $multiShippingAddress = $this->groupMultiAddressItems($order);
        $isMultiCheckout = $order->getData('is_multiple_shipping');
        if($isMultiCheckout && $multiShippingAddress)
        {
            foreach ($order->getAllItems() as $_item) {
                //prevent children items of bundle product
                if(!$_item->getParentItemId() && array_key_exists($_item->getId(),$multiShippingAddress ))
                {
                    $shippingAddress = $multiShippingAddress[$_item->getId()];

                        $address =  __($shippingAddress->getRegion()) . ' '.
                        $shippingAddress->getStreetLine(1). ' '.
                        $shippingAddress->getData('apartment');

                    $firstName = $shippingAddress->getFirstName();
                    $lastName = $shippingAddress->getLastName();
                    $shippingInformation.= self::NEWLINE . __("Shipping Title");
                    $shippingInformation.= self::NEWLINE .sprintf(__("Shipping Name %s %s"),$lastName,$firstName);
                    $shippingInformation.= self::NEWLINE .__("Shipping Postcode").$shippingAddress->getPostcode();
                    $shippingInformation.= self::NEWLINE . $address;
                    $shippingInformation.= self::NEWLINE.sprintf(__("Shipping Telephone: %s"),$shippingAddress->getTelephone());
                }
            }
        }
        else
        {
            $shippingAddress = $order->getShippingAddress();

            $address =  __($shippingAddress->getRegion()) . ' '.
                 $shippingAddress->getStreetLine(1). ' '.
                 $shippingAddress->getData('apartment');

            $firstName = $shippingAddress->getFirstName();
            $lastName = $shippingAddress->getLastName();
            $shippingInformation.= self::NEWLINE . __("Shipping Title");
            $shippingInformation.= self::NEWLINE .sprintf(__("Shipping Name %s %s"),$lastName,$firstName);
            $shippingInformation.= self::NEWLINE .__("Shipping Postcode").$shippingAddress->getPostcode();
            $shippingInformation.= self::NEWLINE . $address;
            $shippingInformation.= self::NEWLINE.sprintf(__("Shipping Telephone: %s"),$shippingAddress->getTelephone());
        }
        return $shippingInformation;
    }


    public function _getAddressObjById($addressId){
        $addressObject = $this->addressFactory->create()->load($addressId);
        return $addressObject;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param array $dataReplace
     * @return string
     */
    public function getSpitDeliveryTypeItemList
    (
        \Magento\Sales\Model\Order $order,
        $dataReplace
    )
    {
        $itemList       = '';
        $this->splitDeliveryEmail->initData();
        $addressGroups  = $this->splitDeliveryEmail->getAddressGroups($order);
        if ( is_array($addressGroups) && count($addressGroups)>0 ){

            foreach ($addressGroups as $addressId => $addressGroup){
                // check multi delitype
                $checkItem = (isset($addressGroup['delivery']) && count ($addressGroup['delivery'])>1 ) ? true : false;
                foreach($addressGroup['delivery'] as  $deliveryType   =>  $deliveryInfo){
                    $itemList .= self::NEWLINE ;
                    // Check multi address
                    if($checkItem){
                        $itemList .= self::NEWLINE . '****** ' .__('Order details and address') .' ******';
                        $itemList .= self::NEWLINE ;
                    }


                    //LIST ITEM
                    if (isset($deliveryInfo['items']) && count ($deliveryInfo['items']) >0 ){

                        foreach ($deliveryInfo['item_ids_object'] as $_item){
                            $itemList .= self::NEWLINE ;
                            $itemList .= self::NEWLINE . __('Product List Title');

                            $rowTotal      = $this->priceHelper->currency($_item->getRowTotalInclTax(), true, false);
                            $wrappingPrice = $this->priceHelper->currency(($_item->getGwPrice()+ $_item->getGwTaxAmount()), true, false); // tax gift wrap
                            $itemList .= self::NEWLINE . __("Product Name")   . self::NEWCOMMA . $_item->getName();
                            $itemList .= self::NEWLINE . __("Product Price")  . self::NEWCOMMA . $rowTotal;
                            $itemList .= self::NEWLINE . __("Wrapping Name")  . self::NEWCOMMA . $this->getWrappingName($_item->getGwId()); // gift Wrapping Name
                            $itemList .= self::NEWLINE . __("Wrapping Price") . self::NEWCOMMA . $wrappingPrice;
                            $itemList .= self::NEWLINE . __("Product Qty")    . self::NEWCOMMA . $this->getProductUnit($_item);
                            $itemList .= self::NEWLINE ;
                        }
                    }

                    //SHIPPING INFO
                    $shippingAddress    = $this->splitDeliveryEmail->getSplitAddressInfo($order,$addressId);
                    $typeShipment       = ($deliveryInfo['delivery_type_name'] !=null) ? $deliveryInfo['delivery_type_name'] : '';

                    $itemList.= self::NEWLINE . __("Shipping Title");
                    $itemList.= self::NEWLINE .sprintf(__("Shipping Name %s %s"),$shippingAddress['lastName'],$shippingAddress['firstName']);
                    $itemList.= self::NEWLINE .__('Shipping Postcode') . ': ' .$shippingAddress['postCode'] ;
                    $itemList.= self::NEWLINE .$shippingAddress['addressInfo'];
                    $itemList.= self::NEWLINE .sprintf(__("Shipping Telephone: %s"),$shippingAddress['phone']);

                    //delivery type
                    $itemList .= self::NEWLINE;
                    $itemList .= self::NEWLINE . sprintf(__("Delivery Type Email : %s"), $typeShipment );
                    $itemList .= self::NEWLINE;

                    $deliveryDate = null;
                    if ($deliveryInfo['delivery_date'] !=null){
                        $deliveryDate = $this->dateTime->gmtDate('Y/m/d', $deliveryInfo['delivery_date']);
                        $itemList .= self::NEWLINE . sprintf(__("Order Delivery Date : %s"), $deliveryDate);
                    }else{
                        $itemList .= self::NEWLINE . __("Delivery date empty");
                    }

                    if ($deliveryInfo['delivery_time']!=null){
                        $itemList .= self::NEWLINE . sprintf(__("Order Delivery Time : %s"), $deliveryInfo['delivery_time']);
                    }else{
                        $itemList .= self::NEWLINE . __("Delivery Time empty");
                    }

                    $itemList .= self::NEWLINE;
                }
            }
        }
        return $itemList;
    }


    /**
     * @param $items
     * @param null $carrierName
     *
     * @return string
     */
    public function groupSplitItemsDelivery($items, $carrierName = null)
    {
        $deliveries = array();
        $arrDelivery = [];
        foreach ($items as $_item) {
            if(isset($deliveries[$_item->getDeliveryType()])){
                if($_item->getDeliveryDate() && $_item->getDeliveryTime()){
                    $deliveries[$_item->getDeliveryType()] = [
                        'delivery_date' => $_item->getDeliveryDate(),
                        'delivery_time' => $_item->getDeliveryTime(),
                    ];
                }
            }else{
                $deliveries[$_item->getDeliveryType()] = [
                    'delivery_date' => $_item->getDeliveryDate(),
                    'delivery_time' => $_item->getDeliveryTime(),
                ];
            }
        }

        if ($deliveries) {
            foreach ($deliveries as $key => $_item) {
                $shippingInformation = '';
                $shippingInformation .= self::NEWLINE;
                if ($carrierName) {
                    $shippingInformation .= self::NEWLINE . sprintf(__("Delivery Company %s"), $carrierName);
                }

                $shippingInformation .= self::NEWLINE . sprintf(__("Delivery Type Email : %s"), __($key));

                $shippingInformation .= self::NEWLINE;
                if (isset($_item['delivery_date'])) {
                    $deliveryDate = $this->dateTime->gmtDate('Y/m/d', $_item['delivery_date']);
                    $shippingInformation .= self::NEWLINE . sprintf(__("Order Delivery Date : %s"), $deliveryDate);
                } else {
                    $shippingInformation .= self::NEWLINE . __("Delivery date empty");
                }


                if (isset($_item['delivery_time'])) {
                    $shippingInformation .= self::NEWLINE . sprintf(__("Order Delivery Time : %s"), $_item['delivery_time']);
                } else {
                    $shippingInformation .= self::NEWLINE .  __("Delivery Time empty");
                }

                $shippingInformation .= self::NEWLINE;
                $arrDelivery[$key] = $shippingInformation;
            }
        }

        return $arrDelivery;
    }




    /**
     * @param $items
     * @param null $carrierName
     *
     * @return string
     */
    public function groupItemsDelivery($items, $carrierName = null)
    {
        $deliveries = array();
        foreach ($items as $_item) {
            if(isset($deliveries[$_item->getDeliveryType()])){
                if($_item->getDeliveryDate() && $_item->getDeliveryTime()){
                    $deliveries[$_item->getDeliveryType()] = [
                        'delivery_date' => $_item->getDeliveryDate(),
                        'delivery_time' => $_item->getDeliveryTime(),
                    ];
                }
            }else{
                $deliveries[$_item->getDeliveryType()] = [
                    'delivery_date' => $_item->getDeliveryDate(),
                    'delivery_time' => $_item->getDeliveryTime(),
                ];
            }

        }
        $shippingInformation = '';
        if ($deliveries) {
            foreach ($deliveries as $key => $_item) {
                $shippingInformation .= self::NEWLINE;
                if ($carrierName) {
                    $shippingInformation .= self::NEWLINE . sprintf(__("Delivery Company %s"), $carrierName);
                }

                $shippingInformation .= self::NEWLINE . sprintf(__("Delivery Type Email : %s"), __($key));

                $shippingInformation .= self::NEWLINE;
                if (isset($_item['delivery_date'])) {
                    $deliveryDate = $this->dateTime->gmtDate('Y/m/d', $_item['delivery_date']);
                    $shippingInformation .= self::NEWLINE . sprintf(__("Order Delivery Date : %s"), $deliveryDate);
                } else {
                    $shippingInformation .= self::NEWLINE . __("Delivery date empty");
                }


                if (isset($_item['delivery_time'])) {
                    $shippingInformation .= self::NEWLINE . sprintf(__("Order Delivery Time : %s"), $_item['delivery_time']);
                } else {
                    $shippingInformation .= self::NEWLINE .  __("Delivery Time empty");
                }

                $shippingInformation .= self::NEWLINE;
            }
        }

        return $shippingInformation;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array|bool
     */
    public function groupMultiAddressItems(\Magento\Sales\Model\Order $order)
    {
        $_items = $order->getAllItems();
        $itemsIds = array();
        $addressIds = array();
        foreach($_items as $_item){
            $itemsIds[] = $_item->getId();
        }
        //get address collection of order
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('parent_id', $order->getId(), 'eq')
            ->create();
        $addressCollection = $this->orderAddressRepository->getList($criteria);
        if($addressCollection->getItems())
        {
            foreach($addressCollection as $_address)
            {
                $addressIds[$_address->getId()] = $_address;
            }
        }
        //get relations of multi address order
        $collectionAddressMulti = $this->rikiAddressItemCollection;
        $collectionAddressMulti->addFieldToFilter('order_item_id',array('in'=>$itemsIds))->load();
        $multiKeys = array();
        $multiShippingAddress = array();
        if($collectionAddressMulti->getSize())
        {
            if ($collectionAddressMulti->getSize())
            {
                foreach ($collectionAddressMulti as $_multi)
                {
                    if(!in_array($_multi->getOrderAddressId(), $multiKeys))
                    {
                        $multiShippingAddress[$_multi->getOrderItemId()] = (isset($addressIds[$_multi->getOrderAddressId()]))  ? $addressIds[$_multi->getOrderAddressId()] : $_multi->getOrderAddressId();
                        $multiKeys[] = $_multi->getOrderAddressId();
                    }
                }
            }

        }
        return $multiShippingAddress;
    }
    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getMultiAddressItems(\Magento\Sales\Model\Order $order)
    {
        $_items = $order->getAllItems();
        $itemsIds = array();
        $addressIds = array();
        foreach($_items as $_item){
            $itemsIds[] = $_item->getId();
        }
        //get address collection of order
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('parent_id', $order->getId(), 'eq')
            ->create();
        $addressCollection = $this->orderAddressRepository->getList($criteria);
        if($addressCollection->getItems())
        {
            foreach($addressCollection as $_address)
            {
                $addressIds[$_address->getId()] = $_address;
            }
        }
        //get relations of multi address order
        $collectionAddressMulti = $this->rikiAddressItemCollection;
        $collectionAddressMulti->addFieldToFilter('order_item_id',array('in'=>$itemsIds))->load();
        if($collectionAddressMulti->getSize())
        {
            $multiShippingAddress = array();
            if ($collectionAddressMulti->getSize())
            {
                foreach ($collectionAddressMulti as $_multi)
                {
                    if(array_key_exists($_multi->getOrderAddressId(), $addressIds))
                    {
                        $multiShippingAddress[$_multi->getOrderItemId()] = $addressIds[$_multi->getOrderAddressId()];
                    }
                }
            }
            return $multiShippingAddress;
        }
        else
        {
            return false;
        }
    }

    /**
     * Get delivery date when order has not shipment
     *
     * @param \Magento\Sales\Model\Order $order
     * @return null|string
     */
    public function getDeliveryDate(\Magento\Sales\Model\Order $order)
    {
        $deliveryDate = null;
        $allItem = $order->getAllItems();
        if (isset($allItem[0])){
            $firstItem = $allItem[0];
            $deliveryDate =  $firstItem->getDeliveryDate();
            if ($firstItem->getDeliveryDate() !=null) {
                $deliveryDate = $this->dateTime->gmtDate('Y/m/d', $firstItem->getDeliveryDate());
            }
        }
        return $deliveryDate;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param null $emailTemplateId
     * @return array
     */
    public function getOrderVariables(
        \Magento\Sales\Model\Order $order,
        $emailTemplateId =null
    )
    {
        $newLine = self::NEWLINE;
        $this->loadTranslationPartial();
        $variables = [];
        try{
            $customer = $this->customerRepository->getById($order->getCustomerId());
            $variables['customer_first_name'] = $customer->getFirstname();
            $variables['customer_last_name'] = $customer->getLastName();
            $variables['receiver'] = $customer->getEmail();

        }catch(\Exception $e){
            $this->_logger->info('Error email sending - customer does not exist with ID:'.$order->getCustomerId());
            $variables['customer_first_name'] = '';
            $variables['customer_last_name'] = '';
            //send to return path email return_path_email
            $variables['receiver'] = $this->_scopeConfig->getScopeConfig()
                                        ->getValue(self::XML_PATH_SENDING_RETURN_PATH_EMAIL);
        }
        /*--------------------------------------------*/
        $variables['order_information'] = $this->getOrderInformation($order);
        $variables['order_type'] = strtoupper($order->getRikiType());
        $variables['subscription_profile_id'] = strtoupper($order->getSubscriptionProfileId());
        $variables['billing_information']  = $this->getOrderBillingInformation($order);
        $variables['payment_total']        = $this->getOrderPaymentTotal($order);
        $variables['item_present_list']    = $this->getOrderProductsPresent($order);
        $variables['payment_information']  = $this->getOrderPaymentMethodInformation($order);
        $variables['order_information']    = $this->getOrderInformation($order);
        $variables['shipping_information'] = $this->getOrderShippingInformation($order);

        /**
         * Split delivery type when order has not shipment for spot confirm order
         * ticket 6469
         */
        if ($emailTemplateId=='spot_confirmation_order' || $emailTemplateId=='spot_cancel_order'  || $emailTemplateId =='order_change_subscription'){
            $variables['item_list']            = $this->getSpitDeliveryTypeItemList($order,$variables);
            $variables['shipping_information'] = null;
            //$variables['payment_total']        = null;
        }else{
            $variables['item_list']            = $this->getOrderProducts($order). $newLine;
            $variables['shipping_information'] = $this->getOrderShippingInformation($order). $newLine;
        }

        /**
         * Get delivery date when order send mail order change.Order has not shipment
         * ticket 7315
         */
        if ($order->hasShipments()<=0){
            $variables['delivery_date'] = $this->getDeliveryDate($order);
        }


        $variables['email_footer'] = $this->dataHelper->getEmailFooter();
        $this->getSubscriptionInformation($order,$variables);

        return $variables;
    }

    /*------------------------------FOR SHIPMENT EMAIL CONTENT --------------------------------------*/

    public function getOrderInformationByShipment
    (
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\Order\Shipment $shipment
    )
    {
        //point used : shopping_point_amount from sales_shipment
        //point earn : get tentative point from riki_reward
        $earnPoints = $this->getTentativePointByShipment($shipment);
        $grandTotal = $this->priceHelper->currency($order->getGrandTotal(), true, false );
        $createdAt = $this->dateTime->gmtDate('Y/m/d H:i:s',$order->getCreatedAt());
        $paymentFee = $this->priceHelper->currency($order->getPaymentFee(),true,false);
        $information = '';
        $information.= self::NEWLINE. sprintf(__("Order increment id: %s"), $order->getIncrementId());
        $information.= self::NEWLINE. sprintf(__("Order creation date: %s"),$createdAt);
        $information.= self::NEWLINE. sprintf(__("Order total: %s"),$grandTotal);
        $information.= self::NEWLINE. sprintf(__("Order used point: %d"), $shipment->getData('shopping_point_amount'));
        $information.= self::NEWLINE. sprintf(__("Order earn point: %d"),$earnPoints);
        return $information;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return string
     */
    public function getShippingInformationByShipment
    (
        \Magento\Sales\Model\Order\Shipment $shipment
    )
    {
        $shippingAddress = $shipment->getShippingAddress();
        $shippingInformation = '';
        $shippingInformation .= self::NEWLINE . __("Shipping Title");
        $shippingInformation .= self::NEWLINE . sprintf(
                __("Shipping Name %s %s"),
                $shippingAddress->getLastname(),
                $shippingAddress->getFirstname()
            );
        $shippingInformation .= self::NEWLINE . __("Shipping Postcode") . $shippingAddress->getPostcode();

            $shippingInformation .= self::NEWLINE . __($shippingAddress->getRegion()) . ' ' .
                $shippingAddress->getStreetLine(1) . ' ' .
                $shippingAddress->getData('apartment');


        $shippingInformation .= self::NEWLINE.sprintf(__("Shipping Telephone: %s"),$shippingAddress->getTelephone());
        return $shippingInformation;
    }

    /**
     * @param $carrierName
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return string
     */
    public function getDeliveryByShipment
    (
        $carrierName,
        \Magento\Sales\Model\Order\Shipment $shipment
    )
    {
        $deliveryInformation = self::NEWLINE. sprintf(__("Delivery Company %s"),$carrierName);
        $items = $shipment->getAllItems();
        $orderItems = array();
        foreach($items as $item)
        {
            $orderItems[] = $item->getOrderItem();
        }
        $deliveryInformation = $this->groupItemsDelivery($orderItems, $carrierName);
        return $deliveryInformation;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return string
     */
    public function getItemslistByShipment
    (
        \Magento\Sales\Model\Order\Shipment $shipment
    )
    {
        $newLine = self::NEWLINE;
        $newComma = self::NEWCOMMA;
        $productLists = '';
        $items = $shipment->getAllItems();
        foreach($items as $item)
        {
            $orderItem = $item->getOrderItem();
            $qty = $item->getQty();
            $rowTotal =$this->priceHelper->currency( $orderItem->getPriceInclTax() * $qty, true, false);
            $wrappingPrice = $this->priceHelper->currency(($item->getGwPrice()+ $item->getGwTaxAmount()) * $qty, true, false); // tax gift wrap
            $productLists .= $newLine . __('Product List Title');
            $productLists .= $newLine . __("Product Name") . $newComma . $item->getName(); //product name
            $productLists .= $newLine . __("Product Price") . $newComma . $rowTotal; //row total
            $productLists .= $newLine . __("Wrapping Name") . $newComma . $this->getWrappingName($item->getGwId()); // gift Wrapping Name
            $productLists .= $newLine . __("Wrapping Price") . $newComma . $wrappingPrice;
            $productLists .= $newLine . __("Product Qty") . $newComma .  $this->getProductUnit($orderItem);
            $productLists .= $newLine ;
        }
        return $productLists;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return string
     */
    public function getPaymentTotalByShipment
    (
        \Magento\Sales\Model\Order\Shipment $shipment
    )
    {
        $grandTotal = $shipment->getAmountTotal()
            + $shipment->getShipmentFee()
            + $shipment->getPaymentFee()
            + $shipment->getGwPrice()
            + $shipment->getGwTaxAmount()
            - $shipment->getShoppingPointAmount()
            + $shipment->getDiscountAmount();

        $grandTotal = $this->priceHelper->currency(intval($grandTotal), true, false);
        $paymentFee = $this->priceHelper->currency(intval($shipment->getPaymentFee()), true, false);
        $shippingFee = $this->priceHelper->currency(intval($shipment->getShipmentFee()), true, false);
        $subTotal = $this->priceHelper->currency(intval($shipment->getAmountTotal()), true, false);
        $paymentTotalInformation = __("Payment Title");
        $paymentTotalInformation .=self::NEWLINE. sprintf(__("Sub Total : %s"),$subTotal);
        $paymentTotalInformation .=self::NEWLINE. sprintf(__("Payment Fee : %s"),$paymentFee);
        $paymentTotalInformation .=self::NEWLINE. sprintf(__("Shipping Fee : %s"),$shippingFee);
        $paymentTotalInformation .=self::NEWLINE. sprintf(__("Grand Total : %s"),$grandTotal);
        return $paymentTotalInformation;

    }
    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return int
     */
    public function getTentativePointByShipment
    (
        \Magento\Sales\Model\Order\Shipment $shipment
    )
    {
        $items = $shipment->getAllItems();
        $orderItemIds = [];
        foreach($items as $item)
        {
            $orderItemIds[] = $item->getOrderItemId();
        }
        $pointCollection = $this->pointRepository->create();
        $pointCollection->addFieldToFilter('status',\Riki\Loyalty\Model\Reward::STATUS_SHOPPING_POINT)
            ->addFieldToFilter('order_item_id', array('in'=>$orderItemIds));
        $totalPoint = 0;
        if($pointCollection->getSize()) {
            foreach($pointCollection as $_point) {
                $totalPoint+= $_point->getPoint();
            }
        }

        return $totalPoint;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return string
     */
    public function getOrderPaymentMethodInformationByShipment
    (
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\Order\Shipment $shipment
    )
    {
        $paymentCode = $order->getPayment()->getMethod();
        $paymentTitle = $this->dataHelper->getPaymentTitle($paymentCode);
        $paymentFee = $this->priceHelper->currency(intval($shipment->getPaymentFee()), true, false );
        $paymentInformation =  self::NEWLINE . __("Payment Method Order").
            self::NEWCOMMA. __($paymentTitle);
        $paymentInformation .=  self::NEWLINE . __("Payment Fee Total").
            self::NEWCOMMA. $paymentFee;
        return $paymentInformation;
    }

    /**
     * @param $carrierName
     * @param $trackingNumber
     * @param $trackingUrl
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return array
     */
    public function getOrderVariablesByShipment
    (
        $carrierName,
        $trackingNumber,
        $trackingUrl,
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\Order\Shipment $shipment
    )
    {
        $variables = [];
        $this->loadTranslationPartial();

        $customerId = $order->getCustomerId();

        try {
            $customer = $this->customerRepository->getById($customerId);
            $variables['customer_first_name'] = $customer->getFirstname();
            $variables['customer_last_name'] = $customer->getLastName();
            $variables['receiver'] =  $customer->getEmail();
        } catch(\Exception $e) {
            $this->_logger->critical($e->getMessage());
            $variables['customer_first_name'] = $order->getCustomerFirstname();
            $variables['customer_last_name'] = $order->getCustomerLastname();
            $variables['receiver'] =  $order->getCustomerEmail();
        }

        $variables['carrier_name'] = $carrierName;
        $variables['tracking_number'] = $trackingNumber;
        $variables['tracking_url'] = $trackingUrl;
        $variables['order_type'] = strtoupper($order->getRikiType());
        $variables['subscription_profile_id'] = strtoupper($order->getSubscriptionProfileId());
        /*--------------------------------------------*/
        $variables['order_information'] = $this->getOrderInformationByShipment($order,$shipment);
        $variables['billing_information'] = $this->getOrderBillingInformation($order);
        $variables['payment_total'] = $this->getPaymentTotalByShipment($shipment);
        $variables['item_list'] = $this->getItemslistByShipment($shipment);
        $variables['shipping_information'] = $this->getShippingInformationByShipment($shipment);
        $variables['delivery_information'] = $this->getDeliveryByShipment($carrierName,$shipment);
        $variables['payment_information'] = $this->getOrderPaymentMethodInformationByShipment($order,$shipment);
        $variables['email_footer'] = $this->dataHelper->getEmailFooter();
        $this->getSubscriptionInformation($order,$variables);
        return $variables;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $variables
     */
    public function getSubscriptionInformation(\Magento\Sales\Model\Order $order, &$variables)
    {
        //already order exist
        if(in_array($variables['order_type'],
                [
                    \Riki\Sales\Helper\Order::RIKI_TYPE_SUBSCRIPTION,
                    \Riki\Sales\Helper\Order::RIKI_TYPE_HANPUKAI,
                    \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT
                ]
            )
            || $order->getSubscriptionProfileId()
        )
        {
            $profileId = $order->getSubscriptionProfileId();
            if($profileId)
            {
                try{
                    $profileObject = $this->profileRepository->get($profileId);
                    $courseId = $profileObject->getCourseId();
                    $courseObject = $this->courseFactory->create()->load($courseId);
                    if($courseObject)
                    {
                        $variables['hanpukai_course_name'] = $courseObject->getName();
                        $variables['subscription_course_name'] = $courseObject->getName();
                    }
                    $variables['order_time'] = $profileObject->getOrderTimes();
                    $variables['order_number_time'] = $courseObject->getData('hanpukai_maximum_order_times');
                }catch(\Exception $e)
                {
                    $this->_logger->info($e->getMessage());
                }
            }
        }
    }//end function

    /**
     * @return array
     */
    public function getProductStockTranslate()
    {
        $this->loadTranslationPartial();
        return
            [
                'product_name' => __("Product Name"),
                'product_stopalert' => __("Click here to stop alerts for this product"),
                'unsubcrible' => __("Unsubscribe from all stock alerts")
            ];

    }

    /**
     * @param $products
     * @return string
     */
    public function processProductUnavailable($products)
    {
        $content = '';
        foreach($products as  $address => $productList)
        {
            foreach($productList as $_product)
            {
                $content .= self::NEWLINE . $_product['name']; //product name
                $content .= self::NEWLINE;
            }
        }
        return $content;
    }
    /**
     * @param $products
     * @return string
     */
    public function processProductUnavailableAdmin($products)
    {
        $content = '';
        foreach($products as $productList)
        {
            foreach($productList as $_product)
            {
                if($_product['type'] == 'bundle'){
                    $content .= self::NEWLINE . sprintf(__("Unavailable partially product Sku: %s"),$_product['sku']);
                } else {
                    $content .= self::NEWLINE . sprintf(__("Unavailable product Sku: %s"),$_product['sku']);
                }

            }
        }
        return $content;
    }

    /**
     * load
     */
    public function loadTranslationPartial()
    {
        $area = $this->areaList->getArea($this->state->getAreaCode());
        $area->load(\Magento\Framework\App\Area::PART_TRANSLATE);
    }

    /**
     * Get price format with currency
     *
     * @param float $price
     * @return string
     */
    public function priceCurrency($price)
    {
        return $this->priceHelper->currency($price, true, false);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return float|null
     */
    public function getFinalGrandTotal(\Magento\Sales\Model\Order $order)
    {

        $grandTotalAmount = $order->getSubtotalInclTax()
            + $order->getFee()
            + $order->getShippingAmount()
            + $order->getShippingTaxAmount()
            + $order->getData('gw_items_price_incl_tax')
            + $order->getDiscountAmount()
            - $order->getUsedPointAmount();
        return $grandTotalAmount;
    }

    /**
     * @param $city
     * @return bool
     */
    public function isCityNull($city)
    {
        $values = array("none",__("City Null"));
        if(!in_array(strtolower($city),$values))
        {
            return $city;
        }
        else
        {
            return '';
        }
    }
    /**
     * @param  $profileId
     * @return Array $variables
     */
    public function getSubscriptionSimulateOrderInformation($profileId)
    {
        $variables = [];
            if($profileId)
            {
                try{
                    $profileObject = $this->profileRepository->get($profileId);
                    $courseId = $profileObject->getCourseId();
                    $courseObject = $this->courseFactory->create()->load($courseId);
                    if($courseObject)
                    {
                        $variables['hanpukai_course_name'] = $courseObject->getName();
                        $variables['subscription_course_name'] = $courseObject->getName();
                    }
                    $variables['order_time'] = $profileObject->getOrderTimes();
                    $variables['order_number_time'] = $courseObject->getData('hanpukai_maximum_order_times');
                    
                }catch(\Exception $e)
                {
                    $this->_logger->info($e->getMessage());
                }
            }
        return $variables;
    }
    

}
