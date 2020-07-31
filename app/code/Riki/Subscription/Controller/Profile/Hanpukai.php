<?php

namespace Riki\Subscription\Controller\Profile;

use Magento\Framework\App\Action\Action;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Result\PageFactory;

class Hanpukai extends Action
{
    /* @var \Magento\Customer\Model\Session */
    protected $customerSession;

    /* @var \Magento\Framework\Registry */
    protected $_coreRegistry = null;


    /**
     * Construct
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param CustomerSession $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        CustomerSession $customerSession
    )
    {
        $this->_coreRegistry = $coreRegistry;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }


    public function execute()
    {
        $customerId = $this->getCustomerId();

        if (!$this->customerSession->isLoggedIn()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('customer/account/login');
            return $resultRedirect;
        }

        $this->_coreRegistry->register('current_subscription_profile_customer', $customerId);
        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();
    }

    /**
     * Retrieve customer data object
     *
     * @return int
     */
    protected function getCustomerId()
    {
        return $this->customerSession->getCustomerId();
    }

}