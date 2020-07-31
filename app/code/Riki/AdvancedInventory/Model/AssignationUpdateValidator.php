<?php

namespace Riki\AdvancedInventory\Model;

use Magento\Framework\Exception\LocalizedException;
use Riki\PointOfSale\Api\PointOfSaleRepositoryInterface;
use Riki\AdvancedInventory\Exception\WarehouseOutOfStockException;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay;

class AssignationUpdateValidator
{
    /**
     * @var \Riki\AdvancedInventory\Model\Assignation
     */
    protected $assignationModel;

    /**
     * @var \Riki\Sales\Helper\Address
     */
    protected $orderAddressHelper;

    /**
     * @var PointOfSaleRepositoryInterface
     */
    protected $pointOfSalesRepository;

    /**
     * @var \Riki\PointOfSale\Helper\Data
     */
    protected $posHelper;

    /**
     * AssignationUpdateValidator constructor.
     * @param \Riki\Sales\Helper\Address $orderAddressHelper
     * @param PointOfSaleRepositoryInterface $pointOfSalesRepository
     */
    public function __construct(
        \Riki\Sales\Helper\Address $orderAddressHelper,
        \Riki\PointOfSale\Api\PointOfSaleRepositoryInterface $pointOfSalesRepository,
        \Riki\PointOfSale\Helper\Data $posHelper
    ) {
        $this->orderAddressHelper = $orderAddressHelper;
        $this->pointOfSalesRepository = $pointOfSalesRepository;
        $this->posHelper = $posHelper;
    }

