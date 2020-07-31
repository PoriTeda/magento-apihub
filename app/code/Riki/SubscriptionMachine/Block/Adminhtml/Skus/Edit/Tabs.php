<?php

namespace Riki\SubscriptionMachine\Block\Adminhtml\Skus\Edit;

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
        $this->setId('skus_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Machine SKUs Information'));
    }
}
