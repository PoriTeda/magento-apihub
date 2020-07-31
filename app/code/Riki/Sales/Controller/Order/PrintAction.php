<?php
namespace Riki\Sales\Controller\Order;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Controller\AbstractController\OrderLoaderInterface;

/**
 * Class PrintAction
 * @package Riki\Sales\Controller\Order
 */
class PrintAction extends \Magento\Sales\Controller\Order\PrintAction
{
    /**
     * @var \Magento\Sales\Controller\AbstractController\OrderLoaderInterface
     */
    protected $orderLoader;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $orderHelper;

    /**
     * PrintAction constructor.
     *
     * @param Context $context
     * @param OrderLoaderInterface $orderLoader
     * @param PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        Context $context,
        OrderLoaderInterface $orderLoader,
        PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Riki\Sales\Helper\Order $orderHelper
    ) {
        $this->coreRegistry = $registry;
        $this->orderHelper = $orderHelper;
        parent::__construct(
            $context,
            $orderLoader,
            $resultPageFactory
        );
    }

    /**
     * Print Order Action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->orderLoader->load($this->_request);
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($result instanceof \Magento\Framework\Controller\ResultInterface) {
            return $result;
        }
        $data = $this->getRequest()->getParams();
        $this->coreRegistry->register('name_info_customer_print', $data);
        $receiptCounter = $this->orderHelper->increaseReceiptNumberPrinting($data);

        if ($receiptCounter > 1) {
            $this->messageManager->addError(__('You already printed this invoice over 2 times.'));
            return $resultRedirect->setPath('sales/order/view/order_id/'.$data['order_id'].'/');
        } else {
            /** @var \Magento\Framework\View\Result\Page $resultPage */
            $resultPage = $this->resultPageFactory->create();
            $resultPage->addHandle('print');
            $resultPage = $this->resultPageFactory ->create();
            // NED-5226 remove info block here since remove in layout does not work
            $resultPage->getLayout()->unsetElement('sales.order.print.info');
            $block = $resultPage->getLayout()->getBlock('sales.order.info.print');
            $block->setData('current_counter', $receiptCounter);
            return $resultPage;
        }
    }
}
