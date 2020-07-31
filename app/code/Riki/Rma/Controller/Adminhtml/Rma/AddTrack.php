<?php
namespace Riki\Rma\Controller\Adminhtml\Rma;

class AddTrack extends \Magento\Rma\Controller\Adminhtml\Rma\AddTrack
{
    /**
     * Check the permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Rma::rma_return_actions_save_w') ||
                $this->_authorization->isAllowed('Riki_Rma::rma_return_actions_save_cc');
    }
}