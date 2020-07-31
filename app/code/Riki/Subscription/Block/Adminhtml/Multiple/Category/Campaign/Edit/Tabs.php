<?php
namespace Riki\Subscription\Block\Adminhtml\Multiple\Category\Campaign\Edit;

/**
 * Class Tabs
 * @package Riki\Subscription\Block\Adminhtml\Multiple\Category\Campaign\Edit
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
        $this->setId('campaign_tab');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Campaign Information'));
    }
}
