<?php
namespace Riki\Sales\Controller\Adminhtml\ShippingReason;

use Riki\Sales\Controller\Adminhtml\ShippingReason\Reason;

class Add extends Reason
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
