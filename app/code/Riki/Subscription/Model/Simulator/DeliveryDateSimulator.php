<?php

namespace Riki\Subscription\Model\Simulator;

use Riki\Subscription\Model\Profile\Profile;
use \Magento\Catalog\Model\Product\Type as ProductType;

class DeliveryDateSimulator implements \Riki\Subscription\Api\Simulator\DeliveryDateSimulatorInterface
{
    /**
     * @var \Riki\DeliveryType\Helper\Data
     */
    protected $_deliveryHelper;

    /**
     * @var OrderSimulator
     */
    protected $_orderSimulator;

    /* @var \Riki\Subscription\Helper\Data */
    protected $_subHelperData;
    /**
     * @var \Riki\Subscription\Model\Profile\FreeGift
     */
    protected $_freeGiftManagement;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $_productRepository;

    /** @var \Magento\Customer\Api\AddressRepositoryInterface */
    protected $_addressRepository;


    /** @var \Riki\Customer\Helper\Address */
    protected $_customerAddressHelper;

    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter
     */
    protected $_extensibleDataObjectConverter;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $_sessionManagerInterface;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $_helperImage;

    /**
     * @var \Riki\BackOrder\Helper\Data
     */
    protected $_backOrderHelper;

    /**
     * @var \Riki\ProductStockStatus\Helper\StockData
     */
    protected $_stockData;
    /**
     * @var \Magento\Framework\Pricing\Adjustment\CalculatorInterface
     */
    protected $_adjustmentCalculator;

    /**
     * @var \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory
     */
    protected $_wrappingCollectionFactory;

    protected $_messageFactory;

    protected $_timezoneHelper;

    protected $_calculateDeliveryDate;

    protected $_objSessionProfile;

    protected $_shippingAddressId;

    protected $_restrictDate;

    protected $_allDeliveryDate;

    public function __construct(
        \Riki\DeliveryType\Helper\Data $deliveryHelper,
        \Riki\Subscription\Helper\Data $subHelperData,
        \Riki\BackOrder\Helper\Data $backOrderHelper,
        \Riki\ProductStockStatus\Helper\StockData $stockData,
        \Riki\Subscription\Model\Profile\FreeGift $freeGiftManagement,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Riki\Customer\Helper\Address $customerAddressHelper,
        \Magento\Framework\Pricing\Adjustment\CalculatorInterface $adjustmentCalculator,
        \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory
    )
    {
        $this->_deliveryHelper = $deliveryHelper;
        $this->_subHelperData = $subHelperData;
        $this->_freeGiftManagement = $freeGiftManagement;
        $this->_productRepository = $productRepository;
        $this->_addressRepository = $addressRepository;
        $this->_customerAddressHelper = $customerAddressHelper;
        $this->_backOrderHelper = $backOrderHelper;
        $this->_stockData = $stockData;
        $this->_adjustmentCalculator = $adjustmentCalculator;
        $this->_wrappingCollectionFactory = $wrappingCollectionFactory;
    }

    /**
     * Load data
     * @param $objectOrderSimulator
     */
    public function initObjectDataOrderSimulator($objectOrderSimulator)
    {
        $this->_orderSimulator = $objectOrderSimulator;
        $this->_extensibleDataObjectConverter = $this->_orderSimulator->_extensibleDataObjectConverter;
        $this->_messageFactory = $this->_orderSimulator->_messageFactory;
        $this->_timezoneHelper = $this->_orderSimulator->_timezoneHelper;
        $this->_calculateDeliveryDate = $this->_orderSimulator->_calculateDeliveryDate;
        $this->_helperImage = $this->_orderSimulator->_helperImage;
    }


