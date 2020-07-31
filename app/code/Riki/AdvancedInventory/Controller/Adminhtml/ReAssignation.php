<?php
namespace Riki\AdvancedInventory\Controller\Adminhtml;

use Magento\Framework\App\ResponseInterface;

class ReAssignation extends \Magento\Backend\App\Action
{
    const REASSIGNATION_RESOURCE = 'Riki_AdvancedInventory::reassignation';

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $this->_setActiveMenu('Riki_AdvancedInventory::reassignation');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::REASSIGNATION_RESOURCE);
    }
}
