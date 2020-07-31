<?php
namespace Riki\MachineApi\Block\Adminhtml\Skus;

class Index extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Riki_MachineApi';
        $this->_controller = 'adminhtml_b2c';
        $this->_headerText = __('B2C Machine SKUs');
        $this->_addButtonLabel = __('Add B2C Machine SKUs');
        parent::_construct();
    }
}
