<?php
namespace Riki\Sales\Controller\Adminhtml\Order\Create;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\Model\View\Result\ForwardFactory;

class Reorder extends \Magento\Sales\Controller\Adminhtml\Order\Create\Reorder
{

    /** @var  \Psr\Log\LoggerInterface */
    protected $_logger;

    /**
     * @param Action\Context $context
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Framework\Escaper $escaper
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Action\Context $context,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Framework\Escaper $escaper,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        \Magento\Sales\Model\Order\Reorder\UnavailableProductsProvider $unavailableProductsProvider,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Helper\Reorder $reorderHelper,
        \Psr\Log\LoggerInterface $logger
    ){

        $this->_logger = $logger;

        parent::__construct(
            $context,
            $productHelper,
            $escaper,
            $resultPageFactory,
            $resultForwardFactory,
            $unavailableProductsProvider,
            $orderRepository,
            $reorderHelper
        );
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Forward|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $this->_getSession()->clearStorage();
        $orderId = $this->getRequest()->getParam('order_id');
        $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($orderId);
        if (!$this->_objectManager->get('Magento\Sales\Helper\Reorder')->canReorder($order->getEntityId())) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($order->getId()) {
            $order->setReordered(true);
            $this->_getSession()->setUseOldShippingMethod(true);
            try{
                $this->_getOrderCreateModel()->initFromOrder($order);
                $resultRedirect->setPath('sales/*');
            }catch (\Magento\Framework\Exception\LocalizedException $e){
                $this->messageManager->addError($e->getMessage());
                $resultRedirect->setPath('sales/order/');
            }catch (\Exception $e){
                $this->_logger->critical($e);
                $this->messageManager->addError(__('Process error, please try again later.'));
                $resultRedirect->setPath('sales/order/');
            }

        } else {
            $resultRedirect->setPath('sales/order/');
        }
        return $resultRedirect;
    }
}
