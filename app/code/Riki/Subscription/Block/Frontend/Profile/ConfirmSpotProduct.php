<?php

namespace Riki\Subscription\Block\Frontend\Profile;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Registry;
use Magento\Customer\Model\AddressFactory;
use Riki\DeliveryType\Model\DeliveryDate;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Pricing\Adjustment\CalculatorInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Riki\Subscription\Helper\Order\Simulator;
use Riki\Subscription\Block\Adminhtml\Profile\ConfirmSpotProduct as BackendConfirm;
use Riki\Subscription\Helper\Profile\Data as ProfileHelper;
use Magento\Framework\DataObject;
use Riki\SubscriptionCourse\Model\Course as CourseModel;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay;
use Riki\StockPoint\Model\Api\BuildStockPointPostData;

class ConfirmSpotProduct extends Template
{
    /**
     * @var Registry
     */
    protected $_registry;
    /**
     * @var AddressFactory
     */
    protected $_customerAddress;
    /**
     * @var DeliveryDate
     */
    protected $_deliveryDate;
    /**
     * @var Image
     */
    protected $_helperImage;
    /**
     * @var ProductRepositoryInterface
     */
    protected $_productRepository;
    /**
     * @var CalculatorInterface
     */
    protected $_adjustmentCalculator;
    /**
     * @var PriceCurrencyInterface
     */
    protected $_priceCurrency;
    /**
     * @var FormatInterface
     */
    protected $_localeFormat;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;
    /**
     * @var Simulator
     */
    protected $_simulator;
    /**
     * @var BackendConfirm
     */
    protected $_backendConfirm;
    /**
     * @var ProfileHelper
     */
    protected $_profileHelper;
    /**
     * @var CourseModel
     */
    protected $_courseModel;
    /**
     * @var CaseDisplay
     */
    protected $_caseDisplay;
    /**
     * @var \Riki\SubscriptionFrequency\Helper\Data
     */
    protected $frequencyHelper;

    protected $address;
    /**
     * @var \Riki\Subscription\Helper\Data
     */
    protected $subscriptionHelper;
    /**
     * @var \Riki\StockPoint\Helper\Data
     */
    private $stockPointHelper;

    /*qty of new spot product*/
    protected $newSpotProductQty = 0;

    /*qty based on unit of new spot product, include unit*/
    protected $newSpotProductQtyAndUnit = "";

    /*price of new spot product*/
    protected $newSpotProductAmount = "";

    /*subtotal of new spot product*/
    protected $newSpotProductTotalAmount = "";

    /**
     * item list of profile
     *
     * @var array
     */
    protected $existedProfileItems;

    /**
     * @var \Riki\Subscription\Helper\StockPoint\Data
     */
    protected $stockPointData;

    /**
     * @var \Riki\StockPoint\Api\BuildStockPointPostDataInterface
     */
    protected $buildStockPointPostData;

