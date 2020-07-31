<?php

namespace Riki\Subscription\Controller\Profile;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;


class HanpukaiPlan extends Action
{

    /* @var \Magento\Framework\Registry */
    protected $_registry;

    /* @var \Magento\Customer\Model\Session */
    protected $customerSession;

    /* @var \Riki\Subscription\Helper\Profile\Data */
    protected $_profileData;


    public function __construct(
        \Magento\Framework\Registry $registry,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        CustomerSession $customerSession,
        Context $context
    ){
        $this->_registry = $registry;
        $this->_profileData = $profileData;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('customer/account/login');
            return $resultRedirect;
        }

        if (!$this->getRequest()->getParam('id')) {
            $this->_redirect('*/*');
            return;
        }

        $profileId = $this->getRequest()->getParam('id');

        $customerId = $this->customerSession->getCustomerId();
        if (!$this->_profileData->isHaveViewProfilePermission($customerId, $profileId)) {
            return $this->_redirect('subscriptions/profile/hanpukai');
        }

        /** @var \Riki\Subscription\Model\Profile\Profile $objProfile */
        $objProfile  = $this->_profileData->load($profileId);

        if ( empty($objProfile) || $objProfile->getId() == null) {
            $this->messageManager->addError(__('This subscription profile do not exists.'));
            $this->_redirect('*/*');
            return;
        }

        if ($objProfile->getData('status') != 1) {
            $this->_redirect('*/*');
            return;
        }

        $this->_registry->register('subscription-profile-id', $profileId);
        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();
    }

}