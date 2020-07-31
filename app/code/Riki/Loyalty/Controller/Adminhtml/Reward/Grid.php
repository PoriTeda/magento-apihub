<?php

namespace Riki\Loyalty\Controller\Adminhtml\Reward;

class Grid extends \Riki\Loyalty\Controller\Adminhtml\Reward
{
    public function execute()
    {
        $this->initCurrentCustomer();
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
