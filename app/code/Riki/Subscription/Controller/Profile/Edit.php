<?php

namespace Riki\Subscription\Controller\Profile;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Riki\SubscriptionCourse\Model\Course\Type as CourseType;

class Edit extends Action implements CsrfAwareActionInterface
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    protected $_resultForwardFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $_courseFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */

    protected $_customerRepository;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_controllerHelper;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $_profileData;

    protected $catalogRuleHelper;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var \Riki\StockPoint\Api\BuildStockPointPostDataInterface
     */
    protected $buildStockPointPostData;

    protected $profileCacheRepository;

    /**
     * @var \Riki\Customer\Helper\CustomerHelper
     */
    protected $customerHelper;

    /**
     * Edit constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\SubscriptionCourse\Model\Course $courseFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\Subscription\Helper\Profile\Data $profileData
     * @param \Riki\CatalogRule\Helper\Data $catalogRuleHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData
     * @param \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\Registry $registry,
        \Riki\SubscriptionCourse\Model\Course $courseFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Riki\CatalogRule\Helper\Data $catalogRuleHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository,
        \Riki\Customer\Helper\CustomerHelper $customerHelper
    ) {
        $this->_resultPageFactory = $resultPageFactory;
        $this->_resultForwardFactory = $resultForwardFactory;
        $this->_registry = $registry;
        $this->_courseFactory = $courseFactory;
        $this->_customerSession = $customerSession;
        $this->_customerRepository = $customerRepository;
        $this->_profileData = $profileData;
        $this->catalogRuleHelper = $catalogRuleHelper;
        $this->date = $date;
        $this->buildStockPointPostData = $buildStockPointPostData;
        $this->profileCacheRepository = $profileCacheRepository;
        $this->customerHelper = $customerHelper;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function getResultPageFactory()
    {
        return $this->_resultPageFactory;
    }

    /**
     * @return \Magento\Framework\Message\ManagerInterface
     */
    public function getMessageManager()
    {
        return $this->messageManager;
    }

    /**
     * @param string $path
     * @param array $arguments
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function _redirect($path, $arguments = [])
    {
        $this->_redirect->redirect($this->getResponse(), $path, $arguments);
        return $this->getResponse();
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Json_Exception
     */
    public function execute()
    {
        $messageManager = $this->getMessageManager();
        $params = $this->_request->getParams();

        if (!$this->getRequest()->getParam('id')) {
            $messageManager->addError(__('This subscription profile do not exists.'));
            $this->_redirect('*/*');
            return;
        }

        // if tmp profile id redirect
        if ($this->_profileData->isTmpProfileId($this->getRequest()->getParam('id'))) {
            $this->_redirect('*/*');
            return;
        }

        if (!$this->_customerSession->isLoggedIn()) {
            return $this->_redirect('customer/account/login');
        }

        $profileId = $this->getRequest()->getParam('id');
        $isList = $this->getRequest()->getParam('list');
        $reset = $isList ? true : false;
        if ($this->_profileData->getTmpProfile($profileId) !== false) {
            $profileId = $this->_profileData->getTmpProfile($profileId)->getData('linked_profile_id');
        }

        $objResultPageFactory = $this->getResultPageFactory();

        /** @var \Riki\Subscription\Model\Profile\Profile $objProfile */
        $objProfile = $this->_profileData->load($profileId);

        if (empty($objProfile) || empty($objProfile->getId())) {
            $messageManager->addError(__('This subscription profile do not exists.'));
            $this->_redirect('*/*');
            return;
        }
        /** Is Have permission to view profile */
        $customerId = $this->_customerSession->getCustomerId();
        if (!$this->_profileData->isHaveViewProfilePermission($customerId, $profileId)) {
            $this->_redirect('*/*');
            return;
        } //ok

        if ($objProfile->getData('status') == 0) {
            $this->_redirect('*/*');
            return;
        }

        // Not Allow Subscription Hanpukai
        if ($this->_profileData->getCourseData($objProfile->getData('course_id'))
                ->getData('subscription_type') == CourseType::TYPE_HANPUKAI) {
            $this->_redirect('*/*');
            return;
        }

        // Check and update home address, company address when consumer changed information from KSS
        $this->customerHelper->checkUpdateHomeAndCompanyAddressForCustomer($customerId);

        $objCache = $this->profileCacheRepository->initProfile($profileId, $reset, $objProfile);

        if (!$objCache) {
            $this->messageManager->addError(__('Something went wrong, please reload page.'));
            $this->_redirect('*/*');
            return;
        }
        $profileCache = $objCache->getProfileData()[$profileId];

        if (isset($profileCache)) {
            $currentProfile = $profileCache;
            if (isset($currentProfile['is_delete_stock_point']) && $currentProfile['is_delete_stock_point']) {
                if (isset($currentProfile['show_message_delete']) && $currentProfile['show_message_delete']) {
                    $messageManager->addSuccess(
                        __(
                            'Please click the button %1 at the bottom of the screen to complete the change.',
                            __('Proceed to the next')
                        )
                    );
                    $profileCache['show_message_delete'] = false;
                }
            }
            $validate = $this->buildStockPointPostData->validateRequestStockPoint(
                $params,
                $profileCache
            );
            if (!$validate['status']) {
                $messageManager->addError($validate['message']);
                $this->_redirect('*/*');
                return;
            }
        }

        /**
         * Set stock_point_data
         */
        $profileCache = $this->processProfileStockPoint($profileCache, $profileId);

        $productIds = [];
        foreach ($objProfile->getProductCartData() as $productCart) {
            $productIds[] = $productCart->getData('product_id');
        }
        if ($productIds) { // improve performance by decrease load catalog rule
            $this->catalogRuleHelper->registerPreLoadedProductIds($productIds);
        }

        if(!is_null($this->_registry->registry('subscription_profile'))){
            $this->_registry->unregister('subscription_profile');
        }
        $this->_registry->register('subscription_profile', $profileCache);
        if(!is_null($this->_registry->registry('subscription_profile_obj'))){
            $this->_registry->unregister('subscription_profile_obj');
        }
        $this->_registry->register('subscription_profile_obj', $objProfile);

        $courseId = $objProfile->hasData('course_id') ? $objProfile->getData('course_id') : 0;
        $frequencyId = $objProfile->getSubProfileFrequencyID();

        if(!is_null($this->_registry->registry(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID))){
            $this->_registry->unregister(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID);
        }
        $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $courseId);
        if(!is_null($this->_registry->registry(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID))){
            $this->_registry->unregister(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID);
        }
        $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $frequencyId);

        $resultPage = $objResultPageFactory->create();
        //[NED-5308] I dont think it is neccessary but the flow is wide - may have issue-can be uncommented!
        //$this->profileCacheRepository->save($profileCache);

        return $resultPage;
    }

    /**
     * @param $profileCache
     * @param $profileId
     * @return mixed
     * @throws \Zend_Json_Exception
     */
    public function processProfileStockPoint($profileCache, $profileId)
    {
        if (isset($profileCache)) {
            $currentProfile = $profileCache;
            $stockPointSession = $this->buildStockPointPostData->getDataNotifyConvert();
            if (empty($stockPointSession)) {
                $stockPointSession = $currentProfile->getData('stock_point_data');
            } else {
                if (isset($currentProfile['is_delete_stock_point'])) {
                    unset($profileCache['is_delete_stock_point']);
                }
            }

            /**
             * Check delete stock point
             */
            $isDelete = false;
            if (isset($currentProfile['is_delete_stock_point']) && $currentProfile['is_delete_stock_point']) {
                $isDelete = true;
            }

            if (!$isDelete &&
                isset($stockPointSession['stock_point_id']) &&
                $stockPointSession['stock_point_id'] != null
            ) {
                $rikiStockPointId = $currentProfile->getData('riki_stock_point_id');
                if ($this->buildStockPointPostData->getRikiStockId()) {
                    $rikiStockPointId = $this->buildStockPointPostData->getRikiStockId();
                }

                $profileCache->setData('riki_stock_point_id', $rikiStockPointId);
                $profileCache = $this->buildStockPointPostData->setDataStockPointToProfile($profileCache, $profileId);
                if (isset($currentProfile['is_delete_stock_point'])) {
                    unset($profileCache['is_delete_stock_point']);
                }
            }
        }

        return $profileCache;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        if ($request->getHeader('Origin')) {
            if (strpos($request->getHeader('Origin'), 'machieco.nestle.jp') !== false) {
                return true;
            }
        }
        if ($request->getHeader('Referer')) {
            if (strpos($request->getHeader('Referer'), 'machieco.nestle.jp') !== false) {
                return true;
            }
        }
        return null;
    }
}

