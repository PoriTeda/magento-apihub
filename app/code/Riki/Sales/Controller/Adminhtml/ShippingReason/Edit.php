<?php

namespace Riki\Sales\Controller\Adminhtml\ShippingReason;

use Riki\Sales\Controller\Adminhtml\ShippingReason\Reason;

class Edit extends Reason
{
    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $reasonId = $this->getRequest()->getParam('id');
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Riki_Sales::shippingreason')
            ->addBreadcrumb(__('Shipping Reason'), __('Shipping Reason'))
            ->addBreadcrumb(__('Management Shipping Reason'), __('Management Shipping Reason'));

        if ($reasonId === null) {
            $resultPage->addBreadcrumb(__('New Shipping Reason'), __('New Shipping Reason'));
            $resultPage->getConfig()->getTitle()->prepend(__('New Shipping Reason'));
        } else {
            $resultPage->addBreadcrumb(__('Edit Shipping Reason'), __('Edit Shipping Reason'));
            $resultPage->getConfig()->getTitle()->prepend(
                $this->shippingReasonRepository->getById($reasonId)->getDescription()
            );
        }
        return $resultPage;
    }
}
