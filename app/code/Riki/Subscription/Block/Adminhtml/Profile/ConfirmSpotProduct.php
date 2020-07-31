<?php

namespace Riki\Subscription\Block\Adminhtml\Profile;

use Magento\Customer\Api\AddressRepositoryInterface;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay;
use Riki\TimeSlots\Model\TimeSlots;
use Magento\Framework\DataObject;
use Riki\SubscriptionCourse\Model\Course;
use Riki\Subscription\Helper\Order\Simulator;
use Riki\Subscription\Helper\Data;
use Riki\DeliveryType\Model\Delitype;
use Symfony\Component\Config\Definition\Exception\Exception;

class ConfirmSpotProduct extends \Magento\Framework\View\Element\Template
{
    const XML_PATH_TAX_ORDER_DISPLAY_CONFIG = 'tax/sales_display/shipping';

    /* @var \Magento\Framework\Registry */
    protected $_registry;

    /* @var \Riki\Subscription\Helper\Profile\Data */
    protected $_helperProfile;

    /* @var \Riki\Subscription\Model\Profile\ProfileFactory */
    protected $_profileFactory;

    /* @var \Magento\Customer\Api\AddressRepositoryInterface */
    protected $_customerAddressRepository;

    /* @var \Riki\TimeSlots\Model\TimeSlots */
    protected $_timeSlot;

    /* @var \Riki\Subscription\Model\ProductCart\ProductCart */
    protected $_productCart;

    /* @var \Riki\SubscriptionCourse\Model\Course */
    protected $_courseModel;

    /* @var \Riki\Subscription\Helper\Order\Simulator */
    protected $_simulator;

    /* @var \Magento\Catalog\Helper\Image */
    protected $_helperImage;

    /* @var  \Magento\Framework\Pricing\Adjustment\CalculatorInterface */
    protected $_adjustmentCalculator;

    /* @var \Riki\Subscription\Helper\Data */
    protected $_subHelperData;

    /* @var \Magento\Framework\Api\SearchCriteriaBuilder */
    protected $_searchCriteriaBuilder;

    /* @var \Magento\Catalog\Api\ProductRepositoryInterface */
    protected $_productRepository;

    /* @var \Magento\Framework\Stdlib\DateTime\Timezone */
    protected $_dateTime;

    /* @var \Magento\GiftWrapping\Api\WrappingRepositoryInterface */
    protected $_wrappingRepository;

    /** @var \Magento\GiftWrapping\Helper\Data */
    protected $_helperWrapping;

    /** @var \Magento\Tax\Model\TaxCalculation */
    protected $_taxCalculation;

    /**
     * @var \Riki\StockPoint\Helper\Data
     */
    private $stockPointHelper;

    private $loadedProfileModel;

