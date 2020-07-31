<?php

/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Observer;

class SalesOrderPaymentCancel implements \Magento\Framework\Event\ObserverInterface
{

    protected $_modelAssignation;

    public function __construct(
        \Wyomind\AdvancedInventory\Model\Assignation $modelAssignation
    ) {
        $this->_modelAssignation = $modelAssignation;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getPayment()->getOrder();
        $this->_modelAssignation->cancel($order->getEntityId());
    }
}
