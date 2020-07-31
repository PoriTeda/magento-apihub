<?php

namespace Riki\AutomaticallyShipment\Observer;

class OrderCancelAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $this->setCanceledItem($order);
    }

    /**
     * Set state canceled for item after cancel order
     * @param $order
     */
    public function setCanceledItem($order)
    {
        if(!$order->getId() || !$order->hasShipments()) {
            return false;
        }
        $items = $order->getAllItems();
        foreach( $items as $item ) {
            try {
                //set canceled item
                $item->setQtyShipped(0);
                $item->setQtyCanceled($item->getQtyOrdered());
                $item->save();
            } catch(\Exception $e) {
                $this->_logger->error($e->getMessage());
            }
        }
        return true;
    }
}
