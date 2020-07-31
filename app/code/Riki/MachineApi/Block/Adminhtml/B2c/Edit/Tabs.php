<?php
namespace Riki\MachineApi\Block\Adminhtml\B2c\Edit;

/**
 * Admin page left menu
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('b2c_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('B2C Machine SKUs Information'));
    }
}
