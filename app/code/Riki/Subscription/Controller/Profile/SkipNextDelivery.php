<?php


namespace Riki\Subscription\Controller\Profile;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;

class SkipNextDelivery extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileData;

    /**
     * SkipNextDelivery constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param Context $context
     * @param \Riki\Subscription\Helper\Profile\Data $profileData
     * @param CustomerSession $customerSession
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        Context $context,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        CustomerSession $customerSession
    ) {
        $this->profileData = $profileData;
        $this->customerSession = $customerSession;
        $this->registry = $registry;
        $this->resultPageFactory = $resultPageFactory;
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

        // if tmp profile id redirect
        if ($this->profileData->isTmpProfileId($this->getRequest()->getParam('id'))) {
            $this->_redirect('*/*');
            return;
        }

        $profileId = $this->getRequest()->getParam('id');
        $customerId = $this->customerSession->getCustomerId();
        if (!$this->profileData->checkProfileBelongToCustomer($profileId, $customerId)) {
            return $this->_redirect('*/*');
        }

        if ($this->profileData->getTmpProfile($profileId) !== false) {
            $profileId = $this->profileData->getTmpProfile($profileId)->getData('linked_profile_id');
        }
        /** @var \Riki\Subscription\Model\Profile\Profile $objProfile */
        $objProfile  = $this->profileData->load($profileId);

        if (empty($objProfile) || $objProfile->getId() == null) {
            $this->messageManager->addError(__('This subscription profile do not exists.'));
            $this->_redirect('*/*');
            return;
        }

        if ($objProfile->getData('status') == 0) {
            $this->_redirect('*/*');
            return;
        }

        /**
         * Doesn't change frequency for product stock point
         */
        if ((int)$objProfile->getData('stock_point_profile_bucket_id')>0) {
            $this->messageManager->addError(__('There are something wrong in the system. Please re-try again.'));
            $this->_redirect('*/*');
            return;
        }

        $this->registry->register('subscription-profile-id', $profileId);
        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        return $this->resultPageFactory->create();
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