    /**
     * ConfirmSpotProduct constructor.
     *
     * @param Template\Context $context
     * @param Registry $registry
     * @param AddressFactory $customerAddress
     * @param DeliveryDate $deliveryDate
     * @param Image $image
     * @param ProductRepositoryInterface $productRepository
     * @param CalculatorInterface $adjustment
     * @param PriceCurrencyInterface $priceCurrency
     * @param FormatInterface $localeFormat
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Simulator $simulator
     * @param BackendConfirm $backendConfirm
     * @param ProfileHelper $profileHelper
     * @param CourseModel $courseModel
     * @param CaseDisplay $caseDisplay
     * @param \Riki\SubscriptionFrequency\Helper\Data $frequencyHelper
     * @param \Riki\Subscription\Helper\Data $subscriptionHelper
     * @param \Riki\StockPoint\Helper\Data $stockPointHelper
     * @param \Riki\Subscription\Helper\StockPoint\Data $stockPointData
     * @param \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Registry $registry,
        AddressFactory $customerAddress,
        DeliveryDate $deliveryDate,
        Image $image,
        ProductRepositoryInterface $productRepository,
        CalculatorInterface $adjustment,
        PriceCurrencyInterface $priceCurrency,
        FormatInterface $localeFormat,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Simulator $simulator,
        BackendConfirm $backendConfirm,
        ProfileHelper $profileHelper,
        CourseModel $courseModel,
        CaseDisplay $caseDisplay,
        \Riki\SubscriptionFrequency\Helper\Data $frequencyHelper,
        \Riki\Subscription\Helper\Data $subscriptionHelper,
        \Riki\StockPoint\Helper\Data $stockPointHelper,
        \Riki\Subscription\Helper\StockPoint\Data $stockPointData,
        \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData,
        array $data = []
    ) {
        $this->_caseDisplay = $caseDisplay;
        $this->_registry = $registry;
        $this->_customerAddress = $customerAddress;
        $this->_deliveryDate = $deliveryDate;
        $this->_helperImage = $image;
        $this->_productRepository = $productRepository;
        $this->_adjustmentCalculator = $adjustment;
        $this->_priceCurrency = $priceCurrency;
        $this->_localeFormat = $localeFormat;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_simulator = $simulator;
        $this->_backendConfirm = $backendConfirm;
        $this->_profileHelper = $profileHelper;
        $this->_courseModel = $courseModel;
        $this->frequencyHelper = $frequencyHelper;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->stockPointHelper = $stockPointHelper;
        $this->stockPointData = $stockPointData;
        $this->buildStockPointPostData = $buildStockPointPostData;
        parent::__construct($context, $data);
    }

    /**
     * get profile
     *
     * @return mixed
     */
    public function getProfile()
    {
        return $this->_registry->registry('profile');
    }

    /**
     * Get the SPOT product which is added to Subscription
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface $product
     */
    public function getProductAdd()
    {
        $product = $this->getNewAddedProduct();

        $unitQty = 1;

        if ($product->getData('case_display') == CaseDisplay::CD_CASE_ONLY
            && $product->getData('unit_qty')
        ) {
            $unitQty = (int) $product->getData('unit_qty');
        }

        $unit = $this->_caseDisplay->getCaseDisplayText(
            $product->getData('case_display')
        );

        /*qty of spot product will be add to profile - based on piece data*/
        $qtyToAssigned = $this->getNewSpotProductQtyAssigned();
        /*qty based on unit*/
        $qtyBasedOnUnit = (int) ($qtyToAssigned / $unitQty);

        /*product price*/
        $amount = (float) $this->getAmount($product, $qtyToAssigned) * $unitQty;

        /*total amount*/
        $totalAmount = (float) ($amount * ($qtyToAssigned / $unitQty));

        $this->newSpotProductQty = $qtyToAssigned;
        $this->newSpotProductQtyAndUnit = $qtyBasedOnUnit . '' . $unit;
        $this->newSpotProductAmount = $this->formatCurrency($amount);
        $this->newSpotProductTotalAmount = $this->formatCurrency($totalAmount);

        return $product;
    }

    /**
     * get qty of new spot product
     *
     * @return int
     */
    public function getNewSpotProductQty()
    {
        if (!$this->newSpotProductQty) {
            $this->getProductAdd();
        }

        return $this->newSpotProductQty;
    }

    /**
     * get qty of new spot product based on unit, include unit
     *
     * @return int
     */
    public function getNewSpotProductQtyAndUnit()
    {
        if (!$this->newSpotProductQtyAndUnit) {
            $this->getProductAdd();
        }

        return $this->newSpotProductQtyAndUnit;
    }

    /**
     * get price of new spot product
     *
     * @return string
     */
    public function getNewSpotProductAmount()
    {
        if (!$this->newSpotProductAmount) {
            $this->getProductAdd();
        }

        return $this->newSpotProductAmount;
    }

    /**
     * get total amount (sub total) of new spot product
     *
     * @return string
     */
    public function getNewSpotProductTotalAmount()
    {
        if (!$this->newSpotProductTotalAmount) {
            $this->getProductAdd();
        }

        return $this->newSpotProductTotalAmount;
    }

    /**
     * @return mixed
     */
    public function getNewAddedProduct()
    {
        $product = $this->_registry->registry('product_add');

        if (!$product instanceof \Magento\Catalog\Model\Product) {
            return $product;
        }

        return $this->stockPointHelper->initStockPointDataForProduct($this->getProfile(), $product);
    }

