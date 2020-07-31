<?php
namespace Riki\ThirdPartyImportExport\Controller\Adminhtml;

use Magento\Framework\Controller\ResultFactory;

abstract class Order extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Riki\ThirdPartyImportExport\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * Order constructor.
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\ThirdPartyImportExport\Model\OrderFactory $orderFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Riki\ThirdPartyImportExport\Model\OrderFactory $orderFactory,
        \Magento\Backend\App\Action\Context $context
    )
    {
        $this->_registry = $registry;
        $this->_orderFactory = $orderFactory;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function initResultPage()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        return $resultPage;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function initLayoutPage()
    {
        $layoutPage = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);

        return $layoutPage;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function initForwardPage()
    {
        $forwardPage = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);

        return $forwardPage;
    }

    /**
     * @return $this
     */
    public function initCurrentOrder()
    {
        $orderId = $this->_request->getParam('id');
        if (!$orderId) {
            /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
            $resultForward = $this->initForwardPage();
            return $resultForward->forward('noroute');
        }

        $order = $this->_orderFactory->create()->load($orderId);

        if ($order->getId()) {
            $this->_registry->register('current_order', $order);
            return $order;
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setUrl($this->getUrl('*/*/history'));
    }
}
