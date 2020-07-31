<?php
namespace Riki\Subscription\Controller\Disengage\Profile;

use Riki\Subscription\Model\Config\Source\Profile\DisengagementUrl;

/**
 * Class ValidateList
 * @package Riki\Subscription\Controller\Disengage\Profile
 */
class ValidateList extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Registry|null
     */
    protected $coreRegistry = null;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * @var \Riki\Subscription\Model\Profile\Disengagement
     */
    protected $profileDisengagement;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * ValidateList constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Riki\Subscription\Model\Profile\Disengagement $profileDisengagement
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Riki\Subscription\Model\Profile\Disengagement $profileDisengagement,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->formKeyValidator = $formKeyValidator;
        $this->profileDisengagement = $profileDisengagement;
        $this->sessionManager = $sessionManager;
        parent::__construct($context);
    }

    /**
     * Validate list page
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        // Check customer login
        if (!$this->customerSession->isLoggedIn()) {
            return $resultRedirect->setPath(DisengagementUrl::URL_CUSTOMER_LOGIN);
        }
        // Check form key
        if (!$this->formKeyValidator->validate($this->getRequest()) || !$this->getRequest()->isPost()) {
            $this->messageManager->addErrorMessage(__('Form key is not valid.'));
            return $resultRedirect->setPath(DisengagementUrl::URL_DISENGAGEMENT_LIST);
        }
        // Load data form post
        $postData = $this->getRequest()->getParams();
        if (isset($postData['profile_id'])) {
            $profile = $this->profileDisengagement->getProfile($postData['profile_id']);
            if ($profile) {
                $errorMessage = $this->profileDisengagement->getDisengagementProfileErrorMessage($profile);
                if (!$errorMessage) {
                    $this->sessionManager->setProfileDisengagement($profile->getProfileId());
                    return $resultRedirect->setPath(DisengagementUrl::URL_DISENGAGEMENT_ATTENTION);
                } else {
                    $this->messageManager->addErrorMessage($errorMessage);
                    return $resultRedirect->setPath(DisengagementUrl::URL_DISENGAGEMENT_LIST);
                }
            } else {
                $this->messageManager->addErrorMessage(__('Profile does not exist.'));
                return $resultRedirect->setPath(DisengagementUrl::URL_DISENGAGEMENT_LIST);
            }
        }
        $this->messageManager->addErrorMessage(__('Profile does not exist.'));
        return $resultRedirect->setPath(DisengagementUrl::URL_DISENGAGEMENT_LIST);
    }
}