    /**
     * @param Assignation $assignation
     */
    public function setAssignationModel(Assignation $assignation)
    {
        $this->assignationModel = $assignation;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param array $data
     */
    public function validate(\Magento\Sales\Model\Order $order, array $data)
    {
        $this->validateMatchItems($order, $data)
            ->validateBundleRule($order, $data)
            ->validatePieceCaseRule($order, $data)
            ->validateWarehouse($data)
            ->validateStock($order, $data);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param array $data
     * @return $this
     * @throws LocalizedException
     * @throws \Exception
     */
    public function validateMatchItems(\Magento\Sales\Model\Order $order, array $data)
    {
        $orderItemIds = [];
        $orderItemCollection = $order->getItemsCollection();

        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        foreach ($orderItemCollection as $orderItem) {
            if (!$orderItem->getHasChildren()) {
                $orderItemIds[] = $orderItem->getId();
            }
        }

        // request items don't match with order items
        if (count(array_diff($orderItemIds, array_keys($data)))) {
            throw new LocalizedException(__('Requested items do not match with order items.'));
        }

        foreach ($data as $itemId => $item) {
            /** @var \Magento\Sales\Model\Order\Item $orderItem */
            $orderItem = $orderItemCollection->getItemById($itemId);

            $assignQty = 0;

            foreach ($item['pos'] as $placeId => $pos) {
                $assignQty += floatval($pos['qty_assigned']);
            }

            if ($assignQty > $orderItem->getQtyOrdered()) {
                throw new \Exception(__('Order item %1 have invalid assign qty.', $orderItem->getId()));
            }
        }

        return $this;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param array $data
     * @return $this
     * @throws \Exception
     */
    public function validateBundleRule(\Magento\Sales\Model\Order $order, array $data)
    {
        $assignWarehouses = [];

        foreach ($data as $itemId => $item) {
            $assignWarehouses[$itemId] = [];

            foreach ($item['pos'] as $placeId => $pos) {
                if (floatval($pos['qty_assigned']) > 0) {
                    $assignWarehouses[$itemId][] = $placeId;
                }
            }
        }

        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        foreach ($order->getItemsCollection() as $orderItem) {
            if ($orderItem->getHasChildren()) {
                $assignWarehouseId = 0;

                foreach ($orderItem->getChildrenItems() as $childrenItem) {
                    foreach ($assignWarehouses[$childrenItem->getId()] as $warehouseId) {
                        if ($assignWarehouseId && $warehouseId != $assignWarehouseId) {
                            throw new LocalizedException(
                                __(
                                    'Children items of bundle product %1 must be assigned to same single warehouse',
                                    $orderItem->getSku()
                                )
                            );
                        }
                        $assignWarehouseId = $warehouseId;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param array $data
     * @return $this
     * @throws LocalizedException
     */
    public function validatePieceCaseRule(\Magento\Sales\Model\Order $order, array $data)
    {
        foreach ($data as $itemId => $item) {

            /** @var \Magento\Sales\Model\Order\Item $orderItem */
            $orderItem = $order->getItemsCollection()->getItemById($itemId);

            $unitQty = 1;

            if ($orderItem->getData('unit_case') == CaseDisplay::PROFILE_UNIT_CASE) {
                $unitQty = max($unitQty, $orderItem->getData('unit_qty'));
            }

            $assignWarehouses[] = $itemId;

            foreach ($item['pos'] as $placeId => $pos) {
                if ($pos['qty_assigned'] % $unitQty > 0) {
                    throw new LocalizedException(
                        __('Product %1 be required to assign full case to a warehouse.', $orderItem->getSku())
                    );
                }
            }
        }

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     * @throws LocalizedException
     */
    public function validateWarehouse(array $data)
    {
        $warehouseIds = array_keys($this->posHelper->getPlaces());

        foreach ($data as $itemId => $item) {
            if (count(array_diff($warehouseIds, array_keys($item['pos'])))) {
                throw new LocalizedException(__('Request data is invalid.'));
            }
        }

        return $this;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param array $data
     * @return $this
     * @throws \Exception
     */
    public function validateStock(\Magento\Sales\Model\Order $order, array $data)
    {
        $orderItemCollection = $order->getItemsCollection();

        $orderItemToAddress = $this->orderAddressHelper->getOrderAddressForEachOrderItem($order);

        $assignedItemData = $this->getAssignedQtyByItems($order);

        $assignQtyByProduct = [];

        foreach ($data as $itemId => $item) {

            /** @var \Magento\Sales\Model\Order\Item $orderItem */
            $orderItem = $orderItemCollection->getItemById($itemId);

            /** @var \Magento\Sales\Model\Order\Address $orderAddress */
            $orderAddress = $orderItemToAddress[$itemId];
            $addressId = $orderAddress->getEntityId();

            $productId = $orderItem->getProductId();

            $key = $productId .'-' . $addressId;

            if (!isset($assignQtyByProduct[$key])) {
                $assignQtyByProduct[$key] = [
                    'product_id'    => $productId,
                    'sku'   => $orderItem->getSku(),
                    'region_code'   => $orderAddress->getData('region_code'),
                    'delivery_type' => $orderItem->getData('delivery_type'),
                    'pos'   => []
                ];
            }

            foreach ($item['pos'] as $placeId => $pos) {
                if (floatval($pos['qty_assigned']) == 0) {
                    continue;
                }

                if (!isset($assignQtyByProduct[$key]['pos'][$placeId])) {
                    $assignQtyByProduct[$key]['pos'][$placeId] = 0;
                }

                $assignQtyByProduct[$key]['pos'][$placeId] += $pos['qty_assigned'];

                if (isset($assignedItemData[$itemId .'-' . $placeId])) { // subtract assigned qty
                    $assignQtyByProduct[$key]['pos'][$placeId] -= $assignedItemData[$itemId .'-' . $placeId];
                }
            }
        }

        foreach ($assignQtyByProduct as $productWarehouseQty) {
            foreach ($productWarehouseQty['pos'] as $warehouseId => $qty) {
                $availableStockInfo = $this->assignationModel->getAvailableStockInfo([
                    'product_id'    => $productWarehouseQty['product_id'],
                    'delivery_type' => $productWarehouseQty['delivery_type'],
                    'region_code' => $productWarehouseQty['region_code']
                ], $warehouseId, $qty);

                if ($availableStockInfo['remaining_qty_to_assign'] > 0) {
                    throw new WarehouseOutOfStockException(
                        __(
                            'Warehouse %1 do not enough stock for product %2',
                            $this->getWarehouseNameById($warehouseId),
                            $productWarehouseQty['sku']
                        )
                    );
                }
            }
        }

        return $this;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getAssignedQtyByItems(\Magento\Sales\Model\Order $order)
    {
        $assignedItemData = $this->assignationModel->getAssignationItemByOrder($order);

        $assignedQtyItemData = [];

        /** @var \Wyomind\AdvancedInventory\Model\Assignation $assignationItem */
        foreach ($assignedItemData as $key => $assignationItem) {
            $assignedQtyItemData[$key]  = floatval($assignationItem['qty_assigned']);
        }

        return $assignedQtyItemData;
    }

    /**
     * @param mixed $warehouseId
     * @return mixed
     */
    protected function getWarehouseNameById($warehouseId)
    {
        try {
            $warehouse = $this->pointOfSalesRepository->get($warehouseId);
            return $warehouse->getName();
        } catch (\Exception $e) {
            return $warehouseId;
        }
    }
}