    /** @inheritdoc */
    public function processDeliveryDateSimulator($objectOrderSimulator)
    {

        $this->initObjectDataOrderSimulator($objectOrderSimulator);

        $orderSimulator = $this->_orderSimulator->getDataOrderSimulator();
        $this->_objSessionProfile = $this->_orderSimulator->getEntity();

        /**
         * Lod product from session
         */
        $arrProductCat = $this->getProductCartSession();
        $freeGifts = $this->_subHelperData->getFreeGifts($orderSimulator);
        if (sizeof($freeGifts)) {
            $arrProductCat = $this->_freeGiftManagement->addFreeGiftsToCartProfile($arrProductCat, $freeGifts);
        }

        $arrReturn = [];
        /**
         * @var string $key
         * @var \Magento\Framework\DataObject $objProductData
         */
        foreach ($arrProductCat as $key => $objProductData) {
            if ($objProductData->getData(Profile::PARENT_ITEM_ID) == 0) {

                $productObj = $this->_productRepository->getById($objProductData->getData('product_id'));
                $addressId = $objProductData->getData(Profile::SHIPPING_ADDRESS_ID);

                /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
                $productDataModel = $this->getProductById($objProductData->getData('product_id'));
                $deliveryType = $productDataModel->getCustomAttribute("delivery_type");
                if ($deliveryType) {
                    $deliveryType = $deliveryType->getValue();
                }

                if (!isset($arrReturn[$addressId][$deliveryType])) {
                    $returnRecord = array();
                    try {
                        $objAddress = $this->_addressRepository->getById($addressId);
                    } catch (\Exception $e) {
                        return $arrReturn;
                    }
                    $addressName = $objAddress->getCustomAttribute('riki_nickname')->getValue();
                    $returnRecord['name'] = $addressName;

                    $lastnameKana = $objAddress->getCustomAttribute('lastnamekana') ?
                        $objAddress->getCustomAttribute('lastnamekana')->getValue() : '';

                    $firstnameKana = $objAddress->getCustomAttribute('firstnamekana') ?
                        $objAddress->getCustomAttribute('firstnamekana')->getValue() : '';

                    $returnRecord['info'] = [
                        $objAddress->getLastname(),
                        $objAddress->getFirstname(),
                        $lastnameKana,
                        $firstnameKana,
                        $objAddress->getPostcode(),
                        $objAddress->getRegion(),
                        implode(' ', $objAddress->getStreet()),
                        $objAddress->getTelephone()
                    ];
                    $returnRecord['address_html'] = $this->_customerAddressHelper->formatCustomerAddressToString($objAddress);
                    $returnRecord['delivery_date'] = [
                        'next_delivery_date' => $objProductData->getData('delivery_date'),
                        'time_slot' => $objProductData->getData('delivery_time_slot'),
                    ];

                    $arrReturn[$addressId][$deliveryType] = $returnRecord;
                }

                /* convert product data */
                $flatProductData = $this->_extensibleDataObjectConverter->toNestedArray(
                    $productDataModel,
                    [],
                    '\Magento\Catalog\Api\Data\ProductInterface'
                );

                $flatProductData["stock_message"] = $this->getStockStatus($productDataModel);
                $flatProductData["thumbnail"] = $this->getProductImagesProfile($productDataModel);

                /* calculating amount */
                if ($productDataModel->getTypeId() != 'bundle') {
                    $amount = $this->_adjustmentCalculator->getAmount(
                        $productDataModel->getFinalPrice($objProductData->getData('qty')), $productDataModel)->getValue();
                } else {
                    $amount = $this->_subHelperData->getBundleMaximumPrice($productDataModel);
                }

                $arrReturn[$addressId][$deliveryType]['product'][] = [
                    'name' => !$objProductData->getData('is_free_gift') ? $productDataModel->getName() : $objProductData->getName(),
                    'price' => $productDataModel->getPrice(),
                    'qty' => $objProductData->getData('qty'),
                    'unit_case' => $objProductData->getData('unit_case'),
                    'unit_qty' => $objProductData->getData('unit_qty'),
                    'gw_id' => $objProductData->getData('gw_id'),
                    'gift_message_id' => $objProductData->getData('gift_message_id'),
                    'product_data' => $flatProductData,
                    'instance' => $productObj,
                    'productcat_id' => $objProductData->getData('cart_id'),
                    'productcart_data' => $objProductData->toArray(),
                    'gw_data' => $this->getAttributeArray($productDataModel->getData('gift_wrapping')),
                    'has_gw_data' => $productDataModel->hasData('gift_wrapping'),
                    'has_gift_message' => $productDataModel->hasData('gift_message_id'),
                    'gift_message_data' => $this->getMessage($objProductData->getData('gift_message_id')),
                    'amount' => !$objProductData->getData('is_free_gift') ? floor($amount) : 0,
                    'is_free_gift' => (bool)$objProductData->getData('is_free_gift'),
                    'allow_seasonal_skip' => $productDataModel->getData('allow_seasonal_skip'),
                    'seasonal_skip_optional' => $productDataModel->getData('seasonal_skip_optional'),
                    'allow_skip_from' => $this->getDateFormat($productDataModel->getData('allow_skip_from')),
                    'allow_skip_to' => $this->getDateFormat($productDataModel->getData('allow_skip_to')),
                    'is_skip' => ($objProductData->getData('is_skip_seasonal')) ? 1 : 0,
                    'skip_from' => $objProductData->getData('skip_from'),
                    'skip_to' => $objProductData->getData('skip_to'),
                    'is_addition' => $objProductData->getData('is_addition')
                ];
            }
        }

        // Group it
        foreach ($arrReturn as $addressId => $arrOfDeliveryType) {
            $arrDeliveryType = array_keys($arrOfDeliveryType);

            foreach ($arrOfDeliveryType as $deliveryType => $arrInfo) {
                $deliveryTypeEdited = $this->_deliveryHelper->getDeliveryTypeNameInAllowGroup($deliveryType, $arrDeliveryType);
                if ($deliveryTypeEdited == $deliveryType) continue;

                if (!isset($arrReturn[$addressId][$deliveryTypeEdited])) {
                    $arrReturn[$addressId][$deliveryTypeEdited] = $arrInfo;
                } else {
                    $arrReturn[$addressId][$deliveryTypeEdited]['product'] = array_merge($arrReturn[$addressId][$deliveryTypeEdited]['product'], $arrReturn[$addressId][$deliveryType]['product']);

                    unset($arrReturn[$addressId][$deliveryType]); // Remove after group.
                }
            }
        }
        /* append calendar setting */
        foreach ($arrReturn as $addressId => $arrOfDeliveryType) {
            foreach ($arrOfDeliveryType as $deliveryType => $arrInfo) {
                $restrictDate = $this->getHelperCalculateDateTime()->getCalendar($addressId, $arrInfo, $deliveryType, null);

                $calendarPeriod = $this->getHelperCalculateDateTime()->getCalendarPeriod();
                $arrReturn[$addressId][$deliveryType]["restrict_date"] = $restrictDate;
                $arrReturn[$addressId][$deliveryType]['is_exist_back_order_not_allow_choose_dd'] = 0;
                $arrReturn[$addressId][$deliveryType]["calendar_period"] = $calendarPeriod;
                if ($this->_shippingAddressId == $addressId) {
                    $this->_restrictDate = $restrictDate;
                }
            }
        }

        $this->_allDeliveryDate = $arrReturn;
        return $this->_allDeliveryDate;
    }