    /**
     * ConfirmSpotProduct constructor.
     * @param \Magento\Tax\Model\TaxCalculation $taxCalculation
     * @param \Magento\GiftWrapping\Helper\Data $giftWrappingHelper
     * @param \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepositoryInterface
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Data $subHelperData
     * @param \Magento\Framework\Pricing\Adjustment\CalculatorInterface $adjustmentCalculator
     * @param \Magento\Catalog\Helper\Image $helperImage
     * @param Simulator $simulator
     * @param Course $courseModel
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Riki\Subscription\Model\ProductCart\ProductCart $productCart
     * @param TimeSlots $timeSlots
     * @param AddressRepositoryInterface $addressRepositoryInterface
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfileData
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $dateTime
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Riki\StockPoint\Helper\Data $stockPointHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Tax\Model\TaxCalculation $taxCalculation,
        \Magento\GiftWrapping\Helper\Data $giftWrappingHelper,
        \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepositoryInterface,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Subscription\Helper\Data $subHelperData,
        \Magento\Framework\Pricing\Adjustment\CalculatorInterface $adjustmentCalculator,
        \Magento\Catalog\Helper\Image $helperImage,
        \Riki\Subscription\Helper\Order\Simulator $simulator,
        \Riki\SubscriptionCourse\Model\Course $courseModel,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\Subscription\Model\ProductCart\ProductCart $productCart,
        TimeSlots $timeSlots,
        AddressRepositoryInterface $addressRepositoryInterface,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Helper\Profile\Data $helperProfileData,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime\Timezone $dateTime,
        \Magento\Backend\Block\Template\Context $context,
        \Riki\StockPoint\Helper\Data $stockPointHelper,
        array $data = []
    ) {
    
        $this->_taxCalculation = $taxCalculation;
        $this->_helperWrapping = $giftWrappingHelper;
        $this->_wrappingRepository = $wrappingRepositoryInterface;
        $this->_dateTime = $dateTime;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_subHelperData = $subHelperData;
        $this->_adjustmentCalculator = $adjustmentCalculator;
        $this->_helperImage = $helperImage;
        $this->_simulator = $simulator;
        $this->_courseModel = $courseModel;
        $this->_productRepository = $productRepository;
        $this->_productCart = $productCart;
        $this->_timeSlot = $timeSlots;
        $this->_customerAddressRepository = $addressRepositoryInterface;
        $this->_profileFactory = $profileFactory;
        $this->_helperProfile = $helperProfileData;
        $this->_registry = $registry;
        $this->stockPointHelper = $stockPointHelper;
        parent::__construct($context, $data);
    }

    public function getProfileId()
    {
        return $this->_registry->registry('subscription-confirm-add-spot-profile-id');
    }


    public function getProductDataInfo()
    {
        return $this->_registry->registry('subscription-confirm-add-spot-product-add-info');
    }

    /**
     * Parse data for new product add
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
    public function getNewProductData()
    {
        $arrDataNewProduct = $this->getProductDataInfo();
        return $arrDataNewProduct;
    }

    /**
     * @return mixed
     */
    public function loadProfileModel()
    {
        if ($this->loadedProfileModel === null) {
            $profileId = $this->getProfileId();
            if ($this->_helperProfile->getTmpProfile($profileId) !== false) {
                $profileId = $this->_helperProfile->getTmpProfile($profileId)->getData('linked_profile_id');
            }

            $this->loadedProfileModel = $this->_profileFactory->create()->load($profileId);
        }

        return $this->loadedProfileModel;
    }

    /**
     * Get slot object
     *
     * @param $slotId
     *
     * @return $this|null
     */
    public function getSlotObject($slotId)
    {
        $slotModel = $this->_timeSlot->load($slotId);
        if ($slotModel && $slotModel->getId()) {
            return $slotModel;
        }
        return null;
    }

    /**
     * Get shipping address
     */
    public function getProductCartStandardValue()
    {
        $arrResult['shipping_address_id'] = '';
        $arrResult['delivery_time_slot'] = '';
        $arrProduct = $this->getListProduct();
        foreach ($arrProduct as $productId => $arrData) {
            if (array_key_exists('profile', $arrData)) {
                $productCartModel = $arrData['profile'];
                if (($productCartModel instanceof \Riki\Subscription\Model\ProductCart\ProductCart)
                    && $productCartModel->getData('shipping_address_id')
                ) {
                    /**
                     * Just need find one shipping address id because subscription is same shipping address
                     * Maybe not ok for case subscription hampukai
                     */
                    $arrResult['shipping_address_id'] = $productCartModel->getData('shipping_address_id');
                    $arrResult['delivery_time_slot'] = $productCartModel->getData('delivery_time_slot');
                    return $arrResult;
                }
            }
        }
        return $arrResult;
    }

    /**
     * Get list product by shipping address id and delivery type
     *
     * @return array
     */
    public function getListProductByAddressIdAndDeliveryType()
    {
        $arrResult = [];
        $listProductInCart = $this->getListProduct();
        foreach ($listProductInCart as $productId => $arrProductInfo) {
            $deliveryType = $arrProductInfo['details']->getData('delivery_type');
            $shippingAddressId = $arrProductInfo['profile']->getData('shipping_address_id');
            if ($deliveryType == Delitype::COOL || $deliveryType == Delitype::NORMAl || $deliveryType == Delitype::DM) {
                $deliveryType = Delitype::COOL_NORMAL_DM;
            }

            $arrResult[$shippingAddressId][$deliveryType][] = $arrProductInfo;
        }

        return $arrResult;
    }

