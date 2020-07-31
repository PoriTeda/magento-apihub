<?php

namespace Riki\Subscription\Controller\Profiles;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Riki\Subscription\Helper\Profile\CampaignHelper;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManagerInterface;
use Riki\Subscription\Model\Multiple\Category\Cache;
use Riki\Subscription\Model\Landing\PageFactory as LandingPageFactory;
use Magento\Customer\Model\Session;

/**
 * Class Select
 *
 * @package Riki\Subscription\Controller\Profiles
 */
class Select extends \Magento\Framework\App\Action\Action implements \Magento\Framework\App\CsrfAwareActionInterface
{
    const SHOP_SITE_KEY = 'matomegai';
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var CampaignHelper
     */
    protected $campaignHelper;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var Cache $multipleCategoryCache
     */
    protected $multipleCategoryCache;

    /**
     * @var LandingPageFactory
     */
    protected $landingPageFactory;

    /**
     * Select constructor.
     * @param Session $customerSession
     * @param LandingPageFactory $landingPageFactory
     * @param Registry $registry
     * @param SerializerInterface $serializer
     * @param CampaignHelper $campaignHelper
     * @param PageFactory $resultPageFactory
     * @param SessionManagerInterface $sessionManager
     * @param Cache $multipleCategoryCache
     * @param Context $context
     */
    public function __construct(
        Session $customerSession,
        LandingPageFactory $landingPageFactory,
        Registry $registry,
        SerializerInterface $serializer,
        CampaignHelper $campaignHelper,
        PageFactory $resultPageFactory,
        SessionManagerInterface $sessionManager,
        Cache $multipleCategoryCache,
        Context $context)
    {
        $this->registry = $registry;
        $this->resultPageFactory = $resultPageFactory;
        $this->campaignHelper = $campaignHelper;
        $this->serializer = $serializer;
        $this->sessionManager = $sessionManager;
        $this->multipleCategoryCache = $multipleCategoryCache;
        $this->landingPageFactory    = $landingPageFactory;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage   = $this->resultPageFactory->create();
        $reqDataValue = $this->getRequest()->getParam('reqdata');

        if (empty($reqDataValue)) {
            return $this->redirectErrorResult(__('The request data is invalid. Please try again'));
        }

        $receiveData = $this->campaignHelper->decodeData($reqDataValue);

        $validator = $this->campaignHelper->validatePostAuthorization($receiveData);

        if ($validator['isValid'] == false) {
            return $this->redirectErrorResult($validator['errorMsg']);
        }

        try {
            $data     = $this->campaignHelper->decodeData($receiveData['data']);
            $customer = $this->getCustomer($data[CampaignHelper::CONSUMER_DB_ID]);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->redirectErrorResult($e->getMessage());
        }
        if ($customer === true) {
            $params = $this->getRequest()->getParams();
            $params['location'] = self::SHOP_SITE_KEY;
            $this->_forward('ssologin', 'account', 'customer', $params);
        } else {
            if (!$customer || !$customer->getId()) {
                return $this->redirectErrorResult(__('Consumer db id is not matched. Please try again'));
            }

            if(!$this->isExistedLandingPage($data[CampaignHelper::LANDING_PAGE_ID])){
                return $this->redirectErrorResult(__('Landing page not found. Please try again'));
            }

            $this->registry->register(CampaignHelper::CUSTOMER_ID, $customer->getId());
            $this->registry->register(CampaignHelper::LANDING_PAGE_ID, $data[CampaignHelper::LANDING_PAGE_ID]);
            $this->registry->register(CampaignHelper::PRODUCTS, base64_encode(
                    $this->serializer->serialize($data[CampaignHelper::PRODUCTS]))
            );

            $this->registry->register(CampaignHelper::REQUIRE_DATA_VALUE, $reqDataValue);

            // Clear session and cache
            if ($identifier = $this->sessionManager->getData(CampaignHelper::SUMMER_CAMPAIGN_CACHE_ID)) {
                $this->multipleCategoryCache->removeCache($identifier);
            }
            $this->sessionManager->setData(CampaignHelper::SUMMER_CAMPAIGN_DATA, null);
            $this->sessionManager->setData(CampaignHelper::SUMMER_CAMPAIGN_CACHE_ID, null);
            $this->sessionManager->setData(CampaignHelper::SUCCESS_DATA, null);
        }
        return $resultPage;
    }

    /**
     * @param $consumerDbId
     * @return bool|\Magento\Customer\Model\Customer
     */
    public function getCustomer($consumerDbId)
    {
        if($this->customerSession->isLoggedIn())
        {
            $customer = $this->customerSession->getCustomer();
            if($customer->getConsumerDbId() === $consumerDbId)
            {
                return $customer;
            }
        }
        else if ($this->getRequest()->getCookie(\Riki\Customer\Model\SsoConfig::SSO_SESSION_ID, null)) {
            return true;
        }
        return false;
    }

    /**
     * @param $msg
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function redirectErrorResult($msg)
    {
        $this->messageManager->addErrorMessage($msg);
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setPath('customer/account');
    }

    /**
     * @param $landingPageId
     * @return bool
     */
    public function isExistedLandingPage($landingPageId)
    {
        $landingPage = $this->landingPageFactory->create()->load($landingPageId);
        if($landingPage->getId()){
            return true;
        }
        return false;
    }
    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        if ($request->getHeader('Origin')) {
            if (strpos($request->getHeader('Origin'), 'shop.nestle.jp') !== false) {
                return true;
            }
        }
        if ($request->getHeader('Referer')) {
            if (strpos($request->getHeader('Referer'), 'shop.nestle.jp') !== false) {
                return true;
            }
        }
        return null;
    }
}