    /**
     * qty of spot product will be added to profile
     *
     * @return mixed
     */
    public function getNewSpotProductQtyAssigned()
    {
        return $this->_registry->registry('new_spot_product_qty_assigned');
    }

    /**
     * Get array full cart data
     *
     * @return array $cartDataItems[
     *      'cart_item'     profile cart data
     *      'product'       product detail data
     * ]
     */
    public function getCartItems()
    {
        if ($this->existedProfileItems === null) {
            $result = [];

            $items = $this->_profileHelper->getProfileItemsByProfile($this->getProfile());

            foreach ($items as $item) {
                if ($item->getData('parent_item_id')) {
                    continue;
                }

                $productId = $item->getProductId();
                $result[$productId]['cart_item'] = $item;

                $product = $item->getProduct();

                if ($product) {
                    $orderQty = $result[$productId]['cart_item']['qty'];
                    $case = $this->getCaseDisplayKey($product->getData('case_display'));
                    if ($case == CaseDisplay::PROFILE_UNIT_CASE && $product->getData('unit_qty')) {
                        $unitQty = $product->getData('unit_qty');
                    } else {
                        $unitQty = 1;
                    }
                    $amount = $this->getAmount($product, $orderQty) * $unitQty;
                    $totalAmount = $amount * ($orderQty / $unitQty);
                    $product->setAmount($this->formatCurrency($amount));
                    $product->setQty($orderQty);
                    $product->setTotalAmount($this->formatCurrency($totalAmount));
                    $result[$productId]['product'] = $product;
                }
            }

            $this->existedProfileItems = $result;
        }

        return $this->existedProfileItems;
    }

    /**
     * get first cart item to pick some single data: address, slot ...
     *
     * @return array|bool
     */
    public function getFirstCartItem()
    {
        if ($cartItems = $this->getCartItems()) {
            $firstItem = array_values($cartItems)[0];
            if (isset($firstItem['cart_item']) && $firstItem['cart_item']->getCartId()) {
                return $firstItem['cart_item'];
            }
        }
        return false;
    }

    /**
     * get first address
     *
     * @return $this|bool|\Magento\Customer\Model\Address
     */
    public function getFirstAddress()
    {
        $address = $this->_customerAddress->create();
        $firstItem = $this->getFirstCartItem();
        if ($firstItem) {
            $addressId = $firstItem->getData('shipping_address_id');
            $address = $address->load($addressId);
            if ($address->getId()) {
                return $address;
            } else {
                return $this->getNextAddress($address);
            }
        }

        return false;
    }

    /**
     * There is one address has problem - get next address or use billing address instead
     *
     * @param \Magento\Customer\Model\AddressFactory $address
     * @return \Magento\Customer\Model\Address|bool
     */
    public function getNextAddress($address)
    {
        // get address from cache
        if ($this->address) {
            return $this->address;
        }

        $cartItems = $this->getCartItems();
        foreach ($cartItems as $item) {
            if (!array_key_exists('cart_item', $item)) {
                continue;
            }
            $cart = $item['cart_item'];
            $address = $address->load($cart->getData('shipping_address_id'));
            if ($address->getId()) {
                $this->address = $address;
                return $address;
            }
        }
        foreach ($cartItems as $item) {
            if (!array_key_exists('cart_item', $item)) {
                continue;
            }
            $cart = $item['cart_item'];
            $address = $address->load($cart->getData('billing_address_id'));
            if ($address->getId()) {
                $this->address = $address;
                return $address;
            }
        }
        // have no address valid
        return false;
    }

    /**
     * get address name
     *
     * @return mixed|null
     */
    public function getAddressName()
    {
        $address = $this->getFirstAddress();
        if ($address->getCustomAttribute('riki_nickname')) {
            return $address->getCustomAttribute('riki_nickname')->getValue();
        }
        return null;
    }

    /**
     * get address data
     *
     * @return string
     */
    public function getAddressData()
    {
        $address = $this->getFirstAddress();
        if ($address->getCustomAttribute('apartment') != null) {
            $apartment = $address->getCustomAttribute('apartment')->getValue();
        } else {
            $apartment = '';
        }
        $data = [
            $address->getPostcode(),
            $address->getRegion(),
            trim($address->getStreetLine(1)),
            $apartment
        ];
        return implode(' ', $data);
    }

