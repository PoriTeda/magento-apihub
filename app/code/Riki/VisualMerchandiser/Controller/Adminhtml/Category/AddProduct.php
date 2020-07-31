<?php

namespace Riki\VisualMerchandiser\Controller\Adminhtml\Category;

class AddProduct extends \Magento\VisualMerchandiser\Controller\Adminhtml\Category\AddProduct
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
