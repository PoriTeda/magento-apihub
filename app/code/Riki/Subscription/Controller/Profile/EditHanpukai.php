<?php

namespace Riki\Subscription\Controller\Profile;

use Magento\Framework\App\Action\Action;
use Magento\Customer\Model\Session as CustomerSession;

class EditHanpukai extends Action
{
    /* @var \Magento\Customer\Model\Session */
    protected $customerSession;

    /* @var \Magento\Framework\Registry */
    protected $_coreRegistry = null;

    /* @var \Riki\Subscription\Helper\Profile\Data */
    protected $_profileHelper;
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
        \Riki\Subscription\Helper\Profile\Data $profileData,
        CustomerSession $customerSession
    )
    {
        $this->_profileHelper = $profileData;
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

        $profileId = $this->getRequest()->getParam('id');
        $referUrl = $this->_request->getServer('HTTP_REFERER');
        if (!$profileId) {
            return $this->_redirect($referUrl);
        }

        if (!$this->_profileHelper->isHaveViewProfilePermission($customerId, $profileId)) {
            return $this->_redirect('subscriptions/profile/hanpukai');
        }

        $profileModel = $this->_profileHelper->loadProfileModel($profileId);
        if (!$profileModel->getData('profile_id')) {
            return $this->_redirect($referUrl);
        }

        if ((int)$profileModel->getData('hanpukai_qty') < 1) {
            return $this->_redirect($referUrl);
        }

        $this->_coreRegistry->register('current_subscription_profile_customer_id', $customerId);
        $this->_coreRegistry->register('current_subscription_profile_id', $profileId);
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