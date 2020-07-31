<?php
namespace Riki\Subscription\Controller\Disengage\Profile;

use Magento\Framework\Exception\LocalizedException;
use Riki\Subscription\Model\Config\Source\Profile\DisengagementUrl;
use Riki\SubscriptionProfileDisengagement\Model\Reason;

/**
 * Class ValidateQuestionnaire
 * @package Riki\Subscription\Controller\Disengage\Profile
 */
class ValidateQuestionnaire extends \Magento\Framework\App\Action\Action
{
    const ATTENTION_FIELD_AGREE = 'disengagement_agree';
    const ATTENTION_FIELD_CONTACT = 'disengagement_contact_info';
    const ATTENTION_FIELD_MACHINE = 'disengagement_machine';
    const ATTENTION_FIELD_SCHEDULE = 'disengagement_schedule';

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
    protected $disengagement;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var \Riki\Subscription\Helper\Profile\Email
     */
    protected $emailHelper;

    /**
     * @var \Riki\Framework\Helper\Transaction\Database
     */
    protected $dbTransaction;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var \Riki\Questionnaire\Model\QuestionnaireAnswer
     */
    protected $questionnairAnswerModel;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileHelper;

    /**
     * @var \Riki\Questionnaire\Model\ResourceModel\Question
     */
    protected $questionResource;

    /**
     * @var \Riki\StockPoint\Api\BuildStockPointPostDataInterface
     */
    protected $buildStockPointPostData;

