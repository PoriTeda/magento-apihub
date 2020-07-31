<?php

namespace Riki\Subscription\Block\Disengage\Profile;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Riki\Questionnaire\Model\ResourceModel\Question;
use Riki\Subscription\Model\Profile\Disengagement;
use Riki\Subscription\Model\Config\Source\Profile\DisengagementUrl;
use Riki\Subscription\Model\Profile\Profile;

/**
 * Class AbstractDisengagement
 * @package Riki\Subscription\Block\Disengage\Profile
 */
abstract class AbstractDisengagement extends \Magento\Framework\View\Element\Template
{
    const XML_CONFIG_DISENGAGEMENT_QUESTIONNAIRE = 'riki_questionnaire/questionnaire/questionnaire_profile_disengagement';

    /**
     * @var Disengagement
     */
    protected $disengagementModel;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfile;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Riki\Subscription\Model\ResourceModel\Profile $profileResource
     */
    protected $profileResource;

    /**
     * @var \Riki\SubscriptionProfileDisengagement\Model\Reason
     */
    protected $reasonModel;

    /**
     * @var \Riki\Questionnaire\Model\ResourceModel\Question
     */
    protected $questionResource;

    /**
     * @var \Riki\Questionnaire\Model\Choice
     */
    protected $choiceModel;

    /**
     * @var \Riki\Subscription\Model\Profile\ResourceModel\Profile\Collection $profiles
     */
    protected $profiles;

    /**
     * @var int
     */
    protected $selectedProfileId;

    /**
     * AbstractDisengagement constructor.
     * @param \Magento\Framework\Registry $registry
     * @param Disengagement $disengagementModel
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param \Riki\Subscription\Model\ResourceModel\Profile $profileResource
     * @param \Riki\SubscriptionProfileDisengagement\Model\Reason $reasonModel
     * @param Question $questionResource
     * @param \Riki\Questionnaire\Model\Choice $choiceModel
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Riki\Subscription\Model\Profile\Disengagement $disengagementModel,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Subscription\Model\ResourceModel\Profile $profileResource,
        \Riki\SubscriptionProfileDisengagement\Model\Reason $reasonModel,
        \Riki\Questionnaire\Model\ResourceModel\Question $questionResource,
        \Riki\Questionnaire\Model\Choice $choiceModel,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->disengagementModel = $disengagementModel;
        $this->coreRegistry = $registry;
        $this->timezone = $context->getLocaleDate();
        $this->helperProfile = $helperProfile;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->profileResource = $profileResource;
        $this->reasonModel = $reasonModel;
        $this->questionResource = $questionResource;
        $this->choiceModel = $choiceModel;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('Regular flight cancellation form'));
    }

    /**
     * @return mixed
     */
    public function getCustomerId()
    {
        return $this->customerSession->getCustomerId();
    }

    /**
     * @param null $profileId
     * @param null $includedStatus
     * @return mixed|\Riki\Subscription\Model\Profile\ResourceModel\Profile\Collection
     */
    public function getSubscriptionProfiles($profileId = null, $includedStatus = null)
    {
        if (!$this->profiles) {
            $this->profiles = $this->profileResource->getCustomerSubscriptionProfiles(
                $this->getCustomerId(),
                $profileId,
                $includedStatus
            );
        }
        return $this->profiles;
    }

    /**
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @return mixed
     */
    public function isAllowedToCancelOnFrontend(\Riki\Subscription\Model\Profile\Profile $profile)
    {
        return $profile->getData('is_allow_cancel_from_frontend');
    }

    /**
     * @param $date
     * @return mixed
     */
    public function formatCustomDate($date)
    {
        if ($date) {
            return $this->timezone->date($date)->format('Y-m-d');
        }
    }

