<?php

namespace Riki\Sales\Controller\Adminhtml\ShippingCause;

use Riki\Sales\Controller\Adminhtml\ShippingCause\Cause;
use Riki\Sales\Model\ShippingCauseData;

class Index extends Cause
{
    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(ShippingCauseData::ACL_ROOT);
        $resultPage->getConfig()->getTitle()->prepend((__('Management Shipping Cause')));
        return $resultPage;

    }
}
