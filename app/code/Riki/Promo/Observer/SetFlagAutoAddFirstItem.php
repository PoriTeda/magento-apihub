<?php

namespace Riki\Promo\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SetFlagAutoAddFirstItem implements ObserverInterface
{
    /**
     * @param Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $observer->getEvent()->getData('quote')->setIsAutoAddFirstItem(true);
    }
}
