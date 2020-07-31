<?php

namespace Riki\Wamb\Block\Adminhtml\Rule;

class View extends \Riki\Wamb\Block\Adminhtml\Rule\Edit
{
    public function _construct()
    {
        parent::_construct();
        $this->buttonList->remove('save');
        $this->buttonList->remove('saveandcontinue');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('reset');
        $this->registry->register('wamb_rule_view', 'view');
    }

}