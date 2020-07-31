<?php
namespace Riki\Rma\Controller\Adminhtml\ReviewCc;

class Index extends \Riki\Rma\Controller\Adminhtml\AbstractAction
{
    /**
     * Default action
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu('Riki_Rma::review_cc');

        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Reviews By CC Operator'));
        $this->_view->renderLayout();
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(\Riki\Rma\Controller\Adminhtml\ReviewCc\Create::ACL_RESOURCE_NAME);
    }
}