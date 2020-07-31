<?php
namespace Riki\Prize\Block\Adminhtml\Index\Edit;

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
        $this->setId('prize_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Prize information'));
    }
}