    /**
     * get time slot
     *
     * @return bool
     */
    public function getTimeSlot()
    {
        $timeSlots = $this->_deliveryDate->getListTimeSlot();
        $firstItem = $this->getFirstCartItem();
        if ($timeSlots && $firstItem) {
            $slotId = $firstItem->getData('delivery_time_slot');
            foreach ($timeSlots as $slot) {
                if ($slot['value'] == $slotId) {
                    return $slot['label'];
                }
            }
        }

        return false;
    }

    /**
     * get product image
     *
     * @param $product
     * @return $this
     */
    public function getProductImage($product)
    {
        return $this->_helperImage->init($product, 'cart_page_product_thumbnail')
            ->keepFrame(true)
            ->constrainOnly(true)
            ->resize(160, 160);
    }

    /**
     * @param $product
     * @param int $qty
     * @return float
     */
    public function getAmount($product, $qty = 1)
    {
        return $this->subscriptionHelper->getProductPriceInProfileEditPage($product, $qty);
    }

    /**
     * @param $price
     * @param null $websiteId
     * @return mixed
     */
    public function formatCurrency($price, $websiteId = null)
    {
        return $this->_storeManager->getWebsite($websiteId)->getBaseCurrency()->format($price);
    }

    /**
     * Simulator with object data
     *
     * @return object|bool
     */
    public function simulator()
    {
        /**
         * Get data simulator if it has
         */
        $simulatedOrder = $this->_registry->registry('simulate_order_after_add_spot_product');
        if ($simulatedOrder) {
            return $simulatedOrder;
        }

        $objectData = $this->prepareSimulator();
        if ($objectData) {
            return $this->_simulator->createSimulatorOrderHasData($objectData);
        }
        return false;
    }

    /**
     * prepare data before simulate
     *
     * @return bool|DataObject
     */
    public function prepareSimulator()
    {
        $profile = $this->getProfile();
        if ($profile) {
            $productAddData = $this->getProductAddData();
            // Warning: data called from other method, can be updated!
            $productCartData = $this->_backendConfirm->makeProductCartData($profile->getId(), $productAddData);
            $courseModel = $this->_courseModel->load($profile->getData('course_id'));

            $obj = new DataObject();
            $obj->setData($profile->getData());
            $obj->setData('course_data', $courseModel);
            $obj->setData("product_cart", $productCartData);
            return $obj;
        }
        return false;
    }

    /**
     * Parse data for new product add
     *
     *  $arrResult = array();
     *  $arrResult['product_id'] = 424;
     *  $arrResult['qty'] = 2;
     *  $arrResult['product_type'] = 'simple';
     *  $arrResult['product_options'] = '';
     *  $arrResult['unit_case'] = 'EA';
     *  $arrResult['unit_qty'] = 2;
     *  $arrResult['gw_id'] = '';
     *  $arrResult['gift_message_id'] = '';
     */
    public function getProductAddData()
    {
        $product = $this->getProductAdd();
        $qty = $this->getNewSpotProductQty();
        $caseDisplay = $this->getCaseDisplayKey($product->getData('case_display'));
        $unit = $this->validateQtyPieceCase($product->getData('case_display'), $product->getData('unit_qty'));

        $data = [
            'product_id' => $product->getId(),
            'qty' => $qty,
            'product_type' => $product->getTypeId(),
            'product_options' => '',
            'unit_case' => $caseDisplay,
            'unit_qty' => $unit,
            'gw_id' => '',
            'gift_message_id' => ''
        ];
        return $data;
    }

    /**
     * create object data for simulate process
     *
     * @param $profileId
     * @param $arrNewProductData
     * @return DataObject
     */
    public function makeObjectDataForSimulate($profileId, $arrNewProductData)
    {
        $profileModel = $this->loadProfileModel();
        $productCartData = $this->makeProductCartData($profileId, $arrNewProductData);
        $obj = new DataObject();
        $obj->setData($profileModel->getData());
        $obj->setData('course_data', $this->getCourseData($profileModel->getData('course_id')));
        $obj->setData("product_cart", $productCartData);
        return $obj;
    }

    /**
     * Get shipping fee include tax
     *
     * @param $shippingFee
     *
     * @return float
     */
    public function getShippingFeeIncludeTax($shippingFee)
    {
        return $this->_profileHelper->getShippingInclueTax($shippingFee, $this->_storeManager->getStore()->getId());
    }

