<?php

namespace Riki\Sales\Controller\Adminhtml\Order;

class Pdfshipments extends \Magento\Sales\Controller\Adminhtml\Order\Pdfshipments
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
