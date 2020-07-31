<?php

namespace Riki\Sales\Controller\Adminhtml\ShippingCause;

use Riki\Sales\Controller\Adminhtml\ShippingCause\Cause;

class Edit extends Cause
{
    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $causeId = $this->getRequest()->getParam('id');
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Riki_Sales::shippingcause')
            ->addBreadcrumb(__('Shipping Cause'), __('Shipping Cause'))
            ->addBreadcrumb(__('Management Shipping Cause'), __('Management Shipping Cause'));

        if ($causeId === null) {
            $resultPage->addBreadcrumb(__('New Shipping Cause'), __('New Shipping Cause'));
            $resultPage->getConfig()->getTitle()->prepend(__('New Shipping Cause'));
        } else {
            $resultPage->addBreadcrumb(__('Edit Shipping Cause'), __('Edit Shipping Cause'));
            $resultPage->getConfig()->getTitle()->prepend(
                $this->shippingCauseRepository->getById($causeId)->getDescription()
            );
        }
        return $resultPage;
    }
}
