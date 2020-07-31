<?php
namespace Riki\Rma\Controller\Adminhtml\Rma;

class NewAction extends \Magento\Rma\Controller\Adminhtml\Rma\NewAction
{
    /**
     * Check the permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Rma::rma_return_actions_save');
    }
}