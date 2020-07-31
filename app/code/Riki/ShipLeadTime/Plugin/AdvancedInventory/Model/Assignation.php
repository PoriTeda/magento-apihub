<?php

namespace Riki\ShipLeadTime\Plugin\AdvancedInventory\Model;

class Assignation
{

    const STOCK_STATUS_LEAD_TIME_INACTIVE = -1;

    /** @var \Magento\Sales\Api\OrderItemRepositoryInterface  */
    protected $orderItemRepository;

    /** @var \Magento\Sales\Api\OrderRepositoryInterface  */
    protected $orderRepository;

    /** @var \Riki\AdvancedInventory\Helper\Assignation  */
    protected $assignationHelper;

    /** @var \Riki\ShipLeadTime\Helper\Data  */
    protected $shipLeadTimeHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /** @var array  */
    protected $activeLeadTimes = [];

    /**
     * Assignation constructor.
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Riki\AdvancedInventory\Helper\Assignation $assignationHelper
     * @param \Riki\ShipLeadTime\Helper\Data $shipLeadTimeHelper
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Riki\AdvancedInventory\Helper\Assignation $assignationHelper,
        \Riki\ShipLeadTime\Helper\Data $shipLeadTimeHelper
    ) {
    
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->storeManager = $storeManager;
        $this->assignationHelper = $assignationHelper;
        $this->shipLeadTimeHelper = $shipLeadTimeHelper;
    }

    /**
     * sort warehouse by priority of lead time
     *
     * @param \Riki\AdvancedInventory\Model\Assignation $subject
     * @param $assignTo
     * @param $orderedItemsArray
     * @param $allowMultipleAssignation
     * @return array
     */
    public function beforeAssignationProcess(
        \Riki\AdvancedInventory\Model\Assignation $subject,
        $assignTo,
        $orderedItemsArray,
        $allowMultipleAssignation
    ) {
    

        $places = $orderedItemsArray['places'];

        if (count($places)) {
            $regionCode = $orderedItemsArray['destination']['region_code'];

            $deliveryType = '';

            foreach ($orderedItemsArray['items'] as $item) {
                $deliveryType = $item['delivery_type'];
                break;
            }

            $leadTimes = $this->shipLeadTimeHelper->getShipLeadTimeByCondition(
                [
                    'pref_id'   => $regionCode,
                    'delivery_type_code'   => $deliveryType
                ]
            );

            $sortedPlaceCodes = [];
            $newPlaces = [];

            /** @var \Riki\ShipLeadTime\Model\Leadtime $leadTime */
            foreach ($leadTimes as $leadTime) {
                foreach ($places as $place) {
                    if ($leadTime->getData('warehouse_id') == $place->getData('store_code') &&
                        !in_array($leadTime->getData('warehouse_id'), $sortedPlaceCodes)
                    ) {
                        $newPlaces[] = $place;
                        $sortedPlaceCodes[] = $place->getData('store_code');
                    }
                }
            }

            foreach ($places as $place) {
                if (!in_array($place->getData('store_code'), $sortedPlaceCodes)) {
                    $newPlaces[] = $place;
                }
            }

            $orderedItemsArray['places'] = $newPlaces;
        }

        return [$assignTo, $orderedItemsArray, $allowMultipleAssignation];
    }

    /**
     * @param \Wyomind\AdvancedInventory\Model\Assignation $subject
     * @param \Closure $proceed
     * @param array $itemData
     * @param $placeId
     * @param $requestedQty
     * @return array|mixed
     */
    public function aroundGetAvailableStockInfo(
        \Wyomind\AdvancedInventory\Model\Assignation $subject,
        \Closure $proceed,
        array $itemData,
        $placeId,
        $requestedQty
    ) {
    
        /*reject simulate process*/
        if (!empty($subject->order) && $subject->order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return $proceed($itemData, $placeId, $requestedQty, $itemData['item_id']);
        }

        if (isset($itemData['delivery_type']) && isset($itemData['region_code'])) {
            if (!$this->isActiveLeadTime($placeId, $itemData['delivery_type'], $itemData['region_code'])) {
                return [
                    "status" => self::STOCK_STATUS_LEAD_TIME_INACTIVE,
                    "remaining_qty_to_assign" => $requestedQty,
                    "qty_assigned" => 0
                ];
            }
        }

        return $proceed($itemData, $placeId, $requestedQty);
    }

    /**
     * @param $placeId
     * @param $deliveryType
     * @param $regionCode
     * @return mixed
     */
    protected function isActiveLeadTime($placeId, $deliveryType, $regionCode)
    {
        return $this->shipLeadTimeHelper->isActivePlaceDeliveryTypeRegion($placeId, $deliveryType, $regionCode);
    }
}
