<?php

namespace Riki\CatalogRule\Block\Adminhtml\Wbs\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('wbs_conversion_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Wbs conversion rule'));
    }
}