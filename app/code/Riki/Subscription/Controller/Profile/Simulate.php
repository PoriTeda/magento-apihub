<?php

namespace Riki\Subscription\Controller\Profile;

/* Use exception namespace */
use Magento\Framework\Exception;

class Simulate extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Riki\Subscription\Model\Profile\Profile $profileModel
     */
    protected $profileModel;
    /**
     * @var \Magento\Customer\Model\Session $customerSession
     */
    protected $customerSession;
    /**
     * @var \Magento\Framework\Message\ManagerInterface $messageManager
     */
    protected $messageManager;
    /**
     * @var \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    protected $resultPageFactory;
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry $coreRegistry
     */
    protected $coreRegistry = null;
    /**
     * @var \Riki\Subscription\Helper\Order\Simulator $profileEmulator
     */
    protected $profileEmulatorHelper = null;

    /* @var \Riki\Subscription\Helper\Profile\Data */
    protected $_helperProfile;

    protected $logger;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Riki\Subscription\Model\Profile\Profile $profileModel,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Riki\Subscription\Helper\Order\Simulator $profileEmulatorHelper,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->_helperProfile = $helperProfile;
        $this->profileModel = $profileModel;
        $this->customerSession = $customerSession;
        $this->messageManager = $context->getMessageManager();
        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry = $coreRegistry;
        $this->profileEmulatorHelper = $profileEmulatorHelper;
        $this->logger = $logger;

        parent::__construct($context);
    }

    /**
     * Simulate subscription for next order creation
     *
     * @return void
     */
    public function execute()
    {
        $profileId = $this->getRequest()->getParam('profile_id', false);
        if ($this->_helperProfile->getTmpProfile($profileId) !== false) {
            $profileId = $this->_helperProfile->getTmpProfile($profileId)->getData('linked_profile_id');
        }
        /* if profile id is missing we put an error and redirect them to previous page */
        if (!$profileId) {
            $this->messageManager->addError(__("Profile with is not exists"));
            $this->_redirect('*/*/view' , [ 'id' => $profileId ]);
            return;
        }

        /* if customer does not loggined , we fall back them */
        if (!$this->customerSession->isLoggedIn()) {
            $this->messageManager->addError(__("Customer does not loggined"));
            $this->_redirect('*/*/view' , [ 'id' => $profileId ]);
            return;
        }

        /* try to load subscription profile */
        try {
            $profileModel = $this->profileModel->load($profileId);
        } catch (\Exception $e) {
            $this->messageManager->addError(__("Profile with is not exists"));
            $this->_redirect('*/*/view' , [ 'id' => $profileId ]);
            return;
        }
        /* profile is loaded successfully */
        if ($profileModel->getId()) {
            $this->coreRegistry->register("current_profile", $profileModel);
        } else {
            throw new Exception\NoSuchEntityException(__("Subscription Profile does not exist"));
        }

        try {
            /** @var \Riki\Subscription\Model\Emulator\Order $emulateOrder */
            $emulateOrder = $this->profileEmulatorHelper->createMageOrder($profileModel->getId());
        } catch (Exception\LocalizedException $e) {
            $this->logger->info($e->getMessage());
        } catch (\Exception $exception) {
            $this->logger->critical($exception);
            $this->messageManager->addError(__('We got error while simulate order'));
            $this->_redirect('*/*/view', ['id' => $profileModel->getData('profile_id')]);
            return;
        }

        if(!\Zend_Validate::is($emulateOrder , 'NotEmpty')){
            $this->messageManager->addError(__("We got error while simulate order"));
            $this->_redirect('*/*/view' , [ 'id' => $profileModel->getData('profile_id') ]);
            return;
        }

        $this->coreRegistry->register('current_order' , $emulateOrder);
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }
}

