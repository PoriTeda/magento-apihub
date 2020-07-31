<?php
namespace Nestle\Gillette\Controller\Adminhtml\Order\Create;

use Magento\Backend\App\Action;

class Index extends \Magento\Sales\Controller\Adminhtml\Order\Create
{
    public function execute()
    {
        $this->_getSession()->clearStorage();

        /**
         * Identify address type
         */
        $this->_getSession()->setData(\Nestle\Gillette\Model\Config\GilletteType::SESSION_FLAG_GILLETTE, 1);

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('sales/order_create', ['customer_id' => $this->getRequest()->getParam('customer_id')]);
    }
}