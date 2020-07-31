<?php

/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Observer;

class SalesOrderCreditmemoRefund implements \Magento\Framework\Event\ObserverInterface
{

    protected $_modelAssignation;

    public function __construct(
        \Wyomind\AdvancedInventory\Model\Assignation $modelAssignation
    ) {
        $this->_modelAssignation = $modelAssignation;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $this->_modelAssignation->refund($observer);
    }
}
