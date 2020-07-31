<?php

namespace Bluecom\PaymentFee\Block\Adminhtml;

class Payment extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Init
     *
     * @return void
     */
    public function _construct()
    {
        $this->_controller = 'adminhtml';
        $this->_blockGroup = 'Bluecom_PaymentFee';
        $this->_headerText = __('Payment Fee');
        parent::_construct();
    }

}