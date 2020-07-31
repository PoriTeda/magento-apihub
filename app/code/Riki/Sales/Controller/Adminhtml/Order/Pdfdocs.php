<?php

namespace Riki\Sales\Controller\Adminhtml\Order;

class Pdfdocs extends \Magento\Sales\Controller\Adminhtml\Order\Pdfdocs
{
    /**
     * Check permission
     *
     * @return bool
     */
    protected function _isAllowed(){
        return $this->_authorization->isAllowed('Magento_Sales::sales_order');
    }
}
