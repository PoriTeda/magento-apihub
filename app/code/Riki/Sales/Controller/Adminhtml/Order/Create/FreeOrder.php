<?php

namespace Riki\Sales\Controller\Adminhtml\Order\Create;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\Model\View\Result\ForwardFactory;

class FreeOrder extends \Magento\Sales\Controller\Adminhtml\Order\Create
{

    protected $_orderFactory;

    public function __construct(
        Action\Context $context,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Framework\Escaper $escaper,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ){

        $this->_orderFactory = $orderFactory;

        parent::__construct(
            $context,
            $productHelper,
            $escaper,
            $resultPageFactory,
            $resultForwardFactory
        );
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Forward|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $this->_getSession()->clearStorage();
        $orderId = $this->getRequest()->getParam('order_id');
        $order = $this->_orderFactory->create()->load($orderId);

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($order->getId()) {

            $createOrderModel = $this->_getOrderCreateModel();

            $session = $createOrderModel->getSession();
            $session->setCurrencyId($order->getOrderCurrencyCode());
            /* Check if we edit guest order */
            $session->setCustomerId($order->getCustomerId() ?: false);
            $session->setStoreId($order->getStoreId());

            $session->setChargeType(\Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_REPLACEMENT);
            $session->setOriginalOrderId($order->getIncrementId());

            $session->setFreeShippingFlag(1);
            $session->setFreeSurcharge(1);

            /* Initialize catalog rule data with new session values */
            $createOrderModel->initRuleData();

            $resultRedirect->setPath('sales/*');
        } else {
            $resultRedirect->setPath('sales/order/');
        }
        return $resultRedirect;
    }
}
