<?php
namespace Riki\ShipLeadTime\Helper;

use Magento\Customer\Api\AddressRepositoryInterface;
use Riki\AdvancedInventory\Model\Assignation;
use Magento\Framework\Api\SortOrder;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $regionCollection;
    protected $deliveryTypeCollection;
    protected $pointOfSaleCollection;

    protected $leadTimeRepositoryInterface;

    /**
     * Filter builder
     *
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * Search criteria builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /** @var \Riki\PointOfSale\Helper\Data  */
    protected $pointOfSaleHelper;

    /** @var AddressRepositoryInterface  */
    protected $customerAddressRepository;

    /** @var Assignation  */
    protected $assignationModel;

    /**
     * @var SortOrder
     */
    protected $sortOrder;

    /**
     * @var \Riki\ShipLeadTime\Model\ResourceModel\Leadtime\CollectionFactory
     */
    protected $shipLeadTimeCollectionFactory;

    protected $loadedShipLeadTimesByPlaceRegion = [];

    protected $shipLeadTimeByCondition = [];

    protected $placeDeliveryTypeRegionLeadTimeStatus = [];

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Riki\DeliveryType\Model\ResourceModel\Delitype\CollectionFactory $deliveryCollection
     * @param \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory $pointOfSaleCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\PointOfSale\Helper\Data $pointOfSaleHelper
     * @param AddressRepositoryInterface $addressRepository
     * @param Assignation $assignationModel
     * @param \Riki\ShipLeadTime\Api\LeadtimeRepositoryInterface $leadTimeRepository
     * @param SortOrder $sortOrder
     * @param \Riki\ShipLeadTime\Model\ResourceModel\Leadtime\CollectionFactory $shipLeadTimeCollectionFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Riki\DeliveryType\Model\ResourceModel\Delitype\CollectionFactory $deliveryCollection,
        \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory $pointOfSaleCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\PointOfSale\Helper\Data $pointOfSaleHelper,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Riki\AdvancedInventory\Model\Assignation $assignationModel,
        \Riki\ShipLeadTime\Api\LeadtimeRepositoryInterface $leadTimeRepository,
        SortOrder $sortOrder,
        \Riki\ShipLeadTime\Model\ResourceModel\Leadtime\CollectionFactory $shipLeadTimeCollectionFactory,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        $this->pointOfSaleCollection = $pointOfSaleCollectionFactory;
        $this->deliveryTypeCollection = $deliveryCollection;
        $this->regionCollection = $regionCollectionFactory;
        $this->leadTimeRepositoryInterface = $leadTimeRepository;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->pointOfSaleHelper = $pointOfSaleHelper;
        $this->customerAddressRepository = $addressRepository;
        $this->assignationModel = $assignationModel;
        $this->sortOrder = $sortOrder;
        $this->shipLeadTimeCollectionFactory = $shipLeadTimeCollectionFactory;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Framework\Serialize\Serializer\Json::class
        );
        parent::__construct($context);
    }

    /**
     * @return \Riki\PointOfSale\Helper\Data
     */
    public function getPointOfSaleHelper()
    {
        return $this->pointOfSaleHelper;
    }

    /**
     * @return Assignation
     */
    public function getAssignationModel()
    {
        return $this->assignationModel;
    }

    /**
     * @return array
     */
    public function getRegionArr()
    {
        $regionArray = [];
        $regionCollection = $this->regionCollection->create()->addCountryFilter('JP');
        foreach ($regionCollection as $region) {
            $regionArray[$region->getCode()] = $region->getName();
        }
        return $regionArray;
    }

    public function getAllDeliveryCollection()
    {
        $deliveryTypeArr = [];
        $deliveryTypeCollection = $this->deliveryTypeCollection->create();
        foreach ($deliveryTypeCollection as $deliveryTypeItem) {
            $deliveryTypeArr[$deliveryTypeItem->getCode()] = $deliveryTypeItem->getName();
        }
        return $deliveryTypeArr;
    }

    public function getWareHouseOptions()
    {
        $result = [];
        $wareHouseCollection =  $this->pointOfSaleCollection->create();
        foreach ($wareHouseCollection as $wareHouse) {
            $result[$wareHouse->getStoreCode()] = $wareHouse->getName();
        }
        return $result;
    }

    /**
     * @param $placeCode
     * @param $regionCode
     * @return mixed
     */
    public function getShipLeadTimeByPlaceAndRegion($placeCode, $regionCode)
    {
        $key = $placeCode . '-' . $regionCode;

        if (!isset($this->loadedShipLeadTimesByPlaceRegion[$key])) {
            $condition = [
                'warehouse_id'  =>  $placeCode,
                'pref_id'   =>  $regionCode
            ];

            $this->loadedShipLeadTimesByPlaceRegion[$key] = $this->getShipLeadTimeByCondition($condition);
        }

        return $this->loadedShipLeadTimesByPlaceRegion[$key];
    }

    /**
     * @param array $condition
     * @return mixed
     */
    public function getShipLeadTimeByCondition(array $condition)
    {
        $key = $this->serializer->serialize($condition);

        if (!isset($this->shipLeadTimeByCondition[$key])) {
            $filters = [];

            foreach ($condition as $field => $value) {
                $filters[] = $this->filterBuilder
                    ->setField($field)
                    ->setValue($value)
                    ->setConditionType('eq')
                    ->create();
            }

            $sortOrder = $this->sortOrder->setField('priority')->setDirection(SortOrder::SORT_ASC);

            $this->searchCriteriaBuilder->addFilters($filters)
                ->setSortOrders([$sortOrder]);
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $result = $this->leadTimeRepositoryInterface->getList($searchCriteria);
            $this->shipLeadTimeByCondition[$key] = $result->getItems();
        }

        return $this->shipLeadTimeByCondition[$key];
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $addressId
     * @param array $quoteItemIds
     * @return array|\Magento\Framework\Api\ExtensibleDataInterface[]
     */
    public function getShipLeadTimeByQuoteAndAddressForListItem(
        \Magento\Quote\Model\Quote $quote,
        $addressId,
        array $quoteItemIds = []
    ) {

        $places = $this->pointOfSaleHelper->getPlacesByQuote($quote);

        if (!empty($places)) {
            $deliveryTypes = [];

            if (!empty($quoteItemIds)) {
                foreach ($quoteItemIds as $quoteItemId) {
                    $quoteItem = $quote->getItemById($quoteItemId);

                    if ($quoteItem && $quoteItem->getId()) {
                        $deliveryTypes[] = $quoteItem->getData('delivery_type');
                    }
                }
            } else { // get all item
                $quoteItems = $quote->getAllVisibleItems();

                /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
                foreach ($quoteItems as $quoteItem) {
                    $deliveryTypes[] = $quoteItem->getData('delivery_type');
                }
            }

            try {
                $address = $this->customerAddressRepository->getById($addressId);
            } catch (\Exception $e) {
                return [];
            }

            $regionCode = $address->getRegion()->getRegionCode();

            foreach ($places as $index => $place) {
                $leadTimes = $this->getShipLeadTimeByPlaceAndRegion($place->getStoreCode(), $regionCode);

                $availableDeliveryTypes = [];

                foreach ($leadTimes as $leadTime) {
                    $availableDeliveryTypes[] = $leadTime->getDeliveryTypeCode();
                }

                if (!empty(array_diff(array_unique($deliveryTypes), array_unique($availableDeliveryTypes)))) {
                    unset($places[$index]);
                }
            }
        }

        return $places;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $deliveryType
     * @param $regionCode
     * @return array
     */
    public function getValidPlaceByShipLeadTime(\Magento\Quote\Model\Quote $quote, $deliveryType, $regionCode)
    {
        return $this->getValidPlaceByShipLeadTimeCondition($quote, [
            'pref_id'   =>  $regionCode,
            'delivery_type_code'    =>  $deliveryType
        ]);
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $condition
     * @return array
     */
    public function getValidPlaceByShipLeadTimeCondition(\Magento\Quote\Model\Quote $quote, array $condition)
    {
        $result = [];

        $places = $this->pointOfSaleHelper->getPlacesByQuote($quote);

        foreach ($places as $place) {
            $condition['warehouse_id'] = $place->getData('store_code');

            $leadTimes = $this->getShipLeadTimeByCondition($condition);

            if (!empty($leadTimes)) {
                $result[] = $place->getId();
            }
        }

        return $result;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $deliveryType
     * @param $regionCode
     * @return bool
     */
    public function validateShipLeadTime(\Magento\Quote\Model\Quote $quote, $deliveryType, $regionCode)
    {
        $places = $this->getValidPlaceByShipLeadTime($quote, $deliveryType, $regionCode);

        return boolval(count($places));
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param $addressId
     * @return bool
     */
    public function validateQuoteItemAddress(\Magento\Quote\Model\Quote\Item $quoteItem, $addressId)
    {
        try {
            $address = $this->customerAddressRepository->getById($addressId);
        } catch (\Exception $e) {
            return false;
        }

        $regionCode = $address->getRegion()->getRegionCode();

        $deliveryType = $quoteItem->getData('delivery_type');

        return $this->validateShipLeadTime($quoteItem->getQuote(), $deliveryType, $regionCode);
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $addressId
     * @param array $quoteItemIds
     * @return array
     */
    public function validateQuoteAddress(\Magento\Quote\Model\Quote $quote, $addressId, $quoteItemIds = [])
    {

        try {
            $address = $this->customerAddressRepository->getById($addressId);
        } catch (\Exception $e) {
            return [];
        }

        return $this->validateQuoteRegion($quote, $address->getRegion()->getRegionCode(), $quoteItemIds);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param $regionCode
     * @return array
     */
    private function getActivePlacesForQuoteItem(\Magento\Quote\Model\Quote\Item $quoteItem, $regionCode)
    {
        $quote = $quoteItem->getQuote();

        if (!$quote) {
            return [];
        }

        $condition = [
            'pref_id'   =>  $regionCode,
            'delivery_type_code'    =>  $quoteItem->getData('delivery_type')
        ];

        $leadTimes = $this->getShipLeadTimeByCondition($condition);

        $places = $this->pointOfSaleHelper->getPlacesByQuote($quote);

        $availablePlaces = [];

        /** @var \Wyomind\PointOfSale\Model\PointOfSale $place */
        foreach ($places as $place) {
            foreach ($leadTimes as $leadTime) {
                if ($place->getStoreCode() == $leadTime->getWarehouseId()) {
                    $availablePlaces[] = $place->getId();
                }
            }
        }

        return $availablePlaces;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param array $placesIds
     * @return float|int|mixed
     */
    private function getAvailableQuoteItemQtyWithSpecifiedPlaces(\Magento\Quote\Model\Quote\Item $quoteItem, array $placesIds)
    {
        $requestedQty = $quoteItem->getQty();

        foreach ($placesIds as $placesId) {
            $stockStatus = $this->assignationModel->checkAvailability($quoteItem->getProductId(), $placesId, $requestedQty, null);

            $requestedQty = $stockStatus['remaining_qty_to_assign'];

            if ($stockStatus['status'] >= Assignation::STOCK_STATUS_AVAILABLE_BACK_ORDER) {
                break;
            }
        }

        return $quoteItem->getQty() - $requestedQty;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $regionCode
     * @param array $quoteItemIds
     * @return array
     */
    public function validateQuoteRegion(\Magento\Quote\Model\Quote $quote, $regionCode, array $quoteItemIds = [])
    {
        $errors = [];

        if (!empty($quoteItemIds)) {

            $quoteItems = [];

            foreach ($quoteItemIds as $quoteItemId) {
                if ($quoteItem = $quote->getItemById($quoteItemId)) {
                    $quoteItems[] = $quoteItem;
                }
            }

        } else {
            $quoteItems = $quote->getAllItems();
        }

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($quoteItems as $quoteItem) {

            /**
             * Skip if product is multiple machine
             */
            $buyRequest = $quoteItem->getBuyRequest();
            if ($buyRequest->getData('is_multiple_machine')) {
                continue;
            }

            if ($quoteItem->getHasChildren()) {
                continue;
            }

            if (!$this->needValidateRegionForQuoteItem($quoteItem)) {
                continue;
            }

            $availablePlaces = $this->getActivePlacesForQuoteItem($quoteItem, $regionCode);

            $displayItemId = $quoteItem->getParentItemId()? $quoteItem->getParentItemId() : $quoteItem->getId();

            if (empty($availablePlaces)) {
                $errors[$displayItemId] = __('The product not available to ship to %1', $regionCode);
            } else {
                $availableQty = $this->getAvailableQuoteItemQtyWithSpecifiedPlaces($quoteItem, $availablePlaces);

                if ($availableQty < $quoteItem->getQty()) {
                    $errors[$displayItemId] = __('There is only %1 quantity available for this product in %2', $availableQty, $regionCode);
                }
            }
        }

        return $errors;
    }

    /**
     * check quote item need to validate Region data or not
     *
     * @param $quoteItem
     * @return bool
     */
    public function needValidateRegionForQuoteItem($quoteItem)
    {
        if (!$quoteItem instanceof \Magento\Quote\Model\Quote\Item) {
            return false;
        }

        return true;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $errors
     * @return $this
     */
    public function prepareQuoteLeadTimeError(
        \Magento\Quote\Model\Quote $quote,
        array $errors
    ) {

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem) {
            if (isset($errors[$quoteItem->getId()])) {
                $quoteItem->addErrorInfo(
                    'advanced_inventory_leadtime',
                    1,
                    $errors[$quoteItem->getId()]
                );

                $quote->addErrorInfo(
                    'error',
                    'advanced_inventory_leadtime',
                    1,
                    __('Not all of your products are available in the requested quantity.')
                );
            } else {
                $quoteItem->removeErrorInfosByParams(['origin' => 'advanced_inventory_leadtime', 'code' => 1]);
            }
        }

        if (!empty($errors)) {
            $quote->removeErrorInfosByParams('error', ['origin' => 'advanced_inventory_leadtime', 'code' => 1]);
        }

        return $this;
    }

    /**
     * @param $placeId
     * @param $deliveryType
     * @param $regionCode
     * @return mixed
     */
    public function isActivePlaceDeliveryTypeRegion($placeId, $deliveryType, $regionCode)
    {
        $key = $placeId . '-' . $deliveryType .'-' . $regionCode;

        if (!isset($this->placeDeliveryTypeRegionLeadTimeStatus[$key])) {

            /** @var \Riki\ShipLeadTime\Model\ResourceModel\Leadtime\Collection $leadTimeCollection */
            $leadTimeCollection = $this->shipLeadTimeCollectionFactory->create();
            $numberActive = $leadTimeCollection->addActiveToFilter()
                ->addWarehouseIdToFilter($placeId)
                ->addFieldToFilter('delivery_type_code', $deliveryType)
                ->addFieldToFilter('pref_id', $regionCode)
                ->setPageSize(1)
                ->getSize();

            $this->placeDeliveryTypeRegionLeadTimeStatus[$key] = $numberActive > 0? true :false;
        }

        return $this->placeDeliveryTypeRegionLeadTimeStatus[$key];
    }
}
