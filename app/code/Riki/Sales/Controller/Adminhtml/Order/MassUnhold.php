<?php

namespace Riki\Sales\Controller\Adminhtml\Order;

class MassUnhold extends \Magento\Sales\Controller\Adminhtml\Order\MassUnhold
{
    /**
     * Check permission
     *
     * @return bool
     */
    protected function _isAllowed(){
        return $this->_authorization->isAllowed('Magento_Sales::unhold');
    }
}
