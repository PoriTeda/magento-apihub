<?php

namespace Riki\AdvancedInventory\Controller\Adminhtml\ReAssignation;

use Magento\Framework\Controller\ResultFactory;

class NewAction extends \Riki\AdvancedInventory\Controller\Adminhtml\ReAssignation
{
    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Riki_AdvancedInventory::reassignation');
        $resultPage->getConfig()->getTitle()->prepend(__('Import New Re-assign CSV File'));
        return $resultPage;
    }
}
