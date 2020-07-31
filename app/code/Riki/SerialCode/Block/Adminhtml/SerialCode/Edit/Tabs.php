<?php

namespace Riki\SerialCode\Block\Adminhtml\SerialCode\Edit;

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
        $this->setId('serial_code_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Serial Code Information'));
    }
}
