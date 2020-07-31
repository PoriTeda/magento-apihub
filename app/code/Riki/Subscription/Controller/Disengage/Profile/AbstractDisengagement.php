<?php
namespace Riki\Subscription\Controller\Disengage\Profile;

use Magento\Customer\Model\Session as CustomerSession;
use Riki\Subscription\Model\Config\Source\Profile\DisengagementUrl;

/**
 * Class AbstractDisengagement
 * @package Riki\Subscription\Controller\Disengage\Profile
 */
abstract class AbstractDisengagement extends \Magento\Framework\App\Action\Action
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
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var \Riki\Subscription\Model\Profile\Disengagement
     */
    protected $disengagementModel;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\Url\EncoderInterface
     */
    protected $urlEncoder;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * AbstractDisengagement constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param CustomerSession $customerSession
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \Riki\Subscription\Model\Profile\Disengagement $disengagementModel
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Riki\Subscription\Model\Profile\Disengagement $disengagementModel,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->sessionManager = $sessionManager;
        $this->disengagementModel = $disengagementModel;
        $this->urlBuilder = $context->getUrl();
        $this->urlEncoder = $urlEncoder;
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * Destroy all sessions
     */
    protected function cleanDisengagedProfile()
    {
        $this->sessionManager->unsProfileDisengagement();
        $this->sessionManager->unsAttentionNote();
        $this->sessionManager->unsSelectedReasons();
        $this->sessionManager->unsCancelProfileSuccess();
        $this->sessionManager->unsSelectedQuestionnaireAnswers();
    }

    /**
     * Validate profile Id which stored in session
     * @return bool|int
     */
    protected function validateProfileIdFromSession()
    {
        $profileId = $this->sessionManager->getProfileDisengagement();
        if ($profileId) {
            $profile = $this->disengagementModel->getProfile($profileId);
            if ($profile) {
                if (!($errorMessage = $this->disengagementModel->getDisengagementProfileErrorMessage($profile))) {
                    return true;
                } else {
                    $this->messageManager->addErrorMessage($errorMessage);
                    $this->sessionManager->unsProfileDisengagement();
                }
            }
        }
        return false;
    }
}
