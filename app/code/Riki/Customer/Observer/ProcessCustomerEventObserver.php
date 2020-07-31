<?php
namespace Riki\Customer\Observer;

class ProcessCustomerEventObserver extends \Magento\CustomerSegment\Observer\ProcessCustomerEventObserver{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        return;
    }
}