    /**
     * Get list product
     *
     * @return array
     */
    public function getListProduct()
    {
        $result = [];

        $profile = $this->loadProfileModel();

        $profileItems = $this->_helperProfile->getProfileItemsByProfile($profile);

        /** @var \Riki\Subscription\Model\ProductCart\ProductCart $profileItem */
        foreach ($profileItems as $profileItem) {
            $result[$profileItem->getProductId()] = [
                'profile'   =>  $profileItem,
                'details'   =>  $profileItem->getProduct()
            ];
        }

        return $result;
    }

    /**
     * Get address detail by address id
     *
     * @param $addressId
     *
     * @return mixed
     * @throws \Exception
     */
    public function getAddressDetail($addressId)
    {
        $customerShippingAddress = $this->_customerAddressRepository->getById($addressId);

        if ($customerShippingAddress instanceof \Magento\Customer\Model\Data\Address) {
            if ($rikiNicknameObj = $customerShippingAddress->getCustomAttribute('riki_nickname')) {
                $rikiNickname = $rikiNicknameObj->getValue();
            } else {
                $rikiNickname = '';
            }

            if ($rikiFirstnameKanaObj = $customerShippingAddress->getCustomAttribute('firstnamekana')) {
                $rikiFirstnameKana = $rikiFirstnameKanaObj->getValue();
            } else {
                $rikiFirstnameKana = '';
            }

            if ($rikiLastnameKanaObj = $customerShippingAddress->getCustomAttribute('lastnamekana')) {
                $rikiLastnameKana = $rikiLastnameKanaObj->getValue();
            } else {
                $rikiLastnameKana = '';
            }

            if ($rikiTypeAddressObj = $customerShippingAddress->getCustomAttribute('riki_type_address')) {
                $rikiTypeAddress = $rikiTypeAddressObj->getValue();
            } else {
                $rikiTypeAddress = '';
            }
        } else {
            $rikiNickname
                = $customerShippingAddress ? $customerShippingAddress->getData('riki_nickname') : '';
            $rikiFirstnameKana
                = $customerShippingAddress ? $customerShippingAddress->getData('firstnamekana') : '';
            $rikiLastnameKana
                = $customerShippingAddress ? $customerShippingAddress->getData('lastnamekana') : '';
            $rikiTypeAddress
                = $customerShippingAddress ? $customerShippingAddress->getData('riki_type_address') : '';
        }
        $arrReturn['lastname'] = $customerShippingAddress->getLastname();
        $arrReturn['firstname'] = $customerShippingAddress->getFirstname();
        $arrReturn['riki_nickname'] = $rikiNickname;
        $arrReturn['riki_firstnamekana'] = $rikiFirstnameKana;
        $arrReturn['riki_lastnamekana'] = $rikiLastnameKana;
        $arrReturn['riki_type_address'] = $rikiTypeAddress;
        $arrReturn['telephone'] = $customerShippingAddress ? $customerShippingAddress->getTelephone() : '';
        return $arrReturn;
    }

    public function getCustomerAddressByText($addressKey)
    {
        // Get all Address of current customer
        $objAddress = $this->_customerAddressRepository->getById($addressKey);
        $arrAddress = [
            $objAddress->getPostcode(),
            $objAddress->getRegion()->getRegion(),
            implode(',', $objAddress->getStreet()),
        ];

        $arrReturn = "〒" . implode(" ", $arrAddress);

        return $arrReturn;
    }


