<?php
namespace Riki\Sales\Plugin\Sales\Convert;

class Order
{
    /**
     * set custom data from order item to shipment item
     *
     * @param \Magento\Sales\Model\Convert\Order $subject
     * @param \Closure $proceed
     * @param \Magento\Sales\Model\Order\Item $item
     * @return mixed
     */
    public function aroundItemToShipmentItem(
        \Magento\Sales\Model\Convert\Order $subject,
        \Closure $proceed,
        \Magento\Sales\Model\Order\Item $item
    ) {

        $result = $proceed($item);

        $result->setSalesOrganization($item->getSalesOrganization());

        return $result;
    }
}
