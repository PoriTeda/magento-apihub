<?php

/* *
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Model;

class Ipn extends \Magento\Paypal\Model\Ipn
{
    
    protected function _registerPaymentCapture($skipFraudDetection = false)
    {
        parent::_registerPaymentCapture($skipFraudDetection);
        $this->_eventManager->dispatch("paypal_ipn_submit_all_after", ["order" => $this->order]);
    }
}
