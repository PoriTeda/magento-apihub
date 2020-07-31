<?php
namespace Riki\SubscriptionPage\Controller\View;

use \Magento\Framework\App\Action\Action;
use \Riki\SubscriptionCourse\Model\Course\Type;

class Index extends Action
{
    const COOKIE_NAME_RT000033S = 'COMPAIN_PAGE_RT000033S';
    const COOKIE_NAME_RT000032S = 'COMPAIN_PAGE_RT000032S';
    const COOKIE_NAME_RT000034S = 'COMPAIN_PAGE_RT000034S';
    const COOKIE_DURATION = 86400; // lifetime in seconds,expired 1 day
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $courseFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */

    protected $customerRepository;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    protected $ssoUrl;

    protected $ssoConfig;

    /**
     * @var \Riki\SubscriptionCourse\Model\ResourceModel\Course\CollectionFactory
     */
    protected $courseCollectionFactory;
    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected $cookieMetadataFactory;
    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;
    /**
     * @var \Riki\SubscriptionPage\Block\ViewModel\ProductCategory
     */
    private $blockSubscriptionViewModel;

    /**
     * Index constructor.
     * @param \Riki\SubscriptionCourse\Model\ResourceModel\Course\CollectionFactory $courseCollection
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\SubscriptionCourse\Model\Course $courseFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Riki\Customer\Helper\SsoUrl $ssoUrl
     * @param \Riki\Customer\Model\SsoConfig $ssoConfig
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     * @param \Riki\SubscriptionPage\Block\ViewModel\ProductCategory $productCategoryViewModel
     */
    public function __construct(
        \Riki\SubscriptionCourse\Model\ResourceModel\Course\CollectionFactory $courseCollection,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\Registry $registry,
        \Riki\SubscriptionCourse\Model\Course $courseFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Customer\Helper\SsoUrl $ssoUrl,
        \Riki\Customer\Model\SsoConfig $ssoConfig,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Riki\SubscriptionPage\Block\ViewModel\ProductCategory $productCategoryViewModel
    ) {
    
        $this->courseCollectionFactory = $courseCollection;
        $this->scopeConfig = $scopeConfig;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->registry = $registry;
        $this->courseFactory = $courseFactory;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->ssoUrl = $ssoUrl;
        $this->ssoConfig = $ssoConfig;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->cookieManager = $cookieManager;
        $this->urlInterface = $context->getUrl();
        $this->blockSubscriptionViewModel = $productCategoryViewModel;
        parent::__construct($context);
    }

    public function execute()
    {
        $courseId = '';
        $currentUrl = $this->_url->getCurrentUrl();
        $resultForward = $this->resultForwardFactory->create();

        if ($this->getRequest()->getParam('id')) {
            $courseCollection = $this->courseCollectionFactory->create()
                ->addFieldToFilter('course_code', $this->getRequest()->getParam('id'))->setPageSize(1);
            if ($courseCollection->getSize() > 0) {
                // url to view normal subscription
                $courseObj = $courseCollection->getFirstItem();

                if (strpos($currentUrl, 'subscription/hanpukai/view') !== false) {
                    return $resultForward->forward('noroute');
                }
                $courseId = $courseObj->getData('course_id');
            }
        } elseif ($this->getRequest()->getParam('code')) {
            $courseCollection = $this->courseCollectionFactory->create()
                ->addFieldToFilter('course_code', $this->getRequest()->getParam('code'))->setPageSize(1);
            if ($courseCollection->getSize() > 0) {
                // url to view normal subscription
                $courseObj = $courseCollection->getFirstItem();
                if (strpos($currentUrl, '/subscription-page/view') !== false) {
                    return $resultForward->forward('noroute');
                }
                $courseId = $courseObj->getData('course_id');
            }
        } else {
            return $resultForward->forward('noroute');
        }

        $frequencyId = $this->getRequest()->getParam('freq');
        if (!$frequencyId) {
            if ($this->getRequest()->getParam('frequency')) {
                $frequencyId = $this->getRequest()->getParam('frequency');
            }
        }
        $model = $this->courseFactory->load($courseId);
        if (!$model->getData('course_id')) {
            return $resultForward->forward('noroute');
        }

        /*subscription course setting - list membership can accept this subscription - null is un limit*/
        $membershipCourse = $model->getMembershipIds();

        /*flag to check current customer can accept this subscription page - vale {0: cannot accept, 1: can accept}*/
        $flagMembership = 0;

        /*customer membership - used to generate cache key for case that */
        $customerMembership = 0;

        if ($this->customerSession->isLoggedIn() && !empty($membershipCourse)) {
            if ($this->customerSession->getCustomerId()) {
                $customerId = $this->customerSession->getCustomerId();
                $customerDataObject = $this->customerRepository->getById($customerId);

                if (!empty($customerDataObject->getCustomAttribute('membership'))) {
                    /*customer membership*/
                    $membershipValue = $customerDataObject->getCustomAttribute('membership')->getValue();
                    $membershipCustomer = explode(',', $membershipValue);

                    foreach ($membershipCustomer as $membership) {
                        // check if customer don't have in membership of subscription course
                        if (in_array($membership, $membershipCourse)) {
                            $customerMembership = $membershipValue;
                            $flagMembership = 1;
                            break;
                        }
                    }
                } else {
                    $flagMembership = 0;
                }
            }
        } else {
            if (!empty($membershipCourse)) {
                // not login and course have set membership
                if (!$this->customerSession->isLoggedIn()) {
                    $resultRedirect = $this->resultRedirectFactory->create();
                    if (!$this->ssoConfig->isEnabled()) {
                        $resultRedirect->setPath('customer/account/login');
                    } else {
                        $resultRedirect->setUrl($this->ssoUrl->getLoginUrl($this->_url->getCurrentUrl()));
                    }

                    return $resultRedirect;
                }
            } else {
                $flagMembership = 1;
            }
        }

        if (empty($membershipCourse)) {
            $flagMembership = 1;
        }

        $this->registry->register('customer_access_subscription_page', $flagMembership);
        $this->registry->register('currently_customer_membership', $customerMembership);

        if (!$model->getData('course_id')) {
            return $resultForward->forward('noroute');
        } else {
            $this->registry->register('subscription-course-id', $model->getData('course_id'));
            $this->registry->register('subscription-course', $model);
            $frequency = 0;
            $frequencies = $model->getData('frequency_ids');
            if ($model->isHanpukai() && $frequencies) {
                $frequency = current($frequencies);
            } elseif ($frequencyId) {
                $frequency = $frequencyId;
            }
            $this->registry->register('subscription-frequency-id', $frequency);
        }

        $resultPageFactory = $this->resultPageFactory->create();

        if ($model->getData('design') == \Riki\SubscriptionCourse\Model\Course::DESIGN_BLACK) {
            $resultPageFactory->addHandle('subscription_nespresso');
        }

        //redirect 404 for link hanpukai change closed date < today
        if (!$this->blockSubscriptionViewModel->isHanpukaiAvailableBetweenLaunchAndCloseTime()) {
            $this->_redirect('404');
        }

        return $resultPageFactory;
    }
}
