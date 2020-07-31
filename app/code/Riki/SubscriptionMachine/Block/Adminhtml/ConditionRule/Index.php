<?php

namespace Riki\SubscriptionMachine\Block\Adminhtml\ConditionRule;

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
        $this->_controller = 'adminhtml_conditionRule';
        $this->_headerText = __('Machine Condition Rule');
        $this->_addButtonLabel = __('Add New');
        parent::_construct();
    }
}
