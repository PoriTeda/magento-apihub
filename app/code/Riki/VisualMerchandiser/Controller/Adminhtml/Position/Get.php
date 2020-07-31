<?php

namespace Riki\VisualMerchandiser\Controller\Adminhtml\Position;

class Get extends \Magento\VisualMerchandiser\Controller\Adminhtml\Position\Get
{
    /**
     * Workaround for admin basic permission bug.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Catalog::category_edit');
    }
}