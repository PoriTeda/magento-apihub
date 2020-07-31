<?php
namespace Riki\Preorder\Controller\Adminhtml\Order\Create;

use Magento\Backend\App\Action;

class Index extends \Magento\Sales\Controller\Adminhtml\Order\Create
{
    public function execute()
    {
        $this->_getSession()->clearStorage();

        /**
         * Identify address type
         */
        $this->_getSession()->setData(\Riki\Preorder\Model\Config\PreOrderType::SESSION_FLAG_NAME, 1);

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('sales/order_create', ['customer_id' => $this->getRequest()->getParam('customer_id')]);
    }
}