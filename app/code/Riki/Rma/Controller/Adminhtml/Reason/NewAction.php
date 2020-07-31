<?php

namespace Riki\Rma\Controller\Adminhtml\Reason;

class NewAction extends \Riki\Rma\Controller\Adminhtml\Reason
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}
