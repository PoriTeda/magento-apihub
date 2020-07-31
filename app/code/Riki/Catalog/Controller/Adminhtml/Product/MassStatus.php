<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Catalog\Controller\Adminhtml\Product;

class MassStatus extends \Magento\Catalog\Controller\Adminhtml\Product\MassStatus
{
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Catalog::actions_edit');
    }
}
