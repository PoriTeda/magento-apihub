<?php

namespace Riki\Catalog\Controller\Adminhtml\Bundle\Selection;

use Magento\Bundle\Controller\Adminhtml\Bundle\Selection\Grid as BundleSelectionGrid;

class Grid extends BundleSelectionGrid
{
    /**
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Catalog::products');
    }
}
