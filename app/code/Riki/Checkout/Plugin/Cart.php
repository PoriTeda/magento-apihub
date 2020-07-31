<?php
namespace Riki\Checkout\Plugin;

class Cart
{
    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $subscriptionCourseModel;

    /**
     * @var \Riki\Checkout\Helper\Data
     */
    protected $rikiCheckoutHelperData;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Riki\SubscriptionFrequency\Model\FrequencyFactory
     */
    protected $frequencyFactory;

    protected $frequencyHelper;

    /**
     * Cart constructor.
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Riki\Checkout\Helper\Data $data
     * @param \Riki\SubscriptionCourse\Model\Course $courseModel
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Checkout\Helper\Data $data,
        \Riki\SubscriptionCourse\Model\Course $courseModel,
        \Riki\SubscriptionFrequency\Model\FrequencyFactory $frequencyFactory,
        \Riki\SubscriptionFrequency\Helper\Data $frequencyHelper
    )
    {
        $this->storeManager = $storeManager;
        $this->currentCustomer = $currentCustomer;
        $this->scopeConfig = $scopeConfig;
        $this->rikiCheckoutHelperData = $data;
        $this->subscriptionCourseModel = $courseModel;
        $this->frequencyFactory = $frequencyFactory;
        $this->frequencyHelper = $frequencyHelper;
    }

    public function afterSetLayout(
        \Magento\Checkout\Block\Cart $subject,
        \Magento\Checkout\Block\Cart $result
    ){
        $courseName = '';
        $quote = $result->getQuote();
        $isSubscription = $this->isSubscription($quote);
        $totalItemInQuote = $this->getTotalItemInQuote($quote);
        if($isSubscription) {
            $courseName = $this->getCourseName($result->getQuote()->getData('riki_course_id'));
        }
        $isHanpukaiSubscription = $this->isHanpukaiSubscription($result->getQuote());
        $result->setData('is_subscription', $isSubscription);
        $result->setData('total_item', $totalItemInQuote);
        $result->setData('subscription_name', $courseName);

        if ($frequencyId = $quote->getRikiFrequencyId()) {
            $result->setData('frequency_text', $this->frequencyHelper->getFrequencyString($frequencyId));
        }

        $result->setData('is_hanpukai_subscription', $isHanpukaiSubscription);
        if ($isHanpukaiSubscription == 1) {
            $result->setData('hanpukai_factor', $this->getFactor($result->getQuote()));
        } else {
            $result->setData('hanpukai_factor', 1);
        }

        $subject->setData('url_top_page', $this->getUrlTopPage());
        $subject->setData('customer_membership', $this->getCustomerMembership());
        $result->setData('have_additional_product',$this->checkCartHaveAdditionalProduct($result->getQuote()));

        return $result;
    }

    protected function _getFrequencyLabel($id)
    {
        foreach ($this->subscriptionCourseModel->getFrequencyValuesForForm() as $frequency) {
            if ($id == $frequency['value']) {
                return $frequency['label'];
            }
        }
        return '';
    }

    public function isSubscription($quote)
    {
        if ($quote->getData('riki_course_id') != null) {
            return 1;
        }
        return 0;
    }

    public function getTotalItemInQuote($quote)
    {
        /* @var $quote \Magento\Quote\Model\Quote */
        if($quote && $quote instanceof \Magento\Quote\Model\Quote) {
            return count($quote->getAllVisibleItems());
        }
        return 0;
    }

    /**
     * Check subscription is hanpukai subscription or not
     *
     * @param $quote
     *
     * @return bool
     */
    public function isHanpukaiSubscription($quote)
    {
        if ($quote->getData('riki_course_id') != null) {
            $courseId = $quote->getData('riki_course_id');
            $courseModel = $this->subscriptionCourseModel->load($courseId);
            if ($courseModel->getData('subscription_type') == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI){
                return true;
            }
        }
        return false;
    }

    public function getCourseName($courseId)
    {
        $courseModelObj = $this->subscriptionCourseModel->load($courseId);
        if($courseModelObj) {
            return $courseModelObj->getData('course_name');
        }
        return '';
    }

    public function getFactor($quote)
    {
        $originProduct = $this->rikiCheckoutHelperData
            ->getArrProductFirstDeliveryHanpukai($quote->getData('riki_course_id'));
        $cartData = $this->rikiCheckoutHelperData->makeCartDataFromQuote($quote);
        $previousFactor = $this->rikiCheckoutHelperData->calculateFactor($originProduct, $cartData, $quote);
        if ($previousFactor === false) {
            return 1;
        }
        return $previousFactor;
    }

    /**
     * @return string
     */
    public function getStoreCode()
    {
        return $this->storeManager->getStore()->getCode();
    }

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
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
     * @return mixed
     */
    public function getSystemConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $config = $this->scopeConfig->getValue($path, $storeScope);
        return $config;
    }

    /**
     * Get membership of customer
     *
     * @return array
     */
    public function getCustomerMembership()
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return [];
        }

        $memberShipAttr = $customer->getCustomAttribute('membership');

        return $memberShipAttr ? explode(',', $memberShipAttr->getValue()) : [];
    }

    /**
     * @return mixed
     */
    public function getUrlTopPage()
    {
        $memberList = $this->getCustomerMembership();
        $storeCode = $this->getStoreCode();

        $urlTopPage = '';
        if ($storeCode) {
            $urlString = 'thank_you_page_link_to_top_page/link_to_top_page/link_to_top_page_';
            $urlTopPage = $this->getSystemConfig($urlString . $storeCode);

            if ($storeCode == 'ec') {
                /**
                 * Priority is decrease:
                 * Wellness Ambassador, Ambassador Member, Alegria Member + Milano Member
                 */
                if (in_array(7, $memberList)) { // Milano Member
                    $urlTopPage = $this->getSystemConfig($urlString . 'milan');
                }
                if (in_array(8, $memberList)) { // Alegria Member
                    $urlTopPage = $this->getSystemConfig($urlString . 'alegria');
                }
                if (in_array(3, $memberList)) { // Ambassador Member
                    $urlTopPage = $this->getSystemConfig($urlString . 'amb');
                }
                if (in_array(15, $memberList)) { // Wellness Ambassador
                    $urlTopPage = $this->getSystemConfig($urlString . 'wellness_amb');
                }
            }
        }
        return $urlTopPage;
    }

    /**
     * return true if cart have additional product
     * @param $quote \Magento\Quote\Model\Quote
     * @return bool
     */
    public function checkCartHaveAdditionalProduct($quote) {
        $items = $quote->getAllVisibleItems();
        foreach ($items as $item) {
            if($item->getData('is_addition')){
               return true;
            }
        }
        return false;
    }
}