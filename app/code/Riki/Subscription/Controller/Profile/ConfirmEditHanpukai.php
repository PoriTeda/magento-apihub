<?php


namespace Riki\Subscription\Controller\Profile;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Riki\Subscription\Model\Constant;

class ConfirmEditHanpukai extends Action
{

    /* @var \Magento\Framework\View\Result\PageFactory */
    protected $_resultPageFactory;

    /* @var \Magento\Framework\Registry */
    protected $_registry;

    /* @var \Magento\Customer\Model\Session */
    protected $customerSession;

    /* @var \Riki\Subscription\Helper\Profile\Data */
    protected $_profileData;

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        Context $context,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        CustomerSession $customerSession
    ){
        $this->_profileData = $profileData;
        $this->customerSession = $customerSession;
        $this->_registry = $registry;
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('customer/account/login');
            return $resultRedirect;
        }

        if (!$this->getRequest()->getParam('profile_id')) {
            $this->_redirect('*/*');
            return;
        }


        $profileId = $this->getRequest()->getParam('profile_id');

        /** @var \Riki\Subscription\Model\Profile\Profile $objProfile */
        $objProfile  = $this->_profileData->load($profileId);
        // if tmp profile id redirect
        if ($this->_profileData->isTmpProfileId($profileId,$objProfile)) {
            $this->_redirect('*/*');
            return;
        }

        if ( empty($objProfile) || $objProfile->getId() == null) {
            $this->messageManager->addError(__('This subscription profile do not exists.'));
            $this->_redirect('*/*');
        }

        $this->_registry->unregister('subscription_profile_obj');
        $this->_registry->register('subscription_profile_obj', $objProfile);
        $this->_registry->register(Constant::REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_PAYMENT_METHOD, $this->getRequest()->getParam('payment_method'));
        $this->_registry->register(Constant::REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_CHANGE_TYPE,
        $this->getRequest()->getParam('profile_type'));
        $this->_registry->register(Constant::REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_PROFILE_ID, $profileId);
        $this->_registry->register(Constant::REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_Preferred_Payment_Method, $this->getRequest()->getParam('preferred_payment_method'));

        /**
         * set coupon code
         */
        $couponCode = $this->getRequest()->getParam('data_coupon_code');
        $this->_registry->unregister(Constant::REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_COUPON_CODE);
        $this->_registry->register(Constant::REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_COUPON_CODE,$couponCode);

        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        return $this->_resultPageFactory->create();
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
