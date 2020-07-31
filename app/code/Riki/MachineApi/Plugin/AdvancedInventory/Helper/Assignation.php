<?php

namespace Riki\MachineApi\Plugin\AdvancedInventory\Helper;

class Assignation
{
    /** @var \Riki\MachineApi\Helper\Data  */
    protected $machineHelper;

    /**
     * Assignation constructor.
     * @param \Riki\MachineApi\Helper\Data $machineHelper
     */
    public function __construct(
        \Riki\MachineApi\Helper\Data $machineHelper
    )
    {
        $this->machineHelper = $machineHelper;
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
    )
    {
        $places = $proceed($order);

        if ($order->getOrderChannel() == \Riki\Sales\Model\Config\Source\OrderChannel::ORDER_CHANEL_TYPE_MACHINE_API) {
            foreach ($places as $placeId => $place) {
                if ($place->getId() == $this->machineHelper->getMachineDefaultPlace()) {
                    return [$placeId => $place];
                }
            }

            return [];
        }

        return $places;
    }

    /**
     * @param \Magento\Sales\Model\Order|null $order
     * @param \Closure $proceed
     * @param \Magento\Sales\Model\Order $order
     * @return bool|mixed
     */
    public function aroundIsAllowMultipleAssignation(
        \Riki\AdvancedInventory\Helper\Assignation $subject,
        \Closure $proceed,
        \Magento\Sales\Model\Order $order
    )
    {
        if ($order->getOrderChannel() == \Riki\Sales\Model\Config\Source\OrderChannel::ORDER_CHANEL_TYPE_MACHINE_API) {
            return false;
        }

        return $proceed($order);
    }
}
