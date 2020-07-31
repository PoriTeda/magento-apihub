<?php

namespace Riki\Sales\Controller\Shipment;

use Magento\Sales\Controller\OrderInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class History extends \Magento\Framework\App\Action\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    protected $_addressFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    protected $_shipmentModel;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Model\AddressFactory $addressFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Sales\Model\Order\Shipment $shipmentModel
    ) {
        $this->_coreRegistry = $registry;
        $this->resultPageFactory = $resultPageFactory;
        $this->_addressFactory = $addressFactory;
        $this->_customerSession = $customerSession;
        $this->_shipmentModel = $shipmentModel;

        parent::__construct($context);
    }

    /**
     * Retrieve customer session object
     *
     * @return \Magento\Customer\Model\Session
     */
    protected function _getSession()
    {
        return $this->_customerSession;
    }

    /**
     * Check customer authentication
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
        if (!$this->_getSession()->authenticate()) {
            $this->_actionFlag->set('', 'no-dispatch', true);
        }
        return parent::dispatch($request);
    }

    /**
     * Customer order delivery
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id', 0);
        if(!$id || !$this->_initAddress($id)) {
            $this->messageManager->addError(__('This delivery no longer exists.'));
            /** \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('customer/address/');
        }

        $filterType = $this->getRequest()->getParam('legacy', 0);

        // 4. Register model to use later in blocks
        $this->_coreRegistry->register('address_id', $id);
        $this->_coreRegistry->register('is_legacy', boolval($filterType));

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Shipment History By Address'));

        /** @var \Magento\Framework\View\Element\Html\Links $navigationBlock */
        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('sales/order/history');
        }

        return $resultPage;
    }

    /**
     * @param $id
     * @return bool
     */
    protected function _initAddress($id){
        /** @var /Magento/Customer/Model/Address $address */
        $address = $this->_addressFactory->create()->load($id);

        if(
            $address->getId() &&
            $address->getCustomerId() == $this->_customerSession->getCustomerId()
        ){
            $this->_coreRegistry->register('current_address', $address);
            return $address;
        }

        return false;
    }
}
