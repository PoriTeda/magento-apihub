<?php
namespace Riki\Promo\Plugin\Sales;

class ShipmentFactory
{
    /**
     * @param \Magento\Sales\Model\Order\ShipmentFactory $subject
     * @param \Closure $proceed
     * @param \Magento\Sales\Model\Order $order
     * @param array $items
     * @param null $tracks
     * @return \Magento\Sales\Api\Data\ShipmentInterface
     */
    public function aroundCreate(
        \Magento\Sales\Model\Order\ShipmentFactory $subject,
        \Closure $proceed,
        \Magento\Sales\Model\Order $order,
        array $items = [],
        $tracks = null
    ) {

        /** @var \Magento\Sales\Api\Data\ShipmentInterface $result */
        $result = $proceed($order, $items, $tracks);

        if($result->getItems()){
            foreach($result->getItems() as $item){
                if($item->getVisibleUserAccount()){
                    return $result;
                }
            }

            $result->setVisibleUserAccount(0);
        }

        return $result;
    }
}
