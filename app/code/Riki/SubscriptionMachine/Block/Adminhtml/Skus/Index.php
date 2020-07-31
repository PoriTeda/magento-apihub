<?php

namespace Riki\SubscriptionMachine\Block\Adminhtml\Skus;

/**
 * Adminhtml course blocks content block
 */
class Index extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Riki_SubscriptionMachine';
        $this->_controller = 'adminhtml_skus';
        $this->_headerText = __('Machine Skus');
        $this->_addButtonLabel = __('Add Machine Skus');
        parent::_construct();
    }
}
