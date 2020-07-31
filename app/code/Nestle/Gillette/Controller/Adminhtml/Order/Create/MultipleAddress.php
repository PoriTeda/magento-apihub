<?php
namespace Nestle\Gillette\Controller\Adminhtml\Order\Create;

use Magento\Backend\App\Action;

class MultipleAddress extends \Magento\Sales\Controller\Adminhtml\Order\Create
{
    public function execute()
    {
        $this->_getSession()->clearStorage();

        /**
         * Identify address type
         */
        $this->_getSession()->setData(\Nestle\Gillette\Model\Config\GilletteType::SESSION_FLAG_GILLETTE, 1);
        $this->_getSession()->setData(\Riki\Sales\Helper\Admin::DELIVERY_ORDER_TYPE_SESSION_NAME, \Riki\Sales\Model\Config\DeliveryOrderType::MULTIPLE_ADDRESS);

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('sales/order_create', ['customer_id' => $this->getRequest()->getParam('customer_id')]);
    }

}