<?php

namespace Riki\Subscription\Controller\Multiple\Category;

class Confirm extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Riki\Subscription\Helper\Profile\CampaignHelper
     */
    protected $campaignHelper;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;

    /**
     * Confirm constructor.
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Riki\Subscription\Helper\Profile\CampaignHelper $campaignHelper
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     */
    public function __construct(
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Action\Context $context,
        \Riki\Subscription\Helper\Profile\CampaignHelper $campaignHelper,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
    ) {
        $this->sessionManager = $sessionManager;
        $this->resultPageFactory = $resultPageFactory;
        $this->registry = $registry;
        $this->campaignHelper = $campaignHelper;
        $this->profileFactory = $profileFactory;
        parent::__construct($context);
    }

    /**
     * Confirm action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $multipleCategoryData = $this->sessionManager->getData('multiple_category_data');
        $identifier = $this->sessionManager->getData('multiple_category_cache_id');

        // Validate confirm, redirect to my page if $multipleCategoryData is empty.
        if (empty($multipleCategoryData) && !$identifier) {
            $this->messageManager->addErrorMessage(
                __('There are something wrong via the system. Please contact our call center for helping.')
            );
            return $this->resultRedirectFactory->create()->setPath('customer/account');
        }

        // Check profile
        $profile = $this->profileFactory->create()->load($multipleCategoryData['profile_id']);
        if (!$profile->getId()) {
            $this->messageManager->addErrorMessage(__('Profile ID %1 no longer exists', $multipleCategoryData['profile_id']));
            return $this->resultRedirectFactory->create()->setPath('customer/account');
        }

        // Store confirm data to registry
        $this->registry->register('multiple_category_cache_id', $identifier);
        $this->registry->register('multiple_category_data', $multipleCategoryData);
        $this->registry->register('profile', $profile);
        $this->registry->register('subscription_profile_obj', $profile);

        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        return $this->resultPageFactory->create();
    }
}