    /**
     * Need profile model data and product cart data
     *
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
     * Make product cart data and new product add
     *
     * @param $profileId
     * @param $arrNewProductData
     *
     * @return array|void
     */
    public function makeProductCartData($profileId, $arrNewProductData)
    {
        if ($this->_helperProfile->getTmpProfile($profileId) !== false) {
            $profileId = $this->_helperProfile->getTmpProfile($profileId)->getData('linked_profile_id');
        }
        $productCartCollection = $this->_productCart->getCollection();
        $productCartCollection->addFieldToFilter('profile_id', $profileId);

        $data = [];
        foreach ($productCartCollection->getItems() as $item) {
            try {
                $productModel = $this->_productRepository->getById($item->getData('product_id'));
                if ($productModel && $productModel->getStatus() == 1) {
                    $obj = new DataObject();
                    $obj->setData($item->getData());
                    $data[$obj->getData("cart_id")] = $obj;
                }
            } catch (\Exception $e) {
                $this->_logger->error('Product ID #' . $item->getData('product_id') . ' was delete');
            }
        }

        if ($this->getArea()==\Magento\Framework\App\Area::AREA_FRONTEND) {
            $data = $this->addNewProductToDataObject($data, $arrNewProductData);
        } else {
            $arrNewsProductAdd = [];
            $currentObAddCart = null;
            foreach ($arrNewProductData as $newProduct) {
                $currentObAddCart = $this->addNewProductToDataObject($data, $newProduct);
                if (isset($currentObAddCart['new_product'])) {
                    $objectProductNew = $currentObAddCart['new_product'] ;
                    $arrNewsProductAdd['new_product_'.$objectProductNew->getProductId()] =$objectProductNew;
                }
            }

            if (is_array($arrNewsProductAdd) && count($arrNewsProductAdd)>0 && $currentObAddCart !=null) {
                if (isset($currentObAddCart['new_product'])) {
                    unset($currentObAddCart['new_product']);
                }
                $data = array_merge($currentObAddCart, $arrNewsProductAdd);
            }
        }

        return $data;
    }

