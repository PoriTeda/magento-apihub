<?php

namespace Riki\Subscription\Controller\Multiple\Category;

class View extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

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
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var \Riki\Subscription\Model\Multiple\Category\Cache
     */
    protected $multipleCategoryCache;

    /**
     * View constructor.
     *
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Subscription\Helper\Profile\CampaignHelper $campaignHelper
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \Riki\Subscription\Model\Multiple\Category\Cache $multipleCategoryCache
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Riki\Subscription\Helper\Profile\CampaignHelper $campaignHelper,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Riki\Subscription\Model\Multiple\Category\Cache $multipleCategoryCache,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->customerSession = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->registry = $registry;
        $this->campaignHelper = $campaignHelper;
        $this->sessionManager = $sessionManager;
        $this->multipleCategoryCache = $multipleCategoryCache;
        parent::__construct($context);
    }

    /**
     * View multiple category  action
     *
     * @return \Magento\Framework\View\Result\Page|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $campaignId = $this->getRequest()->getParam('id');

        // Validate $campaignId
        $campaignModel = $this->campaignHelper->loadCampaign($campaignId);
        if (!$campaignModel->getId()) {
            $this->messageManager->addErrorMessage(
                __('There are something wrong via the system. Please contact our call center for helping.')
            );
            return $resultRedirect->setPath('customer/account');
        }

        // Clear session and cache
        if ($identifier = $this->sessionManager->getData('multiple_category_cache_id')) {
            $this->multipleCategoryCache->removeCache($identifier);
        }
        $this->sessionManager->setData('multiple_category_data', null);
        $this->sessionManager->setData('multiple_category_cache_id', null);
        $this->sessionManager->setData('success_data', null);

        $this->registry->register('campaign_id', $campaignId);
        $this->registry->register('campaign', $campaignModel);

        return $this->resultPageFactory->create();
    }
}
