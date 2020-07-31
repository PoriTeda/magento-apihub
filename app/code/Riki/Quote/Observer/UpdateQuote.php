<?php

namespace Riki\Quote\Observer;

use Magento\Framework\Event\Observer;

class UpdateQuote implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Update trigger_recollect flag.
     *
     * @param Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $observer->getQuote()->setTriggerRecollect(0);
    }
}
