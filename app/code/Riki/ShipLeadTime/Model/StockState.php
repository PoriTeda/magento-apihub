<?php

namespace Riki\ShipLeadTime\Model;

use Riki\AdvancedInventory\Model\Assignation as AssignationModel;
use Riki\Subscription\Helper\Order\Data;

class StockState implements \Riki\ShipLeadTime\Api\StockStateInterface
{
    /** @var \Magento\Catalog\Api\ProductRepositoryInterface  */
    private $productRepository;

    /** @var \Riki\ShipLeadTime\Helper\Data  */
    private $shipLeadTimeHelper;

    /**
     * @var \Riki\Sales\Helper\Address
     */
    private $addressHelper;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    private $customerAddressRepository;

    /**
     * @var \Riki\DeliveryType\Helper\Data
     */
    protected $deliveryTypeHelper;

    /**
     * @var \Riki\Subscription\Logger\LoggerOrder
     */
    protected $loggerOrder;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * StockState constructor.
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Riki\ShipLeadTime\Helper\Data $shipLeadTimeHelper
     * @param \Riki\Sales\Helper\Address $addressHelper
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Riki\DeliveryType\Helper\Data $deliveryTypeHelper
     * @param \Riki\Subscription\Logger\LoggerOrder $loggerOrder
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\ShipLeadTime\Helper\Data $shipLeadTimeHelper,
        \Riki\Sales\Helper\Address $addressHelper,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Riki\DeliveryType\Helper\Data $deliveryTypeHelper,
        \Riki\Subscription\Logger\LoggerOrder $loggerOrder,
        \Magento\Framework\Registry $registry
    ) {
        $this->shipLeadTimeHelper = $shipLeadTimeHelper;
        $this->productRepository = $productRepository;
        $this->addressHelper = $addressHelper;
        $this->customerAddressRepository = $addressRepository;
        $this->deliveryTypeHelper = $deliveryTypeHelper;
        $this->loggerOrder = $loggerOrder;
        $this->registry = $registry;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $sku
     * @param $qtyRequested
     * @param null $placeIds
     * @return float|int|mixed
     */
    public function checkAvailableQty(\Magento\Quote\Model\Quote $quote, $sku, $qtyRequested, $placeIds = null)
    {
        $regionCode = null;
        $quoteItemAddressId = null;

        $assignedWarehouseId = $quote->getData(AssignationModel::ASSIGNED_WAREHOUSE_ID);

        if ($assignedWarehouseId) {
            $placeIds = [$assignedWarehouseId];
        }

        if ($quote->getData('is_multiple_shipping')) {
            $addressId = $quoteItemAddressId = $this->addressHelper->getDefaultShippingAddress($quote->getCustomer());

            try {
                $address = $this->customerAddressRepository->getById($addressId);

                $regionCode = $address->getRegion()->getRegionCode();
            } catch (\Exception $e) {
                $this->loggerOrder->addError(__(
                    'Can not get region code for Address Id #%2 : %3' ,
                    $addressId,
                    $e->getMessage()
                ));
                return 0;
            }
        } else {
            $address = $quote->getShippingAddress();

            if ($address->getRegionId()) {
                $regionCode = $address->getRegionModel()->getCode();
            }
        }

        try {
            $product = $this->productRepository->get($sku);
        } catch (\Exception $e) {
            $this->loggerOrder->addError(__('Can not get Product with SKU #%1 : %2' ,$sku, $e->getMessage()));
            return 0;
        }

        if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
            return $this->checkAvailableQtyForBundle($quote, $regionCode, $sku, $qtyRequested, $quoteItemAddressId, $placeIds);
        }

        return $this->checkAvailableQtyByCondition(
            $quote,
            $regionCode,
            $sku,
            $this->deliveryTypeHelper->getDeliveryTypeProductCart($quote, $product, $quoteItemAddressId),
            $qtyRequested,
            $placeIds
        );
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $region
     * @param $sku
     * @param $deliveryType
     * @param $qtyRequested
     * @param null $placeIds
     * @param null $unitQty
     * @return float|int
     */
    private function checkAvailableQtyByCondition(
        \Magento\Quote\Model\Quote $quote,
        $region,
        $sku,
        $deliveryType,
        $qtyRequested,
        $placeIds = null,
        $unitQty = null
    ) {
        try {
            $product = $this->productRepository->get($sku);
        } catch (\Exception $e) {
            $this->loggerOrder->addError(__('Can not get Product with SKU #%1 : %2' ,$sku, $e->getMessage()));
            return 0;
        }

        $productId = $product->getId();

        $qtyAdded = 0;

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quote->getAllItems() as $item) {
            if ($item->getProductId() == $productId) {
                $qtyAdded += $item->getQty();
            }
        }

        $totalQty = $qtyRequested + $qtyAdded;

        if (!$unitQty) {
            $unitQty = 1;
            if ($product->getCaseDisplay() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY) {
                $unitQty = (int)$product->getUnitQty()? $product->getUnitQty() : 1;
            }
        }

        $qtyToAssign = $totalQty;

        $places = $this->getValidPlaces($quote, $region, $deliveryType, $placeIds);

        $this->registry->unregister(Data::NED2831_LIST_PLACE_IDS_REGISTRY_KEY);
        $this->registry->register(Data::NED2831_LIST_PLACE_IDS_REGISTRY_KEY, $places);

        foreach ($places as $place) {
            $stockStatus = $this->shipLeadTimeHelper->getAssignationModel()->checkAvailability(
                $productId,
                $place->getId(),
                $qtyToAssign,
                null
            );

            if ($stockStatus['status'] > AssignationModel::STOCK_STATUS_UNAVAILABLE) {
                if ($stockStatus['status'] > AssignationModel::STOCK_STATUS_AVAILABLE_PARTIAL) {
                    $qtyToAssign = 0;
                    break;
                }

                $qtyToAssign = $stockStatus['remaining_qty_to_assign'];
            }
        }

        if ($qtyToAssign > 0) {
            $qtyRequested = max($qtyRequested - $qtyToAssign, 0);
        }

        return floor($qtyRequested / $unitQty) * $unitQty;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $region
     * @param $deliveryType
     * @param $placeIds
     * @return array
     */
    private function getValidPlaces(\Magento\Quote\Model\Quote $quote, $region, $deliveryType, $placeIds)
    {
        $places = $this->shipLeadTimeHelper->getPointOfSaleHelper()->getPlacesByQuote($quote);

        $condition = [
            'delivery_type_code'    =>  $deliveryType
        ];

        if ($region !== null) {
            $condition['pref_id'] = $region;
        }

        $validPlaceIds = $this->shipLeadTimeHelper->getValidPlaceByShipLeadTimeCondition($quote, $condition);

        if ($placeIds) {
            $validPlaceIds = array_intersect($validPlaceIds, $placeIds);
        }

        $result = [];

        foreach ($places as $place) {
            if (in_array($place->getId(), $validPlaceIds)) {
                $result[] = $place;
            }
        }

        return $result;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $region
     * @param $sku
     * @param $qtyRequested
     * @param $quoteItemAddressId
     * @param null $placeIds
     * @return int|mixed
     */
    protected function checkAvailableQtyForBundle(\Magento\Quote\Model\Quote $quote, $region, $sku, $qtyRequested, $quoteItemAddressId, $placeIds = null)
    {
        $result = 0;

        try {
            $product = $this->productRepository->get($sku);
        } catch (\Exception $e) {
            return 0;
        }

        $deliveryType = $this->deliveryTypeHelper->getDeliveryTypeProductCart($quote, $product, $quoteItemAddressId);

        if (!$placeIds) {
            $placeIds = [];

            $places = $this->shipLeadTimeHelper->getPointOfSaleHelper()->getPlacesByQuote($quote);

            foreach ($places as $place) {
                $placeIds[] = $place->getId();
            }
        }

        $skuQty = $this->groupQtyByBundleItemSku($product);

        foreach ($placeIds as $placeId) {
            $availableParentQty = $qtyRequested;

            foreach ($skuQty as $sku => $qty) {
                $childQty = $qtyRequested * $qty;

                $availableQty = $this->checkAvailableQtyByCondition(
                    $quote,
                    $region,
                    $sku,
                    $deliveryType,
                    $childQty,
                    [$placeId],
                    1
                );

                $availableParentQty = min($availableParentQty, (int)($availableQty / $qty));
            }

            $result += $availableParentQty;
        }

        return min($result, $qtyRequested);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    private function groupQtyByBundleItemSku(\Magento\Catalog\Model\Product $product)
    {
        $skuQty = [];

        $selectionCollection = $product->getTypeInstance(true)->getSelectionsCollection(
            $product->getTypeInstance(true)->getOptionsIds($product),
            $product
        );

        /** @var \Magento\Catalog\Model\Product $child */
        foreach ($selectionCollection as $child) {
            $childSku = $child->getSku();

            if (!isset($skuQty[$childSku])) {
                $skuQty[$childSku] = 0;
            }

            $skuQty[$childSku] += $child->getSelectionQty();
        }

        return $skuQty;
    }
}