    /**
     * get back url
     *
     * @return mixed
     */
    public function getBackUrl()
    {
        return $this->_registry->registry('back_url');
    }

    /**
     * get profile helper
     *
     * @return ProfileHelper
     */
    public function getProfileHelper()
    {
        return $this->_profileHelper;
    }

    /**
     * get product qty base on unit, include unit
     *
     * @param $product
     * @return string
     */
    public function getProductUnitCase($product)
    {
        $qty = $this->_caseDisplay->getQtyPieceCaseForDisplay(
            $product->getData('unit_qty'),
            $product->getQty(),
            $product->getData('case_display'),
            false
        );
        $unit = $this->_caseDisplay->getCaseDisplayText($product->getData('case_display'));
        return $qty . ' ' . $unit;
    }

    /**
     * get item unit case
     *
     * @param $item
     * @return string
     */
    public function getItemUnitCase($item)
    {
        $qty = $this->_caseDisplay->getQtyPieceCaseForDisplay(
            $item->getData('unit_qty'),
            $item->getQty(),
            $item->getData('unit_case')
        );
        $unit = __($item->getData('unit_case'));
        return $qty . ' ' . $unit;
    }

    /**
     * get case display
     *
     * @param $case
     * @return string
     */
    public function getCaseDisplayKey($case)
    {
        return $this->_caseDisplay->getCaseDisplayKey($case);
    }

    /**
     * get qty piece case for saving
     *
     * @param $display
     * @param $unitQty
     * @param $productQty
     * @return int
     */
    public function getQtyPieceCaseForSaving($display, $unitQty, $productQty)
    {
        return $this->_caseDisplay->getQtyPieceCaseForSaving($display, $unitQty, $productQty);
    }

    /**
     * validate qty piece case
     *
     * @param $display
     * @param $unitQty
     * @return int
     */
    public function validateQtyPieceCase($display, $unitQty)
    {
        return $this->_caseDisplay->validateQtyPieceCase($display, $unitQty);
    }

    /**
     * format frequency
     *
     * @param $interval
     * @param $unit
     * @return string
     */
    public function formatFrequency($interval, $unit)
    {
        return $this->frequencyHelper->formatFrequency($interval, $unit);
    }

    /**
     * Is show address stock point
     *
     * @param $profileData
     * @return boolean
     */
    public function isShowAddressStockPoint($profileData)
    {
        if ($profileData) {
            $bucketId = $profileData->getData('stock_point_profile_bucket_id');
            $stockPointDeliveryType = $profileData->getData('stock_point_delivery_type');
            $pickup = ($stockPointDeliveryType == BuildStockPointPostData::PICKUP) ? true : false;
            $locker = ($stockPointDeliveryType == BuildStockPointPostData::LOCKER) ? true : false;
            if ($bucketId && ($pickup || $locker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get data address of stock point
     *
     * @param $profile
     * @return array
     */
    public function getDataAddressStockPoint($profile)
    {
        $addressData = [];
        $bucketId = $profile->getStockPointProfileBucketId();
        if ($bucketId) {
            $stockPoint = $this->stockPointData->getStockPointByBucketId($bucketId);

            if ($stockPoint) {
                $prefecture = $this->buildStockPointPostData->getRegionNameById($stockPoint->getRegionId());
                $fullAddress = [
                    '〒 ' . $stockPoint->getPostcode(),
                    $prefecture,
                    $stockPoint->getStreet()
                ];
                $addressData = [
                    'firstName' => $stockPoint->getFirstname(),
                    'firstNameKana' => $stockPoint->getFirstnameKana(),
                    'lastName' => $stockPoint->getLastname(),
                    'lastNameKana' => $stockPoint->getLastnameKana(),
                    'addressFull' => implode(" ", $fullAddress),
                    'address' => $stockPoint->getStreet(),
                    'prefecture' => $prefecture,
                    'postcode' => $stockPoint->getPostcode(),
                    'telephone' => $stockPoint->getTelephone(),
                    'deliveryInformation' => $profile->getData('stock_point_delivery_information')
                ];
            }
        }

        return $addressData;
    }
}
