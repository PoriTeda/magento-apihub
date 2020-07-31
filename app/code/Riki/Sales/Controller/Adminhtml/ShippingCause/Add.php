<?php
namespace Riki\Sales\Controller\Adminhtml\ShippingCause;

use Riki\Sales\Controller\Adminhtml\ShippingCause\Cause;

class Add extends Cause
{
    /**
     * Forward to edit
     *
     * @return \Magento\Backend\Model\View\Result\Forward
     */
    public function execute()
    {
        $resultForward = $this->resultForwardFactory->create();
        return $resultForward->forward('edit');
    }
}
