<?php
namespace Riki\RmaWithoutGoods\Controller\Adminhtml;

/**
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Rma extends \Magento\Rma\Controller\Adminhtml\Rma
{
    /**
     * Check the permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_RmaWithoutGoods::rma_wg');
    }

    public function execute(){
        ///
    }
}
