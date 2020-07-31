<?php
namespace Riki\ArReconciliation\Block\Adminhtml\Import\Edit;

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
        $this->setId('importing_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Importing information'));
    }
}
