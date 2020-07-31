<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Controller\Adminhtml\Report\Customer\Customersegment;

class Detail extends \Magento\CustomerSegment\Controller\Adminhtml\Report\Customer\Customersegment
{
    /**
     * Detail Action of customer segment
     *
     * @return void
     */
    public function execute()
    {
        if ($this->_initSegment()) {
            // Add help Notice to Combined Report
            if ($this->_getAdminSession()->getMassactionIds()) {
                $collection = $this->_collectionFactory->create()->addFieldToFilter(
                    'segment_id',
                    ['in' => $this->_getAdminSession()->getMassactionIds()]
                );

                $segments = [];
                foreach ($collection as $item) {
                    $segments[] = $item->getName();
                }
                /* @translation __('Viewing combined "%1" report from segments: %2') */
                if ($segments) {
                    $viewModeLabel = $this->_objectManager->get(
                        \Magento\CustomerSegment\Helper\Data::class
                    )->getViewModeLabel(
                        $this->_getAdminSession()->getViewMode()
                    );
                    $this->messageManager->addNotice(
                        __('Viewing combined "%1" report from segments: %2.', $viewModeLabel, implode(', ', $segments))
                    );
                }
            }

            $this->_initAction();
            $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Customer Segment Report'));
            $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Details'));
            $this->_view->renderLayout();
        } else {
            $this->_redirect('*/*/segment');
            return;
        }
    }
}
