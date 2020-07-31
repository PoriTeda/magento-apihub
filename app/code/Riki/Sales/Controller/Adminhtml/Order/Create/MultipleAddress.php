<?php

namespace Riki\Sales\Controller\Adminhtml\Order\Create;

use Magento\Backend\App\Action;

class MultipleAddress extends \Magento\Sales\Controller\Adminhtml\Order\Create
{
    /**
     * Start order create action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $this->_getSession()->clearStorage();

        /**
         * Identify address type
         */
        $this->_getSession()->setData(\Riki\Sales\Helper\Admin::DELIVERY_ORDER_TYPE_SESSION_NAME, \Riki\Sales\Model\Config\DeliveryOrderType::MULTIPLE_ADDRESS);

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('sales/*', ['customer_id' => $this->getRequest()->getParam('customer_id')]);
    }
}