    /**
     * @return string
     */
    public function getCurrentDisengagementDate()
    {
        return $this->timezone->date()->format('Y-m-d');
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getConsumerDbId()
    {
        $customerId = $this->getCustomerId();
        try {
            $customer = $this->customerRepository->getById($customerId);
            if ($customer && $customer->getCustomAttribute('consumer_db_id')) {
                return $customer->getCustomAttribute('consumer_db_id')->getValue();
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->_logger->critical($e);
        }
    }

    /**
     * @return mixed
     */
    public function getProfileDisengagement()
    {
        if (!$this->selectedProfileId) {
            $this->selectedProfileId = $this->_session->getProfileDisengagement();
        }
        return $this->selectedProfileId;
    }

    /**
     * @return string
     */
    public function getListValidationUrl()
    {
        return $this->getUrl(DisengagementUrl::URL_DISENGAGEMENT_VALIDATION_LIST);
    }

    /**
     * @return string
     */
    public function getAttentionValidationUrl()
    {
        return $this->getUrl(DisengagementUrl::URL_DISENGAGEMENT_VALIDATION_ATTENTION);
    }

    /**
     * @return string
     */
    public function getQuestionnaireValidationUrl()
    {
        return $this->getUrl(DisengagementUrl::URL_DISENGAGEMENT_VALIDATION_QUESTIONNAIRE);
    }

    /**
     * Build a data model for Disengagement profile object
     * @param Profile $profile
     * @param $nextThreeDeliveries
     * @return \Magento\Framework\DataObject
     */
    public function buildDisengagementProfile(
        \Riki\Subscription\Model\Profile\Profile $profile,
        $nextThreeDeliveries
    ) {
        $disengagementItemData = [];
        $disengagementItemData['cancellation_conditions'] = '';
        $disengagementItemData['next_delivery_date_message'] = '';
        $disengagementItemData['next_next_delivery_date_message'] = '';
        $disengagementItemData['course_name'] = $profile->getData('subscription_course_name');
        $disengagementItemData['course_code'] = $profile->getData('course_code');
        $disengagementItemData['profile_id'] = $profile->getId();
        $disengagementItemData['is_allowed_to_cancel_from_frontend'] = $profile->getData('is_allow_cancel_from_frontend');
        $disengagementItemData['next_delivery1'] = $this->formatCustomDate($nextThreeDeliveries[0]['delivery_date']);
        $disengagementItemData['next_delivery2'] = $this->formatCustomDate($nextThreeDeliveries[1]['delivery_date']);
        $disengagementItemData['is_disabled'] = true;
        if ($profile->getData('is_allow_cancel_from_frontend')) {
            $profileOrderTime = (int)$profile->getData('order_times');
            $courseMinimumOrderTime = (int)$profile->getData('minimum_order_times');
            if ($profileOrderTime >= $courseMinimumOrderTime) {
                $disengagementItemData['is_disabled'] = false;
                $disengagementItemData['cancellation_conditions'] = '';
                $disengagementItemData['next_delivery_date_message'] = __('Able to disengage');
                $disengagementItemData['next_next_delivery_date_message'] = __('Able to disengage');
            } else {
                $disengagementItemData['cancellation_conditions'] = __('It is necessary to continue more than %1 times', $courseMinimumOrderTime);
                $disengagementItemData['next_delivery_date_message'] = __('Can not disengage');
                $disengagementItemData['next_next_delivery_date_message'] = __('Can not disengage');
            }
        } else {
            $disengagementItemData['cancellation_conditions'] = __('You can not cancel this profile');
            $disengagementItemData['next_delivery_date_message'] = __('Can not disengage');
            $disengagementItemData['next_next_delivery_date_message'] = __('Can not disengage');
        }
        return new \Magento\Framework\DataObject($disengagementItemData);
    }

    /**
     * @return array
     */
    protected function getConfiguredQuestionnaires()
    {
        $questionnaireIdsRaw = $this->_scopeConfig->getValue(
            self::XML_CONFIG_DISENGAGEMENT_QUESTIONNAIRE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($questionnaireIdsRaw) {
            return explode(',', $questionnaireIdsRaw);
        }
    }

    /**
     * Get Questionnaires include their choices
     *
     * @return array|bool
     */
    public function getQuestionnaires()
    {
        $configedQuestionnaireIds = $this->getConfiguredQuestionnaires();
        if ($configedQuestionnaireIds) {
            $questionItems = $this->questionResource->getConfigedQuestions($configedQuestionnaireIds, false);
            $questionIds = [];
            $questionFilterData = [];
            if ($questionItems) {
                foreach ($questionItems as $questionItem) {
                    $questionIds[$questionItem->getData('question_id')] = $questionItem->getData('question_id');
                }
                $choiceItems = $this->choiceModel->getChoicesByQuestionIds($questionIds);
                foreach ($questionItems as $questionItem) {
                    $questionFilterData[$questionItem->getData('enquete_id')]['name'] = $questionItem->getData('name');
                    $questionData = [
                        'choices' => '',
                        'question' => $questionItem
                    ];
                    if (array_key_exists($questionItem->getData('question_id'), $choiceItems)) {
                        $questionData['choices'] = $choiceItems[$questionItem->getData('question_id')];
                    }
                    $questionFilterData[$questionItem->getData('enquete_id')]['questions'][] = $questionData;
                }
            }
            return $questionFilterData;
        } else {
            return false;
        }
    }

    /**
     * @param null $includeStatus
     * @return \Magento\Framework\DataObject
     */
    public function getProfileDisengagementItem($includeStatus = null)
    {
        $profileId = $this->coreRegistry->registry('disengaged_profile_id')
            ? $this->coreRegistry->registry('disengaged_profile_id')
            : $this->getProfileDisengagement();

        $profile = $this->helperProfile->load($profileId);
        $lastShipmentDate = $this->profileResource->getLastShipmentDeliveryDateOfProfile($profileId);
        $data = [
            'profile_id' => $profile->getProfileId(),
            'course_name' => $profile->getCourseName(),
            'course_code' => $profile->getSubscriptionCourse()->getCourseCode(),
            'last_shipment_date' => $this->formatCustomDate($lastShipmentDate)
        ];
        return new \Magento\Framework\DataObject($data);
    }
}
