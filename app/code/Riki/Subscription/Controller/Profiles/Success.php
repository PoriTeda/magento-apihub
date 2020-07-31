<?php


namespace Riki\Subscription\Controller\Profiles;

use Riki\Subscription\Helper\Profile\CampaignHelper;

class Success extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Riki\Subscription\Model\Multiple\Category\Cache
     */
    protected $multipleCategoryCache;

    /**
     * @var \Riki\Subscription\Logger\LoggerAddProductToProfile
     */
    protected $logger;

    /**
     * Success constructor.
     *
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Riki\Subscription\Model\Multiple\Category\Cache $multipleCategoryCache
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Riki\Subscription\Model\Multiple\Category\Cache $multipleCategoryCache,
        \Riki\Subscription\Logger\LoggerAddProductToProfile $logger,
        \Magento\Framework\App\Action\Context $context
    )
    {
        $this->sessionManager = $sessionManager;
        $this->registry = $registry;
        $this->resultPageFactory = $resultPageFactory;
        $this->multipleCategoryCache = $multipleCategoryCache;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Success Multiple Category action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $successData = $this->sessionManager->getSuccessData();

        // Validate success page, redirect to my page if $successData is empty.
        if (empty($successData)) {
            return $this->resultRedirectFactory->create()->setPath('customer/account');
        }

        // Clear session and cache
        if ($identifier = $this->sessionManager->getData(CampaignHelper::SUMMER_CAMPAIGN_CACHE_ID)) {
            $this->multipleCategoryCache->removeCache($identifier);
        }
        $this->sessionManager->setData(CampaignHelper::SUMMER_CAMPAIGN_DATA, null);
        $this->sessionManager->setData(CampaignHelper::SUMMER_CAMPAIGN_CACHE_ID, null);
        $this->sessionManager->setData(CampaignHelper::SUCCESS_DATA, null);

        // Store success data to registry
        $this->registry->register(CampaignHelper::SUCCESS_DATA, $successData);

        // Log file
        $this->logger->info("Profile {$successData['profile_id']} has added product with those information successfully in summer campaign");
        $this->logger->info("Data: " . json_encode($successData));

        return $this->resultPageFactory->create();
    }
}