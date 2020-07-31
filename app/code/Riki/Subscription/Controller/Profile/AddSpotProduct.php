<?php

namespace Riki\Subscription\Controller\Profile;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Registry;
use Riki\Subscription\Model\Profile\ProfileFactory;

class AddSpotProduct extends Action
{
    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var ProfileFactory
     */
    protected $_profileFactory;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfile;

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        Registry $registry,
        ProfileFactory $profileFactory,
        \Riki\Subscription\Helper\Profile\Data $helperProfile
    )
    {
        $this->_profileFactory = $profileFactory;
        $this->_registry = $registry;
        $this->_customerSession = $customerSession;
        $this->helperProfile = $helperProfile;
        parent::__construct($context);
    }

    public function execute()
    {
        if (!$this->_customerSession->isLoggedIn()) {
            return $this->_redirect('customer/account/login');
        }
        $customerId = $this->_customerSession->getCustomerId();
        $profileId = $this->_request->getParam('id');
        $validate = $this->helperProfile->checkProfileBelongToCustomer($profileId,$customerId);
        if(!$validate){
            return $this->_redirect('*/*');
        }
        $profile = $this->_profileFactory->create()->load($profileId);
        if (!$profile->getId()) {
            $this->messageManager->addError(__('The subscription profile no longer exists'));
            $this->_redirect('/');
        }
        if($this->helperProfile->isTmpProfileId($profileId,$profile)){
            return $this->_redirect('*/*');
        }
        $this->_registry->register('profile_id', $profileId);

        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