    /**
     * @var \Riki\SubscriptionProfileDisengagement\Model\Reason
     */
    protected $reasonModel;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Riki\Subscription\Model\ResourceModel\Profile
     */
    protected $profileResource;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * ValidateQuestionnaire constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Riki\Subscription\Model\Profile\Disengagement $disengagement
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \Riki\Subscription\Helper\Profile\Email $emailHelper
     * @param \Riki\Framework\Helper\Transaction\Database $dbTransaction
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Riki\Questionnaire\Model\QuestionnaireAnswer $questionnaireAnswer
     * @param \Riki\Subscription\Helper\Profile\Data $profileHelper
     * @param \Riki\Questionnaire\Model\ResourceModel\Question $questionResource
     * @param Reason $reasonModel
     * @param \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Riki\Subscription\Model\ResourceModel\Profile $profileResource
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Riki\Subscription\Model\Profile\Disengagement $disengagement,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Riki\Subscription\Helper\Profile\Email $emailHelper,
        \Riki\Framework\Helper\Transaction\Database $dbTransaction,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Questionnaire\Model\QuestionnaireAnswer $questionnaireAnswer,
        \Riki\Subscription\Helper\Profile\Data $profileHelper,
        \Riki\Questionnaire\Model\ResourceModel\Question $questionResource,
        \Riki\SubscriptionProfileDisengagement\Model\Reason $reasonModel,
        \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Subscription\Model\ResourceModel\Profile $profileResource,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->formKeyValidator = $formKeyValidator;
        $this->disengagement = $disengagement;
        $this->sessionManager = $sessionManager;
        $this->emailHelper = $emailHelper;
        $this->dbTransaction = $dbTransaction;
        $this->profileFactory = $profileFactory;
        $this->questionnairAnswerModel = $questionnaireAnswer;
        $this->profileHelper = $profileHelper;
        $this->questionResource = $questionResource;
        $this->buildStockPointPostData = $buildStockPointPostData;
        $this->logger = $logger;
        $this->reasonModel = $reasonModel;
        $this->scopeConfig = $scopeConfig;
        $this->profileResource = $profileResource;
        $this->timezone = $timezone;
        parent::__construct($context);
    }

    /**
     * Validation questionnaire page
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
        $profileDisengagementId = $this->sessionManager->getProfileDisengagement();
        if (!$profileDisengagementId) {
            $this->messageManager->addErrorMessage(__('Profile :%1 does not exist.', $profileDisengagementId));
            return $resultRedirect->setPath(DisengagementUrl::URL_DISENGAGEMENT_LIST);
        } else {
            $profile = $this->disengagement->getProfile($profileDisengagementId);
            if ($profile) {
                /* CAN disengage profile if message is empty */
                $errorMessage = $this->disengagement->getDisengagementProfileErrorMessage($profile);
                if ($errorMessage) {
                    $this->messageManager->addErrorMessage($errorMessage);
                    return $resultRedirect->setPath(DisengagementUrl::URL_DISENGAGEMENT_LIST);
                }
            } else {
                $this->messageManager->addErrorMessage(__('Profile does not exist.'));
                return $resultRedirect->setPath(DisengagementUrl::URL_DISENGAGEMENT_LIST);
            }
        }
        // Load data form post
        $postData = $this->getRequest()->getParams();
        $postReasonIds = $this->_request->getParam('reasons', []);
        //Validate reasons
        if (!empty($postReasonIds)) {
            if (!$this->validateReason(implode(',', $postReasonIds))) {
                $this->messageManager->addErrorMessage(__('Please select a reason for cancellation.'));
                return $resultRedirect->setPath(DisengagementUrl::URL_DISENGAGEMENT_QUESTIONNAIRE);
            } else {
                $this->sessionManager->setSelectedReasons($postReasonIds);
            }
        } else {
            // in case of does not select any reasons but form validation is still passed
            $this->messageManager->addErrorMessage(__('Please select a reason for cancellation.'));
            return $resultRedirect->setPath(DisengagementUrl::URL_DISENGAGEMENT_QUESTIONNAIRE);
        }
        //Validate questionnaire
        if (!$this->validateQuestionnaire($postData)) {
            $this->messageManager->addErrorMessage(__('Please select the target machine.'));
            return $resultRedirect->setPath(DisengagementUrl::URL_DISENGAGEMENT_QUESTIONNAIRE);
        } else {
            $selectedQuestionnaireAnswers = [];
            foreach ($postData as $postKey => $postValue) {
                if (strpos($postKey, 'questionnaire_reply_') !== false) {
                    $selectedQuestionnaireAnswers[$postKey] = $postValue;
                }
            }
            $this->sessionManager->setSelectedQuestionnaireAnswers($selectedQuestionnaireAnswers);
        }
        // pass validations and cancel profile
        if ($this->_doDisengageProfile()) {
            $this->sessionManager->setCancelProfileSuccess(1);
            return $resultRedirect->setPath(DisengagementUrl::URL_DISENGAGEMENT_CONFIRMATION);
        } else {
            $this->messageManager->addErrorMessage(__('An error in disengagement profile'));
            return $resultRedirect->setPath(DisengagementUrl::URL_DISENGAGEMENT_QUESTIONNAIRE);
        }
    }

    /**
     * @return array
     */
    private function _getConfigedQuestionnaireIds()
    {
        $configedQuestionnaireIds = $this->scopeConfig->getValue(
            \Riki\Subscription\Block\Disengage\Profile\AbstractDisengagement::XML_CONFIG_DISENGAGEMENT_QUESTIONNAIRE
        );
        return explode(',', $configedQuestionnaireIds);
    }

    /**
     * Validate answer of questionnaires
     *
     * @param $postData
     * @return bool
     */
    protected function validateQuestionnaire($postData)
    {
        $configedQuestionnaires = $this->questionResource->getConfigedQuestions(
            $this->_getConfigedQuestionnaireIds()
        );
        $questionValidationResult = true;
        if ($configedQuestionnaires) {
            foreach ($configedQuestionnaires as $questionnaireId => $questions) {
                foreach ($questions as $question) {
                    $answerKey = 'questionnaire_reply_'.$questionnaireId.'_'.$question->getQuestionId();
                    if ($question->getData('is_required')) {
                        if (!array_key_exists($answerKey, $postData)) {
                            $questionValidationResult = false;
                        }
                    }
                }
            }
        } else {
            $questionValidationResult = false;
        }
        return $questionValidationResult;
    }

    /**
     * @param string $reasonIds
     * @return bool
     */
    protected function validateReason($reasonIds)
    {
        if ($reasonIds) {
            $reasonIds = explode(',', $reasonIds);
            $visibilities = [Reason::VISIBILITY_FRONTEND, Reason::VISIBILITY_BOTH];
            $reasons = $this->reasonModel->getDisengagementReasons($reasonIds, $visibilities);
            if ($reasons) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    private function _doDisengageProfile()
    {
        $postData = $this->getRequest()->getParams();
        $postReasonIds = $this->_request->getParam('reasons', []);
        $reasonIds = implode(',', $postReasonIds);
        $profileId = $this->sessionManager->getProfileDisengagement();
        //begin transaction
        $this->dbTransaction->beginTransaction();
        try {
            $profileModel = $this->profileFactory->create()->load($profileId);
            $customer = $profileModel->getCustomer();
            $isStockPoint = $profileModel->isStockPointProfile();
            /*
             * Cancel profile
             */
            $profileModel->disengage(
                $reasonIds,
                \Magento\Framework\App\Area::AREA_FRONTEND,
                $customer->getEmail()
            );
            //update profile of customer to KSS
            $consumerDbIdAttribute = $customer->getCustomAttribute('consumer_db_id');
            $consumerDbId = '';
            if ($consumerDbIdAttribute !== null) {
                $consumerDbId = $consumerDbIdAttribute->getValue();
                if ($consumerDbId) {
                    $activeProfileIds = $this->profileResource->getActiveProfileIds(
                        $customer->getId()
                    );
                    $this->disengagement->updateSubscriptionStatusToConsumerDb(
                        $activeProfileIds,
                        $consumerDbId
                    );
                }
            }
            //remove profile from stock point
            if ($isStockPoint) {
                $resultApi = $this->buildStockPointPostData->removeFromBucket($profileId);
                if (isset($resultApi['success']) && !$resultApi['success']) {
                    throw new LocalizedException(__('There are something wrong in the system. Please re-try again.'));
                }
            }
            /*
             * Save questionnaire
             */
            $configedQuestionnaires = $this->questionResource->getConfigedQuestions(
                $this->_getConfigedQuestionnaireIds()
            );
            $this->questionnairAnswerModel->saveAnswerQuestionnaireAfterCancelProfile(
                $configedQuestionnaires,
                $postData,
                $profileId,
                $customer->getId()
            );
            $this->dbTransaction->commit();
            //send email notification to business user
            $businessEmailVariables = [];
            $businessEmailVariables['consumer_db_id'] = $consumerDbId;
            $businessEmailVariables['profile_id'] = $profileModel->getProfileId();
            $businessEmailVariables['cancellation_date'] = $this->timezone->date()->format('Y-m-d');
            $this->emailHelper->sendDisengagementEmailToBusinessUser($businessEmailVariables);
            return true;
        } catch (\Exception $e) {
            $this->logger->error($e);
            $this->dbTransaction->rollback();
            return false;
        }
    }
}
