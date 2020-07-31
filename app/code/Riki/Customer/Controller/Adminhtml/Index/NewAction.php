<?php
namespace Riki\Customer\Controller\Adminhtml\Index;

class NewAction extends \Magento\Customer\Controller\Adminhtml\Index\NewAction
{
    /**
     * Customer access rights checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Customer::new');
    }
}
