<?php

namespace Riki\Sales\Controller\Adminhtml\Order;

class Pdfcreditmemos extends \Magento\Sales\Controller\Adminhtml\Order\Pdfcreditmemos
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
