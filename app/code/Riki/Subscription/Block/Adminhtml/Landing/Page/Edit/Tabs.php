<?php

namespace Riki\Subscription\Block\Adminhtml\Landing\Page\Edit;

/**
 * Class Tabs
 *
 * @package Riki\Subscription\Block\Adminhtml\Landing\Page\Edit
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('landing_tab');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Landing Page Information'));
    }
}
