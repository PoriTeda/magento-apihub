<?php

namespace Riki\DeliveryType\Helper;

use Riki\DeliveryType\Model\Delitype;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_BUFFER_DATE ='shipleadtime/shipping_buffer_days/shipping_couriers_common_buffer';

    /**
     * @var \Wyomind\PointOfSale\Model\PointOfSaleFactory
     */
    protected $posFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollection;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $productModel;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @var \Riki\DeliveryType\Model\ResourceModel\Delitype\CollectionFactory
     */
    protected $deliveryTypeCollection;

    /**
     * @var array
     */
    protected $coolNormalDmTypes = [
        Delitype::COOL,
        Delitype::NORMAl,
        Delitype::DM,
    ];

    /**
     * @var
     */
    protected $simpleStorage = [];

    const MAP_PRODUCTID_DELIVERYTYPE = 'mapProductIdAndDeliveryType';
    const LIST_DELIVERY_TYPE = 'list_delivery_type';

    /**
     * Data constructor.
     * @param \Wyomind\PointOfSale\Model\PointOfSaleFactory $posFactory
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Riki\DeliveryType\Model\ResourceModel\Delitype\CollectionFactory $deliTypeCollection
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Product $productModel
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     */
    public function __construct(
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $posFactory,
        \Magento\Framework\App\Helper\Context $context,
        \Riki\DeliveryType\Model\ResourceModel\Delitype\CollectionFactory $deliTypeCollection,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
    ) {
    
        parent::__construct($context);
        $this->deliveryTypeCollection = $deliTypeCollection;
        $this->posFactory = $posFactory;
        $this->productCollection = $productCollection;
        $this->storeManager = $storeManager;
        $this->productModel = $productModel;
        $this->dateTime = $dateTime;
    }

    /**
     * @return array
     */
    public function getCoolNormalDmTypes()
    {
        return $this->coolNormalDmTypes;
    }

    /**
     * Get config Non-working saturday of warehouse
     *
     * @param $posId
     * @return null
     */
    public function getHolidayOnSaturday($posCode)
    {
        /** @var \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\Collection $collection */
            $collection = $this->posFactory->create()->getPlaces();
            $collection->addFieldToFilter('store_code', ['store_code' => $posCode])
            ->setPageSize(1);
            $data = $collection->getFirstItem()->getHolydaySettingSaturdayEnable();

            return $data;
    }

    /**
     * Get config Non-working sunday of warehouse
     *
     * @param $posId
     * @return null
     */
    public function getHolidayOnSunday($posCode)
    {
        /** @var \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\Collection $collection */
        $collection = $this->posFactory->create()->getPlaces();
        $collection->addFieldToFilter('store_code', ['store_code' => $posCode])
        ->setPageSize(1);
        $data = $collection->getFirstItem()->getHolydaySettingSundaysEnable();

        return $data;
    }

    /**
     * @param $posCode
     * @param $day
     * @return bool
     */
    public function isSpecialHoliday($posCode, $day)
    {
        /** @var \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\Collection $collection */
        $collection = $this->posFactory->create()->getPlaces();
        $collection->addFieldToFilter('store_code', ['store_code' => $posCode])
        ->setPageSize(1);
        $data = $collection->getFirstItem()->getSpecificHolidays();

        $specialDay = explode(';', $data);
        if (in_array($day, $specialDay)) {
            return true;
        }
        return false;
    }

    protected $bufferDay;
    /**
     * @return mixed
     */
    public function getBufferDate()
    {
        if ($this->bufferDay === null) {
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $this->bufferDay = $this->scopeConfig->getValue(self::CONFIG_BUFFER_DATE, $storeScope);
        }
        return $this->bufferDay;
    }

    /**
     * @param $arrType
     * @return array
     *
     * @examples
     * $arrType = [
     *      Delitype::COOL, Delitype::COLD, Delitype::CHILLED
     * ]
     *
     * $return = [Delitype::COOL, Delitype::COLD, Delitype::CHILLED]
     */
    public function getDeliveryTypePriority($arrType)
    {
        $arrReturnType = [];

        // G1
        if (in_array(Delitype::COOL, $arrType)) {
            $arrReturnType[] = Delitype::COOL;
        } elseif (in_array(Delitype::NORMAl, $arrType)) {
            $arrReturnType[] = Delitype::NORMAl;
        } elseif (in_array(Delitype::DM, $arrType)) {
            $arrReturnType[] = Delitype::DM;
        }

        // G2
        if (in_array(Delitype::COLD, $arrType)) {
            $arrReturnType[] = Delitype::COLD;
        }

        // G3
        if (in_array(Delitype::CHILLED, $arrType)) {
            $arrReturnType[] = Delitype::CHILLED;
        }

        // G4
        if (in_array(Delitype::COSMETIC, $arrType)) {
            $arrReturnType[] = Delitype::COSMETIC;
        }

        return $arrReturnType;
    }

    /**
     * @param $myDeliveryType
     * @param $arrAllow
     * @return string
     */
    public function getDeliveryTypeNameInAllowGroup($myDeliveryType, $arrAllow)
    {
        if (in_array($myDeliveryType, $this->coolNormalDmTypes)) {
            if (in_array(Delitype::COOL, $arrAllow)) {
                return Delitype::COOL;
            }

            if (in_array(Delitype::NORMAl, $arrAllow)) {
                return Delitype::NORMAl;
            }

            return Delitype::DM;
        }

        return $myDeliveryType;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Catalog\Model\Product $product
     * @param null $shippingAddress
     * @return mixed|null
     */
    public function getDeliveryTypeProductCart(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Catalog\Model\Product $product,
        $shippingAddress = null
    ) {
        $productDeliveryType = $product->getData('delivery_type');

        if (in_array($productDeliveryType, $this->coolNormalDmTypes)) {
            $sameGroupDeliveryTypes = [$productDeliveryType];

            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            foreach ($quote->getAllItems() as $quoteItem) {
                if (!$quoteItem->isDeleted() &&
                    !$quoteItem->getParentItemId() &&
                    in_array($quoteItem->getProduct()->getData('delivery_type'), $this->coolNormalDmTypes) &&
                    $quoteItem->getData('address_id') == $shippingAddress
                ) {
                    $sameGroupDeliveryTypes[] = $quoteItem->getProduct()->getData('delivery_type');
                }
            }

            $productDeliveryType = $this->getDeliveryTypeOfCoolNormalDmGroup($sameGroupDeliveryTypes);
        }

        return $productDeliveryType;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return mixed|null
     */
    public function getDeliveryTypeQuoteItem(\Magento\Quote\Model\Quote\Item $item)
    {
        if ($parentItem = $item->getParentItem()) {
            return $parentItem->getData('delivery_type');
        }

        $product = $item->getProduct();
        $addressId = $item->getData('address_id');

        if ($quote = $item->getQuote()) {
            return $this->getDeliveryTypeProductCart(
                $quote,
                $product,
                $addressId
            );
        }

        return $product->getData('delivery_type');
    }

    /**
     * @param array $deliveryTypes
     * @return mixed|null
     */
    public function getDeliveryTypeOfCoolNormalDmGroup(array $deliveryTypes)
    {
        foreach ($this->coolNormalDmTypes as $deliveryType) {
            if (in_array($deliveryType, $deliveryTypes)) {
                return $deliveryType;
            }
        }

        return null;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return $this
     */
    public function setDeliveryTypeForQuote(\Magento\Quote\Model\Quote $quote)
    {
        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quote->getAllItems() as $item) {
            if (!$item->isDeleted()) {
                $deliveryType = $this->getDeliveryTypeQuoteItem($item);

                if ($deliveryType) {
                    $item->setData('delivery_type', $deliveryType);
                }
            }
        }

        return $this;
    }

    /**
     * @param $arrProductId
     * @return mixed
     */
    public function getMapBetweenProductIdDeliveryType($arrProductId)
    {
        asort($arrProductId);

        $key = implode("-", $arrProductId);

        if (isset($this->simpleStorage[self::MAP_PRODUCTID_DELIVERYTYPE][$key]) &&
            !empty($this->simpleStorage[self::MAP_PRODUCTID_DELIVERYTYPE][$key])
        ) {
            return $this->simpleStorage[self::MAP_PRODUCTID_DELIVERYTYPE][$key];
        }

        $productCollectionFactory = $this->productCollection->create();

        $productCollectionFactory->addAttributeToFilter('entity_id', ['in' => $arrProductId]);
        $productCollectionFactory->addAttributeToSelect('delivery_type');

        $arrReturn = [];
        if ($productCollectionFactory->getSize()) {
            foreach ($productCollectionFactory as $objProduct) {
                $arrReturn[$objProduct->getId()] = $objProduct->getData("delivery_type");
            }
        }
        $this->simpleStorage[self::MAP_PRODUCTID_DELIVERYTYPE][$key] = $arrReturn;

        return $this->simpleStorage[self::MAP_PRODUCTID_DELIVERYTYPE][$key];
    }

    /**
     * @return array
     */
    public function getArrDeliveryType()
    {

        if (isset($this->simpleStorage[self::LIST_DELIVERY_TYPE]) &&
            !empty($this->simpleStorage[self::LIST_DELIVERY_TYPE])
        ) {
            return $this->simpleStorage[self::LIST_DELIVERY_TYPE];
        }

        $delitypeCollection = $this->deliveryTypeCollection->create()->load();
        $arrayDeli = [];
        if ($delitypeCollection->getSize() > 0) {
            foreach ($delitypeCollection as $deli) {
                $arrayDeli[$deli->getCode()] = $deli->getName();
            }
        }

        $this->simpleStorage[self::LIST_DELIVERY_TYPE] = $arrayDeli;

        return $arrayDeli ;
    }

    /**
     * @param $productId
     * @return mixed
     */
    public function getDeliveryTypeSingleProduct($productId)
    {
        $storeId = $this->storeManager->getStore()->getId();
        return $this->productModel->getResource()->getAttributeRawValue($productId, 'delivery_type', $storeId);
    }

    public function formatDate($stringDate)
    {
        return $this->dateTime->date('Y-m-d', strtotime($stringDate));
    }
}
