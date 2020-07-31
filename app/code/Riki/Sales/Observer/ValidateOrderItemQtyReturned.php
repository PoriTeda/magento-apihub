<?php
namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;

class ValidateOrderItemQtyReturned implements ObserverInterface
{

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Item $item */
        $item= $observer->getEvent()->getItem();

        if ($item->dataHasChangedFor('qty_returned')) {
            $qtyReturned = max($item->getQtyReturned(), 0);

            if ($qtyReturned) {
                if ($qtyReturned > $item->getQtyShipped() &&  !$item->isDummy()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('We found an invalid quantity to return item "%1".', $item->getName())
                    );
                }
            }
        }
    }
}