<?php

namespace Riki\StockPoint\Observer;

class ConvertFreeMachineOrderColumnToOrder implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * convert column free machine column from quote to order
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Riki\Catalog\Model\Quote $quote */
        $quote = $observer->getQuote();

        if ($quote instanceof \Riki\Subscription\Model\Emulator\Cart) {
            return;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getOrder();

        /*sync column free machine order form quote to order*/
        $order->setData('free_machine_order', $quote->getData('free_machine_order'));
    }
}
