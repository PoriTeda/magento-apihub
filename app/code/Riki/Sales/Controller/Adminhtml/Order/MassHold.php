<?php

namespace Riki\Sales\Controller\Adminhtml\Order;

class MassHold extends \Magento\Sales\Controller\Adminhtml\Order\MassHold
{
    /**
     * Check permission
     *
     * @return bool
     */
    protected function _isAllowed(){
        return $this->_authorization->isAllowed('Magento_Sales::hold');
    }
}
