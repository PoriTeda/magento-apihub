<?php

namespace Riki\DeliveryType\Block\Adminhtml;

class Delitype extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_delitype';
        $this->_blockGroup = 'Riki_DeliveryType';
        $this->_headerText = __('Delivery Type');
        parent::_construct();
        $this->removeButton('add');
    }
}