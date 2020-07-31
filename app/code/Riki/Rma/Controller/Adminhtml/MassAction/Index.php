<?php
namespace Riki\Rma\Controller\Adminhtml\MassAction;

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
        $this->_setActiveMenu('Riki_Rma::mass_action_return');

        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Requested Mass Action Returns'));
        $this->_view->renderLayout();
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Rma::magento_rma');
    }
}