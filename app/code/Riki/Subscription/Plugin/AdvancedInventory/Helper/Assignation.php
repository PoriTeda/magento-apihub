<?php

namespace Riki\Subscription\Plugin\AdvancedInventory\Helper;

use Magento\Framework\Exception\LocalizedException;
use Riki\Subscription\Helper\Order\Data as SubscriptionOrderHelper;

class Assignation
{
    /**
     * @var \Riki\ShipLeadTime\Helper\Data
     */
    protected $shipLeadTimeHelper;

    /**
     * Assignation constructor.
     * @param \Riki\ShipLeadTime\Helper\Data $shipLeadTimeHelper
     */
    public function __construct(
        \Riki\ShipLeadTime\Helper\Data $shipLeadTimeHelper
    ) {
    
        $this->shipLeadTimeHelper = $shipLeadTimeHelper;
    }

    /**
     * @param \Riki\AdvancedInventory\Helper\Assignation $subject
     * @param \Closure $proceed
     * @param \Magento\Sales\Model\Order $order
     * @return array|mixed
     */
    public function aroundGetAvailablePlacesByOrder(
        \Riki\AdvancedInventory\Helper\Assignation $subject,
        \Closure $proceed,
        \Magento\Sales\Model\Order $order
    ) {
    
        $places = $proceed($order);

        if ($order->getData(SubscriptionOrderHelper::IS_PROFILE_GENERATED_ORDER_KEY) &&
            $assignedWarehouseId = $order->getData(SubscriptionOrderHelper::ASSIGNED_WAREHOUSE_ID_KEY)
        ) {
            if (!empty($places)) {
                foreach ($places as $placeId => $place) {
                    if ($place->getId() == $assignedWarehouseId) {
                        $this->validateActiveWarehouse($order, $placeId, $place->getStoreCode());
                        return [$placeId => $place];
                    }
                }
            }
        }

        return $places;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $warehouseId
     * @param $warehouseCode
     * @return $this
     * @throws LocalizedException
     */
    protected function validateActiveWarehouse(\Magento\Sales\Model\Order $order, $warehouseId, $warehouseCode)
    {
        $groupItems = $this->shipLeadTimeHelper->getAssignationModel()->groupItemsByAddressAndDeliveryType($order);

        foreach ($groupItems as $groupItem) {
            if (!$this->shipLeadTimeHelper->isActivePlaceDeliveryTypeRegion(
                $warehouseId,
                $groupItem['delivery_type'],
                $groupItem['destination']['region_code']
            )) {
                throw new LocalizedException(
                    __(
                        'Subscription profile [%1] cannot generate order due to (%2, %3, %4) ' .
                        'are inactive in Lead Time Management',
                        $order->getData('profile_id'),
                        $warehouseCode,
                        $groupItem['delivery_type'],
                        $groupItem['destination']['region_code']
                    )
                );
            }
        }

        return $this;
    }
}
