<?php

namespace Riki\VisualMerchandiser\Controller\Adminhtml\Products;

class MassAssign extends \Magento\VisualMerchandiser\Controller\Adminhtml\Products\MassAssign
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