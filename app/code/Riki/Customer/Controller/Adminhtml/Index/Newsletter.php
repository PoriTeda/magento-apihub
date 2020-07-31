<?php
namespace Riki\Customer\Controller\Adminhtml\Index;

class Newsletter extends \Magento\Customer\Controller\Adminhtml\Index\Newsletter
{
    /**
     * Customer access rights checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Customer::edit');
    }
}