    /**
     * Add new product to data object
     *
     * @param $data
     * @param $arrNewProductData
     * @return mixed
     */
    public function addNewProductToDataObject($data, $arrNewProductData)
    {
        // get first item data standard and replaced data
        $newProductObj = $this->loadProductById($arrNewProductData['product_id']);
        if (count($data) > 0) {
            foreach ($data as $productCartId => $value) {
                $obj = new DataObject();
                $obj->setData($value->getData());
                $obj->setData('product_id', $arrNewProductData['product_id']);
                $obj->setData('qty', $arrNewProductData['qty']);
                $obj->setData('product_type', $newProductObj->getTypeId());
                $obj->setData('product_options', $arrNewProductData['product_options']);
                $obj->setData('updated_at', (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
                $obj->setData('unit_case', $arrNewProductData['unit_case']);
                $obj->setData('unit_qty', $arrNewProductData['unit_qty']);
                $obj->setData('gw_id', $arrNewProductData['gw_id']);
                $obj->setData('gift_message_id', $arrNewProductData['gift_message_id']);
                $obj->setData('parent_item_id', 0);
                $obj->setData('gw_used', null);
                $obj->setData('gift_message_id', null);
                $obj->setData('is_skip_seasonal', null);
                $obj->setData('skip_from', null);
                $obj->setData('skip_to', null);
                $obj->setData('is_spot', true);
                $obj->setData('cart_id', 'new_product');
                $data[$obj->getData('cart_id')] = $obj;
                return $data;
            }
        }
        return $data;
    }

    /**
     * Get course data
     *
     * @return mixed
     * @throws \Exception
     */
    public function getCourseData($courseId)
    {
        return $this->_courseModel->load($courseId);
    }

    /**
     * Get image url
     *
     * @param $product
     *
     * @return string
     */
    public function getImageUrl($product)
    {
        /* @var $product \Magento\Catalog\Model\Product */
        return $this->_helperImage->init($product, 'product_listing_thumbnail_preview')
            ->keepFrame(true)
            ->constrainOnly(true)
            ->resize(110, 110);
    }

    /**
     * Simulator with object data
     *
     * @param $objectData
     * @return array|bool
     */
    public function simulator($objectData)
    {
        return $this->_simulator->createSimulatorOrderHasData($objectData);
    }

    /**
     * @param $product
     * @param int $qty
     * @return array
     */
    public function getProductPriceInfo($product, $qty = 1)
    {
        $price = $this->getProductPrice($product, $qty);

        return [
            'price' =>  $price,
            'total' =>  $price * (int)($qty / $this->getUnitQty($product))
        ];
    }

    /**
     * @param $product
     * @param int $qty
     * @return mixed|string
     */
    public function getProductPrice($product, $qty = 1)
    {
        $price = $this->_subHelperData->getProductPriceInProfileEditPage($product, $qty);

        $unitQty = $this->getUnitQty($product);

        return $price * $unitQty;
    }

    /**
     * @param $product
     * @return int|mixed
     */
    public function getUnitQty($product)
    {
        $unitQty = 1;

        if ($product->getCaseDisplay() == CaseDisplay::CD_CASE_ONLY) {
            $unitQty = max($unitQty, (int)$product->getUnitQty());
        }

        return $unitQty;
    }

    /**
     * Get product amount
     *
     * @param $product
     *
     * @return float|int
     * @throws \Exception
     */
    public function getProductAmount($product)
    {
        /* @var $product \Magento\Catalog\Model\Product */
        if ($product->getTypeId() != 'bundle') {
            $finalPrice = $product->getFinalPrice(1) ?: 0;
            $amount = $this->_adjustmentCalculator->getAmount($finalPrice, $product)->getValue();
            return $amount ? $amount : 0;
        } else {
            $price = $this->_subHelperData->getBundleMaximumPrice($product);
            return $price ? $price : 0;
        }
    }

    /**
     * Format price
     *
     * @param $price
     * @param null $websiteId
     * @return mixed
     */
    public function formatCurrency($price, $websiteId = null)
    {
        return $this->_storeManager->getWebsite($websiteId)->getBaseCurrency()->format($price);
    }

    /**
     * @param $productId
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function loadProductById($productId)
    {
        return $this->_productRepository->getById($productId);
    }

    /**
     * @param $productId
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function loadNewAddedProduct($productId)
    {
        return $this->stockPointHelper->initStockPointDataForProduct(
            $this->loadProfileModel(),
            $this->loadProductById($productId)
        );
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
        return $this->_helperProfile->getShippingInclueTax($shippingFee, $this->_storeManager->getStore()->getId());
    }

    /**
     * Get Day
     *
     * @param $stringDate
     *
     * @return string
     */
    public function getDay($stringDate)
    {
        $timestamp = strtotime($stringDate);
        $day = date('D', $timestamp);
        return $day;
    }

    /**
     * Get wrapping title
     *
     * @param $gwId
     * @param $storeId
     *
     * @return string
     */
    public function getWrappingTitle($gwId, $storeId, $qty)
    {
        try {
            $title = '';
            $gwModel = $this->_wrappingRepository->get($gwId, $storeId);
            if ($gwModel instanceof \Magento\GiftWrapping\Model\Wrapping) {
                $title = $gwModel->getData('gift_name');
                $giftPrice = $this->getGiftPriceIncludeTax($gwModel->getBasePrice() * $qty);
                $title = $title . ' (' . $giftPrice . ')';
            }
            return $title;
        } catch (\Exception $e) {
            throw new Exception("Can not load wrapping fee");
        }
    }

    /**
     * Get gift price with tax
     *
     * @param $giftPrice
     *
     * @return string
     */
    public function getGiftPriceIncludeTax($giftPrice)
    {
        $wrappingTax = $this->_helperWrapping->getWrappingTaxClass($this->_storeManager->getStore());
        $wrappingRate = $this->_taxCalculation->getCalculatedRate($wrappingTax);
        if ($giftPrice > 0) {
            $taxRate = $wrappingRate / 100;
            $giftPrice = $giftPrice + ($taxRate * $giftPrice);
        }
        return $this->formatCurrency($giftPrice);
    }

    /**
     * @param $productCartData
     * @return array
     */
    public function getListProductAddSport($productCartData)
    {
        $arrNewProduct = [];
        foreach ($productCartData as $keyNewProduct => $objectNewProduct) {
            if (strpos($keyNewProduct, 'new_product') !==false) {
                $arrNewProduct[] = $objectNewProduct;
            }
        }
        return $arrNewProduct;
    }
}
