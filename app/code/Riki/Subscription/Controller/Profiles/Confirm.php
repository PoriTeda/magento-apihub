<?php


namespace Riki\Subscription\Controller\Profiles;

use Riki\Subscription\Helper\Profile\CampaignHelper;

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
        $summerCampaignData = $this->sessionManager->getData(CampaignHelper::SUMMER_CAMPAIGN_DATA);
        $identifier = $this->sessionManager->getData(CampaignHelper::SUMMER_CAMPAIGN_CACHE_ID);

        // Validate confirm, redirect to my page if $summerCampaignData is empty.
        if (empty($summerCampaignData) && !$identifier) {
            $this->messageManager->addErrorMessage(
                __('There are something wrong via the system. Please contact our call center for helping.')
            );
            return $this->resultRedirectFactory->create()->setPath('customer/account');
        }

        // Check profile
        $profile = $this->profileFactory->create()->load($summerCampaignData['profile_id']);
        if (!$profile->getId()) {
            $this->messageManager->addErrorMessage(__('Profile ID %1 no longer exists', $summerCampaignData['profile_id']));
            return $this->resultRedirectFactory->create()->setPath('customer/account');
        }

        // Store confirm data to registry
        $this->registry->register(CampaignHelper::SUMMER_CAMPAIGN_CACHE_ID, $identifier);
        $this->registry->register(CampaignHelper::SUMMER_CAMPAIGN_DATA, $summerCampaignData);
        $this->registry->register('profile', $profile);
        $this->registry->register('subscription_profile_obj', $profile);

        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        return $this->resultPageFactory->create();
    }
}