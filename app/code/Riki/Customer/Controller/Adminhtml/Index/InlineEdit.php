<?php
namespace Riki\Customer\Controller\Adminhtml\Index;

class InlineEdit extends \Magento\Customer\Controller\Adminhtml\Index\InlineEdit
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
