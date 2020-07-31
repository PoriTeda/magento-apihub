<?php
namespace Riki\Chirashi\Plugin\Sales\Convert;

class Order
{
    /**
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

        $result->setChirashi($item->getChirashi());

        return $result;
    }
}
