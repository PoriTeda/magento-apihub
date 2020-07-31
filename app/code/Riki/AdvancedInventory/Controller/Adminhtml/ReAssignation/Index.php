<?php
namespace Riki\AdvancedInventory\Controller\Adminhtml\ReAssignation;

use Magento\Framework\App\ResponseInterface;

class Index extends \Riki\AdvancedInventory\Controller\Adminhtml\ReAssignation
{

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu('Riki_AdvancedInventory::reassignation');

        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Re-assign stock for order from CSV'));
        $this->_view->renderLayout();
    }
}
