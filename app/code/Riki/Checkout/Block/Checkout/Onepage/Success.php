<?php

namespace Riki\Checkout\Block\Checkout\Onepage;

use Riki\Questionnaire\Model\Questionnaire;
use Riki\Questionnaire\Model\QuestionnaireFactory;
use Riki\SubscriptionCourse\Model\CourseFactory;

/**
 * Class Success
 * @package Riki\Checkout\Block\Checkout\Onepage
 */
class Success extends \Magento\Checkout\Block\Onepage\Success
{
    /**
     * @var \Riki\Questionnaire\Model\QuestionnaireFactory
     */
    protected $questionnaireFactory;

    /**
     * @var \Riki\Questionnaire\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var ProfileRepository
     */
    protected $_profileRepository;
    /* @var \Riki\SubscriptionCourse\Model\CourseFactory */
    protected $courseFactory;

    /**
     * Success constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param QuestionnaireFactory $questionnaireFactory
     * @param \Riki\Questionnaire\Helper\Data $dataHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        QuestionnaireFactory $questionnaireFactory,
        \Riki\Questionnaire\Helper\Data $dataHelper,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        CourseFactory $courseFactory,
        array $data = []
    )
    {
        $this->questionnaireFactory = $questionnaireFactory;
        $this->dataHelper = $dataHelper;
        $this->currentCustomer = $currentCustomer;
        $this->scopeConfig = $context->getScopeConfig();
        $this->_profileRepository = $profileRepository;
        $this->courseFactory = $courseFactory;
        parent::__construct(
            $context,
            $checkoutSession,
            $orderConfig,
            $httpContext,
            $data);
    }

    public function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Order Success Page'));
        return parent::_prepareLayout();
    }

    /**
     * Get order in success page
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $order = $this->_checkoutSession->getLastRealOrder();
    }

    /**
     * Get List Questionnaire by SKUs of order in success page
     *
     * @return array
     */
    public function getListQuestionnaireBySKUsOrder()
    {
        $order = $this->getOrder();

        $orderItems = $order->getAllItems();

        $output = $skuArr = [];

        foreach ($orderItems as $item) {
            $skuArr[] = $item->getSku();
        }

        // Get question by subscription
        if ($order->getData('subscription_profile_id')) {
            $subScriptionData = $this->_profileRepository->get($order->getData('subscription_profile_id'));
            if ($subScriptionData->getCourseId()) {
                $dataCourse = $this->courseFactory->create()->load($subScriptionData->getCourseId());
                if ($dataCourse->getData('course_code')) {
                    $skuArr[] = $dataCourse->getData('course_code');
                }
            }
        }
        if (!empty($skuArr)) {
            $itemData = $this->dataHelper->getQuestionnaireBySKUs(
                $skuArr,
                Questionnaire::VISIBILITY_ON_SUCCESS_PAGE
            );
            if (!empty($itemData)) {
                $output['questionnaire'] = $itemData;
            }
        }
        if (empty($output)) {
            $output['questionnaire'] = $this->dataHelper->getQuestionnaireDefault(Questionnaire::VISIBILITY_ON_SUCCESS_PAGE);
        }

        return $output;
    }

    /**
     * Get link save answer
     *
     * @return string
     */
    public function getSaveAnswerQuestionnaireUrl()
    {
        return $this->getUrl('questionnaire/answers/save/');
    }

    public function getCreatedAt()
    {
        $order = $this->getOrder();
        $createdAt = $order->getCreatedAt();
        return $this->_localeDate->formatDateTime($createdAt, 2, 2);

    }

    /**
     * @return string
     */
    public function getStoreCode()
    {
        return $this->_storeManager->getStore()->getCode();
    }

    /**
     * @return bool|mixed
     */
    public function checkMember()
    {
        if ($this->getCustomer() != null) {
            $memberShip = $this->getCustomer()->getCustomAttribute('membership');
            if ($memberShip) {
                return $memberShip->getValue();

            } else {
                return false;
            }
        }
        return false;
    }

    public function getCustomer()
    {
        try {
            return $this->currentCustomer->getCustomer();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Get System Config
     *
     * @param $path
     *
     * @return mixed
     */
    public function getSystemConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $config = $this->scopeConfig->getValue($path, $storeScope);
        return $config;
    }

    /**
     * @return mixed
     */
    public function getUrlTopPage()
    {
        $member = $this->checkMember();
        $storeCode = $this->getStoreCode();
        $urlString = 'thank_you_page_link_to_top_page/link_to_top_page/link_to_top_page_';
        $urlTopPage = $this->getSystemConfig($urlString . $storeCode);
        if ($member) {
            $memberList = explode(',', $member);
            if ($storeCode) {
                if ($storeCode == 'ec') {
                    if (in_array(3, $memberList)) { // Ambassador Member
                        $urlTopPage = $this->getSystemConfig($urlString . 'amb');
                    }
                    if (in_array(15, $memberList)) { // Wellness Ambassador
                        $urlTopPage = $this->getSystemConfig($urlString . 'wellness_amb');
                    }
                }
            }
        }
        $website = $this->checkCustomerBelongWebsite();
        if ($website && $storeCode != 'ec') {
            $websiteList = explode(',', $website);
            if ($storeCode == 'employee' && in_array(2, $websiteList)) { // 2 id of employee site
                $urlTopPage = $this->getSystemConfig($urlString . 'employee');
            } elseif ($storeCode == 'cnc' && in_array(3, $websiteList)) { // 3 id of cnc site
                $urlTopPage = $this->getSystemConfig($urlString . 'cnc');
            } elseif ($storeCode == 'cis' && in_array(4, $websiteList)) { // 4 id of cis site
                $urlTopPage = $this->getSystemConfig($urlString . 'cis');
            } elseif ($storeCode == 'milan' && in_array(5, $websiteList)) { // 5 id of milan site
                $urlTopPage = $this->getSystemConfig($urlString . 'milan');
            } elseif ($storeCode == 'alegria' && in_array(6, $websiteList)) { // 6 id of alegria site
                $urlTopPage = $this->getSystemConfig($urlString . 'alegria');
            }
        }
        return $urlTopPage;
    }

    public function checkCustomerBelongWebsite()
    {
        if ($this->getCustomer() != null) {
            $asWebsite = $this->getCustomer()->getCustomAttribute('multiple_website');
            if ($asWebsite) {
                return $asWebsite->getValue();
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * return true if order have additional product and course code starts with RT
     *
     * @return bool
     */
    public function checkOrderHaveMachineRentalAdd() {
        $order = $this->getOrder();
        if($order->getData('subscription_profile_id')){
            $haveAdditionalProduct = false;
            $courseCodeHaveRequiredPattern = false;
            foreach ($order->getAllItems() as $item) {
                if($item->getData('is_addition')){
                    $haveAdditionalProduct = true;
                    break;
                }
            }
            $subscriptionData = $this->_profileRepository->get($order->getData('subscription_profile_id'));
            if($subscriptionData->getCourseId()){
                $dataCourse = $this->courseFactory->create()->load($subscriptionData->getCourseId());
                if($dataCourse->getData('course_code')){
                    $courseCode = $dataCourse->getData('course_code');
                    if (preg_match("/^RT/", $courseCode)) {
                        $courseCodeHaveRequiredPattern = true;
                    }
                }
            }
            return $haveAdditionalProduct&&$courseCodeHaveRequiredPattern;
        }
        return false;

    }
}