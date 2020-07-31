<?php

namespace Riki\AdvancedInventory\Plugin\AdvancedInventory\Helper;

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
        $availablePlaces = $proceed($order);
        if($order->getData('re_assign_stock')) {
            $order->setOrderChannel('advancedinventory_reassign_stock');
            foreach ($availablePlaces as $place) {
                if ($place->getId() == 2) {
                    return [$place->getId() => $place];
                }
            }
            return [];
        }
        return $availablePlaces;
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
        if ($order->getData('re_assign_stock')) {
            return false;
        }

        return $proceed($order);
    }
}