    /**
     * @param $id
     * @return \Magento\Catalog\Api\Data\ProductInterface|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductById($id)
    {
        return $this->_productRepository->getById($id);
    }

    public function getProductImagesProfile($product)
    {
        $origImageHelper = $this->_helperImage->init($product, 'product_listing_thumbnail_preview')
            ->keepFrame(true)->constrainOnly(true)->resize(160, 160);
        return $origImageHelper->getUrl();
    }

    /**
     * Get stock status
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return string
     */
    public function getStockStatus($product)
    {
        /* @var \Magento\Catalog\Model\Product $product */
        if ($product->getTypeId() != ProductType::TYPE_BUNDLE) {

            $stock = $this->_stockData->getStockStatusByEnv(
                $product,
                \Riki\ProductStockStatus\Helper\StockData::ENV_BO
            );

            $storeId = $this->getEntity()->getData("store_id");

            if ($stock == __('Out of stock') and $this->_backOrderHelper->isConfigBackOrder($product->getId(), $storeId)) {
                if ($product->getIsSalable()) {
                    $stock = __('In stock');
                }
            }
        } else {
            if ($product->getIsSalable() == true) {
                $stock = __('In stock');
            } else {
                $stock = __('Out of stock');
            }
        }
        return $stock;
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->_orderSimulator->getEntity();
    }

    /**
     * @param $attributeString
     * @return array
     * @throws \Zend_Validate_Exception
     */
    public function getAttributeArray($attributeString)
    {

        if (!\Zend_Validate::is($attributeString, 'NotEmpty')) {
            return [];
        }

        $arrayAttr = explode(',', $attributeString);
        /** @var \Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection $giftCollection */
        $giftCollection = $this->_wrappingCollectionFactory->create()
            ->addFieldToFilter('wrapping_id', array('IN' => $arrayAttr))
            ->addWebsitesToResult()->load();


        if ($giftCollection->getSize() == 0) {
            return array();
        }
        $returnedArray = $giftCollection->toArray();
        foreach ($returnedArray["items"] as $index => $arrayItem) {
            $returnedArray["items"][$index]["price_incl_tax"] = $this->calTax($arrayItem["base_price"]);
        }
        return $returnedArray;
    }

    /**
     * @param $messageId
     * @return $this
     */
    public function getMessage($messageId)
    {
        $giftMessage = $this->_messageFactory->create();
        return $giftMessage->load($messageId);

    }

    /**
     * get Date after format
     *
     * @param $date
     * @return string
     */
    public function getDateFormat($date)
    {
        return $this->_timezoneHelper->date($date)->format(\Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT);
    }

    /**
     * @return mixed
     */
    public function getHelperCalculateDateTime()
    {
        return $this->_calculateDeliveryDate;
    }

    /**
     * @return array
     */
    public function getProductCartSession()
    {
        $productCartItems = $this->_objSessionProfile->getData("product_cart");
        if ($this->_shippingAddressId == null) {
            return $productCartItems;
        }

        if (is_array($productCartItems) && count($productCartItems) > 0) {
            foreach ($productCartItems as $productId => $productCart) {
                $productCart->setData('shipping_address_id', $this->_shippingAddressId);
                $productCartItems[$productId] = $productCart;
            }
        }

        return $productCartItems;
    }

    /**
     * @param $shippingAddressId
     */
    public function setShippingAddressId($shippingAddressId)
    {
        $this->_shippingAddressId = $shippingAddressId;
    }

    /**
     * @return mixed
     */
    public function getRestrictDate()
    {
        return $this->_restrictDate;
    }

}