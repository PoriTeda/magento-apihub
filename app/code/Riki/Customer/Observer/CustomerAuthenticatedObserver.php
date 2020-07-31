<?php

namespace Riki\Customer\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CustomerAuthenticatedObserver implements ObserverInterface
{
    protected $_eventManager;

    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager
    )
    {
        $this->_eventManager = $eventManager;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_eventManager->dispatch('riki_customer_customer_authenticated', ['customer' => $observer->getModel()]);
    }
}