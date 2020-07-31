<?php

namespace Riki\SerialCode\Controller\Adminhtml\Index;

class Index extends \Riki\SerialCode\Controller\Adminhtml\Index
{
    /**
     * Index action
     *
     * @return void
     */
    public function execute()
    {
        $this->_initAction()->_addBreadcrumb(__('Customer'), __('Customer'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Serial code'));
        $this->_view->renderLayout();
    }
}
