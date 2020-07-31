<?php

namespace Riki\Subscription\Controller\Multiple\Category;

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
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->sessionManager = $sessionManager;
        $this->registry = $registry;
        $this->resultPageFactory = $resultPageFactory;
        $this->multipleCategoryCache = $multipleCategoryCache;
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
        if ($identifier = $this->sessionManager->getData('multiple_category_cache_id')) {
            $this->multipleCategoryCache->removeCache($identifier);
        }
        $this->sessionManager->setData('multiple_category_data', null);
        $this->sessionManager->setData('multiple_category_cache_id', null);
        $this->sessionManager->setData('success_data', null);

        // Store success data to registry
        $this->registry->register('success_data', $successData);

        // output into log file
        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = $this->_objectManager->get(\Psr\Log\LoggerInterface::class);
        $logger->debug("Profile {$successData["profile_id"]} has added SPOT successfully in summer campaign!!");

        return $this->resultPageFactory->create();
    }
}
