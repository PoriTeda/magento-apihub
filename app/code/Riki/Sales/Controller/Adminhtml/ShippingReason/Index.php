<?php

namespace Riki\Sales\Controller\Adminhtml\ShippingReason;

use Riki\Sales\Controller\Adminhtml\ShippingReason\Reason;
use Riki\Sales\Model\ShippingReasonData;

class Index extends Reason
{
    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(ShippingReasonData::ACL_ROOT);
        $resultPage->getConfig()->getTitle()->prepend((__('Management Shipping Reason')));
        return $resultPage;

    }
}
